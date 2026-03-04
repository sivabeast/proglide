<?php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login', 'redirect' => 'login.php']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($_GET['id'] ?? 0);
$quantity = min(10, max(1, intval($_GET['qty'] ?? 1)));
$action = $_GET['action'] ?? 'add';
$model_id = isset($_GET['model_id']) ? intval($_GET['model_id']) : null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// For back cases, check by model
if ($action === 'remove') {
    // Remove from cart
    if ($model_id) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND phone_model_id = ?");
        $stmt->bind_param("iii", $user_id, $product_id, $model_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
    }
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
    exit;
}

// Add to cart - check if exists
if ($model_id) {
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND phone_model_id = ?");
    $check->bind_param("iii", $user_id, $product_id, $model_id);
} else {
    $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND phone_model_id IS NULL");
    $check->bind_param("ii", $user_id, $product_id);
}
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    // Update
    $new_qty = $existing['quantity'] + $quantity;
    if ($new_qty > 10) {
        echo json_encode(['success' => false, 'message' => 'Maximum 10 items allowed']);
        exit;
    }
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $new_qty, $existing['id']);
    $update->execute();
    $message = 'Cart updated';
} else {
    // Insert
    $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, phone_model_id, quantity) VALUES (?, ?, ?, ?)");
    $insert->bind_param("iiii", $user_id, $product_id, $model_id, $quantity);
    $insert->execute();
    $message = 'Added to cart';
}

echo json_encode(['success' => true, 'message' => $message]);
$conn->close();
?>