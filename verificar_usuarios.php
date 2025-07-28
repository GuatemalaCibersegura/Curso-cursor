<?php
/**
 * Verificar Usuarios - Car Wash Emanuel
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Verificación de Usuarios - Car Wash Emanuel</h1>";

try {
    $dsn = "mysql:host=localhost;dbname=carwash_emanuel;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>1. Verificando Roles</h2>";
    $stmt = $pdo->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll();
    
    if (empty($roles)) {
        echo "❌ No hay roles. Creando roles...<br>";
        $pdo->exec("
            INSERT INTO roles (id, nombre, descripcion) VALUES
            (1, 'admin', 'Administrador del sistema'),
            (2, 'personal', 'Personal del carwash')
        ");
        echo "✅ Roles creados<br>";
    } else {
        echo "✅ Roles existentes:<br>";
        foreach ($roles as $rol) {
            echo "- ID: {$rol['id']}, Nombre: {$rol['nombre']}<br>";
        }
    }
    
    echo "<h2>2. Verificando Usuarios</h2>";
    $stmt = $pdo->query("
        SELECT u.id, u.nombre_usuario, u.contrasena, u.rol_id, r.nombre as rol_nombre 
        FROM usuarios u 
        LEFT JOIN roles r ON u.rol_id = r.id
    ");
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        echo "❌ No hay usuarios. Creando usuario admin...<br>";
        
        // Crear hash de contraseña
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre_usuario, contrasena, rol_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute(['admin', $password_hash, 1]);
        
        echo "✅ Usuario admin creado con contraseña: admin123<br>";
        
        // Verificar que se creó
        $stmt = $pdo->query("
            SELECT u.id, u.nombre_usuario, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.nombre_usuario = 'admin'
        ");
        $admin = $stmt->fetch();
        echo "✅ Usuario verificado: {$admin['nombre_usuario']} - {$admin['rol_nombre']}<br>";
        
    } else {
        echo "✅ Usuarios existentes:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Rol</th><th>Hash (primeros 20 chars)</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['nombre_usuario']}</strong></td>";
            echo "<td>{$user['rol_nombre']}</td>";
            echo "<td>" . substr($user['contrasena'], 0, 20) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>3. Probando Login</h2>";
    $username = 'admin';
    $password = 'admin123';
    
    echo "Probando login con usuario: <strong>$username</strong> y contraseña: <strong>$password</strong><br><br>";
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre_usuario, u.contrasena, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE u.nombre_usuario = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Usuario encontrado: {$user['nombre_usuario']}<br>";
        echo "Rol: {$user['rol_nombre']}<br>";
        
        if (password_verify($password, $user['contrasena'])) {
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "🎉 <strong>¡LOGIN EXITOSO!</strong><br>";
            echo "El usuario y contraseña son correctos.<br>";
            echo "Usuario: {$user['nombre_usuario']}<br>";
            echo "Rol: {$user['rol_nombre']}";
            echo "</div>";
            
            // Simular inicio de sesión
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['nombre_usuario'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            
            echo "<p>✅ Sesión iniciada correctamente</p>";
            
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "❌ <strong>Contraseña incorrecta</strong><br>";
            echo "El hash almacenado no coincide con la contraseña.";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "❌ <strong>Usuario no encontrado</strong>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<h2>4. Enlaces de Prueba</h2>";
echo "<a href='login.php' target='_blank'>🔐 Ir al Login Real</a><br>";
echo "<a href='index.php' target='_blank'>🏠 Ir a la Página Principal</a><br>";
echo "<a href='dashboard.php' target='_blank'>📊 Ir al Dashboard</a><br>";

echo "<h2>5. Credenciales para el Login</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0;'>";
echo "<strong>Usa estas credenciales:</strong><br>";
echo "👤 Usuario: <code>admin</code><br>";
echo "🔑 Contraseña: <code>admin123</code>";
echo "</div>";
?>