<?php
session_start();
require "includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

/* =========================
   CATEGORIES (HOME)
========================= */
$categories = $conn->query("
    SELECT id, name, slug, image
    FROM categories
    WHERE status = 1
    ORDER BY id ASC
");

/* =========================
   STATISTICS
========================= */
$stats = [
    'products' => $conn->query("SELECT COUNT(*) FROM products WHERE status=1")->fetch_row()[0],
    'categories' => $conn->query("SELECT COUNT(*) FROM categories WHERE status=1")->fetch_row()[0],
    'popular' => $conn->query("SELECT COUNT(*) FROM products WHERE status=1 AND is_popular=1")->fetch_row()[0],
    'users' => $conn->query("SELECT COUNT(*) FROM users WHERE status=1")->fetch_row()[0]
];

/* =========================
   POPULAR PRODUCTS
========================= */
$popular = $conn->query("
    SELECT p.*,
           c.name AS category_name,
           c.slug AS category_slug,
           mt.name AS material_name,
           vt.name AS variant_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN material_types mt ON p.material_type_id = mt.id
    LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
    WHERE p.status = 1 AND p.is_popular = 1
    ORDER BY p.created_at DESC
    LIMIT 12
");

/* =========================
   CATEGORY WISE PRODUCTS
========================= */
$categoryProducts = [];
$catRes = $conn->query("SELECT id, name, slug FROM categories WHERE status=1");

while ($cat = $catRes->fetch_assoc()) {
    $cid = $cat['id'];

    $prod = $conn->query("
        SELECT p.*,
               c.name AS category_name,
               c.slug AS category_slug,
               mt.name AS material_name,
               vt.name AS variant_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN material_types mt ON p.material_type_id = mt.id
        LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
        WHERE p.status=1 AND p.category_id=$cid
        ORDER BY p.created_at DESC
        LIMIT 10
    ");

    if ($prod->num_rows > 0) {
        $categoryProducts[] = [
            'name' => $cat['name'],
            'slug' => $cat['slug'],
            'products' => $prod
        ];
    }
}

/* =========================
   HELPERS
========================= */
function productImage($slug, $img) {
    if (!$img) return "/proglide/assets/no-image.png";

    $base = "/proglide/uploads/products/";
    $map = [
        'protectors' => 'protectors',
        'backcases'  => 'backcases',
        'airpods'    => 'airpods',
        'mobilebatteries' => 'battery'
    ];
    $folder = $map[$slug] ?? 'others';
    $path = $base . $folder . '/' . $img;
    
    // Check if file exists
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return "/proglide/assets/no-image.png";
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PROGLIDE | Premium Phone Accessories</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
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
    padding-bottom: 80px; /* For mobile bottom nav */
}

/* ================= HERO SECTION ================= */
.hero-section {
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    color: white;
    padding: 40px 0 60px;
    position: relative;
    overflow: hidden;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--secondary);
    color: white;
    padding: 6px 12px;
    border-radius: 50px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.hero-title {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 15px;
}

.hero-title span {
    color: var(--secondary);
}

.hero-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 25px;
    max-width: 500px;
}

.hero-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.hero-btn {
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
    border: 2px solid transparent;
}

.hero-btn-primary {
    background: var(--secondary);
    color: white;
}

.hero-btn-primary:hover {
    background: #e55a2b;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(255, 107, 53, 0.3);
}

.hero-btn-outline {
    background: transparent;
    color: white;
    border-color: rgba(255,255,255,0.3);
}

.hero-btn-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: var(--secondary);
    color: var(--secondary);
    transform: translateY(-2px);
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
}

.stat-card {
    text-align: center;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius);
    border: 1px solid rgba(255,255,255,0.1);
    transition: var(--transition);
}

.stat-card:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-3px);
    border-color: var(--secondary);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--secondary);
    display: block;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hero-image img {
    max-width: 100%;
    height: auto;
    border-radius: var(--radius);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

/* ================= CATEGORIES SECTION ================= */
.categories-section {
    padding: 30px 0;
    background: var(--light);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(white);
    margin: 0;
}

.view-all-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--secondary);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    padding: 6px 12px;
    border-radius: 50px;
    background: rgba(255, 107, 53, 0.1);
}

.view-all-btn:hover {
    gap: 10px;
    background: rgba(255, 107, 53, 0.2);
    color: var(--secondary);
}

.scroll-row {
    display: flex;
    gap: 1px;
    overflow-x: auto;
    padding: 10px 0;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}

.scroll-row::-webkit-scrollbar {
    display: none;
}

/* Category cards without border */
.category-card {
    min-width: 150px;
    
    border-radius: var(--radius);
    padding: 0px;
    text-align: center;
    scroll-snap-align: start;
    text-decoration: none;
    color: var(--dark);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    align-items: center;
    border: none; /* Remove border */
}

.category-card:hover {
    transform: translateY(-5px);
    
    text-decoration: none;
    color: var(--dark);
}

.category-card img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 10px;
    border-radius: 50%;
    padding: 10px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.category-card p {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--primary);
}

/* ================= PRODUCTS SECTION ================= */
.products-section {
    padding: 30px 0;
}

.product-card {
    min-width: 200px;
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    scroll-snap-align: start;
    box-shadow: var(--shadow);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.product-card a {
    text-decoration: none;
    color: inherit;
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 160px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.product-image-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    max-height: 130px;
}

.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    border: 1px solid var(--border);
    color: var(--gray);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    z-index: 2;
    text-decoration: none;
}

.wishlist-btn:hover {
    color: #e74c3c;
    border-color: #e74c3c;
}

.wishlist-btn.active {
    background: #e74c3c;
    color: white;
    border-color: #e74c3c;
}

.popular-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: var(--secondary);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 2;
}

.product-body {
    padding: 12px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-title {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 4px;
    height: 36px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    color: var(--dark);
}

.product-material {
    font-size: 0.8rem;
    color: var(--gray);
    margin-bottom: 4px;
}

.product-category {
    font-size: 0.75rem;
    color: var(--secondary);
    margin-bottom: 8px;
    font-weight: 500;
}

.price-section {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.current-price {
    font-weight: 700;
    color: var(--accent);
    font-size: 1.1rem;
    display: block;
}

.original-price {
    font-size: 0.8rem;
    color: var(--gray);
    text-decoration: line-through;
}

.discount {
    font-size: 0.75rem;
    color: #e74c3c;
    font-weight: 600;
    background: rgba(231, 76, 60, 0.1);
    padding: 1px 6px;
    border-radius: 8px;
}

/* Action Buttons */
.action-btn {
    width: 100%;
    border: none;
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
    margin-top: auto;
    text-decoration: none;
}

.add-to-cart-btn {
    background: var(--secondary);
    color: #000;
}

.add-to-cart-btn:hover {
    background: #e55a2b;
    transform: translateY(-2px);
}

.add-to-cart-btn.added {
    background: var(--accent);
    color: white;
}

.select-model-btn {
    background: #3498db;
    color: white;
}

.select-model-btn:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

/* ================= FEATURES SECTION ================= */
.features-section {
    padding: 40px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.feature-card {
    background: white;
    padding: 20px;
    border-radius: var(--radius);
    text-align: center;
    box-shadow: var(--shadow);
    transition: var(--transition);
    border: 1px solid var(--border);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
    border-color: var(--secondary);
}

.feature-icon {
    width: 50px;
    height: 50px;
    background: var(--secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 1.3rem;
    color: white;
    transition: var(--transition);
}

.feature-card:hover .feature-icon {
    transform: rotate(15deg) scale(1.1);
}

.feature-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--primary);
}

.feature-text {
    color: var(--gray);
    font-size: 0.85rem;
    line-height: 1.5;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 768px) {
    .hero-section {
        padding: 30px 0 40px;
    }
    
    .hero-title {
        font-size: 1.8rem;
    }
    
    .hero-actions {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .hero-btn {
        width: 100%;
        justify-content: center;
    }
    
    .hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .view-all-btn {
        align-self: flex-start;
    }
    
    .category-card {
        min-width: 130px;
    }
    
    .category-card img {
        width: 70px;
        height: 70px;
    }
    
    .product-card {
        min-width: 160px;
    }
    
    .product-image-container {
        height: 140px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .category-card {
        min-width: 110px;
    }
    
    .category-card img {
        width: 60px;
        height: 60px;
    }
    
    .product-card {
        min-width: 140px;
    }
    
    .product-image-container {
        height: 120px;
    }
    
    .product-title {
        font-size: 0.85rem;
    }
    
    .current-price {
        font-size: 1rem;
    }
}

/* Toast Notifications */
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 250px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
</head>

<body>

<?php include "includes/header.php"; ?>

<!-- ================= HERO SECTION ================= -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-bolt"></i>
                        Premium Quality • 9H Hardness • Free Shipping
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
                    
                    <h1 class="hero-title">
                        Protect Your Devices<br>
                        With <span>PROGLIDE</span>
                    </h1>
                    
                    <p class="hero-subtitle">
                        Discover premium screen protectors, stylish back cases, and high-quality accessories 
                        for your devices. Military-grade protection meets premium style.
                    </p>
                    
                    <div class="hero-actions">
                        <a href="#products" class="hero-btn hero-btn-primary">
                            <i class="fas fa-shopping-bag"></i> Shop Now
                        </a>
                        <a href="#categories" class="hero-btn hero-btn-outline">
                            <i class="fas fa-th-large"></i> Browse Categories
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat-card">
                            <span class="stat-number"><?= $stats['products'] ?></span>
                            <span class="stat-label">Products</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= $stats['categories'] ?></span>
                            <span class="stat-label">Categories</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= $stats['popular'] ?></span>
                            <span class="stat-label">Popular</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= $stats['users'] ?></span>
                            <span class="stat-label">Customers</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="hero-image text-center">
                    <img src="/proglide/image/logo.png" 
                         alt="PROGLIDE Premium Phone Accessories" 
                         class="img-fluid"
                         style="max-height: 350px;"
                         loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= CATEGORIES SECTION ================= -->
<section id="categories" class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop by Category</h2>
            
        </div>
        
        <div class="scroll-row">
            <?php while($c = $categories->fetch_assoc()): 
                $category_image = !empty($c['image']) ? "/proglide/uploads/categories/" . htmlspecialchars($c['image']) : '/proglide/assets/no-image.png';
            ?>
                <a href="products.php?cat=<?= $c['id'] ?>" class="category-card">
                    <img src="<?= $category_image ?>" 
                         onerror="this.src='/proglide/assets/no-image.png'"
                         alt="<?= htmlspecialchars($c['name']) ?>">
                    <p><?= htmlspecialchars($c['name']) ?></p>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ================= POPULAR PRODUCTS ================= -->
<section id="products" class="products-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-fire text-danger me-2"></i>
                Popular Products
            </h2>
            <a href="products.php" class="view-all-btn">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="scroll-row">
            <?php while($p = $popular->fetch_assoc()): 
                // Calculate discount
                $discount = 0;
                if ($p['original_price'] && $p['original_price'] > $p['price']) {
                    $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                }
                
                // Get product name based on category
                if ($p['category_id'] == 1) {
                    $product_name = trim(($p['model_name'] ?? 'Protector') . 
                        ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                } elseif ($p['category_id'] == 2) {
                    $product_name = $p['design_name'] ?? 'Back Case';
                } else {
                    $product_name = $p['model_name'] ?? 'Product';
                }
                
                // Get image path
                $image_path = productImage($p['category_slug'], $p['image1']);
            ?>
                <div class="product-card" data-product-id="<?= $p['id'] ?>">
                    <a href="productdetails.php?id=<?= $p['id'] ?>">
                        <div class="product-image-container">
                            <img src="<?= $image_path ?>"
                                 alt="<?= htmlspecialchars($product_name) ?>"
                                 onerror="this.src='/proglide/assets/no-image.png'">
                            <?php if ($p['is_popular']): ?>
                                <span class="popular-badge">Popular</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-body">
                        <h4 class="product-title"><?= htmlspecialchars($product_name) ?></h4>
                        
                        <?php if ($p['material_name']): ?>
                            <p class="product-material"><?= htmlspecialchars($p['material_name']) ?></p>
                        <?php endif; ?>
                        
                        <p class="product-category"><?= htmlspecialchars($p['category_name']) ?></p>
                        
                        <div class="price-section">
                            <span class="current-price">₹<?= number_format($p['price'], 2) ?></span>
                            <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                                <span class="original-price">₹<?= number_format($p['original_price'], 2) ?></span>
                                <span class="discount">-<?= $discount ?>%</span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="action-btn <?= $p['category_id']==2?'select-model-btn':'add-to-cart-btn' ?>" 
                                data-id="<?= $p['id'] ?>"
                                data-category="<?= $p['category_id'] ?>">
                            <?php if ($p['category_id'] == 2): ?>
                                <i class="fas fa-mobile-alt"></i> Select Model
                            <?php else: ?>
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            <?php endif; ?>
                        </button>
                        
                        <button class="wishlist-btn" data-product-id="<?= $p['id'] ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ================= CATEGORY WISE PRODUCTS ================= -->
<?php foreach($categoryProducts as $cat): ?>
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><?= htmlspecialchars($cat['name']) ?></h2>
            <a href="products.php?cat=<?= $cat['slug'] ?>" class="view-all-btn">
                View All <?= htmlspecialchars($cat['name']) ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="scroll-row">
            <?php while($p = $cat['products']->fetch_assoc()): 
                // Calculate discount
                $discount = 0;
                if ($p['original_price'] && $p['original_price'] > $p['price']) {
                    $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                }
                
                // Get product name based on category
                if ($p['category_id'] == 1) {
                    $product_name = trim(($p['model_name'] ?? 'Protector') . 
                        ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                } elseif ($p['category_id'] == 2) {
                    $product_name = $p['design_name'] ?? 'Back Case';
                } else {
                    $product_name = $p['model_name'] ?? 'Product';
                }
                
                // Get image path
                $image_path = productImage($cat['slug'], $p['image1']);
            ?>
                <div class="product-card" data-product-id="<?= $p['id'] ?>">
                    <a href="productdetails.php?id=<?= $p['id'] ?>">
                        <div class="product-image-container">
                            <img src="<?= $image_path ?>"
                                 alt="<?= htmlspecialchars($product_name) ?>"
                                 onerror="this.src='/proglide/assets/no-image.png'">
                            <?php if ($p['is_popular']): ?>
                                <span class="popular-badge">Popular</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-body">
                        <h4 class="product-title"><?= htmlspecialchars($product_name) ?></h4>
                        
                        <?php if ($p['material_name']): ?>
                            <p class="product-material"><?= htmlspecialchars($p['material_name']) ?></p>
                        <?php endif; ?>
                        
                        <p class="product-category"><?= htmlspecialchars($p['category_name']) ?></p>
                        
                        <div class="price-section">
                            <span class="current-price">₹<?= number_format($p['price'], 2) ?></span>
                            <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                                <span class="original-price">₹<?= number_format($p['original_price'], 2) ?></span>
                                <span class="discount">-<?= $discount ?>%</span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="action-btn <?= $p['category_id']==2?'select-model-btn':'add-to-cart-btn' ?>" 
                                data-id="<?= $p['id'] ?>"
                                data-category="<?= $p['category_id'] ?>">
                            <?php if ($p['category_id'] == 2): ?>
                                <i class="fas fa-mobile-alt"></i> Select Model
                            <?php else: ?>
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            <?php endif; ?>
                        </button>
                        
                        <button class="wishlist-btn" data-product-id="<?= $p['id'] ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<!-- ================= FEATURES SECTION ================= -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title text-center mb-4">Why Choose PROGLIDE?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Premium Protection</h3>
                <p class="feature-text">
                    9H hardness screen protectors that withstand drops, scratches, and daily wear.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-text">
                    Free shipping on all orders above ₹499. Fast delivery across India.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3 class="feature-title">Easy Returns</h3>
                <p class="feature-text">
                    30-day return policy. If you're not satisfied, return it for a full refund.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-text">
                    Our customer support team is available to assist with any questions.
                </p>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

<!-- ================= JAVASCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle product card clicks (navigate to product details)
    document.querySelectorAll('.product-card > a').forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't navigate if clicking on buttons inside the card
            if (!e.target.closest('.wishlist-btn') && 
                !e.target.closest('.action-btn') &&
                !e.target.closest('.add-to-cart-btn') &&
                !e.target.closest('.select-model-btn')) {
                window.location.href = this.href;
            }
        });
    });
    
    // Handle Add to Cart button clicks
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.id;
            const isBackCase = this.dataset.category == 2;
            
            <?php if (!$user_id): ?>
                // Show login modal or redirect to login
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            const originalBtn = this;
            const originalHtml = this.innerHTML;
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            fetch('ajax/add_to_cart.php?id=' + productId + '&qty=1')
                .then(response => response.text())
                .then(result => {
                    if (result.includes('success') || result.trim() === '') {
                        originalBtn.classList.add('added');
                        originalBtn.innerHTML = '<i class="fas fa-check"></i> Added';
                        
                        // Update cart count in header
                        updateCartCount();
                        
                        // Show success message
                        showToast('Product added to cart!', 'success');
                    } else {
                        originalBtn.innerHTML = originalHtml;
                        showToast('Failed to add to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Cart error:', error);
                    originalBtn.innerHTML = originalHtml;
                    showToast('Network error', 'error');
                })
                .finally(() => {
                    originalBtn.disabled = false;
                });
        });
    });
    
    // Handle Select Model button clicks (for back cases)
    document.querySelectorAll('.select-model-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.id;
            window.location.href = 'productdetails.php?id=' + productId;
        });
    });
    
    // Handle Wishlist button clicks
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            
            <?php if (!$user_id): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            
            const isActive = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            
            if (isActive) {
                
                fetch('ajax/remove_wishlist.php?id=' + productId)
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success')) {
                            this.classList.remove('active');
                            heartIcon.className = 'far fa-heart';
                            showToast('Removed from wishlist', 'success');
                        } else {
                            showToast('Failed to remove from wishlist', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Wishlist error:', error);
                        showToast('Network error', 'error');
                    });
            } else {
                // Add to wishlist
                fetch('ajax/add_to_wishlist.php?id=' + productId)
                    .then(response => response.text())
                    .then(result => {
                        if (result.includes('success')) {
                            this.classList.add('active');
                            heartIcon.className = 'fas fa-heart';
                            showToast('Added to wishlist', 'success');
                        } else {
                            showToast('Failed to add to wishlist', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Wishlist error:', error);
                        showToast('Network error', 'error');
                    });
            }
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '#!') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const header = document.querySelector('.navbar')?.offsetHeight || 70;
                
                window.scrollTo({
                    top: target.offsetTop - header - 20,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add hover effects to cards
    document.querySelectorAll('.product-card, .category-card, .feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

function updateCartCount() {
    // Update cart count in header
    const cartCountElements = document.querySelectorAll('.cart-count, .action-badge');
    cartCountElements.forEach(el => {
        const current = parseInt(el.textContent) || 0;
        el.textContent = current + 1;
        el.style.display = 'flex';
    });
}

function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
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

// Initialize product cards with correct wishlist status
document.addEventListener('DOMContentLoaded', function() {
    // This would typically be done server-side, but we'll handle it here
    // In a real implementation, you would check each product's wishlist status
    // when generating the page and add the 'active' class accordingly
});
</script>

</body>
</html>