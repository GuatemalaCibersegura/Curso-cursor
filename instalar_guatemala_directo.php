<?php
/**
 * Instalador Directo para Guatemala - Car Wash Emanuel
 * Este script te ayudará a instalar el sistema actualizado paso a paso
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$step = $_GET['step'] ?? '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Guatemala - Car Wash Emanuel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
        .step { background: #e7f3ff; padding: 20px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .nav-btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
        .nav-btn:hover { background: #0056b3; }
        textarea { width: 100%; height: 200px; font-family: monospace; }
        .copy-btn { background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🇬🇹 Instalador Guatemala - Car Wash Emanuel</h1>
        
        <?php if ($step == '1'): ?>
        <div class="step">
            <h2>Paso 1: Preparar el Servidor</h2>
            <p>Primero, asegúrate de tener XAMPP funcionando y ejecuta estos comandos en Terminal:</p>
            
            <div class="code-block">cd /Applications/XAMPP/xamppfiles/htdocs
sudo mkdir -p carwash-emanuel
cd carwash-emanuel
sudo mkdir -p config includes api assets/css assets/js logs</div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong> Ejecuta estos comandos en Terminal antes de continuar.
            </div>
            
            <a href="?step=2" class="nav-btn">Siguiente: Configurar Base de Datos →</a>
        </div>
        
        <?php elseif ($step == '2'): ?>
        <div class="step">
            <h2>Paso 2: Configurar Base de Datos</h2>
            <p>Crea el archivo <strong>config/database.php</strong> con este contenido:</p>
            
            <textarea readonly onclick="this.select();"><?php
/**
 * Configuración de Base de Datos
 * Car Wash Emanuel - Guatemala
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_emanuel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}
?></textarea>
            <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">Copiar</button>
            
            <a href="?step=1" class="nav-btn">← Anterior</a>
            <a href="?step=3" class="nav-btn">Siguiente: Funciones Guatemala →</a>
        </div>
        
        <?php elseif ($step == '3'): ?>
        <div class="step">
            <h2>Paso 3: Funciones para Guatemala</h2>
            <p>Crea el archivo <strong>includes/functions.php</strong> con las funciones actualizadas:</p>
            
            <div class="warning">
                <strong>📝 Archivo muy grande:</strong> Este archivo tiene más de 400 líneas. 
                <a href="?step=3a">Ver funciones principales</a> | 
                <a href="?step=3b">Ver funciones de formateo</a>
            </div>
            
            <a href="?step=2" class="nav-btn">← Anterior</a>
            <a href="?step=4" class="nav-btn">Siguiente: Base de Datos →</a>
        </div>
        
        <?php elseif ($step == '3a'): ?>
        <div class="step">
            <h2>Paso 3a: Funciones de Formateo Guatemala</h2>
            <p>Estas son las funciones principales actualizadas para Guatemala:</p>
            
            <textarea readonly onclick="this.select();">/**
 * Formatear moneda en Quetzales Guatemaltecos
 */
function formatCurrency($amount) {
    return 'Q' . number_format($amount, 2);
}

/**
 * Validar número de teléfono guatemalteco (+502)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^(\+502|502)?[2-7][0-9]{7}$/', $phone);
}

/**
 * Formatear número de teléfono guatemalteco
 */
function formatPhone($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    if (preg_match('/^(\+502|502)([2-7][0-9]{7})$/', $phone, $matches)) {
        $number = $matches[2];
        return '+502 ' . substr($number, 0, 4) . '-' . substr($number, 4);
    }
    
    if (preg_match('/^([2-7][0-9]{7})$/', $phone, $matches)) {
        $number = $matches[1];
        return '+502 ' . substr($number, 0, 4) . '-' . substr($number, 4);
    }
    
    return $phone;
}</textarea>
            <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">Copiar</button>
            
            <p><strong>📋 Instrucciones:</strong></p>
            <ol>
                <li>Copia el archivo functions.php original del sistema anterior</li>
                <li>Busca las funciones <code>formatCurrency</code>, <code>validatePhone</code></li>
                <li>Reemplázalas con las de arriba</li>
                <li>Agrega la función <code>formatPhone</code> al final</li>
            </ol>
            
            <a href="?step=3" class="nav-btn">← Volver</a>
        </div>
        
        <?php elseif ($step == '4'): ?>
        <div class="step">
            <h2>Paso 4: Script de Actualización Rápida</h2>
            <p>Crea el archivo <strong>actualizar_guatemala.php</strong>:</p>
            
            <textarea readonly onclick="this.select();"><?php
require_once 'config/database.php';

echo "<h1>🇬🇹 Actualización Guatemala</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Actualizar precios a Quetzales
    $servicios = [
        ['nombre' => 'Lavado Básico', 'precio' => 25.00],
        ['nombre' => 'Lavado Completo', 'precio' => 45.00],
        ['nombre' => 'Lavado Premium', 'precio' => 65.00],
        ['nombre' => 'Encerado', 'precio' => 85.00],
        ['nombre' => 'Lavado y Encerado', 'precio' => 120.00],
        ['nombre' => 'Detallado Completo', 'precio' => 200.00]
    ];
    
    foreach ($servicios as $servicio) {
        $stmt = $conn->prepare("UPDATE tipos_servicio SET precio = ? WHERE nombre = ?");
        $stmt->execute([$servicio['precio'], $servicio['nombre']]);
        echo "✅ {$servicio['nombre']}: Q{$servicio['precio']}<br>";
    }
    
    echo "<h2>✅ Sistema actualizado para Guatemala</h2>";
    echo "<p>Moneda: Quetzales (Q)</p>";
    echo "<p>Teléfonos: +502</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?></textarea>
            <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">Copiar</button>
            
            <a href="?step=3" class="nav-btn">← Anterior</a>
            <a href="?step=5" class="nav-btn">Siguiente: Finalizar →</a>
        </div>
        
        <?php elseif ($step == '5'): ?>
        <div class="step">
            <h2>Paso 5: Finalizar Instalación</h2>
            
            <div class="success">
                <h3>🎉 ¡Instalación Completa!</h3>
                <p>Ahora ejecuta estos pasos finales:</p>
            </div>
            
            <ol>
                <li><strong>Copiar archivos restantes:</strong> Copia login.php, dashboard.php, clients.php, etc. del sistema original</li>
                <li><strong>Actualizar clients.php:</strong> Cambia el placeholder de teléfono a <code>+502 1234-5678</code></li>
                <li><strong>Ejecutar actualización:</strong> Ve a <code>http://localhost/carwash-emanuel/actualizar_guatemala.php</code></li>
                <li><strong>Probar el sistema:</strong> Ve a <code>http://localhost/carwash-emanuel/login.php</code></li>
            </ol>
            
            <div class="warning">
                <strong>📱 Credenciales:</strong><br>
                Usuario: <code>admin</code><br>
                Contraseña: <code>admin123</code>
            </div>
            
            <a href="?step=4" class="nav-btn">← Anterior</a>
            <a href="?step=1" class="nav-btn">🔄 Reiniciar</a>
        </div>
        
        <?php endif; ?>
    </div>

    <script>
    function copyToClipboard(element) {
        element.select();
        element.setSelectionRange(0, 99999);
        document.execCommand('copy');
        
        const btn = element.nextElementSibling;
        const originalText = btn.textContent;
        btn.textContent = '✅ Copiado';
        btn.style.background = '#28a745';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#28a745';
        }, 2000);
    }
    </script>
</body>
</html>