<?php
/**
 * Login Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            if ($conn) {
                // Prepare statement to prevent SQL injection
                $stmt = $conn->prepare("SELECT id, username, password, role, full_name, email FROM users WHERE username = ? AND password IS NOT NULL");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                // Verify password using bcrypt
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful - create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Log the login activity
                    logActivity('User Login', "User {$username} logged in successfully");
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = 'Invalid username or password.';
                    logActivity('Failed Login Attempt', "Failed login for username: {$username}");
                }
            } else {
                $error_message = 'Database connection failed. Please try again later.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = 'An error occurred during login. Please try again.';
        }
    }
}

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success_message = 'You have been successfully logged out.';
}

$page_title = 'Login';
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-car-front display-4 text-primary"></i>
                    <h2 class="mt-3">Car Wash Control System</h2>
                    <p class="text-muted">Please sign in to your account</p>
                </div>
                
                <?php if ($error_message): ?>
                    <?php showError($error_message); ?>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <?php showSuccess($success_message); ?>
                <?php endif; ?>
                
                <form method="POST" action="login.php" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                                   required maxlength="50" autocomplete="username">
                            <div class="invalid-feedback">
                                Please enter your username.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required autocomplete="current-password">
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <strong>Demo Credentials:</strong><br>
                        Admin: admin / admin123<br>
                        Staff: staff1 / admin123
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('loginForm').addEventListener('submit', function(event) {
    if (!validateForm('loginForm')) {
        event.preventDefault();
        event.stopPropagation();
    }
});

// Focus on username field when page loads
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
});
</script>

<?php include 'includes/footer.php'; ?>