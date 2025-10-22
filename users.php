<?php
/**
 * User Management Module (Admin Only)
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Require admin authentication
requireAdmin();

$page_title = 'User Management';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

$error_message = '';
$success_message = '';
$users = [];
$user = null;

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
                $username = sanitizeInput($_POST['username'] ?? '');
                $full_name = sanitizeInput($_POST['full_name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $role = sanitizeInput($_POST['role'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validate required fields
                if (empty($username) || empty($full_name) || empty($email) || empty($role)) {
                    $error_message = 'Please fill in all required fields.';
                } elseif (!validateEmail($email)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (!in_array($role, ['admin', 'staff'])) {
                    $error_message = 'Invalid role selected.';
                } elseif ($action === 'add' && (empty($password) || strlen($password) < 6)) {
                    $error_message = 'Password must be at least 6 characters long.';
                } elseif ($action === 'add' && $password !== $confirm_password) {
                    $error_message = 'Passwords do not match.';
                } elseif (!empty($password) && $password !== $confirm_password) {
                    $error_message = 'Passwords do not match.';
                } else {
                    if ($action === 'add') {
                        // Check if username or email already exists
                        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $stmt->execute([$username, $email]);
                        
                        if ($stmt->fetch()) {
                            $error_message = 'Username or email already exists.';
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert new user
                            $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, ?, ?, ?)");
                            
                            if ($stmt->execute([$username, $hashed_password, $role, $full_name, $email])) {
                                $success_message = 'User added successfully!';
                                logActivity('User Added', "New user: {$username} ({$role})");
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to add user. Please try again.';
                            }
                        }
                    } elseif ($action === 'edit' && $user_id) {
                        // Check if username or email exists for other users
                        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                        $stmt->execute([$username, $email, $user_id]);
                        
                        if ($stmt->fetch()) {
                            $error_message = 'Username or email already exists for another user.';
                        } else {
                            // Update user
                            if (!empty($password)) {
                                // Update with new password
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ?, full_name = ?, email = ? WHERE id = ?");
                                $result = $stmt->execute([$username, $hashed_password, $role, $full_name, $email, $user_id]);
                            } else {
                                // Update without changing password
                                $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, full_name = ?, email = ? WHERE id = ?");
                                $result = $stmt->execute([$username, $role, $full_name, $email, $user_id]);
                            }
                            
                            if ($result) {
                                $success_message = 'User updated successfully!';
                                logActivity('User Updated', "Updated user: {$username} ({$role})");
                                $action = 'list'; // Redirect to list view
                            } else {
                                $error_message = 'Failed to update user. Please try again.';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("User management error: " . $e->getMessage());
            $error_message = 'An error occurred. Please try again.';
        }
    }
}

// Handle delete action
if ($action === 'delete' && $user_id && $conn) {
    try {
        // Prevent deleting self
        if ($user_id == $_SESSION['user_id']) {
            $error_message = 'You cannot delete your own account.';
        } else {
            // Get user info for logging
            $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_info = $stmt->fetch();
            
            if ($user_info) {
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $success_message = 'User deleted successfully!';
                    logActivity('User Deleted', "Deleted user: {$user_info['username']} ({$user_info['role']})");
                } else {
                    $error_message = 'Failed to delete user. Please try again.';
                }
            } else {
                $error_message = 'User not found.';
            }
        }
    } catch (Exception $e) {
        error_log("User delete error: " . $e->getMessage());
        $error_message = 'An error occurred while deleting user.';
    }
    $action = 'list'; // Redirect to list view
}

// Fetch data based on action
if ($conn) {
    try {
        if ($action === 'list') {
            // Get all users
            $stmt = $conn->prepare("SELECT id, username, role, full_name, email, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
        } elseif ($action === 'edit' && $user_id) {
            // Get specific user for editing
            $stmt = $conn->prepare("SELECT id, username, role, full_name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error_message = 'User not found.';
                $action = 'list';
            }
        }
    } catch (Exception $e) {
        error_log("User fetch error: " . $e->getMessage());
        $error_message = 'An error occurred while loading user data.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-gear"></i> 
        <?php 
        switch($action) {
            case 'add': echo 'Add New User'; break;
            case 'edit': echo 'Edit User'; break;
            default: echo 'User Management'; break;
        }
        ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($action === 'list'): ?>
        <div class="btn-group me-2">
            <a href="users.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New User
            </a>
        </div>
        <?php else: ?>
        <div class="btn-group me-2">
            <a href="users.php" class="btn btn-secondary">
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
    <!-- User List View -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">System Users (<?php echo count($users); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-people display-4 text-muted"></i>
                    <p class="text-muted mt-2">No users found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user_row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user_row['username']); ?></strong>
                                    <?php if ($user_row['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-info ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user_row['full_name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user_row['email']); ?>">
                                        <?php echo htmlspecialchars($user_row['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user_row['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user_row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user_row['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="users.php?action=edit&id=<?php echo $user_row['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user_row['id'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?action=delete&id=<?php echo $user_row['id']; ?>" 
                                           class="btn btn-outline-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this user?')">
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
    <!-- Add/Edit User Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil'; ?>"></i>
                        <?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="userForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                                       required maxlength="50" autocomplete="username">
                                <div class="invalid-feedback">
                                    Please enter a username.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="staff" <?php echo ($user['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a role.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Please enter the full name.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                       required maxlength="100" autocomplete="email">
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    Password 
                                    <?php if ($action === 'add'): ?>
                                        <span class="text-danger">*</span>
                                    <?php else: ?>
                                        <small class="text-muted">(leave blank to keep current)</small>
                                    <?php endif; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $action === 'add' ? 'required' : ''; ?> 
                                       minlength="6" autocomplete="new-password">
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters long.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    Confirm Password
                                    <?php if ($action === 'add'): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       <?php echo $action === 'add' ? 'required' : ''; ?> 
                                       minlength="6" autocomplete="new-password">
                                <div class="invalid-feedback">
                                    Passwords must match.
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($action === 'edit'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Leave password fields blank if you don't want to change the password.
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between">
                            <a href="users.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'check'; ?>"></i>
                                <?php echo $action === 'add' ? 'Add User' : 'Update User'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Form validation and password matching
    document.getElementById('userForm').addEventListener('submit', function(event) {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        // Check if passwords match
        if (password !== confirmPassword) {
            document.getElementById('confirm_password').setCustomValidity('Passwords do not match');
        } else {
            document.getElementById('confirm_password').setCustomValidity('');
        }
        
        if (!validateForm('userForm')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // Real-time password matching validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        var password = document.getElementById('password').value;
        var confirmPassword = this.value;
        
        if (password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>