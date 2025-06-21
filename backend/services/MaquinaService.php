<?php
require_once __DIR__ . '/../models/MaquinaModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';
require_once __DIR__ . '/../models/DistribucionModel.php';

class MaquinaService {
    private $maquinaModel;
    private $usuarioModel;
    private $notificacionModel;

    /**
     * Registra una nueva máquina con asignación automática de técnicos.
     * @param array $data Datos de la máquina.
     * @return array Resultado de la operación.
     */
    public function __construct() {
        $this->maquinaModel = new MaquinaModel();
        $this->usuarioModel = new UsuarioModel();
        $this->notificacionModel = new NotificacionModel();
    }

    public function generarPlaca($idTecnico) {
    try {
        $result = $this->maquinaModel->generarPlaca($idTecnico);
        
        if ($result) {
            return [
                'success' => true,
                'placa' => $result['placa'],
                'id_componente' => $result['id_componente']
            ];
        } else {
            return ['success' => false, 'message' => 'Error al generar placa'];
        }
    } catch (Exception $e) {
        error_log("Error en generarPlaca: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al generar placa'];
    }
}
public function registrarMaquina($data) {
    // Validación de campos
    $required = ['nombre', 'tipo', 'idComercio', 'idUsuarioLogistica', 'idPlaca', 'idCarcasa'];
    foreach ($required as $field) {
        if (!isset($data[$field]) ){
            return ['success' => false, 'message' => "El campo $field es requerido"];
        }
    }
    
    try {
        
        // Obtener técnicos
        $ensambladores = $this->usuarioModel->obtenerTecnicosPorEspecialidad('Ensamblador');
        $comprobadores = $this->usuarioModel->obtenerTecnicosPorEspecialidad('Comprobador');
        
        if (empty($ensambladores) || empty($comprobadores)) {
            return ['success' => false, 'message' => 'No hay técnicos disponibles para asignar'];
        }
        
        $idEnsamblador = $ensambladores[0]['ID_Usuario'];
        $idComprobador = $comprobadores[0]['ID_Usuario'];
        
        // Registrar máquina
        $idMaquina = $this->maquinaModel->registrarMaquina(
        $data['nombre'],
            $data['tipo'],
            $idEnsamblador,
            $idComprobador,
            $data['idComercio']
        );    
        if (!$idMaquina) {
            return ['success' => false, 'message' => 'Error al registrar máquina'];
        }
        $this->maquinaModel->registrarMontajeComponente(
            $idMaquina, 
            $data['idPlaca'], 
            $idEnsamblador,
            'Placa base generada automáticamente'
        );
        
        $this->maquinaModel->registrarMontajeComponente(
            $idMaquina, 
            $data['idCarcasa'], 
            $idEnsamblador,
            'Carcasa asignada'
        );
        
        // Crear notificación
        $this->notificacionModel->crearNotificacion(
            $data['idUsuarioLogistica'],
            $idEnsamblador,
            $idMaquina,
            'Nuevo montaje',
            ''
        );

        // Incrementar actividades de técnicos
        $this->usuarioModel->incrementarActividadesTecnico($idEnsamblador);
        $this->usuarioModel->incrementarActividadesTecnico($idComprobador);
        
        return ['success' => true, 'idMaquina' => $idMaquina];
        
    } catch (Exception $e) {
        error_log("Error en registrarMaquina: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al registrar máquina: ' . $e->getMessage()];
    }
}

    public function mandarAComprobacion($idMaquina, $idRemitente, $mensaje) {
        $maquina = $this->maquinaModel->obtenerMaquinaPorId($idMaquina);
        
        if (!$maquina) {
            return ['success' => false, 'message' => 'Maquina no encontrada'];
        }
        
        // Actualizar estado:
        $this->maquinaModel->actualizarEstadoMaquina($idMaquina, 'Comprobandose');
        
        // Crear notificacion para el técnico comprobador:
        $this->notificacionModel->crearNotificacion(
            $idRemitente,
            $maquina['ID_Tecnico_Comprobador'],
            $idMaquina,
            'Comprobar maquina recreativa',
            $mensaje
        );
        
        return ['success' => true];
    }

    public function mandarAReensamblar($idMaquina, $idRemitente, $mensaje) {
        $maquina = $this->maquinaModel->obtenerMaquinaPorId($idMaquina);
        
        if (!$maquina) {
            return ['success' => false, 'message' => 'Maquina no encontrada'];
        }
        
        // Actualizar estado:
        $this->maquinaModel->actualizarEstadoMaquina($idMaquina, 'Reensamblandose');
        
        // Crear notificación para el técnico ensamblador:
        $this->notificacionModel->crearNotificacion(
            $idRemitente,
            $maquina['ID_Tecnico_Ensamblador'],
            $idMaquina,
            'Reensamblar maquina recreativa',
            $mensaje
        );
        
        return ['success' => true];
    }

    public function mandarADistribucion($idMaquina, $idRemitente, $mensaje) {
        $maquina = $this->maquinaModel->obtenerMaquinaPorId($idMaquina);

        if (!$maquina) {
            return ['success' => false, 'message' => 'Maquina no encontrada'];
        }

        // Actualizar estado y etapa:
        $this->maquinaModel->actualizarEstadoMaquina($idMaquina, 'Distribuyendose', 'Distribucion');

        // Crear informe en informe_distribucion
        $distribucionModel = new DistribucionModel();
        $distribucionModel->crearInformeDistribucion(
            $idMaquina,
            $maquina['ID_Tecnico_Comprobador'], // Usar el ID del técnico comprobador
            $maquina['ID_Comercio']
        );

        // Obtener todos los usuarios de logística:
        $logisticas = $this->usuarioModel->obtenerUsuariosPorTipo('Logistica');

        if (!empty($logisticas)) {
            // Crear notificación para cada usuario de logística:
            foreach ($logisticas as $logistica) {
                $this->notificacionModel->crearNotificacion(
                    $idRemitente,
                    $logistica['ID_Usuario'],
                    $idMaquina,
                    'Distribuir maquina recreativa',
                    $mensaje
                );
            }
        }

        return ['success' => true];
    }


    public function ponerOperativa($idMaquina) {
        // Actualizar estado y etapa:
        $result = $this->maquinaModel->actualizarEstadoMaquina($idMaquina, 'Operativa', 'Recaudacion');

        if ($result) {
            // Actualizar informe_distribucion
            $distribucionModel = new DistribucionModel();
            $distribucionModel->actualizarInformeDistribucion($idMaquina, 'Operativa');

            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar maquina'];
        }
    }


    public function obtenerMaquinasPorTecnicoEnsamblador($idTecnico) {
        $maquinas = $this->maquinaModel->obtenerMaquinasPorTecnicoEnsamblador($idTecnico);
        return ['success' => true, 'maquinas' => $maquinas];
    }
    
    public function obtenerMaquinasPorTecnicoComprobador($idTecnico) {
        $maquinas = $this->maquinaModel->obtenerMaquinasPorTecnicoComprobador($idTecnico);
        return ['success' => true, 'maquinas' => $maquinas];
    }

    public function obtenerMaquinasPorTecnicoMantenimiento($idTecnico) {
        $maquinas = $this->maquinaModel->obtenerMaquinasPorTecnicoMantenimiento($idTecnico);
        return ['success' => true, 'maquinas' => $maquinas];
    }
    
    public function darMantenimiento($idMaquina, $mensaje, $idLogistica) {
    // Obtener técnico con menos actividades:
    $tecnicos = $this->usuarioModel->obtenerTecnicosPorEspecialidad('Mantenimiento');

    if (empty($tecnicos)) {
        return ['success' => false, 'message' => 'No hay técnicos de mantenimiento disponibles'];
    }

    $idTecnico = $tecnicos[0]['ID_Usuario'];

    // Actualizar máquina:
    $this->maquinaModel->actualizarEstadoMaquina($idMaquina, 'No operativa');
    $this->maquinaModel->asignarTecnicoMantenimiento($idMaquina, $idTecnico);

    // Crear notificación:
    $this->notificacionModel->crearNotificacion(
        $idLogistica,
        $idTecnico,
        $idMaquina,
        'Dar mantenimiento a máquina recreativa',
        $mensaje
    );

    //  Actualizar informe_distribucion:
    $distribucionModel = new DistribucionModel();
    $distribucionModel->actualizarInformeDistribucion($idMaquina, 'No operativa');

    return ['success' => true];
}

public function finalizarMantenimiento($idMaquina, $idRemitente, $exito, $mensaje) {
    try {
        $maquina = $this->maquinaModel->obtenerMaquinaPorId($idMaquina);
        
        if (!$maquina) {
            return ['success' => false, 'message' => 'Maquina no encontrada'];
        }
        
        // Determinar nuevo estado:
        $nuevoEstado = $exito ? 'Operativa' : 'Retirada';
        
        // Actualizar estado:
        $result = $this->maquinaModel->actualizarEstadoMaquina($idMaquina, $nuevoEstado);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Error al actualizar el estado de la máquina'];
        }
        
        // Obtener todos los usuarios de logística:
        $logisticas = $this->usuarioModel->obtenerUsuariosPorTipo('Logistica');
        
        if (!empty($logisticas)) {
            $tipoNotificacion = $exito ? 'Maquina recreativa reparada' : 'Maquina recreativa retirada';
            
            // Crear notificación para cada usuario de logística:
            foreach ($logisticas as $logistica) {
                $this->notificacionModel->crearNotificacion(
                    $idRemitente,
                    $logistica['ID_Usuario'],
                    $idMaquina,
                    $tipoNotificacion,
                    $mensaje
                );
            }
        }
        
        return ['success' => true, 'message' => 'Mantenimiento finalizado correctamente'];
    } catch (Exception $e) {
        error_log("Error en finalizarMantenimiento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al finalizar el mantenimiento'];
    }
}

    public function obtenerMaquinasPorEstado($estado) {
        $maquinas = $this->maquinaModel->obtenerMaquinasPorEstado($estado);
        return ['success' => true, 'maquinas' => $maquinas];
    }

    public function obtenerMaquinasPorEtapa($etapa) {
        $maquinas = $this->maquinaModel->obtenerMaquinasPorEtapa($etapa);
        return ['success' => true, 'maquinas' => $maquinas];
    }

    public function obtenerMaquinasParaDistribucion() {
        try {
            $maquinas = $this->maquinaModel->obtenerMaquinasPorEtapaYEstado('Distribucion', 'Distribuyendose');
            return [
                'success' => true,
                'maquinas' => $maquinas
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerMaquinasParaDistribucion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener máquinas para distribución'
            ];
        }
    }



    public function obtenerComponentesMaquina($idMaquina) {
        try {
            // Primero verificar si la máquina existe
            $maquina = $this->maquinaModel->obtenerMaquinaPorId($idMaquina);
            if (!$maquina) {
                return ['success' => false, 'message' => 'Máquina no encontrada'];
            }

            // Obtener componentes usados en montaje
            $componentesMontaje = $this->maquinaModel->obtenerComponentesMontaje($idMaquina);
            
            // Obtener componentes usados en mantenimiento
            $componentesMantenimiento = $this->maquinaModel->obtenerComponentesMantenimiento($idMaquina);
            
            // Combinar y eliminar duplicados
            $componentes = array_merge($componentesMontaje, $componentesMantenimiento);
            $componentesUnicos = [];
            $idsVistos = [];
            
            foreach ($componentes as $comp) {
                if (!in_array($comp['ID_Componente'], $idsVistos)) {
                    $idsVistos[] = $comp['ID_Componente'];
                    $componentesUnicos[] = $comp;
                }
            }

            return [
                'success' => true,
                'componentes' => $componentesUnicos
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerComponentesMaquina: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener componentes'];
        }
    }

public function registrarMontaje($data) {
        try {
            // Validaciones mínimas
            if (
                empty($data['ID_Maquina']) ||
                empty($data['ID_Componente']) ||
                empty($data['ID_Tecnico'])
            ) {
                return [
                    'success' => false,
                    'message' => 'Faltan datos obligatorios'
                ];
            }

            $ok = $this->maquinaModel->insertarMontaje([
                'ID_Maquina'    => $data['ID_Maquina'],
                'ID_Componente' => $data['ID_Componente'],
                'ID_Tecnico'    => $data['ID_Tecnico'],
                'detalle'       => $data['detalle'] ?? null,
            ]);

            if ($ok) {
                return [
                    'success' => true,
                    'message' => 'Montaje registrado'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar montaje'
                ];
            }
        } catch (Exception $e) {
            error_log("Error en MaquinaService::registrarMontaje: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno al registrar montaje'
            ];
        }
    }





}
?>