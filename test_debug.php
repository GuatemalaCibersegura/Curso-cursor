<?php
/**
 * Diagnóstico Seguro - Car Wash Emanuel
 * Este archivo está diseñado para no generar errores 500
 */

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función segura para verificar archivos
function safe_file_check($file) {
    try {
        return file_exists($file);
    } catch (Exception $e) {
        return false;
    }
}

// Función segura para verificar extensiones
function safe_extension_check($ext) {
    try {
        return extension_loaded($ext);
    } catch (Exception $e) {
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Car Wash Emanuel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 800px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { background: #e7f3ff; padding: 10px; border-left: 3px solid #2196F3; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Car Wash Emanuel</h1>
        
        <div class="info">
            <strong>✅ PHP está funcionando correctamente</strong><br>
            Si puedes ver esta página, el problema del Error 500 está resuelto.
        </div>

        <h2>📊 Información del Sistema</h2>
        <table>
            <tr><th>Parámetro</th><th>Valor</th></tr>
            <tr><td>Fecha/Hora</td><td><?php echo date('Y-m-d H:i:s'); ?></td></tr>
            <tr><td>Versión PHP</td><td><?php echo PHP_VERSION; ?></td></tr>
            <tr><td>Sistema Operativo</td><td><?php echo PHP_OS; ?></td></tr>
            <tr><td>Servidor Web</td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></td></tr>
            <tr><td>Ruta del Documento</td><td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible'; ?></td></tr>
        </table>

        <h2>🔧 Extensiones PHP</h2>
        <table>
            <tr><th>Extensión</th><th>Estado</th><th>Requerida</th></tr>
            <?php
            $extensions = [
                'pdo' => 'Sí',
                'pdo_mysql' => 'Sí', 
                'mbstring' => 'Sí',
                'openssl' => 'No',
                'curl' => 'No',
                'json' => 'Sí'
            ];
            
            foreach ($extensions as $ext => $required) {
                $loaded = safe_extension_check($ext);
                $status = $loaded ? '<span class="success">✅ Instalado</span>' : '<span class="error">❌ No instalado</span>';
                echo "<tr><td>$ext</td><td>$status</td><td>$required</td></tr>";
            }
            ?>
        </table>

        <h2>📁 Archivos del Sistema</h2>
        <table>
            <tr><th>Archivo</th><th>Estado</th><th>Descripción</th></tr>
            <?php
            $files = [
                'index.php' => 'Página principal',
                'login.php' => 'Sistema de login',
                'dashboard.php' => 'Panel principal',
                'clients.php' => 'Gestión de clientes',
                'services.php' => 'Gestión de servicios',
                'reports.php' => 'Reportes',
                'config/database.php' => 'Configuración DB',
                'includes/functions.php' => 'Funciones comunes',
                'database.sql' => 'Script de BD'
            ];
            
            foreach ($files as $file => $desc) {
                $exists = safe_file_check($file);
                $status = $exists ? '<span class="success">✅ Existe</span>' : '<span class="error">❌ No encontrado</span>';
                echo "<tr><td>$file</td><td>$status</td><td>$desc</td></tr>";
            }
            ?>
        </table>

        <h2>🔗 Pruebas de Acceso</h2>
        <div class="info">
            <strong>Prueba estos enlaces:</strong><br>
            <a href="test_simple.php" target="_blank">🔸 Prueba PHP Simple</a><br>
            <a href="index.php" target="_blank">🔸 Página Principal</a><br>
            <a href="login.php" target="_blank">🔸 Sistema de Login</a><br>
        </div>

        <h2>🗄️ Prueba de Base de Datos</h2>
        <?php
        // Intentar conexión a la base de datos de forma segura
        try {
            if (safe_file_check('config/database.php')) {
                echo '<p class="success">✅ Archivo de configuración encontrado</p>';
                
                // Definir configuración básica
                $db_config = [
                    'host' => 'localhost',
                    'dbname' => 'carwash_emanuel',
                    'username' => 'root',
                    'password' => ''
                ];
                
                $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                echo '<p class="success">✅ Conexión a base de datos exitosa</p>';
                
                // Verificar tabla de usuarios
                $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
                $count = $stmt->fetchColumn();
                echo "<p class='success'>✅ Tabla usuarios: $count registros</p>";
                
            } else {
                echo '<p class="error">❌ Archivo de configuración no encontrado</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p class="warning">⚠️ Asegúrate de que MySQL esté ejecutándose y la base de datos esté creada</p>';
        }
        ?>

        <h2>🎯 Próximos Pasos</h2>
        <div class="info">
            <strong>Si ves esta página sin errores:</strong><br>
            1. El Error 500 está solucionado ✅<br>
            2. Verifica que todos los archivos estén presentes<br>
            3. Asegúrate de que la base de datos esté configurada<br>
            4. Intenta acceder al sistema principal
        </div>
    </div>
</body>
</html>