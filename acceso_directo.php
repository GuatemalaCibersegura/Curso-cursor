<?php
/**
 * Acceso Directo al Sistema (Sin Login)
 * Usar solo para pruebas y desarrollo
 */

session_start();

// Crear sesión temporal de administrador
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_temp';
$_SESSION['user_role'] = 'admin';
$_SESSION['full_name'] = 'Administrador Temporal';
$_SESSION['email'] = 'admin@temp.com';

echo "<h2>🔓 Acceso Directo Activado</h2>";
echo "<p>Se ha creado una sesión temporal de administrador.</p>";
echo "<p><strong>Ahora puedes acceder a cualquier página del sistema:</strong></p>";

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>📋 Enlaces Directos:</h3>";
echo "<ul>";
echo "<li><a href='dashboard.php'>🏠 Dashboard</a></li>";
echo "<li><a href='clients.php'>👥 Gestión de Clientes</a></li>";
echo "<li><a href='services.php'>🔧 Gestión de Servicios</a></li>";
echo "<li><a href='reports.php'>📊 Reportes</a></li>";
echo "<li><a href='users.php'>👨‍💼 Gestión de Usuarios</a></li>";
echo "<li><a href='profile.php'>👤 Perfil</a></li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>⚠️ Importante:</h4>";
echo "<ul>";
echo "<li>Este acceso es solo temporal para pruebas</li>";
echo "<li>Las funciones que requieren base de datos pueden fallar</li>";
echo "<li>Elimina este archivo en producción</li>";
echo "<li>Para restaurar el login normal, revierte los cambios en includes/functions.php</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Dashboard</a></p>";
?>