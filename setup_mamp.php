<?php
/**
 * Setup Automático para MAMP - Sistema Car Wash
 * Este script configura automáticamente la base de datos y usuarios
 */

echo "<h2>🚀 Configuración Automática MAMP - Sistema Car Wash</h2>";

// Incluir la configuración de base de datos
require_once 'config/database.php';

$success = false;
$messages = [];

try {
    // Probar conexión
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $messages[] = "✅ Conexión a MySQL exitosa";
    
    // Verificar si las tablas existen
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    $table_names = array_column($tables, "Tables_in_carwash_system");
    
    $required_tables = ['users', 'clients', 'services', 'service_types'];
    $missing_tables = array_diff($required_tables, $table_names);
    
    if (!empty($missing_tables)) {
        $messages[] = "⚠️ Faltan tablas: " . implode(', ', $missing_tables);
        $messages[] = "📥 Importando estructura de base de datos...";
        
        // Leer y ejecutar el archivo SQL
        if (file_exists('database.sql')) {
            $sql_content = file_get_contents('database.sql');
            
            // Dividir en statements individuales
            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                    try {
                        $conn->exec($statement);
                    } catch (PDOException $e) {
                        // Ignorar errores de "tabla ya existe"
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            $messages[] = "✅ Base de datos importada correctamente";
        } else {
            throw new Exception("No se encontró el archivo database.sql");
        }
    } else {
        $messages[] = "✅ Todas las tablas requeridas existen";
    }
    
    // Verificar usuarios
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    
    if ($user_count == 0) {
        $messages[] = "👤 Creando usuarios por defecto...";
        
        // Crear usuarios por defecto
        $users = [
            [
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'email' => 'admin@carwash.com',
                'full_name' => 'Administrador',
                'role' => 'admin',
                'phone' => '1234567890'
            ],
            [
                'username' => 'staff',
                'password' => password_hash('staff123', PASSWORD_BCRYPT),
                'email' => 'staff@carwash.com',
                'full_name' => 'Personal',
                'role' => 'staff',
                'phone' => '0987654321'
            ]
        ];
        
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, full_name, role, phone, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['username'],
                $user['password'],
                $user['email'],
                $user['full_name'],
                $user['role'],
                $user['phone']
            ]);
        }
        
        $messages[] = "✅ Usuarios creados correctamente";
    } else {
        $messages[] = "✅ Ya existen usuarios en el sistema ($user_count usuarios)";
    }
    
    // Verificar datos de ejemplo
    $stmt = $conn->query("SELECT COUNT(*) as count FROM clients");
    $client_count = $stmt->fetch()['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM service_types");
    $service_types_count = $stmt->fetch()['count'];
    
    if ($client_count == 0 || $service_types_count == 0) {
        $messages[] = "📊 Los datos de ejemplo se importarán automáticamente";
    }
    
    $success = true;
    $messages[] = "🎉 ¡Configuración completada exitosamente!";
    
} catch (Exception $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}

// Mostrar resultados
foreach ($messages as $message) {
    echo "<p>$message</p>";
}

if ($success) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ ¡Sistema Configurado Correctamente!</h3>";
    echo "<p><strong>Credenciales de acceso:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Administrador:</strong> admin / admin123</li>";
    echo "<li><strong>Personal:</strong> staff / staff123</li>";
    echo "</ul>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Ve a la <a href='index.php' style='color: #007bff;'>página principal</a></li>";
    echo "<li>Inicia sesión con las credenciales de administrador</li>";
    echo "<li>Explora el sistema completo</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Enlaces Rápidos:</h3>";
    echo "<ul>";
    echo "<li><a href='index.php'>🏠 Página Principal</a></li>";
    echo "<li><a href='login.php'>🔐 Login</a></li>";
    echo "<li><a href='dashboard.php'>📊 Dashboard</a> (requiere login)</li>";
    echo "</ul>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error en la Configuración</h3>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>MAMP esté ejecutándose correctamente</li>";
    echo "<li>MySQL esté en el puerto 8889</li>";
    echo "<li>El archivo database.sql esté en la carpeta raíz</li>";
    echo "</ul>";
    echo "<p><a href='test_connection_final.php'>🧪 Ejecutar diagnóstico completo</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='color: #666; font-size: 0.9em;'>";
echo "💡 <strong>Tip:</strong> Una vez que el sistema funcione correctamente, ";
echo "puedes eliminar este archivo (setup_mamp.php) por seguridad.";
echo "</p>";
?>