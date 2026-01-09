<?php
include "../includes/admin_db.php";

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("INSERT IGNORE INTO brands (name) VALUES (?)");
$stmt->bind_param("s", $name);
$stmt->execute();
?>