<?php
session_start();
require "includes/db.php";

// Get user name if logged in
$user_name = '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_name = htmlspecialchars($result->fetch_assoc()['name']);
    }
    $stmt->close();
}

/* =========================
   HOME CATEGORIES WITH PRODUCT COUNT
========================= */
$categories = $conn->query("
    SELECT c.id, c.name, c.slug, c.image
    FROM categories c
    WHERE c.status = 1
    ORDER BY c.id ASC
");

/* =========================
   POPULAR PRODUCTS WITH DETAILS
========================= */
$popular = $conn->query("
    SELECT p.id, 
           CASE 
               WHEN p.category_id = 1 THEN p.model_name 
               ELSE p.design_name 
           END as product_name,
           p.price, 
           p.image1 as image,
           c.name as category_name,
           c.slug as category_slug,
           mt.name as material_name,
           vt.name as variant_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN material_types mt ON p.material_type_id = mt.id
    LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
    WHERE p.status = 1 AND p.is_popular = 1
    ORDER BY p.created_at DESC
    LIMIT 12
");

/* =========================
   FEATURED CATEGORY PRODUCTS
========================= */
$categoryProducts = [];
$catRes = $conn->query("SELECT id, name, slug FROM categories WHERE status = 1 AND show_on_home = 1 LIMIT 2");

while ($cat = $catRes->fetch_assoc()) {
    $cat_id = $cat['id'];
    $cat_name = $cat['name'];
    $cat_slug = $cat['slug'];
    
    $pRes = $conn->query("
        SELECT p.id, 
               CASE 
                   WHEN p.category_id = 1 THEN p.model_name 
                   ELSE p.design_name 
               END as product_name,
               p.price, 
               p.image1 as image,
               c.name as category_name,
               c.slug as category_slug,
               mt.name as material_name,
               vt.name as variant_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN material_types mt ON p.material_type_id = mt.id
        LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
        WHERE p.status = 1 AND p.category_id = $cat_id
        ORDER BY p.is_popular DESC, p.created_at DESC
        LIMIT 8
    ");
    
    if ($pRes->num_rows > 0) {
        $categoryProducts[] = [
            'name' => $cat_name,
            'slug' => $cat_slug,
            'products' => $pRes
        ];
    }
}

/* =========================
   STATISTICS
========================= */
$stats = [
    'total_products' => 0,
    'total_categories' => 0,
    'popular_count' => 0
];

$count_res = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 1");
if ($count_res) {
    $stats['total_products'] = $count_res->fetch_assoc()['total'];
}

$cat_count = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 1");
if ($cat_count) {
    $stats['total_categories'] = $cat_count->fetch_assoc()['total'];
}

$popular_count = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 1 AND is_popular = 1");
if ($popular_count) {
    $stats['popular_count'] = $popular_count->fetch_assoc()['total'];
}

// Get user's wishlist and cart items for highlighting
$user_wishlist = [];
$user_cart = [];

if ($user_id) {
    // Get wishlist items
    $wishlist_res = $conn->query("SELECT product_id FROM wishlist WHERE user_id = $user_id");
    while ($row = $wishlist_res->fetch_assoc()) {
        $user_wishlist[] = $row['product_id'];
    }
    
    // Get cart items
    $cart_res = $conn->query("SELECT product_id FROM cart WHERE user_id = $user_id");
    while ($row = $cart_res->fetch_assoc()) {
        $user_cart[] = $row['product_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PROGLIDE | Premium Phone Accessories</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #000000;
    --secondary: #ffcc00;
    --accent: #009933;
    --danger: #ff3b3b;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --radius: 12px;
    --shadow: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #f9f9f9;
    color: var(--dark);
    line-height: 1.6;
}

/* ====================
   HERO SECTION
==================== */
.hero-section {
    background: linear-gradient(135deg, var(--primary) 0%, #1a1a1a 100%);
    color: white;
    padding: 60px 0;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><path fill="rgba(255,204,0,0.05)" d="M0,0L1000,0L1000,1000L0,1000Z"/></svg>');
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(2rem, 5vw, 2.8rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1rem;
}

.hero-title span {
    color: var(--secondary);
    display: block;
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
    max-width: 500px;
}

.welcome-text {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    padding: 6px 12px;
    border-radius: 50px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.welcome-text i {
    color: var(--secondary);
}

.hero-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.stat-box {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--secondary);
    display: block;
    line-height: 1;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 4px;
}

/* ====================
   CATEGORIES (SMALLER)
==================== */
.categories-section {
    padding: 2rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    position: relative;
    padding-bottom: 8px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: var(--secondary);
}

.view-all {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.view-all:hover {
    gap: 10px;
    color: #007a29;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 1rem;
}

@media (max-width: 768px) {
    .categories-grid {
        display: flex;
        overflow-x: auto;
        gap: 0.8rem;
        padding-bottom: 0.8rem;
        scroll-snap-type: x mandatory;
    }
}

.category-card {
    background: white;
    border-radius: var(--radius);
    padding: 1rem;
    text-align: center;
    text-decoration: none;
    color: var(--dark);
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    border: 1px solid #eee;
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--secondary);
}

.category-img {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 0.8rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #f0f0f0;
}

.category-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.category-card:hover .category-img img {
    transform: scale(1.05);
}

.category-img .placeholder {
    color: var(--primary);
    font-size: 1.8rem;
    opacity: 0.7;
}

.category-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--primary);
    text-align: center;
    line-height: 1.3;
}

/* ====================
   PRODUCTS (SMALLER)
==================== */
.products-section {
    padding: 2rem 0;
    background: white;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
}

@media (max-width: 768px) {
    .products-grid {
        display: flex;
        overflow-x: auto;
        gap: 0.8rem;
        padding-bottom: 0.8rem;
        scroll-snap-type: x mandatory;
    }
}

.product-card {
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    border: 1px solid #eee;
    position: relative;
}

@media (max-width: 768px) {
    .product-card {
        min-width: 160px;
        scroll-snap-align: start;
    }
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.product-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--danger);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 2;
}

.product-image {
    width: 100%;
    height: 140px;
    object-fit: cover;
    background: var(--light);
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-info {
    padding: 0.8rem;
}

.product-category {
    font-size: 0.7rem;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.4rem;
    font-weight: 600;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.2rem;
}

.product-details {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin-bottom: 0.6rem;
}

.product-material, .product-variant {
    font-size: 0.75rem;
    color: var(--gray);
    text-transform: capitalize;
}

.product-price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.8rem;
}

.product-actions {
    display: flex;
    gap: 0.6rem;
}

.action-btn {
    flex: 1;
    padding: 6px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.action-btn.wishlist {
    background: var(--light);
    color: var(--dark);
    width: 32px;
    flex: none;
}

.action-btn.wishlist:hover {
    background: #e9ecef;
}

.action-btn.wishlist.active {
    background: var(--danger);
    color: white;
}

.action-btn.cart {
    background: var(--primary);
    color: white;
}

.action-btn.cart:hover {
    background: #333;
}

.action-btn.cart.added {
    background: var(--accent);
}

/* ====================
   FEATURES
==================== */
.features-section {
    padding: 3rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.feature-card {
    background: white;
    padding: 1.5rem;
    border-radius: var(--radius);
    text-align: center;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--secondary), #ffdd44);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: var(--primary);
}

.feature-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
    color: var(--primary);
}

.feature-text {
    color: var(--gray);
    font-size: 0.85rem;
    line-height: 1.4;
}

/* ====================
   LOGIN MODAL
==================== */
.login-modal .modal-content {
    border-radius: var(--radius);
    border: none;
    overflow: hidden;
}

.login-modal .modal-header {
    background: var(--primary);
    color: white;
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.login-modal .modal-body {
    padding: 1.5rem;
}

.login-modal .modal-footer {
    border-top: none;
    background: #f8f9fa;
    padding: 1rem 1.5rem;
}

.login-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--secondary), #ffdd44);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: var(--primary);
}

.login-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-align: center;
    color: var(--primary);
}

.login-text {
    color: var(--gray);
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.login-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.btn-login {
    background: var(--accent);
    color: white;
    padding: 10px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn-login:hover {
    background: #007a29;
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.btn-register {
    background: var(--primary);
    color: white;
    padding: 10px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn-register:hover {
    background: #333;
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

/* ====================
   RESPONSIVE
==================== */
@media (max-width: 768px) {
    .hero-section {
        padding: 40px 0;
    }
    
    .hero-title {
        font-size: 1.6rem;
    }
    
    .hero-stats {
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 1.2rem;
    }
    
    .section-title {
        font-size: 1.3rem;
    }
    
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .login-modal .modal-body {
        padding: 1.2rem;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 30px 0;
    }
    
    .hero-title {
        font-size: 1.4rem;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 0.8rem;
    }
    
    .categories-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .login-buttons {
        gap: 0.6rem;
    }
    
    .btn-login, .btn-register {
        padding: 8px;
        font-size: 0.8rem;
    }
}

/* ====================
   UTILITIES
==================== */
.btn-primary-custom {
    background: var(--secondary);
    color: var(--primary);
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.9rem;
}

.btn-primary-custom:hover {
    background: #ffd633;
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.btn-outline-custom {
    background: transparent;
    color: white;
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255,255,255,0.3);
    font-size: 0.9rem;
}

.btn-outline-custom:hover {
    background: rgba(255,255,255,0.1);
    border-color: var(--secondary);
    color: var(--secondary);
}

.text-gradient {
    background: linear-gradient(45deg, var(--secondary), #ffdd44);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Loading spinner */
.loading-spinner {
    border: 2px solid rgba(0,0,0,0.1);
    border-left-color: var(--accent);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
    display: none;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <!-- ====================
         HERO SECTION
    ==================== -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <?php if ($user_name): ?>
                            <div class="welcome-text">
                                <i class="fas fa-hand-wave"></i>
                                Welcome back, <?= $user_name ?>!
                            </div>
                        <?php endif; ?>
                        
                        <h1 class="hero-title">
                            Premium Protection<br>
                            <span class="text-gradient">For Your Devices</span>
                        </h1>
                        
                        <p class="hero-subtitle">
                            Discover our collection of 9H screen protectors, privacy screens, 
                            and stylish back cases. Military-grade protection with premium style.
                        </p>
                        
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="#products" class="btn-primary-custom">
                                <i class="fas fa-shopping-bag"></i> Shop Now
                            </a>
                            <a href="#categories" class="btn-outline-custom">
                                <i class="fas fa-th-large"></i> Browse Categories
                            </a>
                        </div>
                        
                        <div class="hero-stats">
                            <div class="stat-box">
                                <span class="stat-number"><?= $stats['total_products'] ?></span>
                                <span class="stat-label">Premium Products</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-number"><?= $stats['total_categories'] ?></span>
                                <span class="stat-label">Categories</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-number"><?= $stats['popular_count'] ?></span>
                                <span class="stat-label">Popular Items</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="position-relative">
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-warning rounded-4" style="opacity: 0.1; transform: rotate(5deg);"></div>
                        <div class="position-relative">
                            <img src="/proglide/image/logo.png" 
                                 alt="Premium Phone Accessories" 
                                 class="img-fluid rounded-4 shadow-lg">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====================
         CATEGORIES (SMALLER)
    ==================== -->
    <section id="categories" class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Browse Categories</h2>
                <a href="categories.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if ($categories && $categories->num_rows > 0): ?>
                <div class="categories-grid">
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <a href="products.php?category=<?= $c['slug'] ?>" class="category-card">
                            <div class="category-img">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="../uploads/categories/<?= htmlspecialchars($c['image']) ?>" 
                                         alt="<?= htmlspecialchars($c['name']) ?>"
                                         onerror="this.src='https://via.placeholder.com/70x70?text=<?= urlencode(substr($c['name'], 0, 10)) ?>'">
                                <?php else: ?>
                                    <div class="placeholder">
                                        <?php 
                                        // Show different icons based on category name
                                        $icon = 'fa-mobile-alt';
                                        if (stripos($c['name'], 'back') !== false) $icon = 'fa-mobile';
                                        elseif (stripos($c['name'], 'airpod') !== false) $icon = 'fa-earbuds';
                                        elseif (stripos($c['name'], 'watch') !== false) $icon = 'fa-clock';
                                        elseif (stripos($c['name'], 'protector') !== false) $icon = 'fa-shield-alt';
                                        ?>
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="category-name"><?= htmlspecialchars($c['name']) ?></div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No categories available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====================
         POPULAR PRODUCTS (SMALLER)
    ==================== -->
    <section id="products" class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">🔥 Popular Products</h2>
                <a href="products.php?filter=popular" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if ($popular && $popular->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while($p = $popular->fetch_assoc()): 
                        $price = number_format($p['price'], 2);
                        // Get correct image path based on category
                        $imagePath = '';
                        switch($p['category_slug']) {
                            case 'airpods':
                                $imagePath = "../uploads/products/airpods/";
                                break;
                            default:
                                $imagePath = "../uploads/products/";
                        }
                        $imageSrc = !empty($p['image']) ? $imagePath . htmlspecialchars($p['image']) : 'https://via.placeholder.com/200x140?text=No+Image';
                        
                        // Check if product is in user's wishlist/cart
                        $inWishlist = in_array($p['id'], $user_wishlist);
                        $inCart = in_array($p['id'], $user_cart);
                    ?>
                        <div class="product-card" data-product-id="<?= $p['id'] ?>">
                            <span class="product-badge">Popular</span>
                            
                            <a href="productdetails.php?id=<?= $p['id'] ?>">
                                <img src="<?= $imageSrc ?>" 
                                     alt="<?= htmlspecialchars($p['product_name']) ?>" 
                                     class="product-image"
                                     onerror="this.src='https://via.placeholder.com/200x140?text=No+Image'">
                            </a>
                            
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                                
                                <h3 class="product-name">
                                    <a href="productdetails.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($p['product_name']) ?>
                                    </a>
                                </h3>
                                
                                <div class="product-details">
                                    <?php if (!empty($p['material_name'])): ?>
                                        <div class="product-material">
                                            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($p['material_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['variant_name'])): ?>
                                        <div class="product-variant">
                                            <i class="fas fa-palette"></i> <?= htmlspecialchars($p['variant_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-price">₹ <?= $price ?></div>
                                
                                <div class="product-actions">
                                    <button class="action-btn wishlist <?= $inWishlist ? 'active' : '' ?>" 
                                            data-product-id="<?= $p['id'] ?>" 
                                            <?= !$user_id ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>
                                        <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                                    </button>
                                    <button class="action-btn cart <?= $inCart ? 'added' : '' ?>" 
                                            data-product-id="<?= $p['id'] ?>" 
                                            <?= !$user_id ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>
                                        <i class="fas fa-shopping-cart"></i> 
                                        <span><?= $inCart ? 'Added' : 'Add to Cart' ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No popular products available at the moment.</p>
                    <a href="products.php" class="btn btn-primary btn-sm">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====================
         FEATURES
    ==================== -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title text-center mb-4">Why Choose PROGLIDE</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">9H Hardness Protection</h3>
                    <p class="feature-text">Military-grade screen protectors that survive drops and scratches with 9H hardness rating.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <h3 class="feature-title">Privacy Screens</h3>
                    <p class="feature-text">Complete privacy protection from side angles. Only visible from the front.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="feature-title">Easy Installation</h3>
                    <p class="feature-text">Bubble-free application with precision-cut design for perfect fit every time.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="feature-title">Premium Quality</h3>
                    <p class="feature-text">High-quality materials and craftsmanship for long-lasting protection and style.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ====================
         CATEGORY PRODUCTS
    ==================== -->
    <?php if (!empty($categoryProducts)): ?>
        <?php foreach ($categoryProducts as $categoryData): ?>
            <section class="products-section">
                <div class="container">
                    <div class="section-header">
                        <h2 class="section-title"><?= htmlspecialchars($categoryData['name']) ?></h2>
                        <a href="products.php?category=<?= $categoryData['slug'] ?>" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="products-grid">
                        <?php while($p = $categoryData['products']->fetch_assoc()): 
                            $price = number_format($p['price'], 2);
                            // Get correct image path based on category
                            $imagePath = '';
                            switch($p['category_slug']) {
                                case 'airpods':
                                    $imagePath = "../uploads/products/airpods/";
                                    break;
                                default:
                                    $imagePath = "../uploads/products/";
                            }
                            $imageSrc = !empty($p['image']) ? $imagePath . htmlspecialchars($p['image']) : 'https://via.placeholder.com/200x140?text=No+Image';
                            
                            // Check if product is in user's wishlist/cart
                            $inWishlist = in_array($p['id'], $user_wishlist);
                            $inCart = in_array($p['id'], $user_cart);
                        ?>
                            <div class="product-card" data-product-id="<?= $p['id'] ?>">
                                <a href="productdetails.php?id=<?= $p['id'] ?>">
                                    <img src="<?= $imageSrc ?>" 
                                         alt="<?= htmlspecialchars($p['product_name']) ?>" 
                                         class="product-image"
                                         onerror="this.src='https://via.placeholder.com/200x140?text=No+Image'">
                                </a>
                                
                                <div class="product-info">
                                    <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                                    
                                    <h3 class="product-name">
                                        <a href="productdetails.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-details">
                                        <?php if (!empty($p['material_name'])): ?>
                                            <div class="product-material">
                                                <i class="fas fa-layer-group"></i> <?= htmlspecialchars($p['material_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($p['variant_name'])): ?>
                                            <div class="product-variant">
                                                <i class="fas fa-palette"></i> <?= htmlspecialchars($p['variant_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-price">₹ <?= $price ?></div>
                                    
                                    <div class="product-actions">
                                        <button class="action-btn wishlist <?= $inWishlist ? 'active' : '' ?>" 
                                                data-product-id="<?= $p['id'] ?>" 
                                                <?= !$user_id ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>
                                            <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                                        </button>
                                        <button class="action-btn cart <?= $inCart ? 'added' : '' ?>" 
                                                data-product-id="<?= $p['id'] ?>" 
                                                <?= !$user_id ? 'data-bs-toggle="modal" data-bs-target="#loginModal"' : '' ?>>
                                            <i class="fas fa-shopping-cart"></i> 
                                            <span><?= $inCart ? 'Added' : 'Add to Cart' ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ====================
         LOGIN MODAL
    ==================== -->
    <div class="modal fade login-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="login-icon">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <h3 class="login-title">Please Login First</h3>
                    <p class="login-text">
                        You need to login to add items to your wishlist or cart. 
                        Don't have an account? Register now to enjoy all features!
                    </p>
                    
                    <div class="login-buttons">
                        <a href="login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login to Your Account
                        </a>
                        <a href="register.php" class="btn-register">
                            <i class="fas fa-user-plus"></i> Create New Account
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="text-muted text-center mb-0 w-100">
                        <small>By logging in, you agree to our Terms & Conditions and Privacy Policy</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>

    <!-- ====================
         JAVASCRIPT
    ==================== -->
    <script>
        'use strict';
        
        // Check if user is logged in
        const isLoggedIn = <?= $user_id ? 'true' : 'false' ?>;
        const csrfToken = '<?= bin2hex(random_bytes(32)) ?>';
        
        // Store cart and wishlist state
        let cartState = <?= json_encode($user_cart) ?>;
        let wishlistState = <?= json_encode($user_wishlist) ?>;
        
        // Wishlist functionality
        document.addEventListener('click', function(e) {
            const wishlistBtn = e.target.closest('.wishlist');
            if (wishlistBtn) {
                e.preventDefault();
                
                // Check if user is logged in
                if (!isLoggedIn) {
                    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                    return;
                }
                
                const productId = parseInt(wishlistBtn.dataset.productId);
                const isActive = wishlistBtn.classList.contains('active');
                const icon = wishlistBtn.querySelector('i');
                const productCard = wishlistBtn.closest('.product-card');
                
                // Show loading
                const originalIcon = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                if (isActive) {
                    // Remove from wishlist
                    fetch(`ajax/remove_wishlist.php?id=${productId}&csrf=${csrfToken}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success') || result.trim() === '') {
                            // Update UI
                            wishlistBtn.classList.remove('active');
                            icon.className = 'far fa-heart';
                            
                            // Update state
                            const index = wishlistState.indexOf(productId);
                            if (index > -1) {
                                wishlistState.splice(index, 1);
                            }
                            
                            showNotification('Removed from wishlist', 'info');
                        } else {
                            // Revert on error
                            wishlistBtn.classList.add('active');
                            icon.className = 'fas fa-heart';
                            showNotification('Error removing from wishlist', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Wishlist error:', error);
                        wishlistBtn.classList.add('active');
                        icon.className = 'fas fa-heart';
                        showNotification('Network error', 'error');
                    });
                } else {
                    // Add to wishlist
                    fetch(`ajax/add_to_wishlist.php?id=${productId}&csrf=${csrfToken}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success') || result.trim() === '') {
                            // Update UI
                            wishlistBtn.classList.add('active');
                            icon.className = 'fas fa-heart';
                            
                            // Update state
                            if (!wishlistState.includes(productId)) {
                                wishlistState.push(productId);
                            }
                            
                            showNotification('Added to wishlist!', 'success');
                        } else {
                            // Revert on error
                            wishlistBtn.classList.remove('active');
                            icon.className = 'far fa-heart';
                            showNotification('Error adding to wishlist', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Wishlist error:', error);
                        wishlistBtn.classList.remove('active');
                        icon.className = 'far fa-heart';
                        showNotification('Network error', 'error');
                    });
                }
            }
            
            // Cart functionality
            const cartBtn = e.target.closest('.cart');
            if (cartBtn) {
                e.preventDefault();
                
                // Check if user is logged in
                if (!isLoggedIn) {
                    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                    loginModal.show();
                    return;
                }
                
                const productId = parseInt(cartBtn.dataset.productId);
                const isAdded = cartBtn.classList.contains('added');
                const icon = cartBtn.querySelector('i');
                const textSpan = cartBtn.querySelector('span');
                
                // Show loading
                const originalText = textSpan.textContent;
                const originalIconClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                textSpan.textContent = 'Processing...';
                
                if (isAdded) {
                    // Remove from cart
                    fetch(`ajax/remove_cart.php?id=${productId}&csrf=${csrfToken}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success') || result.trim() === '') {
                            // Update UI
                            cartBtn.classList.remove('added');
                            icon.className = 'fas fa-shopping-cart';
                            textSpan.textContent = 'Add to Cart';
                            
                            // Update state
                            const index = cartState.indexOf(productId);
                            if (index > -1) {
                                cartState.splice(index, 1);
                            }
                            
                            // Update cart count
                            updateCartCount(-1);
                            showNotification('Removed from cart', 'info');
                        } else {
                            // Revert on error
                            cartBtn.classList.add('added');
                            icon.className = 'fas fa-check';
                            textSpan.textContent = 'Added';
                            showNotification('Error removing from cart', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Cart error:', error);
                        cartBtn.classList.add('added');
                        icon.className = 'fas fa-check';
                        textSpan.textContent = 'Added';
                        showNotification('Network error', 'error');
                    });
                } else {
                    // Add to cart
                    fetch(`ajax/add_to_cart.php?id=${productId}&qty=1&csrf=${csrfToken}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success') || result.trim() === '') {
                            // Update UI
                            cartBtn.classList.add('added');
                            icon.className = 'fas fa-check';
                            textSpan.textContent = 'Added';
                            
                            // Update state
                            if (!cartState.includes(productId)) {
                                cartState.push(productId);
                            }
                            
                            // Update cart count
                            updateCartCount(1);
                            showNotification('Added to cart!', 'success');
                        } else {
                            // Revert on error
                            cartBtn.classList.remove('added');
                            icon.className = 'fas fa-shopping-cart';
                            textSpan.textContent = 'Add to Cart';
                            showNotification('Error adding to cart', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Cart error:', error);
                        cartBtn.classList.remove('added');
                        icon.className = 'fas fa-shopping-cart';
                        textSpan.textContent = 'Add to Cart';
                        showNotification('Network error', 'error');
                    });
                }
            }
        });
        
        function updateCartCount(change) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                let current = parseInt(cartCount.textContent) || 0;
                current = Math.max(0, current + change);
                cartCount.textContent = current;
                cartCount.style.display = current > 0 ? 'flex' : 'none';
                
                // Update cart count in mobile bottom nav if exists
                const mobileCartCount = document.querySelector('.mobile-cart-count');
                if (mobileCartCount) {
                    mobileCartCount.textContent = current;
                    mobileCartCount.style.display = current > 0 ? 'flex' : 'none';
                }
            }
        }
        
        function showNotification(message, type) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());
            
            // Create notification
            const notification = document.createElement('div');
            notification.className = `custom-notification alert alert-${type} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 250px;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 8px;
            `;
            
            const icon = type === 'success' ? '✓' : type === 'info' ? 'ℹ' : '✗';
            const bgColor = type === 'success' ? '#d4edda' : type === 'info' ? '#d1ecf1' : '#f8d7da';
            const textColor = type === 'success' ? '#155724' : type === 'info' ? '#0c5460' : '#721c24';
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 24px; height: 24px; border-radius: 50%; background: ${bgColor}; color: ${textColor}; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                        ${icon}
                    </div>
                    <div style="flex: 1; color: ${textColor}; font-size: 14px;">${message}</div>
                    <button type="button" class="btn-close" style="font-size: 10px;" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Image error handling
        document.querySelectorAll('img.product-image, img.category-img img').forEach(img => {
            img.addEventListener('error', function() {
                if (!this.hasAttribute('data-error-handled')) {
                    this.setAttribute('data-error-handled', 'true');
                    
                    const currentSrc = this.src;
                    const fileName = currentSrc.split('/').pop();
                    
                    if (currentSrc.includes('airpods')) {
                        this.src = '../uploads/products/' + fileName;
                    } else if (currentSrc.includes('uploads/products')) {
                        this.src = '../uploads/products/airpods/' + fileName;
                    } else {
                        this.src = 'https://via.placeholder.com/200x140?text=No+Image';
                    }
                    
                    this.addEventListener('error', function() {
                        if (this.src.includes('uploads/products')) {
                            this.src = 'https://via.placeholder.com/200x140?text=No+Image';
                        }
                    }, { once: true });
                }
            });
        });
        
        // Auto-focus login button when modal opens
        document.getElementById('loginModal')?.addEventListener('shown.bs.modal', function() {
            document.querySelector('.btn-login')?.focus();
        });
        
        // Prevent default action for login-triggering buttons
        document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!isLoggedIn) {
                    e.preventDefault();
                }
            });
        });
        
        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            const initialCartCount = cartState.length;
            updateCartCount(0); // This will set the initial count
        });
    </script>
</body>
</html>