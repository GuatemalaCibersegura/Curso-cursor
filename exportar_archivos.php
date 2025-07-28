<?php
/**
 * Exportador de Archivos - Car Wash Emanuel Guatemala
 */

$archivo = $_GET['file'] ?? 'menu';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Archivos - Car Wash Emanuel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 1200px; margin: 0 auto; }
        .menu { background: #e7f3ff; padding: 20px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .file-content { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; overflow-x: auto; margin: 10px 0; }
        .nav-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; font-size: 14px; }
        .nav-btn:hover { background: #0056b3; }
        .copy-btn { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        textarea { width: 100%; height: 400px; font-family: monospace; font-size: 12px; }
        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🇬🇹 Exportar Archivos - Car Wash Emanuel Guatemala</h1>
        
        <?php if ($archivo == 'menu'): ?>
        <div class="menu">
            <h2>📁 Selecciona el archivo a exportar:</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <a href="?file=database" class="nav-btn">🗄️ config/database.php</a>
                <a href="?file=functions" class="nav-btn">⚙️ includes/functions.php</a>
                <a href="?file=clients" class="nav-btn">👥 clients.php (cambios)</a>
                <a href="?file=footer" class="nav-btn">📄 includes/footer.php</a>
                <a href="?file=actualizar" class="nav-btn">🇬🇹 actualizar_guatemala.php</a>
                <a href="?file=htaccess" class="nav-btn">⚙️ .htaccess</a>
            </div>
            
            <div class="warning">
                <strong>📋 Instrucciones:</strong><br>
                1. Haz clic en cada archivo<br>
                2. Copia el contenido mostrado<br>
                3. Pégalo en tu servidor local<br>
                4. Ejecuta actualizar_guatemala.php
            </div>
        </div>
        
        <?php elseif ($archivo == 'database'): ?>
        <h2>📁 config/database.php</h2>
        <p>Copia este contenido y guárdalo como <strong>config/database.php</strong>:</p>
        <textarea readonly onclick="this.select();"><?php echo file_get_contents('config/database.php'); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php elseif ($archivo == 'functions'): ?>
        <h2>📁 includes/functions.php</h2>
        <div class="warning">
            <strong>⚠️ Archivo Grande:</strong> Este archivo tiene más de 400 líneas. 
            Copia el archivo completo o actualiza solo las funciones principales.
        </div>
        <p>Copia este contenido y guárdalo como <strong>includes/functions.php</strong>:</p>
        <textarea readonly onclick="this.select();"><?php echo file_get_contents('includes/functions.php'); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php elseif ($archivo == 'clients'): ?>
        <h2>📁 clients.php - Cambios para Guatemala</h2>
        <p>En tu archivo <strong>clients.php</strong>, busca el campo de teléfono y cámbialo por:</p>
        <textarea readonly onclick="this.select();"><label for="telefono" class="form-label">Teléfono *</label>
<input type="tel" class="form-control" id="telefono" name="telefono" 
       value="<?php echo htmlspecialchars($client['telefono'] ?? ''); ?>" 
       required maxlength="20" placeholder="+502 1234-5678"
       onblur="formatPhoneInput(this)">
<div class="form-text">Formato: +502 1234-5678 o 12345678</div></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <p>También busca donde se muestra el teléfono y cambia:</p>
        <textarea readonly onclick="this.select();"><?php echo htmlspecialchars(formatPhone($client['telefono'])); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php elseif ($archivo == 'footer'): ?>
        <h2>📁 includes/footer.php - JavaScript Guatemala</h2>
        <p>Agrega esta función JavaScript al final de <strong>includes/footer.php</strong>:</p>
        <textarea readonly onclick="this.select();">// Función para formatear número de teléfono guatemalteco
function formatPhoneInput(input) {
    let value = input.value.replace(/\D/g, ''); // Remover todo excepto números
    
    // Si empieza con 502, agregar el +
    if (value.startsWith('502') && value.length > 3) {
        value = '+502' + value.substring(3);
    }
    // Si no tiene código de país y tiene 8 dígitos, agregar +502
    else if (value.length === 8 && /^[2-7]/.test(value)) {
        value = '+502' + value;
    }
    
    // Formatear con guión
    if (value.startsWith('+502') && value.length >= 8) {
        const phone = value.substring(4); // Quitar +502
        if (phone.length >= 4) {
            value = '+502 ' + phone.substring(0, 4) + '-' + phone.substring(4, 8);
        }
    }
    
    input.value = value;
}</textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php elseif ($archivo == 'actualizar'): ?>
        <h2>📁 actualizar_guatemala.php</h2>
        <p>Copia este contenido y guárdalo como <strong>actualizar_guatemala.php</strong>:</p>
        <textarea readonly onclick="this.select();"><?php echo file_get_contents('actualizar_guatemala.php'); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php elseif ($archivo == 'htaccess'): ?>
        <h2>📁 .htaccess</h2>
        <p>Copia este contenido y guárdalo como <strong>.htaccess</strong>:</p>
        <textarea readonly onclick="this.select();"><?php echo file_get_contents('.htaccess'); ?></textarea>
        <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling)">📋 Copiar Contenido</button>
        
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="?file=menu" class="nav-btn">🏠 Volver al Menú</a>
            <a href="instalar_guatemala_directo.php" class="nav-btn">📋 Ver Instalador Paso a Paso</a>
        </div>
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