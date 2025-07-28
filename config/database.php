<?php
/**
 * Configuración de Base de Datos
 * Car Wash Emanuel
 */

// Configuración para XAMPP en Mac
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_emanuel');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP por defecto no tiene contraseña
define('DB_CHARSET', 'utf8mb4');

/**
 * Clase para manejar la conexión a la base de datos
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    /**
     * Obtener conexión a la base de datos
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            return null;
        }
        
        return $this->conn;
    }
}
?>