<?php
/**
 * Script para encontrar MySQL en MAMP
 * Prueba diferentes configuraciones hasta encontrar la correcta
 */

echo "<h2>🔍 Buscando MySQL en MAMP</h2>";

// Configuraciones posibles para MAMP
$configurations = [
    'MAMP Estándar (Puerto 8889)' => [
        'host' => 'localhost:8889',
        'user' => 'root',
        'pass' => 'root'
    ],
    'MAMP Puerto 3306' => [
        'host' => 'localhost:3306',
        'user' => 'root',
        'pass' => 'root'
    ],
    'MAMP sin contraseña' => [
        'host' => 'localhost:8889',
        'user' => 'root',
        'pass' => ''
    ],
    'MAMP IP 127.0.0.1' => [
        'host' => '127.0.0.1:8889',
        'user' => 'root',
        'pass' => 'root'
    ],
    'MySQL estándar' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => ''
    ],
    'Socket MAMP' => [
        'host' => 'localhost:/Applications/MAMP/tmp/mysql/mysql.sock',
        'user' => 'root',
        'pass' => 'root'
    ]
];

echo "<h3>📋 Estado de MAMP:</h3>";
echo "<p><strong>Verificar que MAMP esté ejecutándose:</strong></p>";
echo "<ul>";
echo "<li>✅ Apache: Funcionando (puerto 8888)</li>";
echo "<li>❓ MySQL: <strong>NECESITA VERIFICACIÓN</strong></li>";
echo "</ul>";

echo "<h3>🔧 Probando configuraciones...</h3>";

$working_config = null;

foreach ($configurations as $config_name => $config) {
    echo "<hr>";
    echo "<h4>🧪 $config_name</h4>";
    echo "<p><strong>Host:</strong> {$config['host']}</p>";
    echo "<p><strong>Usuario:</strong> {$config['user']}</p>";
    echo "<p><strong>Contraseña:</strong> " . (empty($config['pass']) ? '(vacía)' : $config['pass']) . "</p>";
    
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "<p style='color: green; font-weight: bold;'>✅ ¡CONEXIÓN EXITOSA!</p>";
        
        // Obtener información de MySQL
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p><strong>Versión MySQL:</strong> $version</p>";
        
        // Listar bases de datos
        $databases = $pdo->query('SHOW DATABASES')->fetchAll();
        echo "<p><strong>Bases de datos disponibles:</strong></p>";
        echo "<ul>";
        foreach ($databases as $db) {
            echo "<li>" . $db['Database'] . "</li>";
        }
        echo "</ul>";
        
        $working_config = $config;
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h5>🎉 ¡Configuración encontrada!</h5>";
        echo "<p><strong>Actualiza config/database.php con:</strong></p>";
        echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>";
        echo "define('DB_HOST', '{$config['host']}');\n";
        echo "define('DB_USER', '{$config['user']}');\n";
        echo "define('DB_PASS', '{$config['pass']}');";
        echo "</pre>";
        echo "</div>";
        
        break; // Salir del loop cuando encontremos una configuración que funcione
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        
        // Analizar el tipo de error
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            echo "<p style='color: orange;'>🔍 MySQL no está ejecutándose en este puerto</p>";
        } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "<p style='color: orange;'>🔍 Credenciales incorrectas</p>";
        } elseif (strpos($e->getMessage(), 'No such file or directory') !== false) {
            echo "<p style='color: orange;'>🔍 Socket no encontrado</p>";
        }
    }
}

if (!$working_config) {
    echo "<hr>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ MySQL no encontrado</h4>";
    echo "<p><strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL no está instalado o ejecutándose en MAMP</li>";
    echo "<li>MAMP está configurado incorrectamente</li>";
    echo "<li>Necesitas MAMP PRO para MySQL</li>";
    echo "</ul>";
    
    echo "<h5>🛠️ Soluciones:</h5>";
    echo "<ol>";
    echo "<li><strong>Verificar MAMP:</strong> Asegúrate de que MySQL aparezca en el panel de MAMP</li>";
    echo "<li><strong>Reinstalar MAMP:</strong> Descarga la versión más reciente</li>";
    echo "<li><strong>Usar XAMPP:</strong> Como alternativa a MAMP</li>";
    echo "<li><strong>Instalar MySQL por separado:</strong> Instalar MySQL independientemente</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📱 Enlaces útiles:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8888/MAMP/' target='_blank'>Página de inicio MAMP</a></li>";
echo "<li><a href='http://localhost:8888/phpMyAdmin/' target='_blank'>phpMyAdmin (solo si MySQL funciona)</a></li>";
echo "<li><a href='https://www.mamp.info/en/downloads/' target='_blank'>Descargar MAMP</a></li>";
echo "</ul>";

echo "<h3>🔧 Verificación de MAMP:</h3>";
echo "<p>Para verificar que MySQL esté funcionando en MAMP:</p>";
echo "<ol>";
echo "<li>Abre MAMP</li>";
echo "<li>Deberías ver <strong>Apache</strong> y <strong>MySQL</strong> en la ventana principal</li>";
echo "<li>Ambos deben tener luces <strong>verdes</strong> cuando estén ejecutándose</li>";
echo "<li>Si solo aparece Apache, necesitas reinstalar MAMP o usar una alternativa</li>";
echo "</ol>";

echo "<p style='color: orange;'><em>⚠️ Elimina este archivo después de resolver el problema</em></p>";
?>