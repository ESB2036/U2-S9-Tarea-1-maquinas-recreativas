<?php
require_once __DIR__ . '/../services/AdministradorService.php';
/**
 * Modelo encargado de interactuar directamente con la base de datos. Ejecuta consultas SQL para realizar operaciones CRUD sobre los usuarios y sus datos asociados.
 */
class AdministradorController {
    private $service;
    /**
     * __construct()

* Inicializa la conexión a la base de datos usando la clase Database.
     */
    public function __construct() {
        $this->service = new AdministradorService();
    }
    /**
     * Obtiene un usuario por su ID.
     * @param mixed $id (int) – ID del usuario a consultar.
     * @return void
     */
    public function getUser($id) {
        try {
            $usuario = $this->service->obtenerUsuario($id);
            $this->sendResponse(['success' => true, 'usuario' => $usuario]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Devuelve todos los usuarios, con posibilidad de aplicar filtros.
     * @param mixed $filters (array, opcional) – Filtros para refinar la búsqueda.
     * @return void
     */
    public function getAllUsers($filters = []) {
        try {
            $usuarios = $this->service->obtenerTodosUsuarios($filters);
            $this->sendResponse(['success' => true, 'usuarios' => $usuarios]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Actualiza todos los datos de un usuario existente.
     * @param mixed $id – ID del usuario.
     * @param mixed $data  – Datos completos del usuario.
     * @return void
     */
    public function updateUser($id, $data) {
        try {
            // Asegurarse que el ID esté en los datos
            $data['ID_Usuario'] = $id; // Cambiado de ID_Usuario a id
            
            $result = $this->service->actualizarUsuario($data);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Actualiza parcialmente los datos del usuario.
     * @param mixed $id  – ID del usuario.
     * @param mixed $data – Datos a actualizar (pueden ser incompletos).
     * @return void
     */
    public function partialUpdateUser($id, $data) {
        try {
            $data['ID_Usuario'] = $id;
            $result = $this->service->actualizarParcialUsuario($data);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Registra un nuevo usuario con rol de administrador.
     * @param mixed $data  (array) – Datos del nuevo usuario.
     * @return void
     */
    public function registerAdmin($data) {
        try {
            $result = $this->service->registrarUsuarioAdmin($data);
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    /**
     * Elimina un usuario según su ID.
     * @param mixed $id 
     * @return void
     */
    public function deleteUser($id) {
        try {
            $result = $this->service->eliminarUsuario($id);
            $this->sendResponse($result);
        } catch (Exception $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            $this->sendResponse([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Obtiene el historial de actividades de los usuarios.
     * @return void
     */
    public function obtenerHistorialActividades() {
        try {
            $historial = $this->service->obtenerHistorialActividades();
            $this->sendResponse(['success' => true, 'historial' => $historial]);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * Envía una respuesta HTTP en formato JSON.
     * @param mixed $response  (array) – Cuerpo de la respuesta.
     * @param mixed $statusCode  (int, opcional) – Código HTTP (por defecto 200).
     * @return void
     */
    private function sendResponse($response, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
?>