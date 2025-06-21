<?php
require_once __DIR__ . '/../services/InformeService.php';
/**
* Clase que actúa como intermediario entre las solicitudes HTTP y el servicio InformeService. 
* Se encarga de recibir datos, invocar métodos del servicio y devolver una respuesta JSON al cliente.
*/
class InformeController {
    private $service;

    public function __construct() {
        $this->service = new InformeService();
    }

    /**
    * Registra una nueva recaudación con todos los campos necesarios
    */
public function registrarRecaudacion() {
    // Iniciar sesión para verificar autenticación
    session_start();
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        return;
    }

    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['ID_Usuario'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado - Debe iniciar sesión']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        return;
    }
    
    // Validación de campos requeridos
    $requiredFields = ['ID_Maquina', 'Tipo_Comercio', 'Monto_Total', 'fecha'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "El campo $field es requerido"]);
            return;
        }
    }

    // Agregar el ID del usuario desde la sesión
    $data['ID_Usuario'] = $_SESSION['ID_Usuario'];

    $response = $this->service->registrarRecaudacion($data);
    $this->sendResponse($response);
}
    /**
    * Obtiene recaudaciones con filtros
    */
    public function obtenerRecaudaciones() {
        $filters = [
            'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
            'fecha_fin' => $_GET['fecha_fin'] ?? null,
            'ID_Maquina' => $_GET['ID_Maquina'] ?? null,
            'Tipo_Comercio' => $_GET['Tipo_Comercio'] ?? null
        ];

        $response = $this->service->obtenerRecaudaciones($filters);
        $this->sendResponse($response);
    }

    /**
    * Obtiene el resumen de recaudaciones
    */
public function obtenerResumenRecaudaciones($limit) {
    // Validate the limit parameter
    if (!is_numeric($limit) ){
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El parámetro limit debe ser numérico']);
        return;
    }
    
    $limit = (int)$limit;
    if ($limit <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El parámetro limit debe ser mayor que 0']);
        return;
    }

    $response = $this->service->obtenerResumenRecaudacionesLimitado($limit);
    $this->sendResponse($response);
}

    /**
    * Actualiza una recaudación existente
    */
    public function actualizarRecaudacion() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ID_Recaudacion']) || empty($data['ID_Recaudacion'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "ID de recaudación es requerido"]);
            return;
        }

        $response = $this->service->actualizarRecaudacion($data);
        $this->sendResponse($response);
    }

    /**
    * Elimina una recaudación
    */
    public function eliminarRecaudacion($id) {
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "ID de recaudación es requerido"]);
            return;
        }

        $response = $this->service->eliminarRecaudacion($id);
        $this->sendResponse($response);
    }

    /**
    * Obtiene máquinas disponibles para recaudación (solo las operativas)
    */
    public function obtenerMaquinasRecaudacion() {
        $response = $this->service->obtenerMaquinasRecaudacion();
        $this->sendResponse($response);
        }
    public function obtenerMaquinasOperativasPorComercio() {
        if (!isset($_GET['ID_Comercio'])) {
            $this->sendResponse(['success' => false, 'message' => 'ID_Comercio es requerido']);
            return;
        }
        
        $id_comercio = $_GET['ID_Comercio'];
        
        if (!is_numeric($id_comercio) ){
            $this->sendResponse(['success' => false, 'message' => 'ID_Comercio debe ser numérico']);
            return;
        }
        
        $response = $this->service->obtenerMaquinasOperativasPorComercio($id_comercio);
        $this->sendResponse($response);
    }
     public function guardarInforme() {
        session_start();
        
        // Verificar autenticación
        if (!isset($_SESSION['ID_Usuario'])) {
            $this->sendResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        // Obtener y validar datos JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendResponse(['success' => false, 'message' => 'Datos JSON inválidos']);
            return;
        }

        // Campos obligatorios
        $requiredFields = [
            'ID_Recaudacion', 
            'CI_Usuario', 
            'Nombre_Maquina', 
            'ID_Comercio',
            'Nombre_Comercio', 
            'Direccion_Comercio', 
            'Telefono_Comercio',
            'Monto_Total'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->sendResponse(['success' => false, 'message' => "El campo $field es requerido"]);
                return;
            }
        }

        // Agregar datos técnicos si están disponibles
        $data['Pago_Ensamblador'] = 400.00;
        $data['Pago_Comprobador'] = 400.00;
        $data['Pago_Mantenimiento'] = isset($data['ID_Tecnico_Mantenimiento']) ? 400.00 : 0.00;
        $data['empresa_nombre'] = 'recreasys.s.a';
        $data['empresa_descripcion'] = 'Una empresa encargada en el ciclo de vida de las maquinas recreativas';

        // Delegar al servicio
        $response = $this->service->guardarInforme($data);
        $this->sendResponse($response);
    }

    /**
     * Obtiene un informe por ID de recaudación
     */
    public function obtenerInformePorRecaudacion($idRecaudacion) {
        $response = $this->service->obtenerInformePorRecaudacion($idRecaudacion);
        $this->sendResponse($response);
    }
    private function sendResponse($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
?>