<?php
/**
 * Script de Debug para Login
 * Diagnosticar y solucionar problemas de autenticación
 */

require_once 'config/database.php';

echo "<h2>🔍 Debug del Sistema de Login</h2>";

// Función para mostrar información de debug
function debugInfo($label, $value, $type = 'info') {
    $colors = [
        'success' => 'green',
        'error' => 'red', 
        'warning' => 'orange',
        'info' => 'blue'
    ];
    $color = $colors[$type] ?? 'black';
    echo "<p style='color: $color;'><strong>$label:</strong> $value</p>";
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        debugInfo("Conexión BD", "❌ FALLO", 'error');
        exit;
    }
    
    debugInfo("Conexión BD", "✅ EXITOSA", 'success');
    
    // Obtener todos los usuarios
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    debugInfo("Usuarios encontrados", count($users), 'info');
    
    if (empty($users)) {
        echo "<p style='color: red;'>❌ No hay usuarios en la base de datos</p>";
        echo "<h3>🔧 Creando usuarios de prueba...</h3>";
        
        // Crear usuarios con contraseñas conocidas
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $staff_pass = password_hash('staff123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute(['admin', $admin_pass, 'admin', 'Administrador', 'admin@test.com']);
        $stmt->execute(['staff1', $staff_pass, 'staff', 'Personal 1', 'staff1@test.com']);
        
        debugInfo("Usuarios creados", "✅ Admin y Staff1", 'success');
        
        // Recargar usuarios
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY id");
        $stmt->execute();
        $users = $stmt->fetchAll();
    }
    
    echo "<h3>👥 Usuarios en la base de datos:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Usuario</th><th>Rol</th><th>Nombre</th><th>Hash Password</th><th>Test Login</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td style='font-size: 10px; max-width: 200px; word-break: break-all;'>" . substr($user['password'], 0, 30) . "...</td>";
        
        // Probar contraseñas comunes
        $test_passwords = ['admin123', 'staff123', 'password', 'admin', 'staff'];
        $working_password = null;
        
        foreach ($test_passwords as $test_pass) {
            if (password_verify($test_pass, $user['password'])) {
                $working_password = $test_pass;
                break;
            }
        }
        
        if ($working_password) {
            echo "<td style='color: green;'>✅ Funciona con: <strong>$working_password</strong></td>";
        } else {
            echo "<td style='color: red;'>❌ No funciona</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    // Probar el proceso completo de login
    echo "<h3>🧪 Prueba de Login Completa:</h3>";
    
    $test_user = 'admin';
    $test_pass = 'admin123';
    
    echo "<p>Probando login con: <strong>$test_user</strong> / <strong>$test_pass</strong></p>";
    
    // Simular el proceso de login
    $stmt = $conn->prepare("SELECT id, username, password, role, full_name, email FROM users WHERE username = ? AND password IS NOT NULL");
    $stmt->execute([$test_user]);
    $user = $stmt->fetch();
    
    if ($user) {
        debugInfo("Usuario encontrado", "✅ {$user['username']}", 'success');
        debugInfo("Hash en BD", substr($user['password'], 0, 50) . "...", 'info');
        
        // Verificar contraseña
        if (password_verify($test_pass, $user['password'])) {
            debugInfo("Verificación password", "✅ EXITOSA", 'success');
            echo "<p style='color: green; font-weight: bold;'>🎉 El login debería funcionar correctamente</p>";
        } else {
            debugInfo("Verificación password", "❌ FALLÓ", 'error');
            echo "<p style='color: red;'>🔧 Reparando contraseña...</p>";
            
            // Reparar contraseña
            $new_hash = password_hash($test_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->execute([$new_hash, $test_user]);
            
            debugInfo("Reparación", "✅ Contraseña actualizada", 'success');
        }
    } else {
        debugInfo("Usuario encontrado", "❌ NO EXISTE", 'error');
    }
    
    // Verificar configuración PHP
    echo "<h3>⚙️ Configuración PHP:</h3>";
    debugInfo("Versión PHP", PHP_VERSION, 'info');
    debugInfo("password_hash disponible", function_exists('password_hash') ? '✅ Sí' : '❌ No', function_exists('password_hash') ? 'success' : 'error');
    debugInfo("password_verify disponible", function_exists('password_verify') ? '✅ Sí' : '❌ No', function_exists('password_verify') ? 'success' : 'error');
    
    // Probar hash y verify
    $test_hash = password_hash('test123', PASSWORD_DEFAULT);
    $test_verify = password_verify('test123', $test_hash);
    debugInfo("Test hash/verify", $test_verify ? '✅ Funciona' : '❌ No funciona', $test_verify ? 'success' : 'error');
    
} catch (Exception $e) {
    debugInfo("Error", $e->getMessage(), 'error');
}

echo "<hr>";
echo "<h3>🔧 Acciones de Reparación:</h3>";
echo "<p><a href='#' onclick='repairPasswords()' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔧 Reparar Todas las Contraseñas</a></p>";
echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🔐 Probar Login</a></p>";

echo "<script>
function repairPasswords() {
    if (confirm('¿Reparar todas las contraseñas? Esto establecerá admin123 para admin y staff123 para staff1')) {
        window.location.href = 'debug_login.php?repair=1';
    }
}
</script>";

// Manejar reparación de contraseñas
if (isset($_GET['repair']) && $_GET['repair'] == '1') {
    echo "<h3>🔧 Reparando contraseñas...</h3>";
    
    try {
        // Reparar admin
        $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$admin_hash]);
        
        // Reparar staff1
        $staff_hash = password_hash('staff123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'staff1'");
        $stmt->execute([$staff_hash]);
        
        // Verificar si no existe staff1, crearlo
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'staff1'");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['staff1', $staff_hash, 'staff', 'Personal 1', 'staff1@test.com']);
            echo "<p style='color: green;'>✅ Usuario staff1 creado</p>";
        }
        
        echo "<p style='color: green;'>✅ Contraseñas reparadas exitosamente</p>";
        echo "<p><strong>Credenciales actualizadas:</strong></p>";
        echo "<p>Admin: admin / admin123</p>";
        echo "<p>Staff: staff1 / staff123</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error reparando: " . $e->getMessage() . "</p>";
    }
}

echo "<br><p style='color: orange;'><em>⚠️ Elimina este archivo después de resolver el problema</em></p>";
?>