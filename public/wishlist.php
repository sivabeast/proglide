<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   FETCH WISHLIST
========================= */
$watchlist = [];

$sql = "
SELECT 
    p.id,
    p.model_name,
    p.design_name,
    p.price,
    p.original_price,
    p.image1 AS image,
    p.is_popular,
    p.category_id,
    cat.name AS main_category,
    cat.slug AS category_slug,
    mt.name AS material_name,
    vt.name AS variant_name
FROM wishlist w
JOIN products p ON p.id = w.product_id
JOIN categories cat ON cat.id = p.category_id
LEFT JOIN material_types mt ON p.material_type_id = mt.id
LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
WHERE w.user_id = ?
ORDER BY w.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $watchlist[] = $row;
}

// Handle Clear All Wishlist request
if (isset($_POST['clear_wishlist'])) {
    $clear_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $clear_stmt->bind_param("i", $user_id);
    if ($clear_stmt->execute()) {
        header("Location: wishlist.php?cleared=1");
        exit;
    }
}

// Handle Remove Single Item via GET
if (isset($_GET['remove_id'])) {
    $remove_id = (int)$_GET['remove_id'];
    $remove_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $remove_stmt->bind_param("ii", $user_id, $remove_id);
    if ($remove_stmt->execute()) {
        header("Location: wishlist.php?removed=1");
        exit;
    }
}

/* =========================
   HELPERS - FIXED IMAGE FUNCTION
========================= */
function productImage($slug, $img) {
    if (!$img) return "/proglide/assets/no-image.png";
    
    // Correct path: uploads/products/{category-slug}/{image}
    $base = "/proglide/uploads/products/";
    $path = $base . $slug . '/' . $img;
    
    // Check if image exists
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        // Fallback to old structure if exists
        $old_path = $base . $img;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $old_path)) {
            return $old_path;
        }
        return "/proglide/assets/no-image.png";
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Wishlist | PROGLIDE</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">
<style>
/* ============================================
   PROGLIDE - WISHLIST PAGE WITH INDEX.PHP STYLE CARDS
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
    --radius: 12px;
    --radius-sm: 8px;
    --radius-lg: 16px;
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
    --shadow-hover: 0 8px 25px rgba(255, 107, 53, 0.2);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
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
    padding: 0 20px;
}

/* ============================================
   HERO SECTION - DESKTOP ONLY (LIKE INDEX)
   ============================================ */
.wishlist-hero {
    display: block;
    background: linear-gradient(145deg, #0f172a, #1e293b);
    color: white;
    padding: 30px 0 50px;
    position: relative;
    overflow: hidden;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .wishlist-hero {
        display: none;
    }
}

.wishlist-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 20px;
}

.wishlist-badge i {
    color: var(--danger);
}

.welcome-text {
    background: rgba(255,255,255,0.05);
    padding: 12px 20px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.welcome-text i {
    color: var(--primary);
}

.welcome-text strong {
    color: white;
}

.wishlist-title {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
}

.wishlist-title span {
    color: var(--danger);
}

.wishlist-subtitle {
    font-size: 1.1rem;
    color: #94a3b8;
    margin-bottom: 30px;
    max-width: 500px;
}

.wishlist-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 500px;
    margin-top: 40px;
}

.stat-card {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 20px 15px;
    text-align: center;
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-info {
    text-align: left;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    display: block;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ============================================
   CONTENT SECTION
   ============================================ */
.content-section {
    padding: 0 0 40px 0;
}

/* Section Header */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 0 5px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: var(--danger);
    font-size: 1.2rem;
}

.clear-all-btn {
    background: rgba(244, 67, 54, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
    padding: 10px 20px;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.clear-all-btn:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
}

/* ============================================
   ALERTS
   ============================================ */
.alert {
    padding: 15px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
    border: 1px solid transparent;
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    border-color: var(--success);
    color: var(--success);
}

.alert-danger {
    background: rgba(244, 67, 54, 0.1);
    border-color: var(--danger);
    color: var(--danger);
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* ============================================
   EMPTY STATE
   ============================================ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--dark-card);
    border-radius: var(--radius-lg);
    border: 1px solid var(--dark-border);
    max-width: 500px;
    margin: 40px auto;
}

.empty-icon {
    width: 100px;
    height: 100px;
    background: var(--dark-hover);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
    color: var(--text-muted);
    border: 2px solid var(--dark-border);
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 25px;
}

.browse-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--primary);
    color: white;
    padding: 12px 30px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
}

.browse-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
}

/* ============================================
   PRODUCTS GRID - INDEX.PHP STYLE CARDS
   ============================================ */
.products-section {
    margin-bottom: 40px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    margin-bottom: 40px;
}

/* Product Card - Index.php Style */
.product-card {
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
    cursor: pointer;
    box-shadow: var(--shadow-sm);
}

.product-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary);
    box-shadow: var(--shadow-hover);
}

/* Product Image */
.product-image-container {
    position: relative;
    width: 100%;
    padding-top: 80%;
    background: white;
    overflow: hidden;
}

.product-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

/* Category Badge - Like Material Badge */
.category-badge {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: var(--dark-card);
    color: var(--text-secondary);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 5;
    border: 1px solid var(--dark-border);
    backdrop-filter: blur(5px);
}

/* Popular Badge */
.popular-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(135deg, var(--warning), #ff9800);
    color: #000;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 5;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.popular-badge i {
    font-size: 0.65rem;
}

/* Product Info */
.product-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.product-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 48px;
    line-height: 1.4;
}

.product-title a {
    color: var(--text-primary);
    text-decoration: none;
    transition: var(--transition);
}

.product-title a:hover {
    color: var(--primary);
}

/* Product Category - Like Material */
.product-category {
    font-size: 0.75rem;
    color: var(--text-secondary);
    background: var(--dark-hover);
    padding: 4px 8px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 8px;
    align-self: flex-start;
    border: 1px solid var(--dark-border);
}

/* Price Section */
.price-section {
    margin: 8px 0 12px;
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
    min-height: 48px;
}

.current-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
}

.original-price {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-decoration: line-through;
}

.discount {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--success);
    background: rgba(76, 175, 80, 0.1);
    padding: 3px 8px;
    border-radius: 20px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    margin-top: auto;
    width: 100%;
}

.btn-remove,
.btn-cart {
    flex: 1;
    padding: 12px 8px;
    border-radius: var(--radius-sm);
    border: none;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-remove {
    background: rgba(244, 67, 54, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn-remove:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
}

.btn-cart {
    background: var(--primary);
    color: white;
}

.btn-cart:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.select-model-btn {
    background: linear-gradient(135deg, var(--info), #1976d2);
}

.select-model-btn:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0);
}

/* ============================================
   RECOMMENDED SECTION
   ============================================ */
.recommended-section {
    background: var(--dark-card);
    padding: 30px 20px;
    border-radius: var(--radius-lg);
    margin: 40px 0;
    border: 1px solid var(--dark-border);
}

.recommended-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.recommended-card {
    background: var(--dark-hover);
    border-radius: var(--radius);
    padding: 25px 15px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid var(--dark-border);
}

.recommended-card:hover {
    border-color: var(--warning);
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.recommended-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 193, 7, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: var(--warning);
    font-size: 1.4rem;
}

.recommended-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.recommended-text {
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.5;
    margin: 0;
}

/* ============================================
   TOAST NOTIFICATION
   ============================================ */
.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    background: var(--dark-card);
    border-left: 4px solid var(--success);
    border-radius: var(--radius);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    color: var(--text-primary);
    box-shadow: var(--shadow);
    transform: translateX(120%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border: 1px solid var(--dark-border);
}

.toast-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-icon i {
    font-size: 1.3rem;
    color: var(--success);
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 700;
    margin-bottom: 4px;
}

.toast-message {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */

/* Desktop Large */
@media (min-width: 1400px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }
}

/* Desktop */
@media (min-width: 1200px) and (max-width: 1399px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
    }
}

/* Small Desktop */
@media (min-width: 992px) and (max-width: 1199px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
    }
    
    .recommended-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 991px) {
    body {
        padding-top: 70px;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .recommended-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .wishlist-title {
        font-size: 2.5rem;
    }
    
    .wishlist-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mobile */
@media (max-width: 767px) {
    body {
        padding-top: 60px;
        padding-bottom: 70px;
    }
    
    .container {
        padding: 0 12px;
    }
    
    .wishlist-hero {
        display: none;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .clear-all-btn {
        width: 100%;
        justify-content: center;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .product-body {
        padding: 12px;
    }
    
    .product-title {
        font-size: 0.9rem;
        min-height: 42px;
    }
    
    .product-category {
        font-size: 0.7rem;
        padding: 3px 6px;
    }
    
    .price-section {
        margin: 6px 0 10px;
        min-height: 42px;
    }
    
    .current-price {
        font-size: 1rem;
    }
    
    .original-price {
        font-size: 0.7rem;
    }
    
    .discount {
        font-size: 0.6rem;
        padding: 2px 6px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 6px;
    }
    
    .btn-remove,
    .btn-cart {
        padding: 10px 6px;
        font-size: 0.75rem;
        min-height: 40px;
    }
    
    .btn-remove i,
    .btn-cart i {
        font-size: 0.75rem;
    }
    
    .recommended-section {
        padding: 20px 15px;
    }
    
    .recommended-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .recommended-card {
        padding: 20px 10px;
    }
    
    .recommended-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
        margin-bottom: 12px;
    }
    
    .recommended-title {
        font-size: 0.9rem;
    }
    
    .recommended-text {
        font-size: 0.7rem;
    }
    
    .toast-notification {
        left: 15px;
        right: 15px;
        bottom: 80px;
        min-width: auto;
        max-width: none;
    }
}

/* Small Mobile */
@media (max-width: 375px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .product-body {
        padding: 10px;
    }
    
    .product-title {
        font-size: 0.85rem;
        min-height: 38px;
    }
    
    .current-price {
        font-size: 0.9rem;
    }
    
    .btn-remove,
    .btn-cart {
        padding: 8px 4px;
        font-size: 0.7rem;
        min-height: 38px;
    }
    
    /* Hide text on very small screens */
    .btn-remove span,
    .btn-cart span {
        display: none;
    }
    
    .btn-remove i,
    .btn-cart i {
        font-size: 0.8rem;
        margin: 0;
    }
    
    .popular-badge {
        padding: 4px 8px;
        font-size: 0.6rem;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .product-card:hover,
    .recommended-card:hover {
        transform: none;
    }
    
    .btn-remove:hover,
    .btn-cart:hover {
        transform: none;
    }
    
    .btn-remove:active,
    .btn-cart:active {
        transform: scale(0.97);
    }
    
    .product-card:active {
        transform: scale(0.98);
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-card {
    animation: fadeIn 0.5s ease;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.fa-spinner {
    animation: spin 1s linear infinite;
}
</style>
</head>
<body>

<?php include "includes/header.php"; ?>

<!-- ================= HERO SECTION - DESKTOP ONLY ================= -->
<section class="wishlist-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="wishlist-badge">
                    <i class="fas fa-heart"></i>
                    Your Personal Collection
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->bind_result($user_name);
                    $stmt->fetch();
                    $stmt->close();
                    ?>
                    <div class="welcome-text">
                        <i class="fas fa-hand-sparkles"></i>
                        Welcome back, <strong><?= htmlspecialchars($user_name) ?></strong>!
                    </div>
                <?php endif; ?>
                
                <h1 class="wishlist-title">
                    My <span>Wishlist</span>
                </h1>
                
                <p class="wishlist-subtitle">
                    Your saved favorites, ready to explore. Add to cart or remove items you no longer need.
                </p>
                
                <div class="wishlist-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?= count($watchlist) ?></div>
                            <div class="stat-label">Items Saved</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number">₹<?php 
                                $total = 0;
                                foreach($watchlist as $item) {
                                    $total += $item['price'];
                                }
                                echo number_format($total, 0);
                            ?></div>
                            <div class="stat-label">Total Value</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number"><?php
                                $categories = [];
                                foreach($watchlist as $item) {
                                    if (!in_array($item['main_category'], $categories)) {
                                        $categories[] = $item['main_category'];
                                    }
                                }
                                echo count($categories);
                            ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= CONTENT SECTION ================= -->
<section class="content-section">
    <div class="container">
        <?php 
        // Show success messages
        if (isset($_GET['removed'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Item removed from wishlist successfully!
                  </div>';
        }
        if (isset($_GET['cleared'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Wishlist cleared successfully!
                  </div>';
        }
        if (isset($_GET['added_to_cart'])) {
            echo '<div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Item added to cart successfully!
                  </div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ' . htmlspecialchars($_GET['error']) . '
                  </div>';
        }
        ?>
        
        <?php if(empty($watchlist)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="far fa-heart"></i>
                </div>
                <h3>Your Wishlist is Empty</h3>
                <p>Start adding products you love to your wishlist to see them here</p>
                <a href="products.php" class="browse-btn">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="products-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-heart" style="color: var(--danger);"></i>
                        Saved Items (<?= count($watchlist) ?>)
                    </h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.');">
                        <button type="submit" name="clear_wishlist" class="clear-all-btn">
                            <i class="fas fa-trash-alt"></i> Clear All
                        </button>
                    </form>
                </div>
                
                <div class="products-grid">
                    <?php foreach($watchlist as $item):
                        $isBackCase = strtolower($item['main_category']) === 'back cases' || $item['category_id'] == 2;
                        
                        // Determine product name based on category
                        if ($item['category_id'] == 1) { // Protectors
                            $product_name = trim(($item['model_name'] ?? 'Protector') . 
                                ($item['variant_name'] ? " ({$item['variant_name']})" : ""));
                        } elseif ($item['category_id'] == 2) { // Back Cases
                            $product_name = $item['design_name'] ?? 'Back Case';
                        } else {
                            $product_name = $item['model_name'] ?? $item['main_category'];
                        }
                        
                        $image_path = productImage($item['category_slug'], $item['image']);
                        
                        // Calculate discount if available
                        $discount = 0;
                        if ($item['original_price'] && $item['original_price'] > $item['price']) {
                            $discount = round((($item['original_price'] - $item['price']) / $item['original_price']) * 100);
                        }
                        
                        // Check if item is in cart
                        $cart_button_class = '';
                        $cart_button_text = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                        if (isset($_SESSION['user_id'])) {
                            $check_cart = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                            $check_cart->bind_param("ii", $user_id, $item['id']);
                            $check_cart->execute();
                            if ($check_cart->get_result()->num_rows > 0) {
                                $cart_button_class = ' added';
                                $cart_button_text = '<i class="fas fa-check"></i> In Cart';
                            }
                        }
                    ?>
                    
                    <div class="product-card" data-product-id="<?= $item['id'] ?>">
                        <a href="productdetails.php?id=<?= $item['id'] ?>">
                            <div class="product-image-container">
                                <img src="<?= $image_path ?>" 
                                     onerror="this.src='/proglide/assets/no-image.png'"
                                     class="product-image" 
                                     alt="<?= htmlspecialchars($product_name) ?>"
                                     loading="lazy">
                                
                                <!-- Material Badge (Like index.php) -->
                                <?php if (!empty($item['material_name'])): ?>
                                <span class="category-badge"><?= htmlspecialchars($item['material_name']) ?></span>
                                <?php endif; ?>
                                
                                <!-- Popular Badge -->
                                <?php if ($item['is_popular']): ?>
                                <span class="popular-badge">
                                    <i class="fas fa-crown"></i> POPULAR
                                </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <div class="product-body">
                            <!-- Product Name -->
                            <h4 class="product-title">
                                <a href="productdetails.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($product_name) ?></a>
                            </h4>
                            
                            <!-- Category (Like material in index) -->
                            <span class="product-category"><?= htmlspecialchars($item['main_category']) ?></span>
                            
                            <!-- Price Section -->
                            <div class="price-section">
                                <span class="current-price">₹<?= number_format($item['price'], 0) ?></span>
                                <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                    <span class="original-price">₹<?= number_format($item['original_price'], 0) ?></span>
                                    <span class="discount">-<?= $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <a href="wishlist.php?remove_id=<?= $item['id'] ?>" class="btn-remove" 
                                   onclick="return confirm('Are you sure you want to remove this item from wishlist?');">
                                    <i class="fas fa-trash-alt"></i> <span>Remove</span>
                                </a>
                                
                                <?php if($isBackCase): ?>
                                    <a href="productdetails.php?id=<?= $item['id'] ?>" class="btn-cart select-model-btn">
                                        <i class="fas fa-mobile-alt"></i> <span>Select Model</span>
                                    </a>
                                <?php else: ?>
                                    <button class="btn-cart add-to-cart-btn<?= $cart_button_class ?>" data-product-id="<?= $item['id'] ?>">
                                        <?= $cart_button_text ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        
            <!-- Recommended Section - Like Features Section -->
            <section class="recommended-section">
                <div class="section-header" style="margin-bottom: 20px;">
                    <h2 class="section-title">
                        <i class="fas fa-lightbulb" style="color: var(--warning);"></i>
                        You Might Also Like
                    </h2>
                </div>
                
                <div class="recommended-grid">
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=1'">
                        <div class="recommended-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="recommended-title">Screen Protectors</h3>
                        <p class="recommended-text">Premium 9H hardness protectors</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=2'">
                        <div class="recommended-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="recommended-title">Back Cases</h3>
                        <p class="recommended-text">Stylish and protective cases</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=3'">
                        <div class="recommended-icon">
                            <i class="fas fa-headphones"></i>
                        </div>
                        <h3 class="recommended-title">AirPods</h3>
                        <p class="recommended-text">Premium AirPods accessories</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=4'">
                        <div class="recommended-icon">
                            <i class="fas fa-battery-full"></i>
                        </div>
                        <h3 class="recommended-title">Batteries</h3>
                        <p class="recommended-text">High-quality replacement</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<!-- Mobile Bottom Navigation -->
<?php include "includes/mobile_bottom_nav.php"; ?>

<!-- Toast Notification -->
<div class="toast-notification" id="toastNotification">
    <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
    <div class="toast-content">
        <div class="toast-title">Success</div>
        <div class="toast-message">Item added to cart successfully!</div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // ========== PRODUCT CARD CLICK ==========
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-remove') && !e.target.closest('.add-to-cart-btn') && !e.target.closest('.btn-cart')) {
                const productId = this.dataset.productId;
                window.location.href = 'productdetails.php?id=' + productId;
            }
        });
    });
    
    // ========== ADD TO CART FROM WISHLIST ==========
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.getAttribute('data-product-id');
            const isAdded = this.classList.contains('added');
            const originalText = this.innerHTML;
            const button = this;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            if (isAdded) {
                // Remove from cart
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('ajax/remove_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.classList.remove('added');
                        button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                        showToast('Removed from cart', 'success');
                        updateCartCount(data.cart_count);
                    } else {
                        button.innerHTML = originalText;
                        showToast(data.message || 'Failed to remove', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                    button.innerHTML = originalText;
                })
                .finally(() => {
                    button.disabled = false;
                });
            } else {
                // Add to cart
                fetch('ajax/add_to_cart.php?id=' + productId + '&qty=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.classList.add('added');
                        button.innerHTML = '<i class="fas fa-check"></i> In Cart';
                        showToast('Added to cart!', 'success');
                        updateCartCount(data.cart_count);
                        
                        // Optional: Remove from wishlist after adding to cart
                        // window.location.href = 'wishlist.php?remove_id=' + productId;
                    } else {
                        if (data.redirect) {
                            if (confirm(data.message + '\n\nDo you want to login?')) {
                                window.location.href = data.redirect;
                            } else {
                                button.innerHTML = originalText;
                            }
                        } else {
                            showToast(data.message || 'Failed to add', 'error');
                            button.innerHTML = originalText;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                    button.innerHTML = originalText;
                })
                .finally(() => {
                    button.disabled = false;
                });
            }
        });
    });
    
    // ========== UPDATE CART COUNT ==========
    function updateCartCount(count = null) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            if (count !== null) {
                el.textContent = count;
            }
            el.style.display = 'flex';
        });
    }
    
    // ========== TOAST NOTIFICATION ==========
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = toast.querySelector('.toast-icon i');
        const title = toast.querySelector('.toast-title');
        const msg = toast.querySelector('.toast-message');
        
        if (type === 'success') {
            icon.className = 'fas fa-check-circle';
            toast.style.borderLeftColor = 'var(--success)';
            title.textContent = 'Success';
        } else {
            icon.className = 'fas fa-exclamation-circle';
            toast.style.borderLeftColor = 'var(--danger)';
            title.textContent = 'Error';
        }
        
        msg.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // ========== IMAGE ERROR HANDLING ==========
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            if (!this.hasAttribute('data-error-handled')) {
                this.setAttribute('data-error-handled', 'true');
                this.src = '/proglide/assets/no-image.png';
            }
        });
    });
    
    // ========== AUTO-HIDE ALERTS ==========
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 300);
        });
    }, 5000);
});
</script>

</body>
</html>
<?php $conn->close(); ?>