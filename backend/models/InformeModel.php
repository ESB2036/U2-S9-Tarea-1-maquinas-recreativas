<?php
require_once __DIR__ . '/../config/database.php';

class InformeModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function registrarRecaudacion($data) {
        $conn = $this->db->getConnection();
        
        // Asignar a variables temporales
        $detalle = $data['detalle'] ?? '';
        $porcentaje = $data['Porcentaje_Comercio'] ?? 0;
        
        $sql = "CALL sp_registrar_recaudacion(?, ?, ?, ?, ?, ?, ?, ?, ?, @p_id_recaudacion)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param(
            "siiiddssd", 
            $data['Tipo_Comercio'],
            $data['ID_Maquina'],
            $data['ID_Usuario'],
            $data['Monto_Total'],
            $data['Monto_Empresa'],
            $data['Monto_Comercio'],
            $data['fecha'],
            $detalle,
            $porcentaje
        );

        if ($stmt->execute()) {
            $result = $conn->query("SELECT @p_id_recaudacion as id");
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        return false;
    }

    public function obtenerRecaudaciones($filters) {
        $conn = $this->db->getConnection();
        
        // Asignar a variables temporales
        $fechaInicio = $filters['fecha_inicio'] ?? null;
        $fechaFin = $filters['fecha_fin'] ?? null;
        $idMaquina = $filters['ID_Maquina'] ?? null;
        $tipoComercio = $filters['Tipo_Comercio'] ?? null;
        
        $sql = "CALL sp_obtener_recaudaciones(?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param(
            "ssis",
            $fechaInicio,
            $fechaFin,
            $idMaquina,
            $tipoComercio
        );

        $stmt->execute();
        $result = $stmt->get_result();
        
        $recaudaciones = [];
        while ($row = $result->fetch_assoc()) {
            $recaudaciones[] = $row;
        }
        
        return $recaudaciones;
    }

    public function obtenerResumenRecaudacionesLimitado($limit) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_resumen_recaudaciones_limitado(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $resumen = [];
        
        while ($row = $result->fetch_assoc()) {
            $resumen[] = $row;
        }
        
        return $resumen;
    }

public function actualizarRecaudacion($data) {
    $conn = $this->db->getConnection();
    
    try {
        // Usar CALL para ejecutar el procedimiento almacenado
        $sql = "CALL sp_actualizar_recaudacion(?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // Asignar valores por defecto si no están presentes
        $detalle = $data['detalle'] ?? '';
        $porcentaje = $data['Porcentaje_Comercio'] ?? 0;
        
        // Vincular parámetros en el orden correcto
        $stmt->bind_param(
            "iisddsssd", // Tipos: i=int, s=string, d=double
            $data['ID_Recaudacion'],
            $data['ID_Maquina'],
            $data['Tipo_Comercio'],
            $data['Monto_Total'],
            $data['Monto_Empresa'],
            $data['Monto_Comercio'],
            $data['fecha'],
            $detalle,
            $porcentaje
        );
        
        // Ejecutar y manejar resultados
        $success = $stmt->execute();
        
        // Obtener filas afectadas si el procedimiento devuelve resultados
        $affectedRows = 0;
        if ($result = $stmt->get_result()) {
            if ($row = $result->fetch_assoc()) {
                $affectedRows = $row['filas_afectadas'] ?? 0;
            }
        }
        
        return [
            'success' => $success,
            'affected_rows' => $affectedRows,
            'message' => $success ? '' : ($stmt->error ?? 'Error desconocido')
        ];
        
    } catch (Exception $e) {
        error_log("Error en actualizarRecaudacion: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error en la base de datos: ' . $e->getMessage()
        ];
    } finally {
        // Cerrar statement si existe
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
    public function eliminarRecaudacion($id) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_eliminar_recaudacion(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function guardarInformePrincipal($data) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_guardar_informe_principal(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @p_id_informe)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param(
            "ississsdddss",
            $data['ID_Recaudacion'],
            $data['CI_Usuario'],
            $data['Nombre_Maquina'],
            $data['ID_Comercio'],
            $data['Nombre_Comercio'],
            $data['Direccion_Comercio'],
            $data['Telefono_Comercio'],
            $data['Pago_Ensamblador'],
            $data['Pago_Comprobador'],
            $data['Pago_Mantenimiento'],
            $data['empresa_nombre'],
            $data['empresa_descripcion']
        );

        if ($stmt->execute()) {
            $result = $conn->query("SELECT @p_id_informe as id");
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        error_log("Error en guardarInformePrincipal: " . $stmt->error);
        return false;
    }

    public function guardarDetalleComponente($idInforme, $idComponente) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_guardar_detalle_componente(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $idInforme, $idComponente);
        
        return $stmt->execute();
    }

    public function obtenerInformePrincipal($idRecaudacion) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_informe_principal(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idRecaudacion);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }

    public function obtenerComponentesInforme($idInforme) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_componentes_informe(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idInforme);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $componentes = [];
        
        while ($row = $result->fetch_assoc()) {
            $componentes[] = $row;
        }
        
        return $componentes;
    }
}
?>