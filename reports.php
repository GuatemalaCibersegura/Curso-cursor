<?php
/**
 * Módulo de Reportes
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Requerir autenticación
requireLogin();

$page_title = 'Reportes e Informes';
$page_icon = 'bi bi-graph-up-arrow';
$breadcrumbs = [
    ['title' => 'Reportes']
];

// Inicializar variables
$error_message = '';
$success_message = '';
$report_type = $_GET['type'] ?? 'dashboard';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Primer día del mes actual
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Hoy

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener estadísticas del dashboard
    $stats = getDashboardStats($conn);
    
    // Procesar según el tipo de reporte
    switch ($report_type) {
        case 'income':
            $period = $_GET['period'] ?? 'diario';
            $income_data = generateIncomeReport($conn, $period, $date_from, $date_to);
            break;
            
        case 'services':
            // Reporte de servicios más populares
            $stmt = $conn->prepare("
                SELECT ts.nombre, COUNT(*) as total_servicios, SUM(ts.precio) as ingresos_totales
                FROM citas c
                JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                WHERE DATE(c.fecha_cita) BETWEEN ? AND ? AND c.estado = 'completada'
                GROUP BY ts.id, ts.nombre
                ORDER BY total_servicios DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $services_data = $stmt->fetchAll();
            break;
            
        case 'clients':
            // Reporte de clientes más activos
            $stmt = $conn->prepare("
                SELECT cl.nombre, cl.telefono, COUNT(*) as total_servicios, 
                       SUM(ts.precio) as gasto_total, MAX(c.fecha_cita) as ultima_visita
                FROM citas c
                JOIN clientes cl ON c.cliente_id = cl.id
                JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                WHERE DATE(c.fecha_cita) BETWEEN ? AND ? AND c.estado = 'completada'
                GROUP BY cl.id, cl.nombre, cl.telefono
                ORDER BY total_servicios DESC
                LIMIT 20
            ");
            $stmt->execute([$date_from, $date_to]);
            $clients_data = $stmt->fetchAll();
            break;
            
        case 'vehicles':
            // Reporte de tipos de vehículos más comunes
            $stmt = $conn->prepare("
                SELECT v.marca, v.modelo, COUNT(*) as cantidad
                FROM vehiculos v
                JOIN clientes cl ON v.cliente_id = cl.id
                GROUP BY v.marca, v.modelo
                ORDER BY cantidad DESC
                LIMIT 15
            ");
            $stmt->execute();
            $vehicles_data = $stmt->fetchAll();
            break;
    }
    
    // Obtener datos para gráficos del dashboard
    if ($report_type === 'dashboard') {
        // Ingresos de los últimos 7 días
        $stmt = $conn->prepare("
            SELECT DATE(c.fecha_cita) as fecha, COALESCE(SUM(ts.precio), 0) as ingresos
            FROM citas c
            JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
            WHERE c.fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
            AND c.estado = 'completada'
            GROUP BY DATE(c.fecha_cita)
            ORDER BY fecha ASC
        ");
        $stmt->execute();
        $daily_income = $stmt->fetchAll();
        
        // Servicios por tipo en el último mes
        $stmt = $conn->prepare("
            SELECT ts.nombre, COUNT(*) as cantidad
            FROM citas c
            JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
            WHERE c.fecha_cita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            AND c.estado = 'completada'
            GROUP BY ts.id, ts.nombre
            ORDER BY cantidad DESC
        ");
        $stmt->execute();
        $services_by_type = $stmt->fetchAll();
        
        // Servicios pendientes por día
        $stmt = $conn->prepare("
            SELECT DATE(fecha_cita) as fecha, COUNT(*) as cantidad
            FROM citas
            WHERE fecha_cita >= CURDATE() AND estado = 'programada'
            GROUP BY DATE(fecha_cita)
            ORDER BY fecha ASC
            LIMIT 7
        ");
        $stmt->execute();
        $upcoming_services = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log('Error en reports.php: ' . $e->getMessage());
}

include 'includes/header.php';
?>

<!-- Mensajes de estado -->
<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php showSuccess($success_message); ?>
<?php endif; ?>

<!-- Navegación de reportes -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'dashboard' ? 'active' : ''; ?>" 
                           href="reports.php?type=dashboard">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'income' ? 'active' : ''; ?>" 
                           href="reports.php?type=income">
                            <i class="bi bi-currency-dollar me-1"></i>Ingresos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'services' ? 'active' : ''; ?>" 
                           href="reports.php?type=services">
                            <i class="bi bi-gear me-1"></i>Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'clients' ? 'active' : ''; ?>" 
                           href="reports.php?type=clients">
                            <i class="bi bi-people me-1"></i>Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'vehicles' ? 'active' : ''; ?>" 
                           href="reports.php?type=vehicles">
                            <i class="bi bi-car-front me-1"></i>Vehículos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($report_type === 'dashboard'): ?>
<!-- Dashboard de Estadísticas -->
<div class="row mb-4">
    <!-- Tarjetas de estadísticas -->
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($stats['total_clientes']); ?></h4>
                        <p class="mb-0">Total Clientes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($stats['servicios_hoy']); ?></h4>
                        <p class="mb-0">Servicios Hoy</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo formatCurrency($stats['ingresos_hoy']); ?></h4>
                        <p class="mb-0">Ingresos Hoy</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo formatCurrency($stats['ingresos_mes']); ?></h4>
                        <p class="mb-0">Ingresos del Mes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-graph-up display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de ingresos diarios -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Ingresos de los Últimos 7 Días
                </h5>
            </div>
            <div class="card-body">
                <canvas id="dailyIncomeChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Servicios pendientes -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock me-2"></i>Próximos Servicios
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_services)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay servicios programados</p>
                    </div>
                <?php else: ?>
                    <canvas id="upcomingServicesChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Servicios por tipo -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Servicios por Tipo (Último Mes)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($services_by_type)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-gear display-4 text-muted"></i>
                        <p class="text-muted mt-2">No hay datos de servicios</p>
                    </div>
                <?php else: ?>
                    <canvas id="servicesByTypeChart" height="200"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Resumen rápido -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Resumen Rápido
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h3 class="text-primary"><?php echo $stats['servicios_pendientes']; ?></h3>
                        <small class="text-muted">Servicios Pendientes</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="text-success"><?php echo number_format($stats['ingresos_mes'] / max($stats['servicios_hoy'], 1), 0); ?></h3>
                        <small class="text-muted">Promedio por Servicio</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="services.php?action=add" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Nueva Cita
                    </a>
                    <a href="clients.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus"></i> Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'income'): ?>
<!-- Reporte de Ingresos -->
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-currency-dollar me-2"></i>Reporte de Ingresos
            </h5>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="type" value="income">
                <select name="period" class="form-select form-select-sm">
                    <option value="diario" <?php echo ($period ?? 'diario') === 'diario' ? 'selected' : ''; ?>>Diario</option>
                    <option value="semanal" <?php echo ($period ?? '') === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                    <option value="mensual" <?php echo ($period ?? '') === 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                </select>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                <button type="submit" class="btn btn-primary btn-sm">Generar</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($income_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-graph-down display-1 text-muted"></i>
                <h4 class="mt-3">No hay datos de ingresos</h4>
                <p class="text-muted">No se encontraron ingresos en el período seleccionado</p>
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-md-8">
                    <canvas id="incomeChart" height="100"></canvas>
                </div>
                <div class="col-md-4">
                    <?php 
                    $total_income = array_sum(array_column($income_data, 'ingresos_totales'));
                    $total_services = array_sum(array_column($income_data, 'total_servicios'));
                    ?>
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?php echo formatCurrency($total_income); ?></h3>
                            <p class="mb-1">Ingresos Totales</p>
                            <small class="text-muted"><?php echo $total_services; ?> servicios</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Servicios</th>
                            <th>Ingresos</th>
                            <th>Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($income_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['periodo']); ?></td>
                            <td><?php echo number_format($row['total_servicios']); ?></td>
                            <td><strong><?php echo formatCurrency($row['ingresos_totales']); ?></strong></td>
                            <td><?php echo formatCurrency($row['ingresos_totales'] / max($row['total_servicios'], 1)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($report_type === 'services'): ?>
<!-- Reporte de Servicios -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-gear me-2"></i>Servicios Más Populares
            </h5>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="type" value="services">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($services_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-gear display-1 text-muted"></i>
                <h4 class="mt-3">No hay datos de servicios</h4>
                <p class="text-muted">No se encontraron servicios en el período seleccionado</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                    <th>Ingresos</th>
                                    <th>Participación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_services = array_sum(array_column($services_data, 'total_servicios'));
                                foreach ($services_data as $service): 
                                    $percentage = ($service['total_servicios'] / max($total_services, 1)) * 100;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($service['nombre']); ?></strong></td>
                                    <td><?php echo number_format($service['total_servicios']); ?></td>
                                    <td><?php echo formatCurrency($service['ingresos_totales']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <canvas id="servicesChart" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($report_type === 'clients'): ?>
<!-- Reporte de Clientes -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-people me-2"></i>Clientes Más Activos
            </h5>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="type" value="clients">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($clients_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h4 class="mt-3">No hay datos de clientes</h4>
                <p class="text-muted">No se encontraron clientes activos en el período seleccionado</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Servicios</th>
                            <th>Gasto Total</th>
                            <th>Última Visita</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients_data as $client): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($client['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($client['telefono']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $client['total_servicios']; ?></span>
                            </td>
                            <td><strong><?php echo formatCurrency($client['gasto_total']); ?></strong></td>
                            <td><?php echo formatDate($client['ultima_visita']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($report_type === 'vehicles'): ?>
<!-- Reporte de Vehículos -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-car-front me-2"></i>Marcas y Modelos Más Comunes
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($vehicles_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-car-front display-1 text-muted"></i>
                <h4 class="mt-3">No hay datos de vehículos</h4>
                <p class="text-muted">No se encontraron vehículos registrados</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_vehicles = array_sum(array_column($vehicles_data, 'cantidad'));
                                foreach ($vehicles_data as $vehicle): 
                                    $percentage = ($vehicle['cantidad'] / max($total_vehicles, 1)) * 100;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehicle['marca']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($vehicle['modelo']); ?></td>
                                    <td><?php echo number_format($vehicle['cantidad']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <canvas id="vehiclesChart" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<!-- Scripts para gráficos -->
<script>
// Configuración de colores
const colors = [
    '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe',
    '#43e97b', '#38f9d7', '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
];

<?php if ($report_type === 'dashboard'): ?>
// Gráfico de ingresos diarios
<?php if (!empty($daily_income)): ?>
const dailyIncomeCtx = document.getElementById('dailyIncomeChart').getContext('2d');
new Chart(dailyIncomeCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($daily_income, 'fecha')); ?>,
        datasets: [{
            label: 'Ingresos Diarios',
            data: <?php echo json_encode(array_column($daily_income, 'ingresos')); ?>,
            borderColor: colors[0],
            backgroundColor: colors[0] + '20',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₡' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Gráfico de servicios por tipo
<?php if (!empty($services_by_type)): ?>
const servicesByTypeCtx = document.getElementById('servicesByTypeChart').getContext('2d');
new Chart(servicesByTypeCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($services_by_type, 'nombre')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($services_by_type, 'cantidad')); ?>,
            backgroundColor: colors.slice(0, <?php echo count($services_by_type); ?>)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Gráfico de próximos servicios
<?php if (!empty($upcoming_services)): ?>
const upcomingServicesCtx = document.getElementById('upcomingServicesChart').getContext('2d');
new Chart(upcomingServicesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($upcoming_services, 'fecha')); ?>,
        datasets: [{
            label: 'Servicios Programados',
            data: <?php echo json_encode(array_column($upcoming_services, 'cantidad')); ?>,
            backgroundColor: colors[2]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>

<?php elseif ($report_type === 'income' && !empty($income_data)): ?>
// Gráfico de ingresos
const incomeCtx = document.getElementById('incomeChart').getContext('2d');
new Chart(incomeCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($income_data, 'periodo')); ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?php echo json_encode(array_column($income_data, 'ingresos_totales')); ?>,
            backgroundColor: colors[0]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₡' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

<?php elseif ($report_type === 'services' && !empty($services_data)): ?>
// Gráfico de servicios
const servicesCtx = document.getElementById('servicesChart').getContext('2d');
new Chart(servicesCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($services_data, 'nombre')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($services_data, 'total_servicios')); ?>,
            backgroundColor: colors.slice(0, <?php echo count($services_data); ?>)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

<?php elseif ($report_type === 'vehicles' && !empty($vehicles_data)): ?>
// Gráfico de vehículos
const vehiclesCtx = document.getElementById('vehiclesChart').getContext('2d');
new Chart(vehiclesCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($v) { return $v['marca'] . ' ' . $v['modelo']; }, array_slice($vehicles_data, 0, 8))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column(array_slice($vehicles_data, 0, 8), 'cantidad')); ?>,
            backgroundColor: colors.slice(0, 8)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>