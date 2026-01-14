<?php
session_start();
require "includes/db.php";

// Get user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
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
    SELECT c.id, c.name, c.slug, c.image,
           COUNT(DISTINCT p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON (
        (c.name = 'Protector' AND p.main_category_id = 1) OR
        (c.name = 'Back Case' AND p.main_category_id = 2)
    ) AND p.status = 1
    WHERE c.status = 1
    GROUP BY c.id, c.name, c.slug, c.image
    ORDER BY c.id ASC
");

/* =========================
   POPULAR PRODUCTS WITH DETAILS
========================= */
$popular = $conn->query("
    SELECT p.id, p.model_name, p.design_name, p.price, p.image, p.main_category_id,
           ct.type_name, mc.name as main_category_name
    FROM products p
    JOIN main_categories mc ON p.main_category_id = mc.id
    JOIN category_types ct ON p.category_type_id = ct.id
    WHERE p.status = 1 AND p.is_popular = 1
    ORDER BY p.id DESC
    LIMIT 16
");

/* =========================
   FEATURED CATEGORY PRODUCTS
========================= */
$categoryProducts = [];
$catRes = $conn->query("SELECT id, name, slug FROM categories WHERE status = 1");

while ($cat = $catRes->fetch_assoc()) {
    $cat_name = $cat['name'];
    $cat_slug = $cat['slug'];
    
    // Determine main_category_id based on category name
    $mainCategoryId = (stripos($cat_name, 'case') !== false) ? 2 : 1;
    
    $pRes = $conn->query("
        SELECT p.id, p.model_name, p.design_name, p.price, p.image, p.main_category_id,
               ct.type_name, mc.name as main_category_name
        FROM products p
        JOIN main_categories mc ON p.main_category_id = mc.id
        JOIN category_types ct ON p.category_type_id = ct.id
        WHERE p.status = 1 AND p.main_category_id = $mainCategoryId
        ORDER BY p.id DESC
        LIMIT 10
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
    padding: 80px 0;
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
    font-size: clamp(2.5rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1rem;
}

.hero-title span {
    color: var(--secondary);
    display: block;
}

.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    max-width: 600px;
}

.welcome-text {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.1);
    padding: 8px 16px;
    border-radius: 50px;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}

.welcome-text i {
    color: var(--secondary);
}

.hero-stats {
    display: flex;
    gap: 2rem;
    margin-top: 3rem;
    flex-wrap: wrap;
}

.stat-box {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--secondary);
    display: block;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-top: 5px;
}

/* ====================
   CATEGORIES
==================== */
.categories-section {
    padding: 3rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary);
    position: relative;
    padding-bottom: 10px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: var(--secondary);
}

.view-all {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.view-all:hover {
    gap: 12px;
    color: #007a29;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1.2rem;
}

@media (max-width: 768px) {
    .categories-grid {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding-bottom: 1rem;
        scroll-snap-type: x mandatory;
    }
}

.category-card {
    background: white;
    border-radius: var(--radius);
    padding: 1.5rem 1rem;
    text-align: center;
    text-decoration: none;
    color: var(--dark);
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    border: 2px solid transparent;
    scroll-snap-align: start;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--secondary);
}

.category-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.category-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.category-img .placeholder {
    color: var(--gray);
    font-size: 2rem;
}

.category-name {
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.category-count {
    font-size: 0.85rem;
    color: var(--gray);
    background: var(--light);
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
}

/* ====================
   PRODUCTS
==================== */
.products-section {
    padding: 3rem 0;
    background: white;
}

.products-grid {
    display: flex;
    overflow-x: auto;
    gap: 1.2rem;
    padding-bottom: 1rem;
    scroll-snap-type: x mandatory;
}

@media (min-width: 992px) {
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        overflow-x: visible;
    }
}

.product-card {
    min-width: 220px;
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    scroll-snap-align: start;
    position: relative;
    border: 1px solid #eee;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--danger);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

.product-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    background: var(--light);
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-info {
    padding: 1.2rem;
}

.product-category {
    font-size: 0.8rem;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.product-name {
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.8rem;
}

.product-type {
    font-size: 0.85rem;
    color: var(--gray);
    margin-bottom: 0.8rem;
    text-transform: capitalize;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    gap: 0.8rem;
}

.action-btn {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.action-btn.wishlist {
    background: var(--light);
    color: var(--dark);
    width: 40px;
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
    padding: 4rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: var(--radius);
    text-align: center;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--secondary), #ffdd44);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 1.8rem;
    color: var(--primary);
}

.feature-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--primary);
}

.feature-text {
    color: var(--gray);
    font-size: 0.95rem;
}

/* ====================
   RESPONSIVE
==================== */
@media (max-width: 768px) {
    .hero-section {
        padding: 50px 0;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-stats {
        gap: 1.5rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .products-grid {
        gap: 1rem;
    }
    
    .product-card {
        min-width: 200px;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 40px 0;
    }
    
    .hero-title {
        font-size: 1.8rem;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* ====================
   UTILITIES
==================== */
.btn-primary-custom {
    background: var(--secondary);
    color: var(--primary);
    padding: 12px 28px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    border: none;
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
    padding: 12px 28px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    border: 2px solid rgba(255,255,255,0.3);
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
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
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
                        
                        <div class="d-flex gap-3 flex-wrap">
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
                            <img src="https://images.unsplash.com/photo-1546054451-aa724f6d7a54?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                                 alt="Premium Phone Accessories" 
                                 class="img-fluid rounded-4 shadow-lg">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====================
         CATEGORIES
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
                        <a href="subcategories.php?cat=<?= $c['slug'] ?>" class="category-card">
                            <div class="category-img">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="../uploads/categories/<?= htmlspecialchars($c['image']) ?>" 
                                         alt="<?= htmlspecialchars($c['name']) ?>"
                                         onerror="this.parentElement.innerHTML='<div class=\"placeholder\"><i class=\"fas fa-mobile-alt\"></i></div>';">
                                <?php else: ?>
                                    <div class="placeholder">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="category-name"><?= htmlspecialchars($c['name']) ?></div>
                            <div class="category-count"><?= $c['product_count'] ?> Products</div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No categories available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====================
         POPULAR PRODUCTS
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
                        $folder = ($p['main_category_id'] == 2) ? 'backcases' : 'protectors';
                        $name = $p['model_name'] ?? $p['design_name'];
                        $price = number_format($p['price'], 2);
                    ?>
                        <div class="product-card">
                            <span class="product-badge">Popular</span>
                            
                            <a href="productdetails.php?id=<?= $p['id'] ?>">
                                <img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($p['image']) ?>" 
                                     alt="<?= htmlspecialchars($name) ?>" 
                                     class="product-image"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            </a>
                            
                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($p['main_category_name']) ?></div>
                                
                                <h3 class="product-name">
                                    <a href="productdetails.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($name) ?>
                                    </a>
                                </h3>
                                
                                <div class="product-type"><?= htmlspecialchars($p['type_name']) ?></div>
                                
                                <div class="product-price">₹ <?= $price ?></div>
                                
                                <div class="product-actions">
                                    <button class="action-btn wishlist" data-product-id="<?= $p['id'] ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="action-btn cart" data-product-id="<?= $p['id'] ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No popular products available at the moment.</p>
                    <a href="products.php" class="btn btn-primary">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====================
         FEATURES
    ==================== -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title text-center mb-5">Why Choose PROGLIDE</h2>
            
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
                        <a href="subcategories.php?cat=<?= $categoryData['slug'] ?>" class="view-all">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="products-grid">
                        <?php while($p = $categoryData['products']->fetch_assoc()): 
                            $folder = ($p['main_category_id'] == 2) ? 'backcases' : 'protectors';
                            $name = $p['model_name'] ?? $p['design_name'];
                            $price = number_format($p['price'], 2);
                        ?>
                            <div class="product-card">
                                <a href="productdetails.php?id=<?= $p['id'] ?>">
                                    <img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($p['image']) ?>" 
                                         alt="<?= htmlspecialchars($name) ?>" 
                                         class="product-image"
                                         onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                                </a>
                                
                                <div class="product-info">
                                    <div class="product-category"><?= htmlspecialchars($p['main_category_name']) ?></div>
                                    
                                    <h3 class="product-name">
                                        <a href="productdetails.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($name) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-type"><?= htmlspecialchars($p['type_name']) ?></div>
                                    
                                    <div class="product-price">₹ <?= $price ?></div>
                                    
                                    <div class="product-actions">
                                        <button class="action-btn wishlist" data-product-id="<?= $p['id'] ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="action-btn cart" data-product-id="<?= $p['id'] ?>">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
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

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>

    <!-- ====================
         JAVASCRIPT
    ==================== -->
    <script>
        'use strict';
        
        // Wishlist functionality
        document.addEventListener('click', function(e) {
            const wishlistBtn = e.target.closest('.wishlist');
            if (wishlistBtn) {
                e.preventDefault();
                const productId = wishlistBtn.dataset.productId;
                const isActive = wishlistBtn.classList.contains('active');
                
                // Toggle UI
                wishlistBtn.classList.toggle('active');
                const icon = wishlistBtn.querySelector('i');
                
                if (wishlistBtn.classList.contains('active')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    showNotification('Added to wishlist!', 'success');
                    
                    // Send AJAX request
                    fetch(`ajax/add_to_wishlist.php?id=${productId}`)
                        .catch(err => console.error('Wishlist error:', err));
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    showNotification('Removed from wishlist', 'info');
                    
                    // Send AJAX request
                    fetch(`ajax/remove_wishlist.php?id=${productId}`)
                        .catch(err => console.error('Wishlist error:', err));
                }
            }
            
            // Cart functionality
            const cartBtn = e.target.closest('.cart');
            if (cartBtn) {
                e.preventDefault();
                const productId = cartBtn.dataset.productId;
                const isAdded = cartBtn.classList.contains('added');
                
                // Toggle UI
                cartBtn.classList.toggle('added');
                const icon = cartBtn.querySelector('i');
                const text = cartBtn.querySelector('span') || cartBtn;
                
                if (cartBtn.classList.contains('added')) {
                    cartBtn.innerHTML = '<i class="fas fa-check"></i> Added';
                    showNotification('Added to cart!', 'success');
                    
                    // Update cart count
                    updateCartCount(1);
                    
                    // Send AJAX request
                    fetch(`ajax/add_to_cart.php?id=${productId}`)
                        .catch(err => console.error('Cart error:', err));
                } else {
                    cartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    showNotification('Removed from cart', 'info');
                    
                    // Update cart count
                    updateCartCount(-1);
                    
                    // Send AJAX request
                    fetch(`ajax/remove_cart.php?id=${productId}`)
                        .catch(err => console.error('Cart error:', err));
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
            }
        }
        
        function showNotification(message, type) {
            // Create notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            notification.innerHTML = `
                <strong>${type === 'success' ? '✓' : type === 'info' ? 'ℹ' : '✗'} </strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                if (!this.hasAttribute('data-error-handled')) {
                    this.setAttribute('data-error-handled', 'true');
                    this.src = 'https://via.placeholder.com/300x200?text=No+Image';
                }
            });
        });
    </script>
</body>
</html>