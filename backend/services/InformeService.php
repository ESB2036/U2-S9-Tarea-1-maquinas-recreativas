<?php
require_once __DIR__ . '/../models/InformeModel.php';
require_once __DIR__ . '/../models/MaquinaModel.php';
/**
 * Clase de servicio encargada de manejar la lógica de negocio relacionada con las recaudaciones.
 */
class InformeService {
    private $model;
    private $maquinaModel;

    public function __construct() {
        $this->model = new InformeModel();
        $this->maquinaModel = new MaquinaModel();
    }

public function registrarRecaudacion($data) {
    try {
        // Validar que la máquina existe y está en etapa de recaudación
        $maquina = $this->maquinaModel->obtenerMaquinaPorId($data['ID_Maquina']);
        if (!$maquina) {
            return ['success' => false, 'message' => 'Máquina no encontrada'];
        }
        
        if ($maquina['Etapa'] !== 'Recaudacion' || $maquina['Estado'] !== 'Operativa') {
            return ['success' => false, 'message' => 'La máquina no está disponible para recaudación'];
        }

        // Validar usuario existe
        $usuarioModel = new UsuarioModel();
        if (!$usuarioModel->obtenerUsuarioPorId($data['ID_Usuario'])) {
            return ['success' => false, 'message' => 'Usuario no válido'];
        }

        $idRecaudacion = $this->model->registrarRecaudacion($data);
        
        if ($idRecaudacion) {
            return [
                'success' => true,
                'message' => 'Recaudación registrada correctamente',
                'idRecaudacion' => $idRecaudacion
            ];
        } else {
            error_log("Error al registrar recaudación - No se pudo insertar en BD");
            return ['success' => false, 'message' => 'Error al registrar la recaudación en la base de datos'];
        }
    } catch (Exception $e) {
        error_log("Error en registrarRecaudacion: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

public function obtenerRecaudaciones($filters) {
    try {
        // Validar que los filtros sean un array
        if (!is_array($filters)) {
            throw new Exception('Parámetros de filtro no válidos');
        }

        $recaudaciones = $this->model->obtenerRecaudaciones($filters);
        
        return [
            'success' => true,
            'recaudaciones' => $recaudaciones,
            'total' => count($recaudaciones),
            'message' => count($recaudaciones) > 0 
                ? 'Recaudaciones encontradas' 
                : 'No se encontraron recaudaciones con los filtros aplicados'
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerRecaudaciones: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al obtener recaudaciones: ' . $e->getMessage()
        ];
    }
}

public function obtenerResumenRecaudacionesLimitado($limit) {
    try {
        $resumen = $this->model->obtenerResumenRecaudacionesLimitado($limit);
        return [
            'success' => true,
            'resumen' => $resumen
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerResumenRecaudacionesLimitado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al obtener resumen limitado'];
    }
}

public function actualizarRecaudacion($data) {
    try {
        // Validar que $data es un array
        if (!is_array($data)) {
            throw new Exception('Datos de entrada no válidos');
        }

        // Validaciones básicas
        $requiredFields = [
            'ID_Recaudacion', 'ID_Maquina', 'Tipo_Comercio',
            'Monto_Total', 'Monto_Empresa', 'Monto_Comercio', 'fecha'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }

        // Validar tipos de datos
        if (!is_numeric($data['ID_Recaudacion'])) {
            throw new Exception('ID de recaudación inválido');
        }

        if (!is_numeric($data['ID_Maquina'])) {
            throw new Exception('ID de máquina inválido');
        }

        if (!in_array($data['Tipo_Comercio'], ['Minorista', 'Mayorista'])) {
            throw new Exception('Tipo de comercio no válido');
        }

        // Validar montos
        if (!is_numeric($data['Monto_Total']) || $data['Monto_Total'] <= 0) {
            throw new Exception('Monto total debe ser positivo');
        }

        // Validar fecha
        if (empty($data['fecha'])) {
            throw new Exception('Fecha no proporcionada');
        }

        // Validar suma de montos
        $totalCalculado = floatval($data['Monto_Empresa']) + floatval($data['Monto_Comercio']);
        if (abs($totalCalculado - floatval($data['Monto_Total'])) > 0.01) {
            throw new Exception('La suma de montos no coincide con el total');
        }

        // Asegurar que el detalle tenga valor
        $data['detalle'] = $data['detalle'] ?? '';

        // Llamar al modelo para actualizar
        $result = $this->model->actualizarRecaudacion($data);
        
        if (!$result['success']) {
            throw new Exception($result['message'] ?? 'Error al actualizar recaudación');
        }

        return [
            'success' => true,
            'message' => 'Recaudación actualizada correctamente',
            'affected_rows' => $result['affected_rows']
        ];

    } catch (Exception $e) {
        error_log("Error en actualizarRecaudacion: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
    public function eliminarRecaudacion($id) {
        try {
            $success = $this->model->eliminarRecaudacion($id);
            return [
                'success' => $success,
                'message' => $success ? 'Recaudación eliminada correctamente' : 'Error al eliminar recaudación'
            ];
        } catch (Exception $e) {
            error_log("Error en eliminarRecaudacion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    public function obtenerMaquinasRecaudacion() {
        try {
            $maquinas = $this->maquinaModel->obtenerMaquinasPorEtapaYEstado('Recaudacion', 'Operativa');
            return [
                'success' => true,
                'maquinas' => $maquinas
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerMaquinasRecaudacion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener máquinas'];
        }
    }
public function obtenerMaquinasOperativasPorComercio($id_comercio) {
    try {
        if (!is_numeric($id_comercio) || $id_comercio <= 0) {
            return ['success' => false, 'message' => 'ID de comercio inválido'];
        }
        
        $maquinas = $this->maquinaModel->obtenerMaquinasOperativasPorComercio($id_comercio);
        
        if ($maquinas === false) {
            return ['success' => false, 'message' => 'Error en la consulta de máquinas'];
        }
        
        return [
            'success' => true,
            'maquinas' => $maquinas,
            'message' => empty($maquinas) 
                ? 'No hay máquinas operativas en etapa de recaudación para este comercio' 
                : 'Máquinas encontradas'
        ];
    } catch (Exception $e) {
        error_log("Error en obtenerMaquinasOperativasPorComercio: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error al obtener máquinas por comercio',
            'error' => $e->getMessage()
        ];
    }
}
/**
     * Guarda un informe de recaudación con sus detalles
     */
    public function guardarInforme($data) {
        try {
            // Validar datos básicos
            if (!is_numeric($data['ID_Recaudacion']) || $data['ID_Recaudacion'] <= 0) {
                return ['success' => false, 'message' => 'ID de recaudación inválido'];
            }

            if (!is_numeric($data['ID_Comercio']) || $data['ID_Comercio'] <= 0) {
                return ['success' => false, 'message' => 'ID de comercio inválido'];
            }

            // Guardar informe principal
            $idInforme = $this->model->guardarInformePrincipal($data);
            
            if (!$idInforme) {
                return ['success' => false, 'message' => 'Error al guardar el informe principal'];
            }

            // Guardar detalles de componentes si existen
            if (!empty($data['componentes'])) {
                foreach ($data['componentes'] as $componente) {
                    if (!isset($componente['ID_Componente']) || !is_numeric($componente['ID_Componente'])) {
                        continue; // Saltar componentes inválidos
                    }

                    $detalleGuardado = $this->model->guardarDetalleComponente(
                        $idInforme,
                        $componente['ID_Componente']
                    );

                    if (!$detalleGuardado) {
                        // No hacemos rollback aquí, pero podríamos si es crítico
                        error_log("Error al guardar componente ID: " . $componente['ID_Componente']);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Informe guardado correctamente',
                'idInforme' => $idInforme
            ];

        } catch (Exception $e) {
            error_log("Error en guardarInforme: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar el informe'];
        }
    }

    /**
     * Obtiene un informe completo por ID de recaudación
     */
    public function obtenerInformePorRecaudacion($idRecaudacion) {
        try {
            if (!is_numeric($idRecaudacion) || $idRecaudacion <= 0) {
                return ['success' => false, 'message' => 'ID de recaudación inválido'];
            }

            // Obtener informe principal
            $informe = $this->model->obtenerInformePrincipal($idRecaudacion);
            
            if (!$informe) {
                return ['success' => false, 'message' => 'Informe no encontrado'];
            }

            // Obtener detalles de componentes
            $componentes = $this->model->obtenerComponentesInforme($informe['ID_Informe']);

            return [
                'success' => true,
                'informe' => $informe,
                'componentes' => $componentes
            ];

        } catch (Exception $e) {
            error_log("Error en obtenerInformePorRecaudacion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener el informe'];
        }
    }

}
?>