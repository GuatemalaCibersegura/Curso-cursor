<?php
/**
 * Client Registration and Management Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require authentication
requireLogin();

$page_title = 'Client Management';
$action = $_GET['action'] ?? 'list';
$client_id = $_GET['id'] ?? null;

$error_message = '';
$success_message = '';
$clients = [];
$client = null;

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
                $name = sanitizeInput($_POST['name'] ?? '');
                $contact_number = sanitizeInput($_POST['contact_number'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $vehicle_type = sanitizeInput($_POST['vehicle_type'] ?? '');
                $license_plate = strtoupper(sanitizeInput($_POST['license_plate'] ?? ''));
                
                // Validate required fields
                if (empty($name) || empty($contact_number) || empty($vehicle_type) || empty($license_plate)) {
                    $error_message = 'Please fill in all required fields.';
                } elseif (!empty($email) && !validateEmail($email)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (!validatePhone($contact_number)) {
                    $error_message = 'Please enter a valid contact number.';
                } else {
                    if ($action === 'add') {
                        // Check if license plate already exists
                        $stmt = $conn->prepare("SELECT id FROM clients WHERE license_plate = ?");
                        $stmt->execute([$license_plate]);
                        
                        if ($stmt->fetch()) {
                            $error_message = 'A client with this license plate already exists.';
                        } else {
                            // Insert new client
                            $stmt = $conn->prepare("INSERT INTO clients (name, contact_number, email, vehicle_type, license_plate) VALUES (?, ?, ?, ?, ?)");
                            
                            if ($stmt->execute([$name, $contact_number, $email, $vehicle_type, $license_plate])) {
                                $success_message = 'Client registered successfully!';
                                logActivity('Client Added', "New client: {$name} ({$license_plate})");
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to register client. Please try again.';
                            }
                        }
                    } elseif ($action === 'edit' && $client_id) {
                        // Check if license plate exists for other clients
                        $stmt = $conn->prepare("SELECT id FROM clients WHERE license_plate = ? AND id != ?");
                        $stmt->execute([$license_plate, $client_id]);
                        
                        if ($stmt->fetch()) {
                            $error_message = 'Another client with this license plate already exists.';
                        } else {
                            // Update client
                            $stmt = $conn->prepare("UPDATE clients SET name = ?, contact_number = ?, email = ?, vehicle_type = ?, license_plate = ? WHERE id = ?");
                            
                            if ($stmt->execute([$name, $contact_number, $email, $vehicle_type, $license_plate, $client_id])) {
                                $success_message = 'Client updated successfully!';
                                logActivity('Client Updated', "Updated client: {$name} ({$license_plate})");
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to update client. Please try again.';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Client management error: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Handle delete action
if ($action === 'delete' && $client_id && $conn) {
    try {
        // Check if client has services
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM services WHERE client_id = ?");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error_message = 'Cannot delete client. Client has existing service records.';
        } else {
            // Get client info for logging
            $stmt = $conn->prepare("SELECT name, license_plate FROM clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $client_info = $stmt->fetch();
            
            // Delete client
            $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
            if ($stmt->execute([$client_id])) {
                $success_message = 'Client deleted successfully!';
                if ($client_info) {
                    logActivity('Client Deleted', "Deleted client: {$client_info['name']} ({$client_info['license_plate']})");
                }
            } else {
                $error_message = 'Failed to delete client. Please try again.';
            }
        }
    } catch (Exception $e) {
        error_log("Client delete error: " . $e->getMessage());
        $error_message = 'An error occurred while deleting client.';
    }
    $action = 'list'; // Redirect to list view
}

// Fetch data based on action
if ($conn) {
    try {
        if ($action === 'list') {
            // Get all clients with service count
            $search = $_GET['search'] ?? '';
            $search_param = '%' . $search . '%';
            
            if (!empty($search)) {
                $stmt = $conn->prepare("
                    SELECT c.*, COUNT(s.id) as service_count, MAX(s.service_date) as last_service
                    FROM clients c 
                    LEFT JOIN services s ON c.id = s.client_id 
                    WHERE c.name LIKE ? OR c.license_plate LIKE ? OR c.contact_number LIKE ?
                    GROUP BY c.id 
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$search_param, $search_param, $search_param]);
            } else {
                $stmt = $conn->prepare("
                    SELECT c.*, COUNT(s.id) as service_count, MAX(s.service_date) as last_service
                    FROM clients c 
                    LEFT JOIN services s ON c.id = s.client_id 
                    GROUP BY c.id 
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute();
            }
            $clients = $stmt->fetchAll();
            
        } elseif ($action === 'edit' && $client_id) {
            // Get specific client for editing
            $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $client = $stmt->fetch();
            
            if (!$client) {
                $error_message = 'Client not found.';
                $action = 'list';
            }
        }
    } catch (Exception $e) {
        error_log("Client fetch error: " . $e->getMessage());
        $error_message = 'An error occurred while loading client data.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-people"></i> 
        <?php 
        switch($action) {
            case 'add': echo 'Add New Client'; break;
            case 'edit': echo 'Edit Client'; break;
            default: echo 'Client Management'; break;
        }
        ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($action === 'list'): ?>
        <div class="btn-group me-2">
            <a href="clients.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Client
            </a>
        </div>
        <?php else: ?>
        <div class="btn-group me-2">
            <a href="clients.php" class="btn btn-secondary">
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
    <!-- Client List View -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0">All Clients (<?php echo count($clients); ?>)</h5>
                </div>
                <div class="col-auto">
                    <form method="GET" action="clients.php" class="d-flex">
                        <input type="text" class="form-control me-2" name="search" 
                               placeholder="Search clients..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if (!empty($_GET['search'])): ?>
                        <a href="clients.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x"></i>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($clients)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-people display-4 text-muted"></i>
                    <p class="text-muted mt-2">
                        <?php echo !empty($_GET['search']) ? 'No clients found matching your search.' : 'No clients registered yet.'; ?>
                    </p>
                    <a href="clients.php?action=add" class="btn btn-primary">Add First Client</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Vehicle</th>
                                <th>License Plate</th>
                                <th>Services</th>
                                <th>Last Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($client['contact_number']); ?></td>
                                <td>
                                    <?php if ($client['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>">
                                            <?php echo htmlspecialchars($client['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($client['vehicle_type']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($client['license_plate']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $client['service_count']; ?></span>
                                </td>
                                <td>
                                    <?php if ($client['last_service']): ?>
                                        <?php echo formatDate($client['last_service']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="clients.php?action=edit&id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="services.php?client_id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-success" title="View Services">
                                            <i class="bi bi-gear"></i>
                                        </a>
                                        <?php if ($client['service_count'] == 0): ?>
                                        <a href="clients.php?action=delete&id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this client?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Client Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil'; ?>"></i>
                        <?php echo $action === 'add' ? 'Add New Client' : 'Edit Client'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="clientForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($client['name'] ?? ''); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Please enter the client's full name.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($client['contact_number'] ?? ''); ?>" 
                                       required maxlength="20">
                                <div class="invalid-feedback">
                                    Please enter a valid contact number.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" 
                                       maxlength="100">
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                    <option value="">Select Vehicle Type</option>
                                    <option value="Sedan" <?php echo ($client['vehicle_type'] ?? '') === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                                    <option value="SUV" <?php echo ($client['vehicle_type'] ?? '') === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                                    <option value="Hatchback" <?php echo ($client['vehicle_type'] ?? '') === 'Hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                                    <option value="Truck" <?php echo ($client['vehicle_type'] ?? '') === 'Truck' ? 'selected' : ''; ?>>Truck</option>
                                    <option value="Van" <?php echo ($client['vehicle_type'] ?? '') === 'Van' ? 'selected' : ''; ?>>Van</option>
                                    <option value="Coupe" <?php echo ($client['vehicle_type'] ?? '') === 'Coupe' ? 'selected' : ''; ?>>Coupe</option>
                                    <option value="Convertible" <?php echo ($client['vehicle_type'] ?? '') === 'Convertible' ? 'selected' : ''; ?>>Convertible</option>
                                    <option value="Other" <?php echo ($client['vehicle_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a vehicle type.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="license_plate" class="form-label">License Plate <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="license_plate" name="license_plate" 
                                   value="<?php echo htmlspecialchars($client['license_plate'] ?? ''); ?>" 
                                   required maxlength="20" style="text-transform: uppercase;">
                            <div class="invalid-feedback">
                                Please enter the vehicle's license plate number.
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="clients.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'check'; ?>"></i>
                                <?php echo $action === 'add' ? 'Add Client' : 'Update Client'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Form validation and formatting
    document.getElementById('clientForm').addEventListener('submit', function(event) {
        if (!validateForm('clientForm')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // Auto-uppercase license plate
    document.getElementById('license_plate').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>