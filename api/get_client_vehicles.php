<?php
/**
 * API Endpoint - Obtener Vehículos de Cliente
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/functions.php';
require_once '../config/database.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Verificar que se proporcione el ID del cliente
$client_id = $_GET['client_id'] ?? null;

if (!$client_id || !is_numeric($client_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de cliente requerido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener vehículos del cliente
    $vehicles = getClientVehicles($conn, $client_id);
    
    // Devolver respuesta JSON
    echo json_encode($vehicles);
    
} catch (Exception $e) {
    error_log('Error en get_client_vehicles.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>