<?php
/**
 * Prueba de Apache y PHP
 * Car Wash Emanuel
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Apache - Car Wash Emanuel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 Car Wash Emanuel - Prueba del Sistema</h1>
        
        <h2>✅ Estado del Servidor</h2>
        <p class="success">Apache está funcionando correctamente</p>
        <p class="success">PHP está funcionando correctamente</p>
        
        <div class="info">
            <strong>📍 Información del Sistema:</strong><br>
            <strong>Fecha/Hora:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Versión PHP:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?><br>
            <strong>Ruta del documento:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible'; ?><br>
            <strong>URL actual:</strong> <?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
        </div>

        <h2>🔧 Extensiones PHP Requeridas</h2>
        <?php
        $required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl'];
        foreach ($required_extensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<p class='success'>✅ $ext: Instalado</p>";
            } else {
                echo "<p class='error'>❌ $ext: No instalado</p>";
            }
        }
        ?>

        <h2>🗂️ Estructura de Archivos</h2>
        <?php
        $required_files = [
            'index.php' => 'Página principal',
            'login.php' => 'Módulo de login',
            'dashboard.php' => 'Panel de control',
            'clients.php' => 'Gestión de clientes',
            'services.php' => 'Gestión de servicios',
            'reports.php' => 'Reportes',
            'config/database.php' => 'Configuración de base de datos',
            'includes/functions.php' => 'Funciones comunes',
            'database.sql' => 'Script de base de datos'
        ];
        
        foreach ($required_files as $file => $description) {
            if (file_exists($file)) {
                echo "<p class='success'>✅ $file - $description</p>";
            } else {
                echo "<p class='error'>❌ $file - $description (No encontrado)</p>";
            }
        }
        ?>

        <h2>🔗 Enlaces de Prueba</h2>
        <p><a href="index.php" target="_blank">🏠 Ir a la página principal</a></p>
        <p><a href="login.php" target="_blank">🔐 Ir al login</a></p>
        <p><a href="test_login.php" target="_blank">🔍 Ejecutar diagnóstico de login</a></p>
        
        <h2>📋 Información de Configuración</h2>
        <pre><?php
        echo "Configuración PHP relevante:\n";
        echo "display_errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
        echo "error_reporting: " . ini_get('error_reporting') . "\n";
        echo "max_execution_time: " . ini_get('max_execution_time') . "s\n";
        echo "memory_limit: " . ini_get('memory_limit') . "\n";
        echo "post_max_size: " . ini_get('post_max_size') . "\n";
        echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
        ?></pre>

        <div class="info">
            <strong>🎯 Próximos Pasos:</strong><br>
            1. Si ves esta página, Apache y PHP funcionan correctamente<br>
            2. Verifica que todos los archivos estén presentes (✅ arriba)<br>
            3. Ejecuta el diagnóstico de login si hay archivos faltantes<br>
            4. Si todo está bien, intenta acceder al sistema principal
        </div>
    </div>
</body>
</html>