<?php
/**
 * Service Entry Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require authentication
requireLogin();

$page_title = 'Service Management';
$action = $_GET['action'] ?? 'list';
$service_id = $_GET['id'] ?? null;
$client_id = $_GET['client_id'] ?? null;

$error_message = '';
$success_message = '';
$services = [];
$service = null;
$clients = [];

// Service type pricing
$service_pricing = [
    'basic' => 15.00,
    'deluxe' => 25.00,
    'premium' => 35.00,
    'full_detail' => 50.00
];

// Database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    $error_message = 'Database connection failed.';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            if ($action === 'add' || $action === 'edit') {
                // Sanitize input data
                $selected_client_id = sanitizeInput($_POST['client_id'] ?? '');
                $service_type = sanitizeInput($_POST['service_type'] ?? '');
                $cost = floatval($_POST['cost'] ?? 0);
                $service_date = sanitizeInput($_POST['service_date'] ?? '');
                $service_time = sanitizeInput($_POST['service_time'] ?? '');
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Validate required fields
                if (empty($selected_client_id) || empty($service_type) || empty($service_date) || empty($service_time) || $cost <= 0) {
                    $error_message = 'Please fill in all required fields.';
                } elseif (!in_array($service_type, array_keys($service_pricing))) {
                    $error_message = 'Invalid service type selected.';
                } elseif (strtotime($service_date) > time()) {
                    $error_message = 'Service date cannot be in the future.';
                } else {
                    // Verify client exists
                    $stmt = $conn->prepare("SELECT name FROM clients WHERE id = ?");
                    $stmt->execute([$selected_client_id]);
                    $client_info = $stmt->fetch();
                    
                    if (!$client_info) {
                        $error_message = 'Selected client not found.';
                    } else {
                        if ($action === 'add') {
                            // Insert new service
                            $stmt = $conn->prepare("INSERT INTO services (client_id, service_type, cost, service_date, service_time, notes) VALUES (?, ?, ?, ?, ?, ?)");
                            
                            if ($stmt->execute([$selected_client_id, $service_type, $cost, $service_date, $service_time, $notes])) {
                                $success_message = 'Service added successfully!';
                                logActivity('Service Added', "New service: {$service_type} for {$client_info['name']} - " . formatCurrency($cost));
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to add service. Please try again.';
                            }
                        } elseif ($action === 'edit' && $service_id) {
                            // Update service
                            $stmt = $conn->prepare("UPDATE services SET client_id = ?, service_type = ?, cost = ?, service_date = ?, service_time = ?, notes = ? WHERE id = ?");
                            
                            if ($stmt->execute([$selected_client_id, $service_type, $cost, $service_date, $service_time, $notes, $service_id])) {
                                $success_message = 'Service updated successfully!';
                                logActivity('Service Updated', "Updated service: {$service_type} for {$client_info['name']} - " . formatCurrency($cost));
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to update service. Please try again.';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Service management error: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Handle delete action
if ($action === 'delete' && $service_id && $conn) {
    try {
        // Get service info for logging
        $stmt = $conn->prepare("
            SELECT s.*, c.name as client_name 
            FROM services s 
            JOIN clients c ON s.client_id = c.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$service_id]);
        $service_info = $stmt->fetch();
        
        if ($service_info) {
            // Delete service
            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            if ($stmt->execute([$service_id])) {
                $success_message = 'Service deleted successfully!';
                logActivity('Service Deleted', "Deleted service: {$service_info['service_type']} for {$service_info['client_name']}");
            } else {
                $error_message = 'Failed to delete service. Please try again.';
            }
        } else {
            $error_message = 'Service not found.';
        }
    } catch (Exception $e) {
        error_log("Service delete error: " . $e->getMessage());
        $error_message = 'An error occurred while deleting service.';
    }
    $action = 'list'; // Redirect to list view
}

// Fetch data based on action
if ($conn) {
    try {
        // Always load clients for the dropdown
        $stmt = $conn->prepare("SELECT id, name, license_plate FROM clients ORDER BY name");
        $stmt->execute();
        $clients = $stmt->fetchAll();
        
        if ($action === 'list') {
            // Get all services with client information
            $search = $_GET['search'] ?? '';
            $search_param = '%' . $search . '%';
            
            $where_clause = '';
            $params = [];
            
            // Filter by client if specified
            if ($client_id) {
                $where_clause = 'WHERE s.client_id = ?';
                $params[] = $client_id;
            } elseif (!empty($search)) {
                $where_clause = 'WHERE c.name LIKE ? OR c.license_plate LIKE ? OR s.service_type LIKE ?';
                $params = [$search_param, $search_param, $search_param];
            }
            
            $stmt = $conn->prepare("
                SELECT s.*, c.name as client_name, c.license_plate 
                FROM services s 
                JOIN clients c ON s.client_id = c.id 
                {$where_clause}
                ORDER BY s.service_date DESC, s.service_time DESC
            ");
            $stmt->execute($params);
            $services = $stmt->fetchAll();
            
        } elseif ($action === 'edit' && $service_id) {
            // Get specific service for editing
            $stmt = $conn->prepare("
                SELECT s.*, c.name as client_name 
                FROM services s 
                JOIN clients c ON s.client_id = c.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $error_message = 'Service not found.';
                $action = 'list';
            }
        }
    } catch (Exception $e) {
        error_log("Service fetch error: " . $e->getMessage());
        $error_message = 'An error occurred while loading service data.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-gear"></i> 
        <?php 
        switch($action) {
            case 'add': echo 'Add New Service'; break;
            case 'edit': echo 'Edit Service'; break;
            default: echo 'Service Management'; break;
        }
        ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($action === 'list'): ?>
        <div class="btn-group me-2">
            <a href="services.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Service
            </a>
        </div>
        <?php else: ?>
        <div class="btn-group me-2">
            <a href="services.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php showSuccess($success_message); ?>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Service List View -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">
                        <?php if ($client_id && !empty($services)): ?>
                            Services for <?php echo htmlspecialchars($services[0]['client_name']); ?> (<?php echo count($services); ?>)
                        <?php else: ?>
                            All Services (<?php echo count($services); ?>)
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="col-auto">
                    <div class="d-flex">
                        <?php if ($client_id): ?>
                        <a href="services.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i> All Services
                        </a>
                        <?php endif; ?>
                        <form method="GET" action="services.php" class="d-flex">
                            <?php if ($client_id): ?>
                            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                            <?php endif; ?>
                            <input type="text" class="form-control me-2" name="search" 
                                   placeholder="Search services..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                            <a href="services.php<?php echo $client_id ? '?client_id=' . $client_id : ''; ?>" class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-x"></i>
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($services)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-gear display-4 text-muted"></i>
                    <p class="text-muted mt-2">
                        <?php 
                        if (!empty($_GET['search'])) {
                            echo 'No services found matching your search.';
                        } elseif ($client_id) {
                            echo 'No services found for this client.';
                        } else {
                            echo 'No services recorded yet.';
                        }
                        ?>
                    </p>
                    <a href="services.php?action=add<?php echo $client_id ? '&client_id=' . $client_id : ''; ?>" class="btn btn-primary">
                        Add First Service
                    </a>
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
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['client_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($service['license_plate']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo ucfirst(str_replace('_', ' ', $service['service_type'])); ?></span>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo formatCurrency($service['cost']); ?></strong>
                                </td>
                                <td><?php echo formatDate($service['service_date']); ?></td>
                                <td><?php echo date('g:i A', strtotime($service['service_time'])); ?></td>
                                <td>
                                    <?php if ($service['notes']): ?>
                                        <span title="<?php echo htmlspecialchars($service['notes']); ?>">
                                            <?php echo htmlspecialchars(substr($service['notes'], 0, 30) . (strlen($service['notes']) > 30 ? '...' : '')); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="services.php?action=edit&id=<?php echo $service['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="services.php?action=delete&id=<?php echo $service['id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this service?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary -->
                <?php 
                $total_services = count($services);
                $total_income = array_sum(array_column($services, 'cost'));
                ?>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5>Total Services</h5>
                                <h3 class="text-primary"><?php echo number_format($total_services); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5>Total Income</h5>
                                <h3 class="text-success"><?php echo formatCurrency($total_income); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Service Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil'; ?>"></i>
                        <?php echo $action === 'add' ? 'Add New Service' : 'Edit Service'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($clients)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            No clients found. Please <a href="clients.php?action=add">add a client</a> first.
                        </div>
                    <?php else: ?>
                    <form method="POST" id="serviceForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" 
                                            <?php echo ($service['client_id'] ?? $client_id) == $client['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['license_plate']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a client.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="service_type" class="form-label">Service Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="service_type" name="service_type" required>
                                    <option value="">Select Service Type</option>
                                    <?php foreach ($service_pricing as $type => $price): ?>
                                    <option value="<?php echo $type; ?>" data-price="<?php echo $price; ?>"
                                            <?php echo ($service['service_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?> (<?php echo formatCurrency($price); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a service type.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cost" class="form-label">Cost <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" 
                                           value="<?php echo $service['cost'] ?? ''; ?>" 
                                           required min="0" step="0.01" max="999.99">
                                    <div class="invalid-feedback">
                                        Please enter a valid cost.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="service_date" class="form-label">Service Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="service_date" name="service_date" 
                                       value="<?php echo $service['service_date'] ?? date('Y-m-d'); ?>" 
                                       required max="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">
                                    Please enter a valid service date.
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="service_time" class="form-label">Service Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="service_time" name="service_time" 
                                       value="<?php echo $service['service_time'] ?? date('H:i'); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please enter a valid service time.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500"><?php echo htmlspecialchars($service['notes'] ?? ''); ?></textarea>
                            <div class="form-text">Optional notes about the service (max 500 characters)</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="services.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'check'; ?>"></i>
                                <?php echo $action === 'add' ? 'Add Service' : 'Update Service'; ?>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Form validation and auto-pricing
    document.getElementById('serviceForm').addEventListener('submit', function(event) {
        if (!validateForm('serviceForm')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // Auto-update cost when service type changes
    document.getElementById('service_type').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        if (price) {
            document.getElementById('cost').value = parseFloat(price).toFixed(2);
        }
    });
    
    // Format cost input
    document.getElementById('cost').addEventListener('blur', function() {
        formatCurrencyInput(this);
    });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>