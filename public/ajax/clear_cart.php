<?php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Clear cart
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
}

$conn->close();
?>