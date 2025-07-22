<?php
/**
 * Database Configuration File
 * Car Wash Client Platform Control System
 */

// Database configuration for MAMP
define('DB_HOST', 'localhost:8889'); // MAMP MySQL port
define('DB_NAME', 'carwash_system');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMP default password
define('DB_CHARSET', 'utf8mb4');

/**
 * Database connection class using PDO
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // MAMP specific DSN
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            // Log the error for debugging
            error_log("Connection error: " . $exception->getMessage());
            
            // Show more specific error in development
            if (defined('DEBUG') && DEBUG === true) {
                die("Database connection failed: " . $exception->getMessage());
            }
            
            return null;
        }

        return $this->conn;
    }
}
?>