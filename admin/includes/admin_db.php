<?php


// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'proglide';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Check admin authentication
function checkAdminAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
        header("Location: login.php");
        exit();
    }
}

// Admin logout
function adminLogout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>