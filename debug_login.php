<?php
/**
 * Diagnóstico Específico del Login
 * Car Wash Emanuel
 */

// Configurar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Login - Car Wash Emanuel</h1>";

// 1. Verificar conexión a la base de datos
echo "<h2>1. Probando conexión a la base de datos</h2>";
try {
    $dsn = "mysql:host=localhost;dbname=carwash_emanuel;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ <strong>Conexión exitosa</strong><br>";
} catch (PDOException $e) {
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "<br>";
    echo "<p><strong>Solución:</strong> Verificar que MySQL esté ejecutándose y que la base de datos 'carwash_emanuel' exista.</p>";
    exit();
}

// 2. Verificar si existen las tablas necesarias
echo "<h2>2. Verificando tablas</h2>";
$tables = ['roles', 'usuarios'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Tabla <strong>$table</strong>: $count registros<br>";
    } catch (PDOException $e) {
        echo "❌ Tabla <strong>$table</strong>: Error - " . $e->getMessage() . "<br>";
    }
}

// 3. Verificar usuarios existentes
echo "<h2>3. Verificando usuarios existentes</h2>";
try {
    $stmt = $pdo->query("
        SELECT u.id, u.nombre_usuario, u.contrasena, u.rol_id, r.nombre as rol_nombre 
        FROM usuarios u 
        LEFT JOIN roles r ON u.rol_id = r.id
    ");
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        echo "❌ <strong>No hay usuarios en la base de datos</strong><br>";
        echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7;'>";
        echo "<strong>🔧 Solución:</strong> Necesitas ejecutar este SQL en phpMyAdmin:<br>";
        echo "<textarea rows='8' cols='80' readonly>";
        echo "USE carwash_emanuel;\n\n";
        echo "INSERT IGNORE INTO roles (id, nombre, descripcion) VALUES\n";
        echo "(1, 'admin', 'Administrador del sistema'),\n";
        echo "(2, 'personal', 'Personal del carwash');\n\n";
        echo "DELETE FROM usuarios WHERE nombre_usuario IN ('admin', 'personal1');\n\n";
        echo "INSERT INTO usuarios (nombre_usuario, contrasena, rol_id) VALUES\n";
        echo "('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),\n";
        echo "('personal1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);";
        echo "</textarea>";
        echo "</p>";
    } else {
        echo "✅ <strong>Usuarios encontrados:</strong><br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Hash Contraseña</th><th>Rol ID</th><th>Rol</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['nombre_usuario']}</strong></td>";
            echo "<td>" . substr($user['contrasena'], 0, 20) . "...</td>";
            echo "<td>{$user['rol_id']}</td>";
            echo "<td>{$user['rol_nombre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "❌ <strong>Error al consultar usuarios:</strong> " . $e->getMessage() . "<br>";
}

// 4. Probar verificación de contraseña
echo "<h2>4. Probando verificación de contraseña</h2>";
$test_password = 'admin123';
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Contraseña de prueba: <strong>$test_password</strong><br>";
echo "Hash almacenado: <code>$stored_hash</code><br>";

if (password_verify($test_password, $stored_hash)) {
    echo "✅ <strong>Verificación de contraseña funciona correctamente</strong><br>";
} else {
    echo "❌ <strong>Error en verificación de contraseña</strong><br>";
}

// 5. Simular proceso de login completo
echo "<h2>5. Simulando proceso de login completo</h2>";
$username = 'admin';
$password = 'admin123';

echo "Intentando login con:<br>";
echo "Usuario: <strong>$username</strong><br>";
echo "Contraseña: <strong>$password</strong><br><br>";

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre_usuario, u.contrasena, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE u.nombre_usuario = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ <strong>Usuario encontrado en la base de datos</strong><br>";
        echo "Usuario: {$user['nombre_usuario']}<br>";
        echo "Rol: {$user['rol_nombre']}<br>";
        echo "Hash en BD: " . substr($user['contrasena'], 0, 30) . "...<br><br>";
        
        if (password_verify($password, $user['contrasena'])) {
            echo "✅ <strong>¡LOGIN EXITOSO!</strong> La contraseña es correcta.<br>";
            echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb;'>";
            echo "🎉 <strong>El sistema de autenticación funciona correctamente.</strong><br>";
            echo "El problema puede estar en el archivo login.php o en las sesiones.";
            echo "</p>";
        } else {
            echo "❌ <strong>Contraseña incorrecta</strong><br>";
            echo "El hash almacenado no coincide con la contraseña proporcionada.<br>";
        }
    } else {
        echo "❌ <strong>Usuario '$username' no encontrado en la base de datos</strong><br>";
        echo "<p style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>";
        echo "Necesitas crear el usuario ejecutando el SQL mostrado arriba.";
        echo "</p>";
    }
} catch (PDOException $e) {
    echo "❌ <strong>Error en consulta de login:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>🎯 Resumen y Próximos Pasos</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3;'>";
echo "<strong>Si ves '¡LOGIN EXITOSO!' arriba:</strong><br>";
echo "1. El problema está en el archivo login.php<br>";
echo "2. Verifica que las sesiones funcionen<br>";
echo "3. Revisa que no haya errores en el código de login.php<br><br>";

echo "<strong>Si NO ves '¡LOGIN EXITOSO!':</strong><br>";
echo "1. Ejecuta el SQL mostrado arriba en phpMyAdmin<br>";
echo "2. Vuelve a ejecutar este diagnóstico<br>";
echo "3. Asegúrate de usar las credenciales: admin / admin123<br>";
echo "</div>";

echo "<p><strong>Enlaces de prueba:</strong></p>";
echo "<a href='login.php' target='_blank'>🔐 Ir al login real</a><br>";
echo "<a href='test_simple.php' target='_blank'>🔸 Prueba PHP simple</a><br>";
?>