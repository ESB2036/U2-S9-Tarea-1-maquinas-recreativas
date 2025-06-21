<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function registrarUsuario($data) {
        $conn = $this->db->getConnection();
        
        // Validar datos de entrada
        $required = ['nombre', 'apellido', 'ci', 'email', 'usuario_asignado', 'contrasena', 'tipo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo $field es requerido"];
            }
        }

        try {
            $especialidad = $data['especialidad'] ?? null;
            $hashedPassword = password_hash($data['contrasena'], PASSWORD_DEFAULT);
            
            $sql = "CALL sp_registrar_usuario(?, ?, ?, ?, ?, ?, ?, ?, @p_id_usuario)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssss", 
                $data['nombre'],
                $data['apellido'],
                $data['ci'],
                $data['email'],
                $data['usuario_asignado'],
                $hashedPassword, // Usamos la variable aquí en lugar de la llamada directa a password_hash()
                $data['tipo'],
                $especialidad
            );

            if ($stmt->execute()) {
                $result = $conn->query("SELECT @p_id_usuario as id");
                $row = $result->fetch_assoc();
                
                if ($row['id'] > 0) {
                    return [
                        'success' => true,
                        'message' => 'Usuario registrado correctamente',
                        'userId' => $row['id']
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Error al registrar el usuario'];
        } catch (Exception $e) {
            // Verificar si es error de duplicado
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
                } elseif (strpos($e->getMessage(), 'ci') !== false) {
                    return ['success' => false, 'message' => 'La cédula ya está registrada'];
                } elseif (strpos($e->getMessage(), 'usuario_asignado') !== false) {
                    return ['success' => false, 'message' => 'El nombre de usuario ya está en uso'];
                }
            }
            
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }

    public function login($usuario_asignado) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_login(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario_asignado);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    }

    public function registrarInicioSesion($userId, $usuarioAsignado, $contrasenaHash) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_registrar_inicio_sesion(?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $userId, $usuarioAsignado, $contrasenaHash);
        
        return $stmt->execute();
    }

    public function registrarLogout($userId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_registrar_logout(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        
        return $stmt->execute();
    }

    public function obtenerEstadoUsuario($userId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_estado_usuario(?, @p_estado)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $conn->query("SELECT @p_estado as estado");
        $row = $result->fetch_assoc();
        return $row['estado'];
    }

    public function incrementarActividadesTecnico($idTecnico) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_incrementar_actividades_tecnico(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idTecnico);
        
        return $stmt->execute();
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
        }
        
        return false;
    }

    public function actualizarPerfil($data) {
        $conn = $this->db->getConnection();
        
        // Asegurarse de que los parámetros requeridos estén presentes
        if (!isset($data['id'], $data['nombre'], $data['apellido'], $data['email'], $data['ci'], $data['tipo'], $data['estado'])) {
            return false;
        }
        
        // Asignar valores por defecto para evitar errores
        $especialidad = $data['especialidad'] ?? null;
        $contrasena = $data['contrasena'] ?? null;

        // Si hay contraseña nueva, hashearla
        if (!empty($contrasena)) {
            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $sql = "UPDATE usuario SET contrasena = ? WHERE ID_Usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $contrasenaHash, $data['id']);
            $stmt->execute();
        }

        // Actualizar el resto de los datos
        $sql = "CALL sp_actualizar_perfil(?, ?, ?, ?, ?, ?, ?, ?, @p_resultado)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", 
            $data['id'],
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['ci'],
            $data['tipo'],
            $data['estado'],
            $especialidad
        );

        if ($stmt->execute()) {
            $result = $conn->query("SELECT @p_resultado as resultado");
            $row = $result->fetch_assoc();
            return (bool)$row['resultado'];
        }
        
        return false;
    }

    public function actualizarUsuarioAsignado($data) {
        $conn = $this->db->getConnection();
        
        // Validar datos de entrada
        if (!isset($data['email'], $data['usuario_asignado'])) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        try {
            // Primero obtener el ID del usuario por email
            $sql = "SELECT ID_Usuario FROM usuario WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $data['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Correo electrónico no encontrado'];
            }
            
            $user = $result->fetch_assoc();
            $userId = $user['ID_Usuario'];

            // Luego actualizar el usuario asignado
            $sql = "CALL sp_actualizar_usuario_asignado(?, ?, @p_resultado)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $userId, $data['usuario_asignado']);
            
            if ($stmt->execute()) {
                $result = $conn->query("SELECT @p_resultado as resultado");
                $row = $result->fetch_assoc();
                
                return [
                    'success' => (bool)$row['resultado'],
                    'message' => $row['resultado'] ? 'Usuario actualizado correctamente' : 'No se pudo actualizar el usuario'
                ];
            }
            
            return ['success' => false, 'message' => 'Error al ejecutar la consulta'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }

    public function recuperarContrasena($data) {
        $conn = $this->db->getConnection();
        
        // Validar datos de entrada
        if (!isset($data['email'], $data['nueva_contrasena'])) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        try {
            // Hashear la nueva contraseña
            $contrasenaHash = password_hash($data['nueva_contrasena'], PASSWORD_DEFAULT);
            
            $sql = "CALL sp_recuperar_contrasena(?, ?, @p_resultado)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $data['email'], $contrasenaHash);
            
            if ($stmt->execute()) {
                $result = $conn->query("SELECT @p_resultado as resultado");
                $row = $result->fetch_assoc();
                
                return [
                    'success' => (bool)$row['resultado'],
                    'message' => $row['resultado'] ? 'Contraseña actualizada correctamente' : 'No se pudo actualizar la contraseña'
                ];
            }
            
            return ['success' => false, 'message' => 'Error al ejecutar la consulta'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }

    public function obtenerTecnicosPorEspecialidad($especialidad, $soloActivos = true) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_tecnicos_por_especialidad(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $especialidad, $soloActivos);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $tecnicos = [];
        
        while ($row = $result->fetch_assoc()) {
            $tecnicos[] = $row;
        }
        
        return $tecnicos;
    }

    public function obtenerUsuariosPorTipo($tipo, $excluirId = null) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_usuarios_por_tipo(?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $tipo, $excluirId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $usuarios = [];
        
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }

    public function registrarActividad($idUsuario, $descripcion) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_registrar_actividad(?, ?, @p_resultado)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $descripcion);
        
        if ($stmt->execute()) {
            $result = $conn->query("SELECT @p_resultado as resultado");
            $row = $result->fetch_assoc();
            return (bool)$row['resultado'];
        }
        
        return false;
    }

    public function obtenerHistorialActividades($usuarioId) {
        $conn = $this->db->getConnection();
        
        $sql = "CALL sp_obtener_historial_actividades(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $actividades = [];
        
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        
        return $actividades;
    }
}
?>