<?php
session_start();
require "../includes/admin_db.php";

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit;
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    exit;
}

$id = (int)$_POST['id'];

/* Toggle status */
$conn->query("
    UPDATE phone_models
    SET status = IF(status='active','hidden','active')
    WHERE id = $id
");

/* Send new status back */
$res = $conn->query("SELECT status FROM phone_models WHERE id=$id");
$row = $res->fetch_assoc();

echo json_encode([
    "success" => true,
    "status"  => $row['status']
]);
