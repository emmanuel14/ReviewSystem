<?php
require_once 'config.php';

// Log the logout activity if user is logged in
if (is_logged_in()) {
    $conn = getDBConnection();
    log_activity($conn, $_SESSION['user_name'], 'Logout', 'User logged out');
    $conn->close();
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>