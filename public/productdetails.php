<?php
// productdetails.php
session_start();
require "includes/db.php";

$product_id = $_GET['id'] ?? 0;
$select_model = isset($_GET['select_model']);

if (!$product_id) {
    header("Location: products.php");
    exit;
}

// Get product details
$sql = "SELECT 
            p.*,
            c.name as category_name,
            c.id as category_id,
            c.slug as category_slug,
            mt.name as material_name,
            vt.name as variant_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN material_types mt ON p.material_type_id = mt.id
        LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
        WHERE p.id = ? AND p.status = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit;
}

$is_back_case = ($product['category_id'] == 2);
$user_id = $_SESSION['user_id'] ?? null;

// For back cases - get all cart items with their models
$cart_models = []; // Stores model_ids that are in cart
$cart_items = [];

if ($user_id && $is_back_case) {
    $cart_stmt = $conn->prepare("SELECT id, quantity, phone_model_id FROM cart WHERE user_id = ? AND product_id = ?");
    $cart_stmt->bind_param("ii", $user_id, $product_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    while ($row = $cart_result->fetch_assoc()) {
        $cart_models[] = $row['phone_model_id'];
        $cart_items[] = $row;
    }
}

// For non-backcase products
$in_cart = false;
$in_cart_quantity = 0;
if ($user_id && !$is_back_case) {
    $cart_stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $cart_stmt->bind_param("ii", $user_id, $product_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_result->num_rows > 0) {
        $in_cart = true;
        $in_cart_quantity = $cart_result->fetch_assoc()['quantity'];
    }
}

// Check wishlist
$in_wishlist = false;
if ($user_id) {
    $wish_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wish_stmt->bind_param("ii", $user_id, $product_id);
    $wish_stmt->execute();
    $in_wishlist = $wish_stmt->get_result()->num_rows > 0;
}

// Get all brands for dropdown
$brands = [];
$brand_sql = "SELECT id, name FROM brands WHERE status = 1 ORDER BY name";
$brand_result = $conn->query($brand_sql);
while ($row = $brand_result->fetch_assoc()) {
    $brands[] = $row;
}

// Get all phone models for JavaScript
$all_models = [];
$models_sql = "SELECT pm.id, pm.model_name, b.id as brand_id, b.name as brand_name 
               FROM phone_models pm 
               JOIN brands b ON pm.brand_id = b.id 
               WHERE pm.status = 1 AND b.status = 1 
               ORDER BY b.name, pm.model_name";
$models_result = $conn->query($models_sql);
while ($row = $models_result->fetch_assoc()) {
    $all_models[] = $row;
}

// Get similar products
$similar_sql = "SELECT 
                    p.*,
                    c.name as category_name,
                    c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.id != ? AND p.status = 1
                ORDER BY p.is_popular DESC, p.created_at DESC
                LIMIT 10";
$similar_stmt = $conn->prepare($similar_sql);
$similar_stmt->bind_param("ii", $product['category_id'], $product_id);
$similar_stmt->execute();
$similar_products = $similar_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function for images
function getProductImagePath($category_slug, $image_name) {
    if (empty($image_name)) return "/proglide/assets/no-image.png";
    
    $new_path = "/proglide/uploads/products/" . $category_slug . "/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $new_path)) return $new_path;
    
    $old_path = "/proglide/uploads/products/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $old_path)) return $old_path;
    
    return "/proglide/assets/no-image.png";
}

// Get images
$images = [];
$category_slug = $product['category_slug'] ?? 'general';
foreach (['image1', 'image2', 'image3'] as $img) {
    if (!empty($product[$img])) {
        $images[] = getProductImagePath($category_slug, $product[$img]);
    }
}
if (empty($images)) $images[] = "/proglide/assets/no-image.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($product['design_name'] ?? $product['model_name'] ?? 'Product Details'); ?> | PROGLIDE</title>
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
/* ============================================
   PRODUCT DETAILS PAGE - FLIPKART STYLE MOBILE OPTIMIZED
   ============================================ */

:root {
    --primary: #FF6B35;
    --primary-dark: #e55a2b;
    --primary-light: rgba(255, 107, 53, 0.1);
    --secondary: #FF8E53;
    --dark-bg: #0a0a0a;
    --dark-card: #1a1a1a;
    --dark-border: #2a2a2a;
    --dark-hover: #252525;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --text-muted: #808080;
    --success: #4CAF50;
    --warning: #FFC107;
    --danger: #F44336;
    --info: #2196F3;
    --flipkart-blue: #2874f0;
    --flipkart-yellow: #faa722;
    --flipkart-green: #388e3c;
    --flipkart-orange: #fb641b;
    --radius: 16px;
    --radius-sm: 12px;
    --radius-lg: 20px;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    --shadow-sm: 0 4px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 15px 40px rgba(255, 107, 53, 0.25);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--dark-bg);
    color: var(--text-primary);
    line-height: 1.5;
    overflow-x: hidden;
    padding-top: 80px;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Back Link - Mobile Optimized */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    margin-bottom: 15px;
    padding: 10px 16px;
    border-radius: 40px;
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    transition: var(--transition);
    font-size: 0.9rem;
    min-height: 44px;
}

.back-link:active {
    background: var(--flipkart-blue);
    color: white;
    border-color: var(--flipkart-blue);
    transform: translateX(-3px);
}

.back-link i {
    font-size: 0.9rem;
}

/* Product Details Card - Mobile First */
.product-details {
    background: var(--dark-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--dark-border);
    overflow: hidden;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
}

/* ============================================
   IMAGE GALLERY - MOBILE OPTIMIZED
   ============================================ */
.product-images {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.main-image {
    width: 100%;
    aspect-ratio: 1/1;
    border-radius: var(--radius);
    overflow: hidden;
    /* background: linear-gradient(145deg, var(--dark-hover), var(--dark-card)); */
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 1px solid var(--dark-border);
    
}

.main-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.main-image:active img {
    transform: scale(1.05);
}

.thumbnail-images {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.thumbnail-images::-webkit-scrollbar {
    display: none;
}

.thumbnail {
    flex: 0 0 70px;
    height: 70px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    background: var(--dark-hover);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.thumbnail:active {
    border-color: var(--flipkart-blue);
    transform: scale(0.95);
}

.thumbnail.active {
    border-color: var(--flipkart-blue);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* ============================================
   PRODUCT INFO - MOBILE OPTIMIZED
   ============================================ */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.product-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.3;
}

.product-category {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--primary-light);
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 0.85rem;
    color: var(--primary);
    font-weight: 600;
    width: fit-content;
}

/* Price Section - Flipkart Style */
.price-section {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--dark-border);
    border-top: 1px solid var(--dark-border);
}

.current-price {
    font-size: 2rem;
    font-weight: 800;
    color: var(--success);
}

.original-price {
    font-size: 1.1rem;
    color: var(--text-muted);
    text-decoration: line-through;
}

.discount-badge {
    background: var(--flipkart-yellow);
    color: white;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 700;
}

/* Quantity Selector - Mobile Optimized */
.quantity-selector {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.qty-label {
    color: var(--text-secondary);
    font-weight: 600;
    font-size: 0.95rem;
}

.qty-controls {
    display: flex;
    align-items: center;
    border: 2px solid var(--dark-border);
    border-radius: 50px;
    overflow: hidden;
    background: var(--dark-hover);
    width: fit-content;
}

.qty-btn {
    width: 44px;
    height: 44px;
    background: var(--dark-hover);
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary);
    transition: var(--transition);
}

.qty-btn:active:not(:disabled) {
    background: var(--flipkart-blue);
    color: white;
    transform: scale(0.95);
}

.qty-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.qty-input {
    width: 60px;
    height: 44px;
    border: none;
    border-left: 2px solid var(--dark-border);
    border-right: 2px solid var(--dark-border);
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
    background: var(--dark-card);
    color: var(--text-primary);
}

/* Product Specs - Flipkart Style */
.product-specs {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px;
    background: var(--dark-hover);
    border-radius: var(--radius);
    border: 1px solid var(--dark-border);
}

.spec-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 0;
}

.spec-icon {
    width: 36px;
    height: 36px;
    background: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--flipkart-blue);
    font-size: 1rem;
}

.spec-label {
    min-width: 90px;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.spec-value {
    flex: 1;
    color: var(--text-primary);
    font-size: 0.95rem;
    font-weight: 500;
}

/* ============================================
   MODEL SELECTION - FLIPKART STYLE
   ============================================ */
.model-selection-section {
    padding: 20px;
    background: var(--dark-hover);
    border-radius: var(--radius);
    border-left: 4px solid var(--flipkart-blue);
    margin: 10px 0;
}

.model-selection-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.model-selection-header h3 {
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.model-selection-header h3 i {
    color: var(--flipkart-blue);
}

.cart-count-badge {
    background: var(--flipkart-blue);
    color: white;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 600;
}

.model-select-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.select-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.select-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 600;
}

.model-select, .model-search {
    width: 100%;
    padding: 14px 16px;
    background: var(--dark-card);
    border: 2px solid var(--dark-border);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 0.95rem;
    min-height: 50px;
}

.search-container {
    position: relative;
    margin-top: auto !important;   
}

.model-search {
    padding-left: 45px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.models-list {
    max-height: 300px;
    overflow-y: auto;
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    border-radius: var(--radius-sm);
    margin-top: 5px;
    display: none;
    position: absolute;
    width: 100%;
    z-index: 100;
}

.models-list.active {
    display: block;
}

.model-option {
    padding: 14px 16px;
    border-bottom: 1px solid var(--dark-border);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: var(--transition);
}

.model-option:active {
    background: var(--primary-light);
}

.model-option.selected {
    background: var(--primary-light);
    border-left: 4px solid var(--flipkart-blue);
}

.model-option .in-cart-icon {
    margin-left: auto;
    color: var(--flipkart-green);
}

/* Selected Models Grid */
.selected-models-section {
    margin-top: 20px;
    border-top: 1px solid var(--dark-border);
    padding-top: 16px;
}

.selected-models-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.selected-models-title i {
    color: var(--flipkart-green);
}

.selected-models-title h4 {
    font-size: 1rem;
}

.selected-models-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.selected-model-item {
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    border-radius: var(--radius-sm);
    padding: 12px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.model-info {
    flex: 1;
    min-width: 140px;
}

.model-brand {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: block;
    margin-bottom: 2px;
}

.model-name {
    font-weight: 600;
    font-size: 0.95rem;
}

.model-qty {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--dark-hover);
    padding: 6px 10px;
    border-radius: 30px;
    border: 1px solid var(--dark-border);
}

.model-qty span {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
}

.qty-change-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.qty-change-btn:active {
    background: var(--flipkart-blue);
    color: white;
}

.remove-model-btn {
    background: none;
    border: none;
    color: var(--danger);
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-left: 5px;
}

.remove-model-btn:active {
    background: var(--danger);
    color: white;
}

/* Description */
.description {
    padding: 20px;
    background: var(--dark-hover);
    border-radius: var(--radius);
    font-size: 0.95rem;
    line-height: 1.7;
}

.description p {
    color: var(--text-secondary);
}

/* ============================================
   ACTION BUTTONS - FLIPKART STYLE
   ============================================ */
.action-row {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 15px;
    width: 100%;
}

.action-btn {
    min-height: 54px;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    transition: transform 0.1s ease, background 0.2s ease;
    padding: 0 20px;
    width: 100%;
}

.action-btn:active {
    transform: scale(0.97);
}

.btn-cart {
    background: linear-gradient(135deg, var(--flipkart-orange), #ff8a4f);
    color: white;
    box-shadow: 0 4px 15px rgba(251, 100, 27, 0.3);
}

.btn-cart:active {
    background: linear-gradient(135deg, #e55a2b, #ff6b35);
}

.btn-cart.in-cart {
    background: linear-gradient(135deg, var(--flipkart-green), #45a049);
}

.btn-buy {
    background: linear-gradient(135deg, var(--flipkart-blue), #1a5dc7);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 116, 240, 0.3);
}

.btn-buy.view-cart {
    background: linear-gradient(135deg, #8a4fff, #6b3fcc);
}

.btn-wishlist {
    background: transparent;
    color: var(--text-primary);
    border: 2px solid var(--dark-border);
}

.btn-wishlist:active {
    background: var(--dark-hover);
}

.btn-wishlist.in-wishlist {
    background: linear-gradient(135deg, #ff4d4d, #ff1a1a);
    border-color: transparent;
}

/* In Cart Message */
.in-cart-message {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid var(--flipkart-green);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--flipkart-green);
    font-size: 0.95rem;
}

.in-cart-message i {
    font-size: 1.2rem;
}

/* ============================================
   MOBILE STICKY BOTTOM BAR - FLIPKART STYLE
   ============================================ */
.mobile-sticky-buttons {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--dark-card);
    backdrop-filter: blur(10px);
    padding: 12px 15px;
    box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    border-top: 1px solid var(--dark-border);
    gap: 10px;
}

.mobile-sticky-btn {
    flex: 1;
    min-height: 48px;
    border-radius: 50px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
    padding: 0 8px;
}

.mobile-sticky-btn:active {
    transform: scale(0.96);
}

.mobile-sticky-btn.buy {
    background: linear-gradient(135deg, var(--flipkart-blue), #1a5dc7);
}

.mobile-sticky-btn.buy.view-cart {
    background: linear-gradient(135deg, #8a4fff, #6b3fcc);
}

.mobile-sticky-btn.cart {
    background: linear-gradient(135deg, var(--flipkart-orange), #ff8a4f);
}

.mobile-sticky-btn.cart.in-cart {
    background: linear-gradient(135deg, var(--flipkart-green), #45a049);
}

.mobile-sticky-btn.wishlist {
    background: transparent;
    border: 2px solid var(--dark-border);
    color: var(--text-primary);
}

.mobile-sticky-btn.wishlist.in-wishlist {
    background: linear-gradient(135deg, #ff4d4d, #ff1a1a);
    border-color: transparent;
}

/* ============================================
   SIMILAR PRODUCTS - HORIZONTAL SCROLL
   ============================================ */
.similar-products {
    margin-top: 30px;
    background: var(--dark-card);
    border-radius: var(--radius);
    padding: 20px 15px;
    border: 1px solid var(--dark-border);
    margin-bottom: 80px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.section-header h3 {
    font-size: 1.2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header h3 i {
    color: var(--flipkart-blue);
}

.view-all-link {
    color: var(--flipkart-blue);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 8px 14px;
    background: rgba(40, 116, 240, 0.1);
    border-radius: 30px;
    min-height: 40px;
    display: flex;
    align-items: center;
}

.view-all-link:active {
    background: var(--flipkart-blue);
    color: white;
}

.similar-scroll-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.similar-scroll-container::-webkit-scrollbar {
    display: none;
}

.similar-scroll-wrapper {
    display: flex;
    gap: 15px;
    width: max-content;
    padding-bottom: 5px;
}

.similar-card {
    width: 160px;
    background: var(--dark-hover);
    border-radius: var(--radius);
    border: 1px solid var(--dark-border);
    overflow: hidden;
    cursor: pointer;
}

.similar-card:active {
    transform: scale(0.98);
}

.similar-image-container {
    width: 100%;
    aspect-ratio: 1/1;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark-card);
}

.similar-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.similar-info {
    padding: 12px;
}

.similar-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 42px;
}

.similar-price {
    color: var(--flipkart-orange);
    font-weight: 700;
    font-size: 0.95rem;
}

/* Notification */
.notification {
    position: fixed;
    top: 80px;
    left: 15px;
    right: 15px;
    background: var(--dark-card);
    border-left: 4px solid var(--flipkart-green);
    border-radius: var(--radius);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-primary);
    box-shadow: var(--shadow);
    z-index: 9999;
    transform: translateY(-20px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    border: 1px solid var(--dark-border);
    max-width: 400px;
    margin: 0 auto;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.notification.error {
    border-left-color: var(--danger);
}

.notification i {
    font-size: 1.2rem;
}

/* Loading Spinner */
.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ============================================
   TABLET & DESKTOP OVERRIDES
   ============================================ */
@media (min-width: 576px) {
    .action-row {
        flex-direction: row;
    }
    
    .selected-models-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    body {
        padding-top: 90px;
    }
    
    .container {
        padding: 0 20px;
    }
    
    .product-details {
        padding: 30px;
    }
    
    .product-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    
    .product-title {
        font-size: 2rem;
    }
    
    .current-price {
        font-size: 2.2rem;
    }
    
    .thumbnail {
        flex: 0 0 90px;
        height: 90px;
    }
    
    .selected-models-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .similar-card {
        width: 200px;
    }
    
    .mobile-sticky-buttons {
        display: none !important;
    }
    
    .action-row {
        display: flex !important;
    }
}

@media (min-width: 992px) {
    .product-title {
        font-size: 2.2rem;
    }
    
    .current-price {
        font-size: 2.5rem;
    }
    
    .selected-models-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .similar-card {
        width: 220px;
    }
}

@media (min-width: 1200px) {
    .container {
        padding: 0 30px;
    }
    
    .selected-models-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

/* ============================================
   MOBILE ONLY (UP TO 767px)
   ============================================ */
@media (max-width: 767px) {
    body {
        padding-bottom: 90px;
    }
    
    .product-layout {
        display: block;
    }
    
    .desktop-only {
        display: none !important;
    }
    
    .mobile-sticky-buttons {
        display: flex;
    }
    
    .action-row {
        display: none;
    }
    
    .product-title {
        font-size: 1.5rem;
    }
    
    .current-price {
        font-size: 1.8rem;
    }
    
    .price-section {
        padding: 10px 0;
    }
    
    .spec-item {
        flex-wrap: wrap;
    }
    
    .spec-label {
        min-width: 80px;
    }
    
    .selected-models-grid {
        grid-template-columns: 1fr;
    }
    
    .selected-model-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .model-info {
        width: 100%;
    }
    
    .model-qty {
        align-self: flex-start;
    }
    
    .similar-card {
        width: 150px;
    }
    
    .similar-title {
        font-size: 0.85rem;
        min-height: 38px;
    }
}

/* Small Mobile */
@media (max-width: 375px) {
    .product-title {
        font-size: 1.4rem;
    }
    
    .current-price {
        font-size: 1.6rem;
    }
    
    .qty-btn {
        width: 40px;
        height: 40px;
    }
    
    .qty-input {
        width: 50px;
        height: 40px;
    }
    
    .mobile-sticky-btn {
        font-size: 0.8rem;
        padding: 0 5px;
    }
    
    .mobile-sticky-btn i {
        font-size: 0.9rem;
    }
    
    .similar-card {
        width: 140px;
    }
    
    .similar-title {
        font-size: 0.8rem;
        min-height: 36px;
    }
}

/* Extra Small Mobile */
@media (max-width: 320px) {
    .mobile-sticky-btn span {
        display: none;
    }
    
    .mobile-sticky-btn i {
        font-size: 1.1rem;
    }
    
    .model-qty {
        width: 100%;
        justify-content: center;
    }
    
    .remove-model-btn {
        width: 100%;
        margin-left: 0;
        margin-top: 5px;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .back-link:hover,
    .thumbnail:hover,
    .action-btn:hover,
    .view-all-link:hover,
    .similar-card:hover {
        transform: none;
    }
    
    .qty-btn:hover:not(:disabled) {
        background: var(--dark-hover);
    }
}
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="container">
        <a href="products.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Products</a>

        <div class="product-details">
            <div class="product-layout">
                <!-- Images -->
                <div class="product-images">
                    <div class="main-image" id="mainImage">
                        <img id="currentImage" src="<?= $images[0] ?>" alt="Product" onerror="this.src='/proglide/assets/no-image.png';">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="thumbnail <?= $i === 0 ? 'active' : '' ?>" data-image="<?= $img ?>">
                                <img src="<?= $img ?>" alt="Thumbnail" onerror="this.src='/proglide/assets/no-image.png';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <h1 class="product-title"><?= htmlspecialchars($product['design_name'] ?? $product['model_name'] ?? 'Product') ?></h1>
                    <div class="product-category"><i class="fas fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?></div>

                    <div class="price-section">
                        <span class="current-price">₹<?= number_format($product['price'], 2) ?></span>
                        <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                            <span class="original-price">₹<?= number_format($product['original_price'], 2) ?></span>
                            <span class="discount-badge"><?= round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <!-- Specs -->
                    <div class="product-specs">
                        <?php if (!empty($product['material_name'])): ?>
                            <div class="spec-item"><div class="spec-icon"><i class="fas fa-gem"></i></div><span class="spec-label">Material:</span><span class="spec-value"><?= htmlspecialchars($product['material_name']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($product['variant_name'])): ?>
                            <div class="spec-item"><div class="spec-icon"><i class="fas fa-palette"></i></div><span class="spec-label">Variant:</span><span class="spec-value"><?= htmlspecialchars($product['variant_name']) ?></span></div>
                        <?php endif; ?>
                        <div class="spec-item"><div class="spec-icon"><i class="fas fa-box"></i></div><span class="spec-label">Availability:</span><span class="spec-value">In Stock</span></div>
                    </div>

                    <!-- Model Selection for Back Cases - Multiple Models Support -->
                    <?php if ($is_back_case): ?>
                        <div class="model-selection-section">
                            <div class="model-selection-header">
                                <h3><i class="fas fa-mobile-alt"></i> Select Phone Models</h3>
                                <span class="cart-count-badge" id="cartModelCount"><?= count($cart_models) ?> in cart</span>
                            </div>
                            
                            <div class="model-select-grid">
                                <div class="select-group">
                                    <label class="select-label">Brand</label>
                                    <select class="model-select" id="brandSelect">
                                        <option value="">Select Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="select-group search-container">
                                    <label class="select-label">Search Model</label>
                                    <input type="text" class="model-search" id="modelSearch" placeholder="Type to search..." disabled>
                                    <i class="fas fa-search search-icon"></i>
                                    <div class="models-list" id="modelsList"></div>
                                </div>
                            </div>

                            <!-- Selected Models List -->
                            <div class="selected-models-section" id="selectedModelsSection" style="<?= empty($cart_models) ? 'display:none;' : '' ?>">
                                <div class="selected-models-title">
                                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                    <h4>Models in Your Cart</h4>
                                </div>
                                <div class="selected-models-grid" id="selectedModelsGrid">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($product['description'])): ?>
                        <div class="description"><p><?= nl2br(htmlspecialchars($product['description'])) ?></p></div>
                    <?php endif; ?>

                    <!-- Desktop Buttons -->
                    <div class="action-row">
                        <?php if ($is_back_case): ?>
                            <button class="action-btn btn-half btn-cart" id="addToCartBtnDesktop">
                                <i class="fas fa-plus-circle"></i> <span>ADD MODEL TO CART</span>
                            </button>
                        <?php else: ?>
                            <button class="action-btn btn-half btn-cart <?= $in_cart ? 'in-cart' : '' ?>" id="addToCartBtnDesktop">
                                <i class="fas <?= $in_cart ? 'fa-check-circle' : 'fa-shopping-cart' ?>"></i> 
                                <span><?= $in_cart ? 'IN CART' : 'ADD TO CART' ?></span>
                            </button>
                        <?php endif; ?>
                        
                        <button class="action-btn btn-half btn-wishlist <?= $in_wishlist ? 'in-wishlist' : '' ?>" id="wishlistBtnDesktop">
                            <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i> 
                            <span><?= $in_wishlist ? 'IN WISHLIST' : 'WISHLIST' ?></span>
                        </button>
                    </div>
                    
                    <div class="action-row">
                        <button class="action-btn btn-full btn-buy <?= (!empty($cart_models) || $in_cart) ? 'view-cart' : '' ?>" id="buyNowBtnDesktop">
                            <i class="fas <?= (!empty($cart_models) || $in_cart) ? 'fa-eye' : 'fa-bolt' ?>"></i> 
                            <span><?= (!empty($cart_models) || $in_cart) ? 'VIEW CART' : 'BUY NOW' ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sticky Buttons -->
        <div class="mobile-sticky-buttons">
            <button class="mobile-sticky-btn wishlist <?= $in_wishlist ? 'in-wishlist' : '' ?>" id="wishlistBtnMobile"><i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i></button>
            
            <?php if ($is_back_case): ?>
                <button class="mobile-sticky-btn cart" id="addToCartBtnMobile">
                    <i class="fas fa-plus-circle"></i> <span>ADD</span>
                </button>
            <?php else: ?>
                <button class="mobile-sticky-btn cart <?= $in_cart ? 'in-cart' : '' ?>" id="addToCartBtnMobile">
                    <i class="fas <?= $in_cart ? 'fa-check-circle' : 'fa-shopping-cart' ?>"></i> <span><?= $in_cart ? 'IN CART' : 'ADD' ?></span>
                </button>
            <?php endif; ?>
            
            <button class="mobile-sticky-btn buy <?= (!empty($cart_models) || $in_cart) ? 'view-cart' : '' ?>" id="buyNowBtnMobile">
                <i class="fas <?= (!empty($cart_models) || $in_cart) ? 'fa-eye' : 'fa-bolt' ?>"></i> <span><?= (!empty($cart_models) || $in_cart) ? 'VIEW' : 'BUY' ?></span>
            </button>
        </div>

        <!-- Similar Products -->
        <?php if (!empty($similar_products)): ?>
        <div class="similar-products">
            <div class="section-header">
                <h3><i class="fas fa-layer-group"></i> You May Also Like</h3>
                <a href="products.php?cat=<?= $product['category_id'] ?>" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="similar-scroll-container">
                <div class="similar-scroll-wrapper">
                    <?php foreach ($similar_products as $similar): 
                        $similar_name = $similar['design_name'] ?? $similar['model_name'] ?? 'Product';
                        $similar_image = getProductImagePath($similar['category_slug'] ?? 'general', $similar['image1'] ?? '');
                    ?>
                    <div class="similar-card" onclick="window.location.href='productdetails.php?id=<?= $similar['id'] ?>'">
                        <div class="similar-image-container"><img src="<?= $similar_image ?>" alt="" class="similar-image" onerror="this.src='/proglide/assets/no-image.png';"></div>
                        <div class="similar-info">
                            <h4 class="similar-title"><?= htmlspecialchars($similar_name) ?></h4>
                            <div class="similar-price">₹<?= number_format($similar['price'], 2) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Thumbnail clicks
        document.querySelectorAll('.thumbnail').forEach(t => {
            t.addEventListener('click', function() {
                document.querySelectorAll('.thumbnail').forEach(th => th.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('currentImage').src = this.dataset.image;
            });
        });

        // State
        const isBackCase = <?= $is_back_case ? 'true' : 'false' ?>;
        const productId = <?= $product_id ?>;
        const allModels = <?= json_encode($all_models) ?>;
        let cartModels = <?= json_encode($cart_models) ?>;
        let cartItems = <?= json_encode($cart_items) ?>;
        let selectedModelId = null;
        let selectedModelName = null;
        let selectedBrandName = null;
        
        // DOM Elements
        const brandSelect = document.getElementById('brandSelect');
        const modelSearch = document.getElementById('modelSearch');
        const modelsList = document.getElementById('modelsList');
        const selectedModelsSection = document.getElementById('selectedModelsSection');
        const selectedModelsGrid = document.getElementById('selectedModelsGrid');
        const cartModelCount = document.getElementById('cartModelCount');
        
        // Buttons
        const addCartDesktop = document.getElementById('addToCartBtnDesktop');
        const buyDesktop = document.getElementById('buyNowBtnDesktop');
        const wishDesktop = document.getElementById('wishlistBtnDesktop');
        const addCartMobile = document.getElementById('addToCartBtnMobile');
        const buyMobile = document.getElementById('buyNowBtnMobile');
        const wishMobile = document.getElementById('wishlistBtnMobile');

        // Initialize - show selected models if any
        if (isBackCase && cartItems.length > 0) {
            renderSelectedModels();
        }

        // Brand change
        if (brandSelect) {
            brandSelect.addEventListener('change', function() {
                const brandId = this.value;
                selectedModelId = null;
                selectedModelName = null;
                selectedBrandName = this.options[this.selectedIndex]?.text;
                
                if (brandId) {
                    modelSearch.disabled = false;
                    modelSearch.value = '';
                    filterModelsByBrand(brandId);
                } else {
                    modelSearch.disabled = true;
                    modelsList.classList.remove('active');
                }
            });
        }

        // Model search
        if (modelSearch) {
            modelSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const brandId = brandSelect.value;
                
                if (!brandId) {
                    modelsList.innerHTML = '<div class="model-option">Select a brand first</div>';
                    modelsList.classList.add('active');
                    return;
                }
                
                const filtered = allModels.filter(m => 
                    m.brand_id == brandId && 
                    m.model_name.toLowerCase().includes(searchTerm)
                );
                displayModels(filtered);
            });
        }

        function filterModelsByBrand(brandId) {
            const filtered = allModels.filter(m => m.brand_id == brandId);
            displayModels(filtered);
        }

        function displayModels(models) {
            modelsList.innerHTML = '';
            
            if (models.length === 0) {
                modelsList.innerHTML = '<div class="model-option">No models found</div>';
            } else {
                models.forEach(model => {
                    const inCart = cartModels.includes(model.id);
                    const opt = document.createElement('div');
                    opt.className = 'model-option' + (inCart ? ' selected' : '');
                    opt.innerHTML = `
                        <i class="fas fa-mobile-alt"></i>
                        <span>${model.model_name}</span>
                        ${inCart ? '<i class="fas fa-check-circle in-cart-icon"></i>' : ''}
                    `;
                    opt.dataset.id = model.id;
                    opt.dataset.name = model.model_name;
                    
                    opt.addEventListener('click', function() {
                        if (inCart) {
                            showNotification('This model is already in your cart', 'error');
                            return;
                        }
                        
                        document.querySelectorAll('.model-option').forEach(o => o.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedModelId = this.dataset.id;
                        selectedModelName = this.dataset.name;
                        modelSearch.value = selectedModelName;
                        modelsList.classList.remove('active');
                        
                        // Enable add to cart for this model
                        if (addCartDesktop) addCartDesktop.disabled = false;
                        if (addCartMobile) addCartMobile.disabled = false;
                    });
                    
                    modelsList.appendChild(opt);
                });
            }
            
            modelsList.classList.add('active');
        }

        // Add model to cart
        function addModelToCart() {
            if (!selectedModelId) {
                showNotification('Please select a model first', 'error');
                return;
            }
            
            const modelId = selectedModelId;
            const modelName = selectedModelName;
            
            // Find brand name
            const model = allModels.find(m => m.id == modelId);
            const brandName = model ? model.brand_name : '';
            
            let url = `ajax/add_to_cart.php?id=${productId}&qty=1&model_id=${modelId}`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Add to cartModels array
                        cartModels.push(parseInt(modelId));
                        cartItems.push({
                            phone_model_id: parseInt(modelId),
                            quantity: 1
                        });
                        
                        // Update UI
                        renderSelectedModels();
                        updateCartCount(data.cart_count);
                        
                        // Clear selection
                        selectedModelId = null;
                        selectedModelName = null;
                        modelSearch.value = '';
                        brandSelect.value = '';
                        modelSearch.disabled = true;
                        modelsList.classList.remove('active');
                        
                        // Update buy button
                        updateBuyButton(true);
                        
                        showNotification(`Added ${brandName} ${modelName} to cart`, 'success');
                    } else {
                        showNotification(data.message || 'Failed to add', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('Connection error', 'error');
                });
        }

        // Remove model from cart
        function removeModelFromCart(modelId) {
            let url = `ajax/remove_cart.php?product_id=${productId}&model_id=${modelId}`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Remove from arrays
                        cartModels = cartModels.filter(id => id != modelId);
                        cartItems = cartItems.filter(item => item.phone_model_id != modelId);
                        
                        // Update UI
                        renderSelectedModels();
                        updateCartCount(data.cart_count);
                        
                        // Update buy button
                        updateBuyButton(cartModels.length > 0);
                        
                        showNotification('Model removed from cart', 'success');
                    } else {
                        showNotification(data.message || 'Failed to remove', 'error');
                    }
                });
        }

        // Update model quantity
        function updateModelQuantity(modelId, delta) {
            const item = cartItems.find(i => i.phone_model_id == modelId);
            if (!item) return;
            
            const newQty = item.quantity + delta;
            if (newQty < 1 || newQty > 10) return;
            
            // Update via AJAX (you'll need to implement this endpoint)
            let url = `ajax/update_cart_qty.php?product_id=${productId}&model_id=${modelId}&qty=${newQty}`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        item.quantity = newQty;
                        renderSelectedModels();
                        updateCartCount(data.cart_count);
                    }
                });
        }

        // Render selected models grid
        function renderSelectedModels() {
            if (cartItems.length === 0) {
                selectedModelsSection.style.display = 'none';
                if (cartModelCount) cartModelCount.innerText = '0 in cart';
                return;
            }
            
            selectedModelsSection.style.display = 'block';
            if (cartModelCount) cartModelCount.innerText = `${cartItems.length} in cart`;
            
            let html = '';
            cartItems.forEach(item => {
                const model = allModels.find(m => m.id == item.phone_model_id);
                if (!model) return;
                
                html += `
                    <div class="selected-model-item" data-model-id="${model.id}">
                        <div class="model-info">
                            <span class="model-brand">${model.brand_name}</span>
                            <span class="model-name">${model.model_name}</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div class="model-qty">
                                <button class="qty-change-btn" onclick="window.updateModelQuantity(${model.id}, -1)"><i class="fas fa-minus"></i></button>
                                <span>${item.quantity}</span>
                                <button class="qty-change-btn" onclick="window.updateModelQuantity(${model.id}, 1)"><i class="fas fa-plus"></i></button>
                            </div>
                            <button class="remove-model-btn" onclick="window.removeModelFromCart(${model.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            selectedModelsGrid.innerHTML = html;
        }

        // Update buy button based on cart status
        function updateBuyButton(hasItems) {
            [buyDesktop, buyMobile].forEach(btn => {
                if (btn) {
                    btn.innerHTML = `<i class="fas ${hasItems ? 'fa-eye' : 'fa-bolt'}"></i> <span>${hasItems ? (btn.id.includes('Mobile') ? 'VIEW' : 'VIEW CART') : (btn.id.includes('Mobile') ? 'BUY' : 'BUY NOW')}</span>`;
                    btn.classList.toggle('view-cart', hasItems);
                }
            });
        }

        // For non-backcase products
        function toggleRegularCart(action, button) {
            let qty = document.getElementById('quantity')?.value || 1;
            let url = `ajax/toggle_cart.php?id=${productId}&qty=${qty}&action=${action}`;

            let original = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            fetch(url).then(r => r.json()).then(d => {
                if (d.success) {
                    let inCart = action === 'add';
                    showNotification(d.message, 'success');
                    
                    [addCartDesktop, addCartMobile].forEach(btn => {
                        if (btn) {
                            btn.innerHTML = `<i class="fas ${inCart ? 'fa-check-circle' : 'fa-shopping-cart'}"></i> <span>${inCart ? 'IN CART' : (btn.id.includes('Mobile') ? 'ADD' : 'ADD TO CART')}</span>`;
                            btn.classList.toggle('in-cart', inCart);
                        }
                    });
                    
                    updateBuyButton(inCart);
                } else {
                    button.innerHTML = original;
                    if (d.redirect) window.location.href = d.redirect;
                    else showNotification(d.message || 'Error', 'error');
                }
            }).catch(() => { button.innerHTML = original; showNotification('Connection error', 'error'); })
            .finally(() => button.disabled = false);
        }

        // Wishlist toggle
        function toggleWishlist(button) {
            let inWish = button.classList.contains('in-wishlist');
            let url = inWish ? 'ajax/remove_wishlist.php' : 'ajax/add_to_wishlist.php';
            let fd = new FormData(); fd.append('product_id', productId);
            
            let original = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            fetch(url, { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if (d.success) {
                    showNotification(d.message, 'success');
                    [wishDesktop, wishMobile].forEach(btn => {
                        if (btn) {
                            let icon = !inWish ? 'fas' : 'far';
                            btn.innerHTML = `<i class="${icon} fa-heart"></i>` + (btn.id.includes('Desktop') ? ` <span>${!inWish ? 'IN WISHLIST' : 'WISHLIST'}</span>` : '');
                            btn.classList.toggle('in-wishlist', !inWish);
                        }
                    });
                } else {
                    button.innerHTML = original;
                    showNotification(d.message || 'Error', 'error');
                }
            }).catch(() => { button.innerHTML = original; showNotification('Connection error', 'error'); })
            .finally(() => button.disabled = false);
        }

        // Buy now / view cart
        function handleBuy(button) {
            if (button.classList.contains('view-cart') || (isBackCase && cartItems.length > 0) || (!isBackCase && button.classList.contains('in-cart'))) {
                window.location.href = 'cart.php';
                return;
            }
            
            let qty = document.getElementById('quantity')?.value || 1;
            
            if (isBackCase) {
                if (cartItems.length === 0) {
                    showNotification('Add at least one model to cart first', 'error');
                    return;
                }
                // For back cases, checkout with first model? Or show cart
                window.location.href = 'cart.php';
            } else {
                window.location.href = `checkout.php?buy_now=1&id=${productId}&qty=${qty}`;
            }
        }

        // Attach event listeners
        if (isBackCase) {
            if (addCartDesktop) addCartDesktop.addEventListener('click', e => { e.preventDefault(); addModelToCart(); });
            if (addCartMobile) addCartMobile.addEventListener('click', e => { e.preventDefault(); addModelToCart(); });
        } else {
            if (addCartDesktop) addCartDesktop.addEventListener('click', e => { e.preventDefault(); toggleRegularCart(addCartDesktop.classList.contains('in-cart') ? 'remove' : 'add', addCartDesktop); });
            if (addCartMobile) addCartMobile.addEventListener('click', e => { e.preventDefault(); toggleRegularCart(addCartMobile.classList.contains('in-cart') ? 'remove' : 'add', addCartMobile); });
        }

        if (wishDesktop) wishDesktop.addEventListener('click', e => { e.preventDefault(); toggleWishlist(wishDesktop); });
        if (wishMobile) wishMobile.addEventListener('click', e => { e.preventDefault(); toggleWishlist(wishMobile); });
        if (buyDesktop) buyDesktop.addEventListener('click', e => { e.preventDefault(); handleBuy(buyDesktop); });
        if (buyMobile) buyMobile.addEventListener('click', e => { e.preventDefault(); handleBuy(buyMobile); });

        // Expose functions globally for inline handlers
        window.removeModelFromCart = removeModelFromCart;
        window.updateModelQuantity = updateModelQuantity;

        // Notification
        function showNotification(msg, type = 'success') {
            let n = document.getElementById('notification');
            n.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i><span>${msg}</span>`;
            n.className = `notification ${type}`;
            n.classList.add('show');
            setTimeout(() => n.classList.remove('show'), 3000);
        }

        window.showNotification = showNotification;

        // Image error handling
        document.querySelectorAll('img').forEach(i => i.addEventListener('error', function() { if (!this.dataset.error) { this.dataset.error = '1'; this.src = '/proglide/assets/no-image.png'; } }));

        // Update cart count function
        function updateCartCount(count) {
            document.querySelectorAll('.cart-count').forEach(el => {
                if (el) {
                    el.textContent = count;
                    el.style.display = 'flex';
                }
            });
        }
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>