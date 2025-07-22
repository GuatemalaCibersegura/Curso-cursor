<?php
/**
 * Index/Home Page
 * Car Wash Client Platform Control System
 */

require_once 'includes/functions.php';

// TEMPORAL: Omitir login y ir directo al dashboard
header("Location: dashboard.php");
exit();

// Redirect based on authentication status
// if (isLoggedIn()) {
//     // User is logged in, redirect to dashboard
//     header("Location: dashboard.php");
//     exit();
// } else {
//     // User is not logged in, redirect to login
//     header("Location: login.php");
//     exit();
// }
?>