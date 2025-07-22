<?php
/**
 * Database Configuration File
 * Car Wash Client Platform Control System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_system');
define('DB_USER', 'root');
define('DB_PASS', '');
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
            // Try MySQL first
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("MySQL Connection error: " . $exception->getMessage());
            
            // Fallback to SQLite
            try {
                $sqlite_path = __DIR__ . '/../data/carwash_system.sqlite';
                $data_dir = dirname($sqlite_path);
                
                if (!is_dir($data_dir)) {
                    mkdir($data_dir, 0755, true);
                }
                
                $dsn = "sqlite:" . $sqlite_path;
                $this->conn = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                
                // Create tables for SQLite
                $this->createSQLiteTables();
                
            } catch(PDOException $sqlite_exception) {
                error_log("SQLite Connection error: " . $sqlite_exception->getMessage());
                return null;
            }
        }

        return $this->conn;
    }
    
    /**
     * Create SQLite tables if they don't exist
     */
    private function createSQLiteTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(10) NOT NULL DEFAULT 'staff',
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            contact_number VARCHAR(20) NOT NULL,
            email VARCHAR(100),
            vehicle_type VARCHAR(50) NOT NULL,
            license_plate VARCHAR(20) UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            service_type VARCHAR(20) NOT NULL,
            cost DECIMAL(10, 2) NOT NULL,
            service_date DATE NOT NULL,
            service_time TIME NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE INDEX IF NOT EXISTS idx_clients_license_plate ON clients(license_plate);
        CREATE INDEX IF NOT EXISTS idx_clients_name ON clients(name);
        CREATE INDEX IF NOT EXISTS idx_services_client_id ON services(client_id);
        CREATE INDEX IF NOT EXISTS idx_services_date ON services(service_date);
        ";

        $this->conn->exec($sql);

        // Create default admin user if not exists
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                'admin',
                password_hash('admin123', PASSWORD_DEFAULT),
                'admin',
                'System Administrator',
                'admin@carwash.local'
            ]);
        }
    }
}
?>