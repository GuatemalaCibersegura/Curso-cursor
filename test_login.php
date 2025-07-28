<?php
/**
 * Script de Prueba - Verificar Login
 * Car Wash Emanuel
 */

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_emanuel');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP por defecto
define('DB_CHARSET', 'utf8mb4');

echo "<h2>🔍 Diagnóstico del Sistema Car Wash Emanuel</h2>";

// 1. Probar conexión a la base de datos
echo "<h3>1. Probando conexión a la base de datos...</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ <strong>Conexión exitosa</strong> a la base de datos.<br>";
} catch (PDOException $e) {
    echo "❌ <strong>Error de conexión:</strong> " . $e->getMessage() . "<br>";
    echo "<p><strong>Solución:</strong> Verificar que MySQL esté ejecutándose y que la base de datos 'carwash_emanuel' exista.</p>";
    exit();
}

// 2. Verificar si las tablas existen
echo "<h3>2. Verificando tablas...</h3>";
$tables = ['roles', 'usuarios', 'clientes', 'vehiculos', 'tipos_servicio', 'citas'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✅ Tabla <strong>$table</strong>: $count registros<br>";
    } catch (PDOException $e) {
        echo "❌ Tabla <strong>$table</strong>: No existe o error - " . $e->getMessage() . "<br>";
    }
}

// 3. Verificar usuarios
echo "<h3>3. Verificando usuarios...</h3>";
try {
    $stmt = $pdo->query("
        SELECT u.id, u.nombre_usuario, u.rol_id, r.nombre as rol_nombre 
        FROM usuarios u 
        LEFT JOIN roles r ON u.rol_id = r.id
    ");
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        echo "❌ <strong>No hay usuarios en la base de datos</strong><br>";
        echo "<p><strong>Solución:</strong> Ejecutar el script SQL para crear usuarios.</p>";
    } else {
        echo "✅ <strong>Usuarios encontrados:</strong><br>";
        foreach ($usuarios as $user) {
            echo "- ID: {$user['id']}, Usuario: <strong>{$user['nombre_usuario']}</strong>, Rol: {$user['rol_nombre']}<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ <strong>Error al consultar usuarios:</strong> " . $e->getMessage() . "<br>";
}

// 4. Probar verificación de contraseña
echo "<h3>4. Probando verificación de contraseña...</h3>";
$test_password = 'admin123';
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($test_password, $stored_hash)) {
    echo "✅ <strong>Verificación de contraseña funciona correctamente</strong><br>";
    echo "La contraseña 'admin123' coincide con el hash almacenado.<br>";
} else {
    echo "❌ <strong>Error en verificación de contraseña</strong><br>";
}

// 5. Intentar login completo
echo "<h3>5. Probando login completo...</h3>";
try {
    $username = 'admin';
    $password = 'admin123';
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre_usuario, u.contrasena, r.nombre as rol_nombre 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE u.nombre_usuario = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ <strong>Usuario encontrado:</strong> {$user['nombre_usuario']}<br>";
        
        if (password_verify($password, $user['contrasena'])) {
            echo "✅ <strong>¡Login exitoso!</strong> Contraseña verificada correctamente.<br>";
            echo "Rol: {$user['rol_nombre']}<br>";
        } else {
            echo "❌ <strong>Contraseña incorrecta</strong><br>";
            echo "Hash almacenado: " . substr($user['contrasena'], 0, 20) . "...<br>";
        }
    } else {
        echo "❌ <strong>Usuario 'admin' no encontrado</strong><br>";
    }
} catch (PDOException $e) {
    echo "❌ <strong>Error en login:</strong> " . $e->getMessage() . "<br>";
}

// 6. Script para recrear usuarios
echo "<h3>6. Script SQL para recrear usuarios</h3>";
echo "<p>Si hay problemas, ejecuta este SQL en phpMyAdmin:</p>";
echo "<textarea rows='10' cols='80' readonly>";
echo "USE carwash_emanuel;

-- Insertar roles
INSERT IGNORE INTO roles (id, nombre, descripcion) VALUES
(1, 'admin', 'Administrador del sistema con acceso completo'),
(2, 'personal', 'Personal del carwash con acceso limitado');

-- Eliminar usuarios existentes
DELETE FROM usuarios WHERE nombre_usuario IN ('admin', 'personal1');

-- Insertar usuarios nuevos
INSERT INTO usuarios (nombre_usuario, contrasena, rol_id) VALUES
('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('personal1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);";
echo "</textarea>";

echo "<h3>7. Información del sistema</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PDO MySQL disponible: " . (extension_loaded('pdo_mysql') ? 'Sí' : 'No') . "<br>";
echo "Fecha/Hora actual: " . date('Y-m-d H:i:s') . "<br>";

echo "<hr>";
echo "<p><strong>Instrucciones:</strong></p>";
echo "<ol>";
echo "<li>Si ves errores de conexión, verifica que MySQL esté ejecutándose en XAMPP</li>";
echo "<li>Si las tablas no existen, importa nuevamente el archivo database.sql</li>";
echo "<li>Si no hay usuarios, ejecuta el script SQL mostrado arriba</li>";
echo "<li>Si todo está correcto aquí pero el login falla, revisa el archivo login.php</li>";
echo "</ol>";
?>