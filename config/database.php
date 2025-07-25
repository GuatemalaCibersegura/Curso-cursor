<?php
/**
 * Database Configuration File
 * Car Wash Client Platform Control System
 * Auto-detection for MAMP, XAMPP, and other environments
 */

define('DB_NAME', 'carwash_system');
define('DB_CHARSET', 'utf8mb4');

/**
 * Database connection class with auto-detection
 */
class Database {
    private $db_name = DB_NAME;
    private $charset = DB_CHARSET;
    private $conn;
    
    // Configuraciones posibles para diferentes entornos
    private $configurations = [
        // MAMP configuraciones
        [
            'host' => 'localhost:8889',
            'user' => 'root',
            'pass' => 'root',
            'name' => 'MAMP (Puerto 8889)'
        ],
        [
            'host' => 'localhost:3306',
            'user' => 'root',
            'pass' => 'root',
            'name' => 'MAMP (Puerto 3306)'
        ],
        [
            'host' => 'localhost:8889',
            'user' => 'root',
            'pass' => '',
            'name' => 'MAMP sin contraseña'
        ],
        // XAMPP configuraciones
        [
            'host' => 'localhost',
            'user' => 'root',
            'pass' => '',
            'name' => 'XAMPP/Local'
        ],
        [
            'host' => 'localhost:3306',
            'user' => 'root',
            'pass' => '',
            'name' => 'MySQL Estándar'
        ],
        // Configuraciones alternativas
        [
            'host' => '127.0.0.1:8889',
            'user' => 'root',
            'pass' => 'root',
            'name' => 'MAMP IP'
        ],
        [
            'host' => '127.0.0.1:3306',
            'user' => 'root',
            'pass' => '',
            'name' => 'MySQL IP'
        ],
        [
            'host' => '127.0.0.1',
            'user' => 'root',
            'pass' => '',
            'name' => 'Local IP'
        ]
    ];

    /**
     * Get database connection with auto-detection
     * @return PDO|null
     */
    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT            => 5
        ];
        
        // Intentar cada configuración hasta encontrar una que funcione
        foreach ($this->configurations as $config) {
            try {
                $dsn = "mysql:host={$config['host']};dbname={$this->db_name};charset={$this->charset}";
                $this->conn = new PDO($dsn, $config['user'], $config['pass'], $options);
                
                // Si llegamos aquí, la conexión fue exitosa
                error_log("Database connection successful using: " . $config['name']);
                return $this->conn;
                
            } catch(PDOException $exception) {
                // Log el intento fallido y continuar con la siguiente configuración
                error_log("Failed connection attempt with {$config['name']}: " . $exception->getMessage());
                continue;
            }
        }
        
        // Si ninguna configuración funcionó, intentar crear la base de datos
        return $this->createDatabaseAndConnect();
    }
    
    /**
     * Intentar crear la base de datos si no existe
     * @return PDO|null
     */
    private function createDatabaseAndConnect() {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ];
        
        foreach ($this->configurations as $config) {
            try {
                // Conectar sin especificar base de datos
                $dsn = "mysql:host={$config['host']};charset={$this->charset}";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
                
                // Intentar crear la base de datos
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Conectar a la base de datos creada
                $dsn = "mysql:host={$config['host']};dbname={$this->db_name};charset={$this->charset}";
                $this->conn = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
                
                error_log("Database created and connected successfully using: " . $config['name']);
                return $this->conn;
                
            } catch(PDOException $exception) {
                error_log("Failed to create database with {$config['name']}: " . $exception->getMessage());
                continue;
            }
        }
        
        // Si todo falla, log el error final
        error_log("All database connection attempts failed. Please check your MySQL server.");
        return null;
    }
    
    /**
     * Get connection info for debugging
     * @return array
     */
    public function getConnectionInfo() {
        foreach ($this->configurations as $config) {
            try {
                $dsn = "mysql:host={$config['host']};charset={$this->charset}";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3
                ]);
                
                return [
                    'status' => 'success',
                    'config' => $config,
                    'server_version' => $pdo->query('SELECT VERSION()')->fetchColumn()
                ];
                
            } catch(PDOException $e) {
                continue;
            }
        }
        
        return [
            'status' => 'failed',
            'message' => 'No se pudo conectar con ninguna configuración'
        ];
    }
}
?>