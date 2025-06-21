<?php
// Definición de constantes para la configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bd_recrea_sys');

class Database {
    private $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");

        // Ruta del archivo de bloqueo
        $lockFile = __DIR__ . '/.admin_insertado.lock';

        // Eliminar archivo si existe y volverlo a crear
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }

        // Crear el archivo
        file_put_contents($lockFile, "Revisado el " . date("Y-m-d H:i:s"));

        // Verificar si ya existe el admin (por email o CI)
        $emailAdmin = 'jean@admin.com';
        $ci = '1111111111';

        $checkQuery = "SELECT id_usuario FROM usuario WHERE email = ? OR ci = ?";
        $stmtCheck = $this->connection->prepare($checkQuery);
        $stmtCheck->bind_param("ss", $emailAdmin, $ci);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        // Solo insertar si no existe
        if ($stmtCheck->num_rows === 0) {
            $nombre = 'Jean';
            $apellido = 'Castro';
            $contrasenaPlano = '12345678';
            $contrasenaHash = password_hash($contrasenaPlano, PASSWORD_BCRYPT);
            $tipo = 'Administrador';
            $usuario_asignado = 'admin1';
            $estado = 'Activo';

            $insert = "INSERT INTO usuario (nombre, apellido, ci, email, contrasena, tipo, usuario_asignado, estado)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $this->connection->prepare($insert);
            $stmtInsert->bind_param("ssssssss", $nombre, $apellido, $ci, $emailAdmin, $contrasenaHash, $tipo, $usuario_asignado, $estado);
            $stmtInsert->execute();
            $stmtInsert->close();

            // Actualizar contenido del archivo indicando creación
            file_put_contents($lockFile, "Administrador creado el " . date("Y-m-d H:i:s"));
        }

        $stmtCheck->close();
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result) {
            error_log("Error en consulta SQL: " . $this->connection->error);
            error_log("Consulta: " . $sql);
        }
        return $result;
    }

    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }

    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
}
?>
