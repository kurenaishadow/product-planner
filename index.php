<?php
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
} else {
    // Not logged in, go to login page
    header('Location: login.php');
}
exit();
?>