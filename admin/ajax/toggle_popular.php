<?php
require "../includes/admin_db.php";

if (!isset($_POST['id'], $_POST['status'])) {
    http_response_code(400);
    exit;
}

$id = (int)$_POST['id'];
$status = (int)$_POST['status'];

$stmt = $conn->prepare("
    UPDATE products
    SET is_popular = ?
    WHERE id = ?
");
$stmt->bind_param("ii", $status, $id);
$stmt->execute();

echo "ok";
$stmt->close();
?>