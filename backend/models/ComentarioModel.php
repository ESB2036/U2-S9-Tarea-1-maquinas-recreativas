<?php
require_once __DIR__ . '/../config/database.php';
/**
 * Clase de modelo encargada de interactuar con la base de datos para operaciones CRUD de comentarios.
 */
class ComentarioModel {
    private $db;
    /**
     * Summary of __construct Establece la conexión a la base de datos.
     */
    public function __construct() {
        $this->db = new Database();
    }
    /**
     * Inserta un nuevo comentario.
     * @param mixed $reporteId ID del reporte,
     * @param mixed $emisorId  ID del usuario emisor,
     * @param mixed $comentario contenido del comentario.
     * @return bool|int|string
     */
    public function crearComentario($reporteId, $emisorId, $comentario) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_crear_comentario(?, ?, ?, @id_comentario)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $reporteId, $emisorId, $comentario);
        
        if ($stmt->execute()) {
            $result = $conn->query("SELECT @id_comentario AS id");
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return false;
    }

    public function obtenerComentariosPorReporte($reporteId, $userId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_comentarios_por_reporte(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $reporteId, $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $comentarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $comentarios[] = $row;
        }
        
        return $comentarios;
    }

    public function obtenerComentariosPorChat($emisorId, $destinatarioId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_comentarios_por_chat(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $emisorId, $destinatarioId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $comentarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $comentarios[] = $row;
        }
        
        return $comentarios;
    }
}
?>