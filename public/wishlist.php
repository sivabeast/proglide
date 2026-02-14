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
    cat.name AS main_category,
    cat.slug AS category_slug
FROM wishlist w
JOIN products p ON p.id = w.product_id
JOIN categories cat ON cat.id = p.category_id
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Wishlist | PROGLIDE</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<meta name="description" content="Your saved favorites - Premium phone protectors, cases, and accessories">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* =====================================
   WISHLIST PAGE - PROGLIDE DESIGN
===================================== */
:root {
    --primary: #000000;
    --secondary: #ff6b35;
    --accent: #2ecc71;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --border: #e9ecef;
    --radius: 8px;
    --shadow: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-lg: 0 4px 20px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #ffffff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    color: var(--dark);
    line-height: 1.5;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    padding-top: 80px;
}

/* ================= HERO SECTION ================= */
.wishlist-hero {
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    color: white;
    padding: 40px 0 60px;
    position: relative;
    overflow: hidden;
    margin-bottom: 40px;
}

.wishlist-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 40%;
    height: 100%;
    background: radial-gradient(circle at 70% 50%, rgba(255, 107, 53, 0.1) 0%, transparent 70%);
    pointer-events: none;
}

.wishlist-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--secondary);
    color: white;
    padding: 8px 16px;
    border-radius: 50px;
    margin-bottom: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.wishlist-title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 15px;
}

.wishlist-title span {
    color: var(--secondary);
}

.wishlist-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 500px;
    margin-bottom: 25px;
}

.wishlist-stats {
    display: flex;
    gap: 20px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    padding: 12px 20px;
    border-radius: var(--radius);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-icon {
    font-size: 1.2rem;
    color: var(--secondary);
}

.stat-number {
    font-weight: 700;
    font-size: 1.3rem;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.8;
}

/* ================= CONTENT SECTION ================= */
.content-section {
    padding: 0 0 60px;
}

.page-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: var(--light);
    border-radius: var(--radius);
    border: 2px dashed var(--border);
    margin: 40px 0;
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: var(--light);
    border: 2px solid var(--secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2rem;
    color: var(--secondary);
}

.empty-state h3 {
    font-size: 1.5rem;
    color: var(--dark);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--gray);
    margin-bottom: 25px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.browse-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--secondary);
    color: white;
    padding: 12px 28px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border: 2px solid transparent;
}

.browse-btn:hover {
    background: #e55a2b;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(255, 107, 53, 0.3);
    text-decoration: none;
}

/* ================= PRODUCT GRID ================= */
.products-section {
    margin: 40px 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    position: relative;
    display: inline-block;
}

.section-title::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 60px;
    height: 3px;
    background: var(--secondary);
    border-radius: 2px;
}

.clear-all-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #e74c3c;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    padding: 8px 16px;
    border-radius: 50px;
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid #e74c3c;
    cursor: pointer;
    text-decoration: none;
}

.clear-all-btn:hover {
    gap: 10px;
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    text-decoration: none;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

/* Product Card */
.product-card {
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    border: 1px solid var(--border);
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--secondary);
}

.product-card a {
    text-decoration: none;
    color: inherit;
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 200px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    max-height: 160px;
    transition: var(--transition);
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.category-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 2;
}

/* Popular Badge */
.popular-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #ff6b35, #ff8e53);
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
}

.popular-badge i {
    margin-right: 4px;
}

.product-body {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Product Name Styling */
.product-title-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.product-title {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
    color: var(--dark);
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-category {
    font-size: 0.8rem;
    color: var(--secondary);
    margin: 0;
    font-weight: 600;
}

.price-section {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.current-price {
    font-weight: 700;
    color: var(--accent);
    font-size: 1.2rem;
    display: block;
}

.original-price {
    font-size: 0.9rem;
    color: var(--gray);
    text-decoration: line-through;
}

.discount {
    font-size: 0.8rem;
    color: #e74c3c;
    font-weight: 600;
    background: rgba(231, 76, 60, 0.1);
    padding: 2px 8px;
    border-radius: 15px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: auto;
}

.btn-remove, .btn-cart {
    flex: 1;
    padding: 10px;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
    text-decoration: none;
}

.btn-remove {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid #e74c3c;
    text-decoration: none;
    text-align: center;
}

.btn-remove:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-2px);
    text-decoration: none;
}

.btn-cart {
    background: var(--secondary);
    color: #000;
    text-decoration: none;
}

.btn-cart:hover {
    background: #e55a2b;
    color: white;
    transform: translateY(-2px);
    text-decoration: none;
}

.btn-cart.added {
    background: var(--accent);
    color: white;
}

/* Select Model Button */
.select-model-btn {
    background: #3498db;
    color: white;
}

.select-model-btn:hover {
    background: #2980b9;
    color: white;
    transform: translateY(-2px);
}

/* ================= RECOMMENDED SECTION ================= */
.recommended-section {
    padding: 40px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    margin-top: 40px;
}

.recommended-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.recommended-card {
    background: white;
    padding: 20px;
    border-radius: var(--radius);
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
    border: 1px solid var(--border);
    cursor: pointer;
}

.recommended-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--secondary);
}

.recommended-icon {
    width: 60px;
    height: 60px;
    background: var(--secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 1.5rem;
    color: white;
    transition: var(--transition);
}

.recommended-card:hover .recommended-icon {
    transform: rotate(15deg) scale(1.1);
}

.recommended-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--primary);
}

.recommended-text {
    color: var(--gray);
    font-size: 0.85rem;
    line-height: 1.5;
}

/* ================= FOOTER ================= */
.wishlist-footer {
    background: var(--primary);
    color: white;
    padding: 60px 0 30px;
    margin-top: 60px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto 40px;
    padding: 0 20px;
}

.footer-section h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--secondary);
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.footer-links a:hover {
    color: var(--secondary);
    gap: 12px;
}

.contact-info {
    color: rgba(255, 255, 255, 0.8);
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 15px;
}

.contact-icon {
    color: var(--secondary);
    font-size: 1rem;
    margin-top: 2px;
}

.footer-bottom {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

/* ================= TOAST NOTIFICATION ================= */
.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 280px;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    border-left: 4px solid var(--accent);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    transform: translateX(100%);
    opacity: 0;
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.toast-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-notification.error {
    border-left-color: #e74c3c;
}

.toast-notification.error .toast-icon {
    color: #e74c3c;
}

.toast-icon {
    color: var(--accent);
    font-size: 1.2rem;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    margin-bottom: 2px;
}

.toast-message {
    font-size: 0.9rem;
    color: var(--gray);
}

/* ================= RESPONSIVE ================= */
/* Desktop: 4 products per row */
@media (min-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
    }
}

/* Tablet: 3 products per row */
@media (min-width: 768px) and (max-width: 1199px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
}

/* Mobile: 2 products per row */
@media (max-width: 767px) {
    body {
        padding-top: 70px;
    }
    
    .wishlist-hero {
        padding: 30px 0 40px;
        text-align: center;
    }
    
    .wishlist-title {
        font-size: 2rem;
    }
    
    .wishlist-stats {
        flex-direction: column;
        align-items: center;
    }
    
    .stat-item {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .clear-all-btn {
        align-self: flex-start;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .product-image-container {
        height: 180px;
        padding: 15px;
    }
    
    .product-body {
        padding: 15px;
    }
    
    .product-title {
        font-size: 0.9rem;
        height: auto;
        -webkit-line-clamp: 2;
    }
    
    .current-price {
        font-size: 1.1rem;
    }
    
    .btn-remove, .btn-cart {
        padding: 8px;
        font-size: 0.75rem;
        gap: 4px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 8px;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .recommended-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}

/* Small Mobile: 1 product per row */
@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
        gap: 20px;
        max-width: 320px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .product-image-container {
        height: 200px;
    }
    
    .recommended-grid {
        grid-template-columns: 1fr;
    }
    
    .clear-all-btn {
        width: 100%;
        justify-content: center;
    }
    
    .action-buttons {
        flex-direction: row;
    }
    
    .btn-remove, .btn-cart {
        font-size: 0.8rem;
        padding: 10px;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .product-card:hover {
        transform: none;
    }
    
    .btn-remove:hover, .btn-cart:hover {
        transform: none;
    }
    
    .product-card:active {
        transform: scale(0.98);
    }
    
    .btn-remove:active, .btn-cart:active {
        transform: scale(0.95);
    }
    
    .recommended-card:active {
        transform: scale(0.98);
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    body {
        background: #121212;
        color: #e0e0e0;
    }
    
    .product-card {
        background: #1e1e1e;
        border-color: #333;
    }
    
    .product-title, .product-category {
        color: #e0e0e0;
    }
    
    .empty-state {
        background: #1e1e1e;
        border-color: #333;
    }
    
    .recommended-section {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    }
    
    .recommended-card {
        background: #1e1e1e;
        border-color: #333;
    }
}
</style>
</head>

<body>

<?php include "includes/header.php"; ?>

<!-- ================= HERO SECTION ================= -->
<section class="wishlist-hero">
    <div class="page-container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="hero-content">
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
                        <div class="welcome-text mb-3">
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
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <div class="stat-number"><?= count($watchlist) ?></div>
                                <div class="stat-label">Items Saved</div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
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
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
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
            
            <div class="col-lg-4">
                <div class="hero-image text-center">
                    <i class="fas fa-heart" style="font-size: 10rem; color: rgba(255, 107, 53, 0.3);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= CONTENT SECTION ================= -->
<section class="content-section">
    <div class="page-container">
        <?php 
        // Show success messages
        if (isset($_GET['removed'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Item removed from wishlist successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        if (isset($_GET['cleared'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Wishlist cleared successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        if (isset($_GET['added_to_cart'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Item added to cart successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ' . htmlspecialchars($_GET['error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                        <i class="fas fa-heart text-danger me-2"></i>
                        Saved Items (<?= count($watchlist) ?>)
                    </h2>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.');">
                        <button type="submit" name="clear_wishlist" class="clear-all-btn">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </form>
                </div>
                
                <div class="products-grid">
                    <?php foreach($watchlist as $item):
                        $isBackCase = strtolower($item['main_category']) === 'back cases';
                        
                        // Determine product name
                        if ($isBackCase) {
                            $product_name = !empty($item['design_name']) ? $item['design_name'] : 'Design Back Case';
                        } else {
                            $product_name = !empty($item['model_name']) ? $item['model_name'] : $item['main_category'];
                        }
                        
                        // Set folder based on category slug
                        $folder = 'others';
                        if ($item['category_slug'] == 'protectors' || stripos($item['main_category'], 'protector') !== false) {
                            $folder = 'protectors';
                        } elseif ($item['category_slug'] == 'backcases' || $item['category_slug'] == 'back-cases' || stripos($item['main_category'], 'case') !== false) {
                            $folder = 'backcases';
                        } elseif ($item['category_slug'] == 'airpods' || stripos($item['main_category'], 'airpod') !== false) {
                            $folder = 'airpods';
                        }
                        
                        $imagePath = "/proglide/uploads/products/" . $folder . "/" . htmlspecialchars($item['image']);
                        $fallbackPath = "/proglide/assets/no-image.png";
                        
                        // Calculate discount if available
                        $discount = 0;
                        if ($item['original_price'] && $item['original_price'] > $item['price']) {
                            $discount = round((($item['original_price'] - $item['price']) / $item['original_price']) * 100);
                        }
                    ?>
                    
                    <div class="product-card" data-product-id="<?= $item['id'] ?>">
                        <a href="productdetails.php?id=<?= $item['id'] ?>">
                            <div class="product-image-container">
                                <img src="<?= $imagePath ?>" 
                                     onerror="this.src='<?= $fallbackPath ?>'"
                                     class="product-image" 
                                     alt="<?= htmlspecialchars($product_name) ?>">
                                
                                <!-- Category Badge -->
                                <span class="category-badge"><?= htmlspecialchars($item['main_category']) ?></span>
                                
                                <!-- Popular Badge -->
                                <?php if ($item['is_popular']): ?>
                                <span class="popular-badge">
                                    <i class="fas fa-fire"></i> POPULAR
                                </span>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <div class="product-body">
                            <!-- Product Name -->
                            <div class="product-title-wrapper">
                                <h4 class="product-title">
                                    <a href="productdetails.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($product_name) ?></a>
                                </h4>
                            </div>
                            
                            <!-- Category -->
                            <p class="product-category"><?= htmlspecialchars($item['main_category']) ?></p>
                            
                            <!-- Price Section -->
                            <div class="price-section">
                                <span class="current-price">₹<?= number_format($item['price'], 2) ?></span>
                                <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                    <span class="original-price">₹<?= number_format($item['original_price'], 2) ?></span>
                                    <span class="discount">-<?= $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <a href="wishlist.php?remove_id=<?= $item['id'] ?>" class="btn-remove" 
                                   onclick="return confirm('Are you sure you want to remove this item from wishlist?');">
                                    <i class="fas fa-heart"></i> Remove
                                </a>
                                
                                <?php if($isBackCase): ?>
                                    <a href="productdetails.php?id=<?= $item['id'] ?>" class="btn-cart select-model-btn">
                                        <i class="fas fa-mobile-alt"></i> Select Model
                                    </a>
                                <?php else: ?>
                                    <button class="btn-cart add-to-cart-btn" data-product-id="<?= $item['id'] ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recommended Section -->
        <?php if(!empty($watchlist)): ?>
        <section class="recommended-section">
            <div class="page-container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        You Might Also Like
                    </h2>
                </div>
                
                <div class="recommended-grid">
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=1'">
                        <div class="recommended-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="recommended-title">Screen Protectors</h3>
                        <p class="recommended-text">Premium 9H hardness protectors for ultimate screen protection</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=2'">
                        <div class="recommended-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="recommended-title">Back Cases</h3>
                        <p class="recommended-text">Stylish and protective cases for your devices</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php?cat=3'">
                        <div class="recommended-icon">
                            <i class="fas fa-headphones"></i>
                        </div>
                        <h3 class="recommended-title">AirPods</h3>
                        <p class="recommended-text">Premium accessories for your AirPods</p>
                    </div>
                    
                    <div class="recommended-card" onclick="window.location.href='products.php'">
                        <div class="recommended-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <h3 class="recommended-title">Popular Items</h3>
                        <p class="recommended-text">Check out our most popular products</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="wishlist-footer">
    <div class="footer-grid">
        <div class="footer-section">
            <h4>PROGLIDE</h4>
            <p style="color: rgba(255,255,255,0.8); line-height: 1.6;">
                Premium screen protectors and mobile accessories for all major smartphone brands. 
                Protecting your devices since 2015.
            </p>
        </div>
        
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul class="footer-links">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="categories.php"><i class="fas fa-th-large"></i> Categories</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Support</h4>
            <ul class="footer-links">
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                <li><a href="shipping.php"><i class="fas fa-shipping-fast"></i> Shipping Policy</a></li>
                <li><a href="returns.php"><i class="fas fa-undo"></i> Return Policy</a></li>
                <li><a href="warranty.php"><i class="fas fa-shield-alt"></i> Warranty</a></li>
                <li><a href="installation.php"><i class="fas fa-wrench"></i> Installation Guide</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Contact Info</h4>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt contact-icon"></i>
                    <span>123 Tech Street, San Francisco, CA</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone contact-icon"></i>
                    <span>+1 (234) 567-8910</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope contact-icon"></i>
                    <span>support@proglide.com</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>© 2026 PROGLIDE. All rights reserved. | Premium Mobile Accessories</p>
    </div>
</footer>

<?php include "includes/mobile_bottom_nav.php"; ?>

<!-- ================= JAVASCRIPT ================= -->
<script>
// Handle Add to Cart from Wishlist
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const productId = this.getAttribute('data-product-id');
        const originalText = this.innerHTML;
        const button = this;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Use your existing add_to_cart.php endpoint
        fetch(`ajax/add_to_cart.php?id=${productId}&qty=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('Added to cart successfully!', 'success');
                
                // Update cart count in header
                if (data.cart_count !== undefined) {
                    updateCartCount(data.cart_count);
                }
                
                // Update button appearance
                button.classList.add('added');
                button.innerHTML = '<i class="fas fa-check"></i> Added';
                
                // After 2 seconds, change back to original
                setTimeout(() => {
                    button.classList.remove('added');
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // Redirect to refresh wishlist
                    setTimeout(() => {
                        window.location.href = 'wishlist.php?added_to_cart=1';
                    }, 500);
                }, 2000);
                
            } else {
                if (data.redirect) {
                    if (confirm(data.message + '\n\nDo you want to login?')) {
                        window.location.href = data.redirect;
                    } else {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                } else {
                    showToast(data.message || 'Failed to add to cart', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
});

// Update cart count in header
function updateCartCount(count = null) {
    const cartCountElements = document.querySelectorAll('.cart-count, .action-badge');
    
    cartCountElements.forEach(el => {
        if (count !== null) {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        } else {
            const current = parseInt(el.textContent) || 0;
            el.textContent = current + 1;
            el.style.display = 'flex';
        }
    });
}

// Toast Notification
function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type === 'error' ? 'error' : ''}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} toast-icon"></i>
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Success' : 'Error'}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Image error handling
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', function() {
        if (!this.hasAttribute('data-error-handled')) {
            this.setAttribute('data-error-handled', 'true');
            this.src = '/proglide/assets/no-image.png';
        }
    });
});

// Mobile touch optimizations
if ('ontouchstart' in window || navigator.maxTouchPoints) {
    // Add touch feedback for product cards
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.cursor = 'pointer';
        let touchTimer;
        
        card.addEventListener('touchstart', function() {
            touchTimer = setTimeout(() => {
                this.style.opacity = '0.7';
            }, 50);
        });
        
        card.addEventListener('touchend', function() {
            clearTimeout(touchTimer);
            this.style.opacity = '1';
        });
    });
    
    // Increase button tap targets for mobile
    document.querySelectorAll('.btn-remove, .btn-cart').forEach(btn => {
        btn.style.minHeight = '44px';
        btn.style.padding = '12px';
    });
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php $conn->close(); ?>