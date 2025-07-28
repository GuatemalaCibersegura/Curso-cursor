<?php
/**
 * Página de Descarga - Car Wash Emanuel
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Sistema Actualizado</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background: #f4f4f4; 
            text-align: center;
        }
        .container { 
            background: white; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .download-btn {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            display: inline-block;
            margin: 20px 0;
        }
        .download-btn:hover {
            background: #0056b3;
        }
        .info {
            background: #e7f3ff;
            padding: 20px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🇬🇹 Car Wash Emanuel - Versión Guatemala</h1>
        <h2>Sistema Actualizado - 17:00 hrs</h2>
        
        <div class="info">
            <strong>🎉 Actualizaciones Incluidas:</strong><br>
            ✅ Moneda en Quetzales (Q)<br>
            ✅ Teléfonos con código +502<br>
            ✅ Validación guatemalteca<br>
            ✅ Precios actualizados<br>
            ✅ Formateo automático
        </div>

        <?php
        $zipFile = 'Carwash Actualizado 17 horas.zip';
        if (file_exists($zipFile)) {
            $fileSize = round(filesize($zipFile) / 1024, 2);
            echo "<p><strong>Archivo:</strong> $zipFile</p>";
            echo "<p><strong>Tamaño:</strong> {$fileSize} KB</p>";
            echo "<a href='$zipFile' class='download-btn' download>📥 Descargar Sistema Completo</a>";
        } else {
            echo "<p style='color: red;'>❌ Archivo no encontrado</p>";
        }
        ?>

        <div class="info">
            <strong>📋 Instrucciones:</strong><br>
            1. Haz clic en "Descargar Sistema Completo"<br>
            2. Descomprime el archivo ZIP<br>
            3. Copia los archivos a tu servidor XAMPP<br>
            4. Ejecuta actualizar_guatemala.php<br>
            5. ¡Listo para usar!
        </div>

        <p><a href="dashboard.php">🏠 Volver al Dashboard</a></p>
    </div>
</body>
</html>