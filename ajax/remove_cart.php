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

// Remove from cart
$delete_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
$delete_stmt->bind_param("ii", $user_id, $product_id);
if ($delete_stmt->execute()) {
    $success = true;
    $message = 'Removed from cart';
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove from cart']);
    exit;
}

// Get updated cart count
$count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$cart_count = $count_result['total'] ?? 0;

echo json_encode([
    'success' => true,
    'message' => 'Removed from cart successfully!',
    'cart_count' => $cart_count
]);

$conn->close();
?>