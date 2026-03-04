<?php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['product_id'] ?? 0;
$model_id = $_GET['model_id'] ?? null;
$quantity = $_GET['qty'] ?? 1;

if (!$product_id || !$model_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$quantity = min(10, max(1, intval($quantity)));

// Update quantity
$update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND phone_model_id = ?");
$update_stmt->bind_param("iiii", $quantity, $user_id, $product_id, $model_id);

if ($update_stmt->execute()) {
    // Get updated cart count
    $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $cart_count = $count_result['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated',
        'cart_count' => $cart_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update quantity']);
}

$conn->close();
?>