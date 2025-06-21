<?php
require_once __DIR__ . '/../services/UsuarioService.php';
/**
 * Este archivo implementa el controlador de usuarios en un patrón MVC. Su función principal es recibir las solicitudes HTTP (normalmente desde el frontend o cliente), extraer los datos necesarios del cuerpo de la solicitud o de la sesión, y delegar la lógica a la clase UsuarioService. 
 * Luego, devuelve una respuesta en formato JSON.
 */
class UsuarioController {
    private $service;

    public function __construct() {
        $this->service = new UsuarioService();
    }

    // Registro de nuevo usuario:
    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        $response = $this->service->registrarUsuario($data);
        $this->sendResponse($response);
    }
public function login() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['usuario_asignado']) || !isset($data['contrasena'])) {
            $this->sendResponse(['success' => false, 'message' => 'Usuario y contraseña son requeridos']);
            return;
        }
        
        // Obtener respuesta del servicio
        $response = $this->service->login($data['usuario_asignado'], $data['contrasena']);
        
        if ($response['success']) {
            // Iniciar sesión
            session_start();
            session_regenerate_id(true);
            
            // Configurar variables de sesión
            $_SESSION['ID_Usuario'] = $response['usuario']['ID_Usuario'];
            $_SESSION['usuario_asignado'] = $response['usuario']['usuario_asignado'];
            $_SESSION['rol'] = $response['usuario']['tipo'];
            
            // Incluir datos necesarios en la respuesta
            $response['usuario']['ID_Usuario'] = $response['usuario']['ID_Usuario'] ?? $response['usuario']['id'] ?? null;
        }
        
        $this->sendResponse($response);
    } catch (Exception $e) {
        error_log("ERROR LOGIN: " . $e->getMessage());
        $this->sendResponse([
            'success' => false,
            'message' => 'Error interno del servidor'
        ]);
    }
}

public function logout() {
    try {
        // Iniciar la sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $response = ['success' => false, 'message' => 'No hay sesión activa'];
        
        if (isset($_SESSION['ID_Usuario'])) {
            $userId = $_SESSION['ID_Usuario'];
            
            // Registrar el cierre de sesión
            $response = $this->service->logout($userId);
            
            // Destruir la sesión
            $_SESSION = array();
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), 
                    '', 
                    time() - 42000,
                    $params["path"], 
                    $params["domain"],
                    $params["secure"], 
                    $params["httponly"]
                );
            }
            
            session_destroy();
        }
        
        $this->sendResponse($response);
    } catch (Exception $e) {
        error_log("ERROR LOGOUT: " . $e->getMessage());
        $this->sendResponse([
            'success' => false,
            'message' => 'Error al cerrar sesión'
        ]);
    }
}
    /**
     * Autenticación de usuario.
     * Valida presencia de usuario_asignado y contraseña.
     */
   
    // Obtener perfil de usuario por ID:
    /*
    public function obtenerUsuarioPorId($id) {
        $response = $this->service->obtenerUsuarioPorId($id);
        $this->sendResponse($response);
    }
    */

    /**
     * Obtiene técnicos filtrados por especialidad.
     * @param string $especialidad Tipo de técnico (Ensamblador, Comprobador o Mantenimiento).
     */
    public function obtenerTecnicos($especialidad) {
        $response = $this->service->obtenerTecnicosPorEspecialidad($especialidad);
        $this->sendResponse($response);
    }

    /**
     * Devuelve el perfil del usuario autenticado.
    * Verifica que el usuario autenticado consulte solo su propio perfil.
     * @param mixed $id ID de usuario (opcional, puede venir por $_GET).
     * @return void
     */
    public function getProfile($id = null) {
        try {
            // Si no viene ID por parámetro, verificar si viene por GET
            if ($id === null && isset($_GET['id'])) {
                $id = $_GET['id'];
            }
            
            if (!$id) {
                $this->sendResponse(['success' => false, 'message' => 'ID de usuario no proporcionado']);
                return;
            }
            
            // Verificar que el usuario autenticado solo pueda ver su propio perfil
            session_start();
            if (!isset($_SESSION['ID_Usuario']) || $_SESSION['ID_Usuario'] != $id) {
                $this->sendResponse(['success' => false, 'message' => 'No autorizado']);
                return;
            }
            
            $response = $this->service->obtenerUsuario($id);
            
            if ($response['success']) {
                $response['usuario']['ID_Usuario'] = $id;
                unset($response['usuario']['contrasena']);
            }
            
            $this->sendResponse($response);
        } catch (Exception $e) {
            error_log("Error en getProfile: " . $e->getMessage());
            $this->sendResponse(['success' => false, 'message' => 'Error al obtener perfil']);
        }
    }
    /**
     *  Actualiza los datos del perfil del usuario autenticado.
    * Verifica que el usuario solo actualice su propio perfil.
     * @return void 
     */
    public function updateProfile() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos
        if (!$data || !isset($data['id'])) {
            $this->sendResponse(['success' => false, 'message' => 'Datos inválidos']);
            return;
        }
        
        // Validar que el usuario autenticado solo pueda actualizar su propio perfil
        session_start();
        if (!isset($_SESSION['ID_Usuario']) || $_SESSION['ID_Usuario'] != $data['id']) {
            $this->sendResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        $response = $this->service->actualizarPerfil($data);
        $this->sendResponse($response);
    }
    /**
     * Cambia el nombre de usuario (usuario_asignado).
     * Llama a actualizarUsuarioAsignado() en el servicio.
     * @return void
     */
    public function updateUsername() {
        $data = json_decode(file_get_contents('php://input'), true);
        error_log(print_r($data, true)); // Esto mostrará los datos recibidos en los logs
        $response = $this->service->actualizarUsuarioAsignado($data);
        $this->sendResponse($response);
    }
    /**
     *  Permite cambiar la contraseña.
    * Parámetros: JSON con información para restablecer la contraseña.
     * @return void
     */
    public function resetPassword() {
        $data = json_decode(file_get_contents('php://input'), true);
        $response = $this->service->recuperarContrasena($data);
        $this->sendResponse($response);
    }
    /**
     *  Obtiene técnicos por especialidad.
     * @param mixed $especialidad
     * @return void
     */
    public function getTecnicos($especialidad) {
        $response = $this->service->obtenerTecnicosPorEspecialidad($especialidad);
        $this->sendResponse($response);
    }

    /**
     *  Devuelve usuarios filtrados por tipo (ej: Técnico, Administrador).
     * @param mixed $tipo
     * @return void
     */
    public function getByTipo($tipo) {
        $emisorId = isset($_GET['emisorId']) ? intval($_GET['emisorId']) : null;
    
        if (!$tipo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de usuario requerido']);
            return;
        }
    
        $model = new UsuarioModel();
        $usuarios = $model->obtenerUsuariosPorTipo($tipo, $emisorId);
    
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
    }
    /**
     *  Registra una actividad realizada por el usuario autenticado.
     *  Requiere sesión iniciada.
     * @return void
     */
    public function registrarActividad() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['ID_Usuario'])) {
            $this->sendResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }
    
        $data = json_decode(file_get_contents('php://input'), true);
        $descripcion = $data['descripcion'] ?? 'Actividad no especificada';
    
        $response = $this->service->registrarActividad($_SESSION['ID_Usuario'], $descripcion);
        $this->sendResponse($response);
    }
    /**
     *  Devuelve el historial de actividades del usuario.
     * Solo permite acceso al historial si hay una sesión activa.
     * @param mixed $usuarioId ID del usuario.
     * @return void
     */
    public function obtenerHistorialActividades($usuarioId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['ID_Usuario'])) {
            $this->sendResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        $historial = $this->service->obtenerHistorialActividades($usuarioId);
        $this->sendResponse([
            'success'   => true,
            'historial' => $historial
        ]);
    }
    
    
    private function sendResponse($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }


}
?>
