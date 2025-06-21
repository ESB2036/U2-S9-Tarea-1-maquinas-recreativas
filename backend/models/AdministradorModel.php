<?php
require_once __DIR__ . '/../config/database.php';

class AdministradorModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function obtenerUsuarioPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_usuario_por_id(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return false;
        }
    }

    public function obtenerTodosUsuarios() {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_todos_usuarios()";
        $result = $conn->query($sql);
        $usuarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }

    public function actualizarUsuario($data) {
        $conn = $this->db->getConnection();
        
        // Asignar a variables primero
        $especialidad = isset($data['especialidad']) ? $data['especialidad'] : null;
        
        $sql = "CALL sp_actualizar_usuario(?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssss", 
            $data['id'], 
            $data['nombre'], 
            $data['apellido'], 
            $data['email'], 
            $data['tipo'], 
            $data['estado'], 
            $data['usuario_asignado'], 
            $especialidad
        );
        
        return $stmt->execute();
    }

    public function obtenerTipoUsuario($id) {
        $conn = $this->db->getConnection();

        $sql = "SELECT tipo FROM usuario WHERE ID_Usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return false;
    }

    public function eliminarUsuario($id) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_eliminar_usuario(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        try {
            return $stmt->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function registrarUsuarioAdmin($data) {
        $conn = $this->db->getConnection();
        
        $contrasenaHash = password_hash($data['contrasena'], PASSWORD_BCRYPT);
        $especialidad = isset($data['especialidad']) ? $data['especialidad'] : null;
        
        $sql = "CALL sp_registrar_usuario_admin(?, ?, ?, ?, ?, ?, ?, ?, ?, @id_usuario)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss", 
            $data['nombre'], 
            $data['apellido'], 
            $data['ci'], 
            $data['email'], 
            $data['usuario_asignado'], 
            $data['tipo'], 
            $data['estado'], 
            $contrasenaHash, 
            $especialidad
        );
        
        if ($stmt->execute()) {
            $result = $conn->query("SELECT @id_usuario as id");
            return $result->fetch_assoc()['id'];
        } else {
            error_log("Error al registrar usuario admin: " . $conn->error);
            return false;
        }
    }

    public function getUsuarios($f) {
        $conn = $this->db->getConnection();
        
        // Asignar a variables primero
        $ci = isset($f['ci']) ? $f['ci'] : null;
        $estado = isset($f['estado']) ? $f['estado'] : null;
        $tipo = isset($f['tipo']) ? $f['tipo'] : null;
        $rango = isset($f['rango']) ? $f['rango'] : null;
        
        $sql = "CALL sp_obtener_usuarios_filtrados(?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $ci, $estado, $tipo, $rango);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerHistorialActividades() {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_historial_actividades()";
        $result = $conn->query($sql);
        $historial = [];
        
        while ($row = $result->fetch_assoc()) {
            $historial[] = $row;
        }
        
        return $historial;
    }

    public function cambiarEstadoUsuario($id, $estado) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_cambiar_estado_usuario(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id, $estado);
        
        return $stmt->execute();
    }
}
?>