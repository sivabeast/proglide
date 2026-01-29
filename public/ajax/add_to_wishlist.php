<?php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check if product exists
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
if (!$stmt->get_result()->num_rows) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Add to wishlist
$insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
$insert_stmt->bind_param("ii", $user_id, $product_id);
$insert_stmt->execute();

echo json_encode(['success' => true, 'message' => 'Added to wishlist']);

$conn->close();
?>