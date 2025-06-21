<?php
/**
 * API propia (es decir, una API REST personalizada).
 * Es el enrutador principal que maneja todas las
 * solicitudes HTTP entrantes y las dirige a los
 * controladores adecuados.
 */

// =============================================
// CONFIGURACIÓN CORS (Cross-Origin Resource Sharing)
// =============================================
// Permite solicitudes desde cualquier origen (*) - (en producción real debería restringirse)
header("Access-Control-Allow-Origin: *");
// Métodos HTTP permitidos:
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
// Cabeceras permitidas en las solicitudes:
header("Access-Control-Allow-Headers: Content-Type, Authorization");
// Indicar que todas las respuestas serán en formato JSON:
header("Access-Control-Allow-Credentials: true"); 
header("Content-Type: application/json");

// =============================================
// INCLUIR CONTROLADORES
// =============================================
// Cargar los archivos de los controladores que manejarán las solicitudes:
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/ComercioController.php';
require_once __DIR__ . '/../controllers/MaquinaController.php';
require_once __DIR__ . '/../controllers/NotificacionController.php';
require_once __DIR__ . '/../controllers/AdministradorController.php';
require_once __DIR__ . '/../controllers/ReporteController.php';
require_once __DIR__ . '/../controllers/ComentarioController.php';
require_once __DIR__ . '/../controllers/InformeController.php';
require_once __DIR__ . '/../controllers/DistribucionController.php';
require_once __DIR__ . '/../controllers/ComponenteController.php';

// =============================================
// FUNCIÓN PRINCIPAL DE ENRUTAMIENTO
// =============================================
/**
 * Función que enruta las solicitudes a los controladores adecuados:
 * 
 * @param string $apiRoute La ruta solicitada (ej. '/api/usuario/login')
 * @param string $requestMethod El método HTTP usado (GET, POST, etc.)
 */
function routeRequest($apiRoute, $requestMethod) {
    // Usar switch para manejar diferentes rutas:
    switch ($apiRoute) {
        // --------------------------------
        // ENDPOINTS DE USUARIO
        // --------------------------------
        case '/api/usuario/register':
            // Solo aceptar método POST para registro:
            if ($requestMethod === 'POST') {
                // Verificar si los datos vienen como JSON
                $input = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Si no es JSON válido, usar datos POST tradicionales
                    $input = $_POST;
                }
                
                // Crear instancia del controlador y llamar al método register
                $controller = new UsuarioController();
                $controller->register();
            }
            break;

        case '/api/usuario/login':
            if ($requestMethod === 'POST') {
                $controller = new UsuarioController();
                $controller->login();
            } else {
                // Método no permitido (405):
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        // Ruta dinámica para perfiles de usuario (ej. /api/usuario/profile/123)
        case (preg_match('/\/api\/usuario\/profile\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new UsuarioController();
                // Pasar el ID capturado en la URL al método:
                $controller->getProfile($matches[1]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        // Ruta dinámica para obtener técnicos por especialidad:
        case (preg_match('/\/api\/usuario\/tecnicos\/(\w+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new UsuarioController();
                $controller->obtenerTecnicos($matches[1]); // Pasar especialidad.
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        // --------------------------------
        // ENDPOINTS DE COMERCIO
        // --------------------------------
        case '/api/comercio/register':
            if ($requestMethod === 'POST') {
                $controller = new ComercioController();
                $controller->register();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        case '/api/comercio/all':
            if ($requestMethod === 'GET') {
                $controller = new ComercioController();
                $controller->obtenerComercios(); // Obtener todos los comercios
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        // --------------------------------
        // ENDPOINTS DE MÁQUINAS RECREATIVAS
        // --------------------------------
        case '/api/maquina/register':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->register();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        // Endpoints para el flujo de trabajo de las máquinas:
        case '/api/maquina/mandar-comprobacion':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->mandarAComprobacion();
            }
            break;

        case '/api/maquina/mandar-reensamblar':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->mandarAReensamblar();
            }
            break;

        case '/api/maquina/mandar-distribucion':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->mandarADistribucion();
            }
            break;

        case '/api/maquina/poner-operativa':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->ponerOperativa();
            }
            break;

        // Obtener máquinas por técnico de ensamblaje/reensamblaje:
        case (preg_match('/\/api\/maquina\/ensamblador\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new MaquinaController();
                $controller->obtenerPorTecnicoEnsamblador($matches[1]);
            }
            break;

        // Obtener máquinas por técnico de comprobación:
        case (preg_match('/\/api\/maquina\/comprobador\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new MaquinaController();
                $controller->obtenerPorTecnicoComprobador($matches[1]);
            }
            break;

        // Obtener máquinas por técnico de mantenimiento:
        case (preg_match('/\/api\/maquina\/mantenimiento\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new MaquinaController();
                $controller->obtenerPorTecnicoMantenimiento($matches[1]);
            }
            break;

        // Endpoints para mantenimiento:
        case '/api/maquina/dar-mantenimiento':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->darMantenimiento();
            }
            break;

        case '/api/maquina/finalizar-mantenimiento':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->finalizarMantenimiento();
            }
            break;

        // Obtener máquinas por estado:
        case (preg_match('/\/api\/maquina\/estado\/(\w+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new MaquinaController();
                $controller->obtenerPorEstado($matches[1]);
            }
            break;
        
        // Obtener máquinas por etapa:
        case (preg_match('/\/api\/maquina\/etapa\/(\w+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new MaquinaController();
                $controller->obtenerPorEtapa($matches[1]);
            }
            break;

        // --------------------------------
        // ENDPOINTS DE NOTIFICACIONES
        // --------------------------------
        
        case (preg_match('/\/api\/notificaciones_maquina\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new NotificacionController();
                $controller->obtenerPorUsuario($matches[1]); // Obtener notificaciones por usuario.
            }
            break;

        case (preg_match('/\/api\/notificaciones\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new NotificacionController();
                $controller->getByUser($matches[1]); // Obtener notificaciones por usuario.
            }
            break;
        case '/api/notificaciones/create':
            if ($requestMethod === 'POST') {
                $controller = new NotificacionController();
                $controller->create();
            }
            break;

        case '/api/notificaciones/marcar-leida':
            if ($requestMethod === 'POST') {
                $controller = new NotificacionController();
                $controller->marcarComoLeida();
            }
            break;

        case (preg_match('/\/api\/notificaciones\/no-leidas\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new NotificacionController();
                $controller->obtenerNoLeidas($matches[1]);
            }
            break;


        // --------------------------------
        // PERFIL DE USUARIO ENDPOINTS
        // --------------------------------
        case '/api/usuario/perfil':
            if ($requestMethod === 'GET') {
                $controller = new UsuarioController();
                $id = isset($_GET['id']) ? $_GET['id'] : null;
                $controller->getProfile($id);
            }
            break;
            
        case '/api/usuario/recuperar-contrasena':
            if ($requestMethod === 'POST') {
                $controller = new UsuarioController();
                $controller->resetPassword();
            }
            break;
        case '/api/usuario/recuperar-usuario':
            if ($requestMethod === 'POST') {
                $controller = new UsuarioController();
                $controller->updateUsername();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
           /// AdministradorModulo
        case '/api/administrador/usuarios':
            $controller = new AdministradorController();
            if ($requestMethod === 'GET') {
                // Obtener todos los usuarios con filtros
                $filters = $_GET; // Puede incluir tipo, estado, etc.
                $controller->getAllUsers($filters);
            } elseif ($requestMethod === 'POST') {
                // Registrar nuevo usuario
                $input = json_decode(file_get_contents('php://input'), true);
                $controller->registerAdmin($input);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        case (preg_match('/\/api\/administrador\/usuarios\/(\d+)/', $apiRoute, $matches) ? true : false):
            $controller = new AdministradorController();
            $userId = $matches[1];
            
            if ($requestMethod === 'GET') {
                $controller->getUser($userId);
            } elseif ($requestMethod === 'PUT') {
                $input = json_decode(file_get_contents('php://input'), true);
                $controller->updateUser($userId, $input);
            } elseif ($requestMethod === 'PATCH') {
                $input = json_decode(file_get_contents('php://input'), true);
                $controller->partialUpdateUser($userId, $input);
            } elseif ($requestMethod === 'DELETE') {
                $controller->deleteUser($userId);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
        case '/api/usuario/logout':
                if ($requestMethod === 'POST') {
                    $controller = new UsuarioController();
                    $controller->logout();
                } else {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                }
                break;

        case '/api/usuario/update-profile':
            if ($requestMethod === 'POST') {
                $controller = new UsuarioController();
                $input = json_decode(file_get_contents('php://input'), true);
                $controller->updateProfile();
            }
            break;
        case '/api/usuarios/activos':
            if ($requestMethod === 'GET') {
                (new UsuarioController())->getActiveUsers();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
        case '/api/usuarios/por-tipo':
            if ($requestMethod === 'GET') {
                $controller = new UsuarioController();
                $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
                $emisorId = isset($_GET['emisorId']) ? $_GET['emisorId'] : null;
                $controller->getByTipo($tipo, $emisorId);  // <--- pasa también $emisorId
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
// ── Historial y registro de actividades ──────────────────────────────────
        case '/api/historial-actividades':
            session_start();

            if ($requestMethod === 'GET') {
                // 1) GET /api/historial-actividades?usuarioId=123
                if (isset($_GET['usuarioId'])) {
                    $controller = new UsuarioController();
                    $controller->obtenerHistorialActividades($_GET['usuarioId']);
                }
                // 2) GET /api/historial-actividades  → historial completo (solo admin)
                else {
                    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador del sistema') {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'No autorizado']);
                        break;
                    }
                    $controller = new AdministradorController();
                    $controller->obtenerHistorialActividades();
                }
            }
            else if ($requestMethod === 'POST') {
                // POST /api/historial-actividades
                if (!isset($_SESSION['ID_Usuario'])) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'No autorizado']);
                    break;
                }
                $controller = new UsuarioController();
                $controller->registrarActividad();
            }
            else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;  
        // --------------------------------
        // ENDPOINTS DE REPORTES (NUEVOS)
        // --------------------------------
        case '/api/reportes/crear':
            if ($requestMethod === 'POST') {
                (new ReporteController())->create();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        case (preg_match('#^/api/reportes/usuario/(\d+)$#', $apiRoute, $m) ? true : false):
            if ($requestMethod === 'GET') {
                (new ReporteController())->getByUser($m[1]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;


        case (preg_match('#^/api/reportes/chat/(\d+)/(\d+)$#', $apiRoute, $m) ? true : false):
            if ($requestMethod === 'GET') {
                (new ReporteController())->getChat($m[1], $m[2]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
        case (preg_match('#^/api/reportes/(\d+)/estado$#', $apiRoute, $m) ? true : false):
            if ($requestMethod === 'PUT') {
                (new ReporteController())->updateStatus($m[1]);
            }
            break;
            case '/api/reportes/usuarios-chat':
                if ($requestMethod === 'GET') {
                    $userId = $_GET['userId'] ?? null;
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Se requiere userId']);
                        break;
                    }
                    (new ReporteController())->getUsuariosChat($userId);
                }
                break;
            
        case '/api/reportes/chat-completo':
            if ($requestMethod === 'GET') {
                $emisorId = $_GET['emisorId'] ?? null;
                $destinatarioId = $_GET['destinatarioId'] ?? null;
                $reporteId = $_GET['reporteId'] ?? null;
                
                if (!$emisorId || !$destinatarioId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Se requieren emisorId y destinatarioId']);
                    break;
                }
                
                (new ReporteController())->getCompleteChat($emisorId, $destinatarioId, $reporteId);
            }
            break;
        // --------------------------------
        // ENDPOINTS DE COMENTARIOS (NUEVOS)
        // --------------------------------
        case '/api/comentarios':
            if ($requestMethod === 'POST') {
                (new ComentarioController())->create();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

            case (preg_match('#^/api/comentarios/reporte/(\d+)$#', $apiRoute, $m) ? true : false):
                if ($requestMethod === 'GET') {
                    (new ComentarioController())->getByReporte($m[1]);
                }
                break;
        // --------------------------------
        // ENDPOINTS DE LOGISTICA Y MANTENIMIENTO 
        // --------------------------------
        case '/api/maquina/distribucion':
            if ($requestMethod === 'GET') {
                (new MaquinaController())->obtenerMaquinasParaDistribucion();
            }
            break;

        // Endpoint para obtener informes de distribución
        case '/api/distribucion/informes':
            if ($requestMethod === 'GET') {
                (new DistribucionController())->obtenerInformesDistribucion();
            }
            break;
        // --------------------------------
        // ENDPOINTS DE NOTIFICACIONES 
        // --------------------------------
        case (preg_match('#^/api/notificaciones/(\d+)$#', $apiRoute, $m) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new NotificacionController();
                $controller->getByUser($m[1]); // Obtener notificaciones por usuario.
                (new NotificacionController())->getNotificaciones($m[1]);
            }
            break;
   
    

        case (preg_match('#^/api/notificaciones/(\d+)/marcarla-leida$#', $apiRoute, $m) ? true : false):
            if ($requestMethod === 'POST') {
                (new NotificacionController())->marcarComoLeidaNotificacion($m[1]);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        case '/api/notificaciones/marcarla-todas-leidas':
            if ($requestMethod === 'POST') {
                (new NotificacionController())->marcarTodasComoLeidas();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;
        // --------------------------------
        // ENDPOINTS DE COMPONENTES
        // --------------------------------
        case '/api/componentes':
            if ($requestMethod === 'GET') {
                $controller = new ComponenteController();
                $tipo = $_GET['tipo'] ?? null;
                $controller->obtenerComponentes($tipo);
            }
            break;
        case '/api/componentes/disponibles':
            if ($requestMethod === 'GET') {
                $controller = new ComponenteController();
                $tipo = $_GET['tipo'] ?? null;
                $controller->obtenerComponentesDisponibles($tipo);
            }
            break;

        case '/api/maquina/generar-placa':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->generarPlaca();
            }
            break;
        case '/api/componentes/usar':
            if ($requestMethod === 'POST') {
                $controller = new ComponenteController();
                $controller->usarComponente();
            }
            break;

        case '/api/componentes/liberar':
            if ($requestMethod === 'POST') {
                $controller = new ComponenteController();
                $controller->liberarComponente();
            }
            break;
        case '/api/componentes/asignar-carcasa':
            if ($requestMethod === 'POST') {
                $controller = new ComponenteController();
                $controller->asignarCarcasa();
            }
            break;
        case (preg_match('/\/api\/componentes\/en-uso\/(\d+)/', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                $controller = new ComponenteController();
                $controller->obtenerComponentesEnUso($matches[1]);
            }
            break;
        case '/api/componentes/liberar-cancelacion':
            if ($requestMethod === 'POST') {
                $controller = new ComponenteController();
                $controller->liberarComponentesCancelacion();
            }
            break;
            
        // --------------------------------
        // ENDPOINTS DE INFORMES DE CONTABILIDAD
        // --------------------------------

        case '/api/contabilidad/registrar-recaudacion':
            if ($requestMethod === 'POST') {
                (new InformeController())->registrarRecaudacion();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
            break;

        case '/api/contabilidad/recaudaciones':
            if ($requestMethod === 'GET') {
                (new InformeController())->obtenerRecaudaciones();
            }
            break;

        case '/api/contabilidad/resumen-recaudaciones':
            if ($requestMethod === 'GET') {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 2;
                
                (new InformeController())->obtenerResumenRecaudaciones($limit);
            }
            break;

        case '/api/contabilidad/actualizar-recaudacion':
            if ($requestMethod === 'PUT') {
                (new InformeController())->actualizarRecaudacion();
            }
            break;

        case (preg_match('#^/api/contabilidad/eliminar-recaudacion/(\d+)$#', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'DELETE') {
                (new InformeController())->eliminarRecaudacion($matches[1]);
            }
            break;

        case '/api/contabilidad/maquinas-recaudacion':
            if ($requestMethod === 'GET') {
                (new InformeController())->obtenerMaquinasRecaudacion();
            }
            break;
        case '/api/contabilidad/maquinas-operativas-por-comercio':
            if ($requestMethod === 'GET' && isset($_GET['ID_Comercio'])) {
                (new InformeController())->obtenerMaquinasOperativasPorComercio();
            }
            break;

        case (preg_match('#^/api/maquina/componentes/(\d+)$#', $apiRoute, $matches) ? true : false):
            if ($requestMethod === 'GET') {
                (new MaquinaController())->obtenerComponentesMaquina($matches[1]);
            }
            break;

        case '/api/contabilidad/guardar-informe':
            if ($requestMethod === 'POST') {
                (new InformeController())->guardarInforme();
            }
            break;
        //ENDPOINT DE MONTAJE
        case '/api/maquina/registrar-montaje':
            if ($requestMethod === 'POST') {
                $controller = new MaquinaController();
                $controller->registrarMontaje();
            }
            break;


        // --------------------------------
        // ENDPOINT POR DEFECTO (404)
        // --------------------------------
        default:
            // Si no coincide con ninguna ruta, devolver 404:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint no encontrado']);
            break;
    }
}
?>