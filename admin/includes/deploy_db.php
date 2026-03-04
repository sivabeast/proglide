<?php
// Database configuration (InfinityFree)

define('DB_HOST', 'sql101.infinityfree.com');  // MySQL Hostname
define('DB_USER', 'if0_41213194');             // MySQL Username
define('DB_PASS', 'siva9342573137');           // MySQL Password
define('DB_NAME', 'if0_41213194_proglide');    // Database Name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>