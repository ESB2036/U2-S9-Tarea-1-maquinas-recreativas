<?php
// Importar el servicio de Comercio que contiene la lógica de negocio:
require_once __DIR__ . '/../services/ComercioService.php';

class ComercioController {
    private $service; // Propiedad para almacenar la instancia del servicio.

    // Constructor: inicializa el servicio al crear el controlador.
    public function __construct() {
        $this->service = new ComercioService();
    }

    /**
     * Maneja el registro de un nuevo comercio:
     * - Lee los datos JSON del cuerpo de la solicitud.
     * - Delega el registro al servicio.
     * - Devuelve la respuesta al cliente.
     */
    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        $response = $this->service->registrarComercio($data);
        $this->sendResponse($response);
    }

    /**
     * Obtiene todos los comercios registrados:
     * - No requiere parámetros.
     * - Delega la operación al servicio.
     */
    public function obtenerComercios() {
        $response = $this->service->obtenerComercios();
        $this->sendResponse($response);
    }

    /**
     * Método privado para enviar respuestas consistentes:
     * - Establece el tipo de contenido como JSON.
     * - Codifica la respuesta en formato JSON.
     */
    private function sendResponse($response) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}
?>