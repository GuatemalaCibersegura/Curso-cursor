<?php
/**
 * User Profile Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require authentication
requireLogin();

$page_title = 'My Profile';

$error_message = '';
$success_message = '';
$user = null;

// Database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    $error_message = 'Database connection failed.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            // Sanitize input data
            $full_name = sanitizeInput($_POST['full_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate required fields
            if (empty($full_name) || empty($email)) {
                $error_message = 'Please fill in all required fields.';
            } elseif (!validateEmail($email)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                // Check if email exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $error_message = 'Email already exists for another user.';
                } else {
                    // Handle password change if provided
                    $password_update = '';
                    $params = [$full_name, $email, $_SESSION['user_id']];
                    
                    if (!empty($new_password)) {
                        if (empty($current_password)) {
                            $error_message = 'Please enter your current password to change password.';
                        } elseif (strlen($new_password) < 6) {
                            $error_message = 'New password must be at least 6 characters long.';
                        } elseif ($new_password !== $confirm_password) {
                            $error_message = 'New passwords do not match.';
                        } else {
                            // Verify current password
                            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $current_user = $stmt->fetch();
                            
                            if (!password_verify($current_password, $current_user['password'])) {
                                $error_message = 'Current password is incorrect.';
                            } else {
                                $password_update = ', password = ?';
                                array_splice($params, -1, 0, [password_hash($new_password, PASSWORD_DEFAULT)]);
                            }
                        }
                    }
                    
                    if (empty($error_message)) {
                        // Update user profile
                        $sql = "UPDATE users SET full_name = ?, email = ?{$password_update} WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt->execute($params)) {
                            $success_message = 'Profile updated successfully!';
                            
                            // Update session data
                            $_SESSION['full_name'] = $full_name;
                            $_SESSION['email'] = $email;
                            
                            logActivity('Profile Updated', 'User updated their profile information');
                            
                            if (!empty($new_password)) {
                                $success_message .= ' Password has been changed.';
                            }
                        } else {
                            $error_message = 'Failed to update profile. Please try again.';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Fetch current user data
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT username, full_name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Profile fetch error: " . $e->getMessage());
        $error_message = 'An error occurred while loading profile data.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-person-circle"></i> My Profile</h1>
</div>

<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php showSuccess($success_message); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <!-- Profile Summary Card -->
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-1 text-primary"></i>
                <h4 class="mt-3"><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown'); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?></p>
                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> mb-2">
                    <?php echo ucfirst($user['role'] ?? 'Unknown'); ?>
                </span>
                <p class="text-muted small">
                    Member since <?php echo $user['created_at'] ? formatDate($user['created_at']) : 'Unknown'; ?>
                </p>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <?php
                // Get user activity stats
                $stats = [];
                if ($conn) {
                    try {
                        // Get services added by this user (if they added any)
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM services WHERE DATE(created_at) = CURDATE()");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        $stats['today_services'] = $result['count'];
                        
                        // Get total clients
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM clients");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        $stats['total_clients'] = $result['count'];
                        
                        // Get today's income
                        $stmt = $conn->prepare("SELECT COALESCE(SUM(cost), 0) as income FROM services WHERE service_date = CURDATE()");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        $stats['today_income'] = $result['income'];
                    } catch (Exception $e) {
                        // Ignore errors for stats
                    }
                }
                ?>
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="text-primary"><?php echo $stats['today_services'] ?? 0; ?></h4>
                        <small class="text-muted">Today's Services</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-info"><?php echo $stats['total_clients'] ?? 0; ?></h4>
                        <small class="text-muted">Total Clients</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-success"><?php echo formatCurrency($stats['today_income'] ?? 0); ?></h4>
                        <small class="text-muted">Today's Income</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Profile Update Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-pencil"></i> Update Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="profileForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Account Information -->
                    <h6 class="border-bottom pb-2 mb-3">Account Information</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                                   disabled>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?php echo ucfirst($user['role'] ?? ''); ?>" 
                                   disabled>
                            <div class="form-text">Role is assigned by administrators</div>
                        </div>
                    </div>
                    
                    <!-- Personal Information -->
                    <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                   required maxlength="100">
                            <div class="invalid-feedback">
                                Please enter your full name.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                   required maxlength="100">
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Change -->
                    <h6 class="border-bottom pb-2 mb-3">Change Password (Optional)</h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Leave password fields blank if you don't want to change your password.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" 
                                   autocomplete="current-password">
                            <div class="invalid-feedback">
                                Please enter your current password.
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" autocomplete="new-password">
                            <div class="invalid-feedback">
                                Password must be at least 6 characters long.
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" autocomplete="new-password">
                            <div class="invalid-feedback">
                                Passwords must match.
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and password matching
document.getElementById('profileForm').addEventListener('submit', function(event) {
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    var currentPassword = document.getElementById('current_password').value;
    
    // If changing password, validate all password fields
    if (newPassword || confirmPassword || currentPassword) {
        if (!currentPassword) {
            document.getElementById('current_password').setCustomValidity('Current password is required to change password');
        } else {
            document.getElementById('current_password').setCustomValidity('');
        }
        
        if (newPassword !== confirmPassword) {
            document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
        } else {
            document.getElementById('confirm_password').setCustomValidity('');
        }
    } else {
        // Clear custom validity if not changing password
        document.getElementById('current_password').setCustomValidity('');
        document.getElementById('confirm_password').setCustomValidity('');
    }
    
    if (!validateForm('profileForm')) {
        event.preventDefault();
        event.stopPropagation();
    }
});

// Real-time password matching validation
document.getElementById('confirm_password').addEventListener('input', function() {
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = this.value;
    
    if (newPassword && confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Clear password validation when new password is cleared
document.getElementById('new_password').addEventListener('input', function() {
    var confirmPassword = document.getElementById('confirm_password');
    if (!this.value) {
        confirmPassword.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>