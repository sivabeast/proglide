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
$quantity = $_POST['quantity'] ?? 1;
$phone_model_id = $_POST['phone_model_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check if product exists and is active
$stmt = $conn->prepare("SELECT id, price, category_id FROM products WHERE id = ? AND status = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not available']);
    exit;
}

// For back cases, check if model is selected
if ($product['category_id'] == 2 && !$phone_model_id) { // Back Case requires model
    echo json_encode(['success' => false, 'message' => 'Please select phone model for back case']);
    exit;
}

// Set phone_model_id to NULL for non-back case products
if ($product['category_id'] != 2) {
    $phone_model_id = null;
}

// Check if product already in cart
$check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$check_params = [$user_id, $product_id];
$check_types = "ii";

if ($phone_model_id !== null) {
    $check_sql .= " AND phone_model_id = ?";
    $check_params[] = $phone_model_id;
    $check_types .= "i";
} else {
    $check_sql .= " AND phone_model_id IS NULL";
}

$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param($check_types, ...$check_params);
$check_stmt->execute();
$existing = $check_stmt->get_result()->fetch_assoc();

if ($existing) {
    // Update quantity
    $new_qty = $existing['quantity'] + $quantity;
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_qty, $existing['id']);
    $update_stmt->execute();
} else {
    // Insert new
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, phone_model_id, quantity) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("iiii", $user_id, $product_id, $phone_model_id, $quantity);
    $insert_stmt->execute();
}

// Get updated cart count
$count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$cart_count = $count_result['total'] ?? 0;

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cart_count' => $cart_count
]);

$conn->close();
?>