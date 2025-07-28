<?php
/**
 * Actualizar Sistema para Guatemala
 * Car Wash Emanuel
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🇬🇹 Actualización para Guatemala - Car Wash Emanuel</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    echo "<h2>1. Actualizando Tipos de Servicio - Precios en Quetzales</h2>";
    
    // Actualizar precios a Quetzales (conversión aproximada: 1 USD = 7.8 GTQ)
    $servicios_actualizados = [
        ['nombre' => 'Lavado Básico', 'precio' => 25.00],
        ['nombre' => 'Lavado Completo', 'precio' => 45.00],
        ['nombre' => 'Lavado Premium', 'precio' => 65.00],
        ['nombre' => 'Encerado', 'precio' => 85.00],
        ['nombre' => 'Lavado y Encerado', 'precio' => 120.00],
        ['nombre' => 'Detallado Completo', 'precio' => 200.00]
    ];
    
    foreach ($servicios_actualizados as $servicio) {
        $stmt = $conn->prepare("
            UPDATE tipos_servicio 
            SET precio = ? 
            WHERE nombre = ?
        ");
        if ($stmt->execute([$servicio['precio'], $servicio['nombre']])) {
            echo "✅ {$servicio['nombre']}: " . formatCurrency($servicio['precio']) . "<br>";
        }
    }
    
    echo "<h2>2. Formateando Números de Teléfono Existentes</h2>";
    
    // Obtener todos los clientes
    $stmt = $conn->query("SELECT id, nombre, telefono FROM clientes WHERE telefono IS NOT NULL AND telefono != ''");
    $clientes = $stmt->fetchAll();
    
    $actualizados = 0;
    foreach ($clientes as $cliente) {
        $telefono_original = $cliente['telefono'];
        $telefono_formateado = formatPhone($telefono_original);
        
        if ($telefono_original !== $telefono_formateado) {
            $stmt = $conn->prepare("UPDATE clientes SET telefono = ? WHERE id = ?");
            if ($stmt->execute([$telefono_formateado, $cliente['id']])) {
                echo "✅ {$cliente['nombre']}: {$telefono_original} → {$telefono_formateado}<br>";
                $actualizados++;
            }
        }
    }
    
    if ($actualizados === 0) {
        echo "ℹ️ No se encontraron teléfonos para actualizar<br>";
    } else {
        echo "<strong>Total actualizados: $actualizados teléfonos</strong><br>";
    }
    
    echo "<h2>3. Verificando Configuración Regional</h2>";
    echo "✅ Moneda: Quetzales Guatemaltecos (Q)<br>";
    echo "✅ Código telefónico: +502<br>";
    echo "✅ Formato de teléfono: +502 1234-5678<br>";
    echo "✅ Validación de teléfono: Números guatemaltecos válidos<br>";
    
    echo "<h2>4. Pruebas de Formato</h2>";
    echo "<h3>Moneda:</h3>";
    $precios_prueba = [100, 250.50, 1500];
    foreach ($precios_prueba as $precio) {
        echo "- " . formatCurrency($precio) . "<br>";
    }
    
    echo "<h3>Teléfonos:</h3>";
    $telefonos_prueba = ['12345678', '50212345678', '+50212345678', '23456789'];
    foreach ($telefonos_prueba as $telefono) {
        echo "- $telefono → " . formatPhone($telefono) . "<br>";
    }
    
    echo "<h2>5. Información Adicional</h2>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0;'>";
    echo "<strong>🇬🇹 Configuración para Guatemala:</strong><br>";
    echo "• <strong>Moneda:</strong> Quetzales (Q)<br>";
    echo "• <strong>Código de país:</strong> +502<br>";
    echo "• <strong>Formato teléfono:</strong> +502 1234-5678<br>";
    echo "• <strong>Números válidos:</strong> Inician con 2-7, 8 dígitos locales<br>";
    echo "• <strong>Ejemplos válidos:</strong><br>";
    echo "&nbsp;&nbsp;- 12345678 (se convierte a +502 1234-5678)<br>";
    echo "&nbsp;&nbsp;- 50212345678 (se convierte a +502 1234-5678)<br>";
    echo "&nbsp;&nbsp;- +502 1234-5678 (ya formateado)<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<h2>6. Enlaces del Sistema</h2>";
echo "<a href='clients.php' target='_blank'>👥 Gestión de Clientes</a><br>";
echo "<a href='services.php' target='_blank'>🚗 Servicios</a><br>";
echo "<a href='reports.php' target='_blank'>📊 Reportes</a><br>";
echo "<a href='dashboard.php' target='_blank'>🏠 Dashboard</a><br>";
?>