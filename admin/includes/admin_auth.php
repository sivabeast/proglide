<?php
// Admin authentication check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Optional: Check if admin is active
if (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] != 1) {
    session_destroy();
    header("Location: login.php?error=inactive");
    exit;
}
?>