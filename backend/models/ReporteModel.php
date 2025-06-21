<?php
require_once __DIR__ . '/../config/database.php';

class ReporteModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function crearReporte($emisorId, $destinatarioId, $descripcion) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_crear_reporte(?, ?, ?, @p_id_reporte)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $emisorId, $destinatarioId, $descripcion);
        
        if ($stmt->execute()) {
            $result = $conn->query("SELECT @p_id_reporte as id");
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return false;
    }

    public function obtenerReportesPorUsuario($userId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_reportes_por_usuario(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $reportes = [];
        
        while ($row = $result->fetch_assoc()) {
            $reportes[] = $row;
        }
        
        return $reportes;
    }

    public function obtenerReportePorId($reporteId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_reporte_por_id(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reporteId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function actualizarEstadoReporte($reporteId, $estado) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_actualizar_estado_reporte(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $reporteId, $estado);
        
        return $stmt->execute();
    }

    public function obtenerChat($emisorId, $destinatarioId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_chat(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $emisorId, $destinatarioId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $reportes = [];
        
        while ($row = $result->fetch_assoc()) {
            $reportes[] = $row;
        }
        
        return $reportes;
    }

    public function obtenerUsuariosChat($userId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_usuarios_chat(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $usuarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }
}
?>