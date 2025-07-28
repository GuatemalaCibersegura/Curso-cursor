<?php
/**
 * API Endpoint - Ping para mantener sesión activa
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

header('Content-Type: application/json');

require_once '../includes/functions.php';

// Verificar si hay una sesión activa
if (isLoggedIn()) {
    echo json_encode(['status' => 'success', 'message' => 'Sesión activa']);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
}
?>