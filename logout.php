<?php
/**
 * Logout Module
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';

// Log the logout activity before destroying session
if (isLoggedIn()) {
    logActivity('User Logout', "User {$_SESSION['username']} logged out");
}

// Destroy session and redirect to login
session_start();
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>