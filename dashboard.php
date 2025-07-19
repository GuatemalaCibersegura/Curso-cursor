<?php
/**
 * Dashboard Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require authentication
requireLogin();

$page_title = 'Dashboard';

// Initialize variables
$total_clients = 0;
$total_services_today = 0;
$total_income_today = 0;
$total_income_month = 0;
$recent_services = [];
$error_message = '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        // Get total clients count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients");
        $stmt->execute();
        $result = $stmt->fetch();
        $total_clients = $result['total'];
        
        // Get today's services count and income
        $stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(cost), 0) as income FROM services WHERE service_date = CURDATE()");
        $stmt->execute();
        $result = $stmt->fetch();
        $total_services_today = $result['total'];
        $total_income_today = $result['income'];
        
        // Get this month's income
        $stmt = $conn->prepare("SELECT COALESCE(SUM(cost), 0) as income FROM services WHERE YEAR(service_date) = YEAR(CURDATE()) AND MONTH(service_date) = MONTH(CURDATE())");
        $stmt->execute();
        $result = $stmt->fetch();
        $total_income_month = $result['income'];
        
        // Get recent services (last 10)
        $stmt = $conn->prepare("
            SELECT s.*, c.name as client_name, c.license_plate 
            FROM services s 
            JOIN clients c ON s.client_id = c.id 
            ORDER BY s.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $recent_services = $stmt->fetchAll();
        
    } else {
        $error_message = 'Database connection failed.';
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = 'An error occurred while loading dashboard data.';
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="services.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Service
            </a>
        </div>
    </div>
</div>

<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Clients</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($total_clients); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Services Today</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($total_services_today); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-gear display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Today's Income</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo formatCurrency($total_income_today); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Monthly Income</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo formatCurrency($total_income_month); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Services -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Recent Services</h5>
                <a href="services.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_services)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox display-4 text-muted"></i>
                        <p class="text-muted mt-2">No recent services found.</p>
                        <a href="services.php?action=add" class="btn btn-primary">Add First Service</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>License Plate</th>
                                    <th>Service Type</th>
                                    <th>Cost</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_services as $service): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($service['client_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($service['license_plate']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo ucfirst($service['service_type']); ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?php echo formatCurrency($service['cost']); ?></strong>
                                    </td>
                                    <td><?php echo formatDate($service['service_date']); ?></td>
                                    <td><?php echo date('g:i A', strtotime($service['service_time'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="clients.php?action=add" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus"></i><br>
                            <small>Add New Client</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="services.php?action=add" class="btn btn-outline-success w-100">
                            <i class="bi bi-gear-wide-connected"></i><br>
                            <small>New Service</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reports.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-graph-up"></i><br>
                            <small>View Reports</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="clients.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-search"></i><br>
                            <small>Search Clients</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>