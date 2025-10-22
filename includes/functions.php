<?php
/**
 * Archivo de Funciones Comunes
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está logueado
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verificar si el usuario es administrador
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirigir al login si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Redirigir al login si no es administrador
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

/**
 * Sanitizar datos de entrada
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validar formato de email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar número de teléfono (validación básica)
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    return preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $phone);
}

/**
 * Validar placa de vehículo
 * @param string $plate
 * @return bool
 */
function validatePlate($plate) {
    // Formato básico para placas (letras y números)
    return preg_match('/^[A-Z0-9\-]+$/i', $plate);
}

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatear moneda
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₡' . number_format($amount, 2);
}

/**
 * Formatear fecha
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatear fecha y hora
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    return date('d/m/Y g:i A', strtotime($datetime));
}

/**
 * Mostrar mensaje de éxito
 * @param string $message
 */
function showSuccess($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-check-circle-fill me-2"></i>';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

/**
 * Mostrar mensaje de error
 * @param string $message
 */
function showError($message) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

/**
 * Mostrar mensaje de información
 * @param string $message
 */
function showInfo($message) {
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
    echo '<i class="bi bi-info-circle-fill me-2"></i>';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

/**
 * Registrar actividad (logging simple en archivo)
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    $log_file = __DIR__ . '/../logs/activity.log';
    $log_dir = dirname($log_file);
    
    // Crear directorio de logs si no existe
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'Desconocido';
    $username = $_SESSION['username'] ?? 'Desconocido';
    
    $log_entry = "[{$timestamp}] Usuario ID: {$user_id} ({$username}) - Acción: {$action}";
    if (!empty($details)) {
        $log_entry .= " - Detalles: {$details}";
    }
    $log_entry .= PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Obtener lista de clientes
 * @param PDO $conn
 * @return array
 */
function getClients($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM clientes ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error obteniendo clientes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtener cliente por ID
 * @param PDO $conn
 * @param int $id
 * @return array|null
 */
function getClientById($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log('Error obteniendo cliente: ' . $e->getMessage());
        return null;
    }
}

/**
 * Obtener vehículos de un cliente
 * @param PDO $conn
 * @param int $cliente_id
 * @return array
 */
function getClientVehicles($conn, $cliente_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE cliente_id = ? ORDER BY placa ASC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error obteniendo vehículos: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtener tipos de servicio
 * @param PDO $conn
 * @return array
 */
function getServiceTypes($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM tipos_servicio ORDER BY precio ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error obteniendo tipos de servicio: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtener servicios/citas con información completa
 * @param PDO $conn
 * @param string $fecha_inicio
 * @param string $fecha_fin
 * @return array
 */
function getServicesWithDetails($conn, $fecha_inicio = null, $fecha_fin = null) {
    try {
        $sql = "SELECT * FROM vista_servicios_completos";
        $params = [];
        
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " WHERE DATE(fecha_cita) BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
        } elseif ($fecha_inicio) {
            $sql .= " WHERE DATE(fecha_cita) >= ?";
            $params = [$fecha_inicio];
        }
        
        $sql .= " ORDER BY fecha_cita DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error obteniendo servicios: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtener estadísticas del dashboard
 * @param PDO $conn
 * @return array
 */
function getDashboardStats($conn) {
    $stats = [
        'total_clientes' => 0,
        'servicios_hoy' => 0,
        'ingresos_hoy' => 0,
        'ingresos_mes' => 0,
        'servicios_pendientes' => 0
    ];
    
    try {
        // Total de clientes
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clientes");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['total_clientes'] = $result['total'];
        
        // Servicios de hoy
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total, COALESCE(SUM(ts.precio), 0) as ingresos 
            FROM citas c 
            JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id 
            WHERE DATE(c.fecha_cita) = CURDATE() AND c.estado = 'completada'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['servicios_hoy'] = $result['total'];
        $stats['ingresos_hoy'] = $result['ingresos'];
        
        // Ingresos del mes
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(ts.precio), 0) as ingresos 
            FROM citas c 
            JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id 
            WHERE YEAR(c.fecha_cita) = YEAR(CURDATE()) 
            AND MONTH(c.fecha_cita) = MONTH(CURDATE()) 
            AND c.estado = 'completada'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['ingresos_mes'] = $result['ingresos'];
        
        // Servicios pendientes
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM citas 
            WHERE estado = 'programada' AND fecha_cita >= NOW()
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['servicios_pendientes'] = $result['total'];
        
    } catch (Exception $e) {
        error_log('Error obteniendo estadísticas: ' . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Generar reporte de ingresos
 * @param PDO $conn
 * @param string $periodo ('diario', 'semanal', 'mensual')
 * @param string $fecha_inicio
 * @param string $fecha_fin
 * @return array
 */
function generateIncomeReport($conn, $periodo, $fecha_inicio, $fecha_fin) {
    try {
        $sql = "";
        $params = [$fecha_inicio, $fecha_fin];
        
        switch ($periodo) {
            case 'diario':
                $sql = "
                    SELECT 
                        DATE(c.fecha_cita) as periodo,
                        COUNT(*) as total_servicios,
                        SUM(ts.precio) as ingresos_totales
                    FROM citas c
                    JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                    WHERE DATE(c.fecha_cita) BETWEEN ? AND ? 
                    AND c.estado = 'completada'
                    GROUP BY DATE(c.fecha_cita)
                    ORDER BY periodo DESC
                ";
                break;
            case 'semanal':
                $sql = "
                    SELECT 
                        CONCAT(YEAR(c.fecha_cita), '-W', WEEK(c.fecha_cita)) as periodo,
                        COUNT(*) as total_servicios,
                        SUM(ts.precio) as ingresos_totales
                    FROM citas c
                    JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                    WHERE DATE(c.fecha_cita) BETWEEN ? AND ? 
                    AND c.estado = 'completada'
                    GROUP BY YEAR(c.fecha_cita), WEEK(c.fecha_cita)
                    ORDER BY periodo DESC
                ";
                break;
            case 'mensual':
                $sql = "
                    SELECT 
                        CONCAT(YEAR(c.fecha_cita), '-', LPAD(MONTH(c.fecha_cita), 2, '0')) as periodo,
                        COUNT(*) as total_servicios,
                        SUM(ts.precio) as ingresos_totales
                    FROM citas c
                    JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                    WHERE DATE(c.fecha_cita) BETWEEN ? AND ? 
                    AND c.estado = 'completada'
                    GROUP BY YEAR(c.fecha_cita), MONTH(c.fecha_cita)
                    ORDER BY periodo DESC
                ";
                break;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error generando reporte: ' . $e->getMessage());
        return [];
    }
}
?>