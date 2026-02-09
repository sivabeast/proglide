<?php
// ajax/add_to_cart.php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please login to add items to cart',
        'redirect' => 'login.php'
    ]);
    exit;
}

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($_GET['id']);
$quantity = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;
$phone_model_id = isset($_GET['model_id']) && is_numeric($_GET['model_id']) ? intval($_GET['model_id']) : null;

// Validate maximum quantity
if ($quantity > 10) {
    echo json_encode(['success' => false, 'message' => 'Maximum quantity per order is 10']);
    exit;
}

// Check if product exists and is active
$check_stmt = $conn->prepare("
    SELECT p.id, p.price, p.original_price, p.image1, p.model_name, p.design_name,
           c.name as category_name
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 1 AND c.status = 1
");

$check_stmt->bind_param("i", $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product is not available']);
    exit;
}

$product = $check_result->fetch_assoc();

// Check if product with same model already in cart
$cart_stmt = $conn->prepare("
    SELECT id, quantity 
    FROM cart 
    WHERE user_id = ? AND product_id = ? 
    AND (phone_model_id = ? OR (? IS NULL AND phone_model_id IS NULL))
");

$cart_stmt->bind_param("iiii", $user_id, $product_id, $phone_model_id, $phone_model_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity
    $row = $cart_result->fetch_assoc();
    $new_quantity = $row['quantity'] + $quantity;
    
    // Check if new quantity exceeds maximum (10)
    if ($new_quantity > 10) {
        echo json_encode([
            'success' => false, 
            'message' => 'Maximum 10 items per product in cart'
        ]);
        exit;
    }
    
    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $new_quantity, $row['id']);
    
    if ($update_stmt->execute()) {
        // Get updated cart count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM cart WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $cart_count = $count_result->fetch_assoc()['item_count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
} else {
    // Insert new item
    $insert_stmt = $conn->prepare("
        INSERT INTO cart (user_id, product_id, phone_model_id, quantity) 
        VALUES (?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("iiii", $user_id, $product_id, $phone_model_id, $quantity);
    
    if ($insert_stmt->execute()) {
        // Get updated cart count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as item_count FROM cart WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $cart_count = $count_result->fetch_assoc()['item_count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
}

$conn->close();
?>