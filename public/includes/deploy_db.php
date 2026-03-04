<?php
$host = "sql101.infinityfree.com";   // Correct Host
$user = "if0_41213194";
$pass = "siva9342573137";
$db   = "if0_41213194_proglide";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>