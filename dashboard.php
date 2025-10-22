<?php
/**
 * Dashboard Principal
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Requerir autenticación
requireLogin();

$page_title = 'Dashboard';
$page_icon = 'bi bi-speedometer2';

// Inicializar variables
$error_message = '';
$stats = [];
$recent_services = [];
$upcoming_appointments = [];
$daily_income = [];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener estadísticas principales
    $stats = getDashboardStats($conn);
    
    // Obtener servicios recientes (últimos 10)
    $stmt = $conn->prepare("
        SELECT c.id, c.fecha_cita, c.estado,
               cl.nombre as cliente_nombre,
               v.placa,
               ts.nombre as tipo_servicio,
               ts.precio
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        JOIN vehiculos v ON c.vehiculo_id = v.id
        JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
        ORDER BY c.creado_en DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_services = $stmt->fetchAll();
    
    // Obtener próximas citas (próximos 7 días)
    $stmt = $conn->prepare("
        SELECT c.id, c.fecha_cita, c.estado,
               cl.nombre as cliente_nombre,
               cl.telefono as cliente_telefono,
               v.placa,
               ts.nombre as tipo_servicio
        FROM citas c
        JOIN clientes cl ON c.cliente_id = cl.id
        JOIN vehiculos v ON c.vehiculo_id = v.id
        JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
        WHERE c.fecha_cita >= NOW() AND c.fecha_cita <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND c.estado = 'programada'
        ORDER BY c.fecha_cita ASC
        LIMIT 10
    ");
    $stmt->execute();
    $upcoming_appointments = $stmt->fetchAll();
    
    // Obtener ingresos de los últimos 7 días para el gráfico
    $stmt = $conn->prepare("
        SELECT DATE(c.fecha_cita) as fecha, 
               COALESCE(SUM(ts.precio), 0) as ingresos,
               COUNT(*) as servicios
        FROM citas c
        JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
        WHERE c.fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND c.estado = 'completada'
        GROUP BY DATE(c.fecha_cita)
        ORDER BY fecha ASC
    ");
    $stmt->execute();
    $daily_income = $stmt->fetchAll();
    
    // Obtener alertas del sistema
    $alerts = [];
    
    // Verificar inventario bajo (si existe)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM articulos_inventario 
        WHERE cantidad_stock <= umbral_bajo_stock
    ");
    $stmt->execute();
    $low_stock = $stmt->fetch();
    if ($low_stock['count'] > 0) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "Hay {$low_stock['count']} artículo(s) con stock bajo",
            'icon' => 'bi-exclamation-triangle'
        ];
    }
    
    // Verificar citas para hoy
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM citas 
        WHERE DATE(fecha_cita) = CURDATE() AND estado = 'programada'
    ");
    $stmt->execute();
    $today_appointments = $stmt->fetch();
    if ($today_appointments['count'] > 0) {
        $alerts[] = [
            'type' => 'info',
            'message' => "Tienes {$today_appointments['count']} cita(s) programada(s) para hoy",
            'icon' => 'bi-calendar-check'
        ];
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log('Error en dashboard.php: ' . $e->getMessage());
}

include 'includes/header.php';
?>

<!-- Mensajes de estado -->
<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<!-- Alertas del sistema -->
<?php if (!empty($alerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
            <i class="<?php echo $alert['icon']; ?> me-2"></i>
            <?php echo $alert['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tarjetas de estadísticas principales -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($stats['total_clientes']); ?></h4>
                        <p class="mb-0">Total Clientes</p>
                        <small class="opacity-75">Registrados en el sistema</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people display-4 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="clients.php" class="text-white text-decoration-none">
                    <small><i class="bi bi-arrow-right me-1"></i>Ver todos los clientes</small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($stats['servicios_hoy']); ?></h4>
                        <p class="mb-0">Servicios Hoy</p>
                        <small class="opacity-75"><?php echo formatCurrency($stats['ingresos_hoy']); ?> generados</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check display-4 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="services.php?filter_date=<?php echo date('Y-m-d'); ?>" class="text-white text-decoration-none">
                    <small><i class="bi bi-arrow-right me-1"></i>Ver servicios de hoy</small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo formatCurrency($stats['ingresos_mes']); ?></h4>
                        <p class="mb-0">Ingresos del Mes</p>
                        <small class="opacity-75">
                            <?php 
                            $promedio = $stats['servicios_hoy'] > 0 ? $stats['ingresos_mes'] / $stats['servicios_hoy'] : 0;
                            echo formatCurrency($promedio) . ' promedio';
                            ?>
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar display-4 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="reports.php?type=income" class="text-white text-decoration-none">
                    <small><i class="bi bi-arrow-right me-1"></i>Ver reporte de ingresos</small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($stats['servicios_pendientes']); ?></h4>
                        <p class="mb-0">Citas Pendientes</p>
                        <small class="opacity-75">Próximos servicios programados</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock display-4 opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="services.php?filter_status=programada" class="text-white text-decoration-none">
                    <small><i class="bi bi-arrow-right me-1"></i>Ver citas pendientes</small>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de ingresos -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Ingresos de los Últimos 7 Días
                    </h5>
                    <a href="reports.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-bar-chart"></i> Ver Reportes
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($daily_income)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-graph-down display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay datos de ingresos en los últimos 7 días</p>
                    </div>
                <?php else: ?>
                    <canvas id="incomeChart" height="100"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="services.php?action=add" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Cita
                    </a>
                    <a href="clients.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Cliente
                    </a>
                    <a href="services.php?filter_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-success">
                        <i class="bi bi-calendar-day me-2"></i>Servicios de Hoy
                    </a>
                    <a href="reports.php" class="btn btn-outline-info">
                        <i class="bi bi-graph-up me-2"></i>Ver Reportes
                    </a>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <h6 class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></h6>
                    <small class="text-muted">
                        Rol: <span class="badge bg-secondary"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Próximas citas -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-event me-2"></i>Próximas Citas
                    </h5>
                    <span class="badge bg-primary"><?php echo count($upcoming_appointments); ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_appointments)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay citas programadas para los próximos 7 días</p>
                        <a href="services.php?action=add" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Programar Cita
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($upcoming_appointments, 0, 5) as $appointment): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($appointment['cliente_nombre']); ?></h6>
                                    <p class="mb-1">
                                        <span class="badge bg-dark"><?php echo htmlspecialchars($appointment['placa']); ?></span>
                                        <span class="text-muted">- <?php echo htmlspecialchars($appointment['tipo_servicio']); ?></span>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($appointment['cliente_telefono']); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted"><?php echo formatDateTime($appointment['fecha_cita']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($upcoming_appointments) > 5): ?>
                    <div class="text-center mt-3">
                        <a href="services.php?filter_status=programada" class="btn btn-sm btn-outline-primary">
                            Ver todas las citas (<?php echo count($upcoming_appointments); ?>)
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actividad reciente -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>Actividad Reciente
                    </h5>
                    <span class="badge bg-info"><?php echo count($recent_services); ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($recent_services)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-activity display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay actividad reciente</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($recent_services, 0, 5) as $service): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($service['cliente_nombre']); ?></h6>
                                    <p class="mb-1">
                                        <span class="badge bg-dark"><?php echo htmlspecialchars($service['placa']); ?></span>
                                        <span class="text-muted">- <?php echo htmlspecialchars($service['tipo_servicio']); ?></span>
                                    </p>
                                    <small class="text-muted"><?php echo formatCurrency($service['precio']); ?></small>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $estado_class = '';
                                    switch ($service['estado']) {
                                        case 'completada':
                                            $estado_class = 'bg-success';
                                            break;
                                        case 'programada':
                                            $estado_class = 'bg-primary';
                                            break;
                                        case 'cancelada':
                                            $estado_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $estado_class; ?>"><?php echo ucfirst($service['estado']); ?></span>
                                    <br>
                                    <small class="text-muted"><?php echo formatDateTime($service['fecha_cita']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="services.php" class="btn btn-sm btn-outline-info">
                            Ver todos los servicios
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para el gráfico -->
<?php if (!empty($daily_income)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('incomeChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($daily_income, 'fecha')); ?>,
            datasets: [{
                label: 'Ingresos Diarios',
                data: <?php echo json_encode(array_column($daily_income, 'ingresos')); ?>,
                borderColor: '#667eea',
                backgroundColor: '#667eea20',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Ingresos: ₡' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Fecha'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Ingresos (₡)'
                    },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₡' + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>