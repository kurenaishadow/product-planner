<?php
require_once 'config.php';

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        log_activity($_SESSION['user_id'], 'logout', 'User logged out', $db);
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: login.php?logout=1');
exit();
?>