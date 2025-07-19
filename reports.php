<?php
/**
 * Reports Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require authentication
requireLogin();

$page_title = 'Reports';
$report_type = $_GET['type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$error_message = '';
$success_message = '';
$report_data = [];
$summary_stats = [];

// Database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    $error_message = 'Database connection failed.';
}

// Validate date range
if (strtotime($date_from) > strtotime($date_to)) {
    $error_message = 'Start date cannot be after end date.';
} elseif (strtotime($date_from) > time()) {
    $error_message = 'Start date cannot be in the future.';
}

// Generate reports
if ($conn && empty($error_message)) {
    try {
        if ($report_type === 'daily') {
            // Daily report - services for each day in the range
            $stmt = $conn->prepare("
                SELECT 
                    s.service_date,
                    COUNT(s.id) as service_count,
                    SUM(s.cost) as total_income,
                    AVG(s.cost) as avg_service_cost,
                    GROUP_CONCAT(DISTINCT s.service_type) as service_types
                FROM services s
                WHERE s.service_date BETWEEN ? AND ?
                GROUP BY s.service_date
                ORDER BY s.service_date DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $report_data = $stmt->fetchAll();
            
        } elseif ($report_type === 'weekly') {
            // Weekly report - services grouped by week
            $stmt = $conn->prepare("
                SELECT 
                    YEAR(s.service_date) as year,
                    WEEK(s.service_date) as week,
                    MIN(s.service_date) as week_start,
                    MAX(s.service_date) as week_end,
                    COUNT(s.id) as service_count,
                    SUM(s.cost) as total_income,
                    AVG(s.cost) as avg_service_cost
                FROM services s
                WHERE s.service_date BETWEEN ? AND ?
                GROUP BY YEAR(s.service_date), WEEK(s.service_date)
                ORDER BY year DESC, week DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $report_data = $stmt->fetchAll();
            
        } elseif ($report_type === 'service_types') {
            // Service types report
            $stmt = $conn->prepare("
                SELECT 
                    s.service_type,
                    COUNT(s.id) as service_count,
                    SUM(s.cost) as total_income,
                    AVG(s.cost) as avg_cost,
                    MIN(s.cost) as min_cost,
                    MAX(s.cost) as max_cost
                FROM services s
                WHERE s.service_date BETWEEN ? AND ?
                GROUP BY s.service_type
                ORDER BY total_income DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $report_data = $stmt->fetchAll();
            
        } elseif ($report_type === 'clients') {
            // Top clients report
            $stmt = $conn->prepare("
                SELECT 
                    c.name,
                    c.license_plate,
                    c.vehicle_type,
                    COUNT(s.id) as service_count,
                    SUM(s.cost) as total_spent,
                    AVG(s.cost) as avg_service_cost,
                    MAX(s.service_date) as last_service
                FROM clients c
                LEFT JOIN services s ON c.id = s.client_id AND s.service_date BETWEEN ? AND ?
                GROUP BY c.id
                HAVING service_count > 0
                ORDER BY total_spent DESC, service_count DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $report_data = $stmt->fetchAll();
        }
        
        // Generate summary statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(s.id) as total_services,
                SUM(s.cost) as total_income,
                AVG(s.cost) as avg_service_cost,
                COUNT(DISTINCT s.client_id) as unique_clients,
                COUNT(DISTINCT s.service_date) as active_days
            FROM services s
            WHERE s.service_date BETWEEN ? AND ?
        ");
        $stmt->execute([$date_from, $date_to]);
        $summary_stats = $stmt->fetch();
        
        // Get service type breakdown for summary
        $stmt = $conn->prepare("
            SELECT 
                s.service_type,
                COUNT(s.id) as count,
                SUM(s.cost) as income
            FROM services s
            WHERE s.service_date BETWEEN ? AND ?
            GROUP BY s.service_type
            ORDER BY count DESC
        ");
        $stmt->execute([$date_from, $date_to]);
        $service_breakdown = $stmt->fetchAll();
        $summary_stats['service_breakdown'] = $service_breakdown;
        
    } catch (Exception $e) {
        error_log("Reports error: " . $e->getMessage());
        $error_message = 'An error occurred while generating the report.';
    }
}

// Log report generation
if (!empty($report_data)) {
    logActivity('Report Generated', "Generated {$report_type} report for {$date_from} to {$date_to}");
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-graph-up"></i> Reports</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php showSuccess($success_message); ?>
<?php endif; ?>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="reports.php" class="row g-3">
            <div class="col-md-3">
                <label for="type" class="form-label">Report Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                    <option value="weekly" <?php echo $report_type === 'weekly' ? 'selected' : ''; ?>>Weekly Report</option>
                    <option value="service_types" <?php echo $report_type === 'service_types' ? 'selected' : ''; ?>>Service Types</option>
                    <option value="clients" <?php echo $report_type === 'clients' ? 'selected' : ''; ?>>Top Clients</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($date_from); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($date_to); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Generate Report
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        Quick Select
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>">Today</a></li>
                        <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&date_from=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>">Last 7 Days</a></li>
                        <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&date_from=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>">Last 30 Days</a></li>
                        <li><a class="dropdown-item" href="?type=<?php echo $report_type; ?>&date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-t'); ?>">This Month</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<?php if (!empty($summary_stats)): ?>
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-gear display-6 mb-2"></i>
                <h5>Total Services</h5>
                <h3><?php echo number_format($summary_stats['total_services']); ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar display-6 mb-2"></i>
                <h5>Total Income</h5>
                <h3><?php echo formatCurrency($summary_stats['total_income']); ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-calculator display-6 mb-2"></i>
                <h5>Average Service</h5>
                <h3><?php echo formatCurrency($summary_stats['avg_service_cost']); ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people display-6 mb-2"></i>
                <h5>Unique Clients</h5>
                <h3><?php echo number_format($summary_stats['unique_clients']); ?></h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report Content -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-file-earmark-text"></i>
            <?php
            $report_titles = [
                'daily' => 'Daily Income Report',
                'weekly' => 'Weekly Income Report',
                'service_types' => 'Service Types Report',
                'clients' => 'Top Clients Report'
            ];
            echo $report_titles[$report_type] ?? 'Report';
            ?>
            <small class="text-muted">(<?php echo formatDate($date_from) . ' to ' . formatDate($date_to); ?>)</small>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($report_data)): ?>
            <div class="text-center py-4">
                <i class="bi bi-file-earmark-x display-4 text-muted"></i>
                <p class="text-muted mt-2">No data found for the selected date range.</p>
                <p class="text-muted">Try selecting a different date range or check if there are any services recorded.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <?php if ($report_type === 'daily'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Services</th>
                                <th>Total Income</th>
                                <th>Average Cost</th>
                                <th>Service Types</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><strong><?php echo formatDate($row['service_date']); ?></strong></td>
                                <td><span class="badge bg-primary"><?php echo $row['service_count']; ?></span></td>
                                <td><strong class="text-success"><?php echo formatCurrency($row['total_income']); ?></strong></td>
                                <td><?php echo formatCurrency($row['avg_service_cost']); ?></td>
                                <td>
                                    <?php 
                                    $types = explode(',', $row['service_types']);
                                    foreach (array_unique($types) as $type) {
                                        echo '<span class="badge bg-info text-dark me-1">' . ucfirst(str_replace('_', ' ', $type)) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                <?php elseif ($report_type === 'weekly'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Period</th>
                                <th>Services</th>
                                <th>Total Income</th>
                                <th>Average Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><strong>Week <?php echo $row['week']; ?>, <?php echo $row['year']; ?></strong></td>
                                <td><?php echo formatDate($row['week_start']) . ' - ' . formatDate($row['week_end']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $row['service_count']; ?></span></td>
                                <td><strong class="text-success"><?php echo formatCurrency($row['total_income']); ?></strong></td>
                                <td><?php echo formatCurrency($row['avg_service_cost']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                <?php elseif ($report_type === 'service_types'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Service Type</th>
                                <th>Count</th>
                                <th>Total Income</th>
                                <th>Average Cost</th>
                                <th>Min Cost</th>
                                <th>Max Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><span class="badge bg-info text-dark"><?php echo ucfirst(str_replace('_', ' ', $row['service_type'])); ?></span></td>
                                <td><span class="badge bg-primary"><?php echo $row['service_count']; ?></span></td>
                                <td><strong class="text-success"><?php echo formatCurrency($row['total_income']); ?></strong></td>
                                <td><?php echo formatCurrency($row['avg_cost']); ?></td>
                                <td><?php echo formatCurrency($row['min_cost']); ?></td>
                                <td><?php echo formatCurrency($row['max_cost']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                <?php elseif ($report_type === 'clients'): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>License Plate</th>
                                <th>Vehicle</th>
                                <th>Services</th>
                                <th>Total Spent</th>
                                <th>Average Cost</th>
                                <th>Last Service</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['license_plate']); ?></span></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['vehicle_type']); ?></span></td>
                                <td><span class="badge bg-primary"><?php echo $row['service_count']; ?></span></td>
                                <td><strong class="text-success"><?php echo formatCurrency($row['total_spent']); ?></strong></td>
                                <td><?php echo formatCurrency($row['avg_service_cost']); ?></td>
                                <td><?php echo $row['last_service'] ? formatDate($row['last_service']) : '<span class="text-muted">Never</span>'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Service Breakdown Chart (if summary stats available) -->
<?php if (!empty($summary_stats['service_breakdown'])): ?>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Service Type Distribution</h5>
            </div>
            <div class="card-body">
                <?php foreach ($summary_stats['service_breakdown'] as $service): ?>
                    <?php 
                    $percentage = ($service['count'] / $summary_stats['total_services']) * 100;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?php echo ucfirst(str_replace('_', ' ', $service['service_type'])); ?></span>
                            <span class="text-muted"><?php echo $service['count']; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Income by Service Type</h5>
            </div>
            <div class="card-body">
                <?php foreach ($summary_stats['service_breakdown'] as $service): ?>
                    <?php 
                    $percentage = ($service['income'] / $summary_stats['total_income']) * 100;
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?php echo ucfirst(str_replace('_', ' ', $service['service_type'])); ?></span>
                            <span class="text-muted"><?php echo formatCurrency($service['income']); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
@media print {
    .btn, .card-header .btn, .navbar, .sidebar {
        display: none !important;
    }
    .main-content {
        padding: 0 !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>