<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioService {
    private $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    /**
     * Registra un nuevo usuario con validación.
     * @param array $data Datos del usuario.
     * @return array Resultado de la operación.
     */
    public function registrarUsuario($data) {
        $required = ['nombre', 'apellido', 'ci', 'email', 'usuario_asignado', 'contrasena', 'tipo'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ['success' => false, 'message' => "El campo $field es requerido"];
            }
        }
        
        // Validar fortaleza de la contraseña
        if (strlen($data['contrasena']) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        
        // Validar tipos de usuario permitidos
        $tiposPermitidos = ['Tecnico', 'Logistica', 'Contabilidad', 'Administrador'];
        if (!in_array($data['tipo'], $tiposPermitidos)) {
            return ['success' => false, 'message' => 'Tipo de usuario no válido'];
        }
        
        // Solo requerir especialidad para técnicos
        if ($data['tipo'] === 'Tecnico' && empty($data['especialidad'])) {
            return ['success' => false, 'message' => 'La especialidad es requerida para técnicos'];
        }
        
        // Llamar al modelo correctamente
        $result = $this->model->registrarUsuario($data);
        
        if ($result['success']) {
            return ['success' => true, 'idUsuario' => $result['userId'], 'message' => $result['message']];
        } else {
            return ['success' => false, 'message' => $result['message']];
        }
    }
        
   
public function login($usuario_asignado, $contrasena) {
    try {
        // 1. Verificar credenciales con el modelo
        $usuario = $this->model->login($usuario_asignado);
        
        if (!$usuario) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // 2. Verificar contraseña
        if (!password_verify($contrasena, $usuario['contrasena'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // 3. Verificar estado del usuario
        if ($usuario['estado'] !== 'Activo') {
            return ['success' => false, 'message' => 'Usuario no está activo'];
        }
        
        // 4. Registrar el inicio de sesión
        $this->model->registrarInicioSesion(
            $usuario['ID_Usuario'],
            $usuario['usuario_asignado'],
            $usuario['contrasena']
        );
        
        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'usuario' => $usuario
        ];
    } catch (Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al iniciar sesión'];
    }
}

public function logout($userId) {
    try {
        // Registrar el cierre de sesión
        $result = $this->model->registrarLogout($userId);
        
        if ($result) {
            return ['success' => true, 'message' => 'Sesión cerrada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al registrar cierre de sesión'];
        }
    } catch (Exception $e) {
        error_log("Error en logout: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al cerrar sesión'];
    }
}
    /**
     * Registrar inicio sesion
     * @param mixed $userId
     * @param mixed $usuarioAsignado
     * @param mixed $contrasenaHash
     * @return bool
     */
    public function registrarInicioSesion($userId, $usuarioAsignado, $contrasenaHash) {
        return $this->model->registrarInicioSesion($userId, $usuarioAsignado, $contrasenaHash);
    }

    public function obtenerEstadoUsuario($userId) {
        return $this->model->obtenerEstadoUsuario($userId);
    }

    public function obtenerUsuario($id) {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            return ['success' => false, 'message' => 'ID de usuario no válido'];
        }
        
        $usuario = $this->model->obtenerUsuarioPorId($id);
        
        if ($usuario) {
            // No devolver información sensible como contraseñas
            unset($usuario['contrasena']);
            return ['success' => true, 'usuario' => $usuario];
        } else {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
    }
    public function obtenerTecnicosPorEspecialidad($especialidad) {
        $tecnicos = $this->model->obtenerTecnicosPorEspecialidad($especialidad);
        return ['success' => true, 'tecnicos' => $tecnicos];
    }
    /**
     * ActualizarPerfil
     * @param mixed $data
     * @return array{message: string, success: bool}
     */
    public function actualizarPerfil($data) {
        try {
            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'El formato del email es inválido'];
            }
            
            // Validar longitud de CI
            if (strlen($data['ci']) < 6) {
                return ['success' => false, 'message' => 'La cédula debe tener al menos 6 caracteres'];
            }
            
            // Si se proporciona nueva contraseña, validar fortaleza
            if (!empty($data['contrasena'])) {
                if (strlen($data['contrasena']) < 8) {
                    return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
                }
                // Hashear la nueva contraseña
                $data['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
            }
            
            $result = $this->model->actualizarPerfil($data);
            
            if ($result) {
                return ['success' => true, 'message' => 'Perfil actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar el perfil en la base de datos'];
            }
        } catch (Exception $e) {
            error_log("Error en actualizarPerfil: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al actualizar el perfil'];
        }
    }
    /**
     * Actualizar contraseña
     * @param mixed $data
     * @return array{message: string, success: bool}
     */
    public function actualizarUsuarioAsignado($data) {
        if (!isset($data['email']) || !isset($data['usuario_asignado'])) {
            return ['success' => false, 'message' => 'Email y nuevo usuario son requeridos'];
        }

        $result = $this->model->actualizarUsuarioAsignado($data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar el usuario'];
        }
    }

    public function recuperarContrasena($data) {
        $result = $this->model->recuperarContrasena($data);
        return ['success' => $result];
    }
 
    public function obtenerUsuariosPorTipo($tipo) {
        if (empty($tipo)) {
            throw new InvalidArgumentException("Tipo de usuario no proporcionado.");
        }
        return $this->model->obtenerUsuariosPorTipo($tipo);
    }
    /**
     * Registro de actividades del usuario para guardarla en su historial
     * @param mixed $idUsuario
     * @param mixed $descripcion
     * @return array{message: string, success: array{success: bool|bool}}
     */
    public function registrarActividad($idUsuario, $descripcion) {
        return $this->model->registrarActividad($idUsuario, $descripcion);
    }
    public function obtenerHistorialActividades($usuarioId) {
        return $this->model->obtenerHistorialActividades($usuarioId);
    }
    
}
?>