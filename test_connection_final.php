<?php
/**
 * Test de Conexión Final - Sistema Car Wash
 * Diagnostica y encuentra la configuración correcta para tu MAMP
 */

echo "<h2>🔍 Diagnóstico Final de Conexión</h2>";

// Incluir la nueva clase Database
require_once 'config/database.php';

echo "<h3>📋 Información del Sistema:</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>PDO MySQL:</strong> " . (extension_loaded('pdo_mysql') ? '✅ Disponible' : '❌ No disponible') . "</p>";

echo "<h3>🧪 Probando Nueva Configuración Auto-Detect:</h3>";

try {
    $database = new Database();
    
    // Obtener información de conexión
    $info = $database->getConnectionInfo();
    
    if ($info['status'] === 'success') {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ ¡Conexión Encontrada!</h4>";
        echo "<p><strong>Configuración exitosa:</strong> {$info['config']['name']}</p>";
        echo "<p><strong>Host:</strong> {$info['config']['host']}</p>";
        echo "<p><strong>Usuario:</strong> {$info['config']['user']}</p>";
        echo "<p><strong>Contraseña:</strong> " . (empty($info['config']['pass']) ? '(vacía)' : $info['config']['pass']) . "</p>";
        echo "<p><strong>Versión MySQL:</strong> {$info['server_version']}</p>";
        echo "</div>";
        
        // Probar conexión completa
        $conn = $database->getConnection();
        if ($conn) {
            echo "<p style='color: green; font-weight: bold;'>✅ Conexión completa exitosa</p>";
            
            // Verificar si existe la base de datos carwash_system
            try {
                $stmt = $conn->query("SELECT DATABASE() as current_db");
                $result = $stmt->fetch();
                echo "<p><strong>Base de datos actual:</strong> {$result['current_db']}</p>";
                
                // Verificar tablas
                $stmt = $conn->query("SHOW TABLES");
                $tables = $stmt->fetchAll();
                
                if (count($tables) > 0) {
                    echo "<p><strong>Tablas encontradas:</strong></p>";
                    echo "<ul>";
                    foreach ($tables as $table) {
                        $table_name = $table["Tables_in_carwash_system"];
                        echo "<li>$table_name</li>";
                    }
                    echo "</ul>";
                    
                    // Verificar usuarios si existe la tabla
                    $table_exists = false;
                    foreach ($tables as $table) {
                        if ($table["Tables_in_carwash_system"] === 'users') {
                            $table_exists = true;
                            break;
                        }
                    }
                    
                    if ($table_exists) {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                        $user_count = $stmt->fetch()['count'];
                        echo "<p><strong>Usuarios en el sistema:</strong> $user_count</p>";
                        
                        if ($user_count > 0) {
                            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                            echo "<h5>🎉 ¡Sistema Completamente Funcional!</h5>";
                            echo "<p>La base de datos está configurada y tiene usuarios.</p>";
                            echo "<p><strong>Puedes restaurar el login normal ahora:</strong></p>";
                            echo "<ol>";
                            echo "<li>Revierte los cambios en includes/functions.php</li>";
                            echo "<li>Usa las credenciales: admin / admin123</li>";
                            echo "<li>El sistema debería funcionar perfectamente</li>";
                            echo "</ol>";
                            echo "</div>";
                        } else {
                            echo "<p style='color: orange;'>⚠️ La tabla users existe pero está vacía</p>";
                            echo "<p><a href='debug_login.php?repair=1' style='background: #ffc107; color: black; padding: 10px; text-decoration: none; border-radius: 5px;'>🔧 Crear Usuarios</a></p>";
                        }
                    } else {
                        echo "<p style='color: orange;'>⚠️ La tabla 'users' no existe</p>";
                        echo "<p>Necesitas importar el archivo database.sql</p>";
                    }
                    
                } else {
                    echo "<p style='color: orange;'>⚠️ La base de datos está vacía</p>";
                    echo "<p>Necesitas importar el archivo database.sql</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error verificando base de datos: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Error en conexión completa</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ No se encontró conexión MySQL</h4>";
        echo "<p>{$info['message']}</p>";
        echo "</div>";
        
        echo "<h4>🛠️ Soluciones Recomendadas:</h4>";
        echo "<ol>";
        echo "<li><strong>Verificar MAMP:</strong> Asegúrate de que MySQL esté ejecutándose</li>";
        echo "<li><strong>Reinstalar MAMP:</strong> Descarga la versión más reciente</li>";
        echo "<li><strong>Usar XAMPP:</strong> Como alternativa más estable</li>";
        echo "<li><strong>Instalar MySQL por separado</strong></li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error general: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔗 Enlaces Útiles:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8888/MAMP/' target='_blank'>Página inicio MAMP</a></li>";
echo "<li><a href='http://localhost:8888/phpMyAdmin/' target='_blank'>phpMyAdmin</a></li>";
echo "<li><a href='acceso_directo.php'>🔓 Acceso directo al sistema (sin BD)</a></li>";
echo "<li><a href='dashboard.php'>🏠 Dashboard</a></li>";
echo "</ul>";

echo "<h3>📝 Próximos Pasos:</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Si la conexión fue exitosa:</strong></p>";
echo "<ol>";
echo "<li>Importa database.sql en phpMyAdmin si no hay tablas</li>";
echo "<li>Crea usuarios con debug_login.php si la tabla users está vacía</li>";
echo "<li>Restaura el login normal en includes/functions.php</li>";
echo "<li>Prueba el login con admin / admin123</li>";
echo "</ol>";

echo "<p><strong>Si no hay conexión:</strong></p>";
echo "<ol>";
echo "<li>Verifica que MAMP tenga MySQL ejecutándose</li>";
echo "<li>Considera cambiar a XAMPP</li>";
echo "<li>Usa el acceso directo para ver el sistema sin BD</li>";
echo "</ol>";
echo "</div>";

echo "<br><p style='color: orange;'><em>⚠️ Elimina este archivo después de resolver el problema</em></p>";
?>