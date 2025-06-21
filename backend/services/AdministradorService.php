<?php
require_once __DIR__ . '/../models/AdministradorModel.php';
/**
 * Este archivo implementa una clase de servicio (AdministradorService) que gestiona la lógica relacionada con los usuarios administradores. Se comunica con AdministradorModel para acceder o modificar datos de usuarios.
 */
class AdministradorService {
    private $model;
    /**
     *  Inicializa el modelo de administrado
     */
    public function __construct() {
        $this->model = new AdministradorModel();
    }
    /**
     * Retorna todos los usuarios o solo los filtrados.
     * @param mixed $id  (int) - ID del usuario.
     * @throws \Exception
     * @return array|bool 
     */
    public function obtenerUsuario($id) {
        $usuario = $this->model->obtenerUsuarioPorId($id);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado');
        }
        return $usuario;
    }
    /**
     * Retorna todos los usuarios o solo los filtrados.
     * @param mixed $filters  (array) - filtros opcionales.
     * @return array
     */
    public function obtenerTodosUsuarios($filters = []) {
        if (!empty($filters)) {
            return $this->model->getUsuarios($filters);
        }
        return $this->model->obtenerTodosUsuarios();
    }
    /**
     * Actualiza todos los datos de un usuario.
     * @param mixed $data (array) - datos completos del usuario.
     * @throws \Exception 
     * @return array{message: string, success: bool}
     */
    public function actualizarUsuario($data) {
        $this->validarDatosUsuario($data, true);
        $success = $this->model->actualizarUsuario($data);
        
        if (!$success) {
            throw new Exception('Error al actualizar en la base de datos');
        }
        
        return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
    }
    /**
     * Solo cambia el estado de un usuario.
     * @param mixed $data (array) - incluye ID y nuevo estado.
     * @throws \Exception
     * @return array{message: string, success: bool}
     */
    public function actualizarParcialUsuario($data) {
        if (!isset($data['estado'])) {
            throw new Exception('Estado no proporcionado');
        }
        
        $success = $this->model->cambiarEstadoUsuario($data['ID_Usuario'], $data['estado']);
        
        if (!$success) {
            throw new Exception('Error al actualizar el estado en la base de datos');
        }
        
        return ['success' => true, 'message' => 'Estado de usuario actualizado correctamente'];
    }
    /**
     *  Elimina un usuario por su ID.
     * @param mixed $id (int) - ID del usuario.
     * @throws \Exception
     * @return array{message: string, success: bool}
     */
    public function eliminarUsuario($id) {
        if (empty($id)) {
            throw new Exception('ID de usuario no proporcionado');
        }

        // Obtener tipo de usuario
        $usuario = $this->model->obtenerTipoUsuario($id);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado');
        }

        if ($usuario['tipo'] === 'Administrador') {
            throw new Exception('No puedes eliminar a otro administrador del sistema');
        }

        // Intentar eliminar usuario
        $success = $this->model->eliminarUsuario($id);

        if (!$success) {
            throw new Exception('Error al eliminar el usuario en la base de datos');
        }

        return ['success' => true, 'message' => 'Usuario eliminado correctamente'];
    }

    /**
     *  Registra un nuevo usuario administrador.
     * @param mixed $data (array) - datos necesarios del usuario.
     * @throws \Exception
     * @return array{id: bool|int|string, message: string, success: bool} 
     */
    public function registrarUsuarioAdmin($data) {
        // Validación adicional para usuario_asignado
        if (empty($data['usuario_asignado']) || trim($data['usuario_asignado']) === '') {
            throw new Exception("El campo usuario_asignado es requerido y no puede estar vacío");
        }
        
        // Validar fortaleza de la contraseña
        if (strlen($data['contrasena']) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }
        
        $this->validarDatosUsuario($data);
        
        try {
            $id = $this->model->registrarUsuarioAdmin($data);
            
            if (!$id) {
                throw new Exception('Error al registrar el usuario');
            }
            
            return ['success' => true, 'message' => 'Usuario registrado correctamente', 'id' => $id];
        } catch (Exception $e) {
            error_log("Error en registrarUsuarioAdmin: " . $e->getMessage());
            throw new Exception('Error al procesar el registro del usuario');
        }
    }
    /**
     * Valida campos requeridos y reglas según tipo de usuario.
     * @param mixed $data  datos del usuario,
     * @param mixed $isUpdate si es true, exige también el ID.
     * @throws \Exception
     * @return void
     */
    private function validarDatosUsuario($data, $isUpdate = false) {
        $required = $isUpdate ? 
            ['ID_Usuario', 'nombre', 'apellido', 'email', 'tipo', 'estado'] : 
            ['nombre', 'apellido', 'ci', 'email', 'usuario_asignado', 'contrasena', 'tipo'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }
    
        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del email no es válido");
        }
    
        // Validar tipo de usuario
        $tiposPermitidos = ['Tecnico', 'Logistica', 'Contabilidad', 'Administrador'];
        if (!in_array($data['tipo'], $tiposPermitidos)) {
            throw new Exception("Tipo de usuario no válido");
        }
    
        if ($data['tipo'] === 'Tecnico' && empty($data['especialidad'])) {
            throw new Exception("La especialidad es requerida para técnicos");
        }
    }
    /**
     *  Devuelve el historial de actividades.
     * @return array<array|bool|null>
     */
    public function obtenerHistorialActividades() {
        return $this->model->obtenerHistorialActividades();
    }
}
?>