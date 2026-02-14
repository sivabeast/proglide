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
            'id' => $cat['id'],
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
<meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style/index.css">
<link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">


<style>
/* Horizontal Scroll Styles */
.scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) #e0e0e0;
    padding-bottom: 15px;
}

.scroll-container::-webkit-scrollbar {
    height: 6px;
}

.scroll-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.scroll-container::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 10px;
}

.scroll-container::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

.scroll-wrapper {
    display: flex;
    gap: 20px;
    padding: 10px 0 20px 0;
}

.scroll-wrapper .product-card {
    flex: 0 0 280px;
    width: 280px;
}
</style>
</head>
<body>

<?php include "includes/header.php"; ?>

<!-- ============================================
     HERO SECTION - DESKTOP ONLY
     ============================================ -->
<section class="hero-desktop-only">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
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
                    <div style="background: rgba(255,255,255,0.05); padding: 12px 20px; border-radius: 50px; display: inline-flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <i class="fas fa-hand-sparkles" style="color: var(--primary);"></i>
                        <span>Welcome back, <strong style="color: white;"><?= htmlspecialchars($user_name) ?></strong>!</span>
                    </div>
                <?php endif; ?>
                
                <h1 class="hero-title">
                    Protect Your Devices<br>
                    With <span>PROGLIDE</span>
                </h1>
                
                <p class="hero-subtitle">
                    Premium screen protectors, stylish back cases, and high-quality accessories for your devices.
                </p>
                
                <div class="d-flex gap-3">
                    <a href="#popular-products" class="btn" style="background: var(--primary); color: white; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none;">
                        <i class="fas fa-shopping-bag me-2"></i> Shop Now
                    </a>
                    <a href="#categories" class="btn" style="background: rgba(255,255,255,0.1); color: white; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,0.2);">
                        <i class="fas fa-th-large me-2"></i> Categories
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
            
            <div class="col-lg-5 d-none d-lg-block text-center">
                <img src="/proglide/image/logo.png" alt="PROGLIDE" style="max-height: 300px; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));">
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     CATEGORIES - FLIPCART/AMAZON STYLE (TINY CARDS)
     ============================================ -->
<section id="categories" class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-th"></i>
                Shop by Category
            </h2>
            <a href="products.php" class="view-all-btn">
                View All <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <div class="categories-grid">
            <?php 
            $categories->data_seek(0);
            while($c = $categories->fetch_assoc()): 
                $category_image = !empty($c['image']) ? "/proglide/uploads/categories/" . htmlspecialchars($c['image']) : '/proglide/assets/no-image.png';
                $count_query = $conn->query("SELECT COUNT(*) FROM products WHERE category_id = {$c['id']} AND status = 1");
                $product_count = $count_query->fetch_row()[0];
            ?>
                <a href="products.php?cat=<?= $c['id'] ?>" class="category-item">
                    <img src="<?= $category_image ?>" 
                         onerror="this.src='/proglide/assets/no-image.png'"
                         alt="<?= htmlspecialchars($c['name']) ?>"
                         loading="lazy">
                    <span><?= htmlspecialchars($c['name']) ?></span>
                    <span class="category-count"><?= $product_count ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ============================================
     POPULAR PRODUCTS - HORIZONTAL SCROLL (MANUAL)
     ============================================ -->
<section id="popular-products" class="products-section">
    <div class="container">
        <div class="products-header">
            <h2 class="products-title">
                <i class="fas fa-fire" style="color: #fb641b;"></i>
                Popular Products
            </h2>
            <a href="products.php" class="view-all-btn">
                View All <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <!-- HORIZONTAL SCROLL CONTAINER - MANUAL SCROLL ONLY -->
        <div class="scroll-container">
            <div class="scroll-wrapper" id="popular-scroll">
                <?php 
                $popular->data_seek(0);
                while($p = $popular->fetch_assoc()): 
                    $discount = 0;
                    if ($p['original_price'] && $p['original_price'] > $p['price']) {
                        $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                    }
                    
                    if ($p['category_id'] == 1) {
                        $product_name = trim(($p['model_name'] ?? 'Protector') . 
                            ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                    } elseif ($p['category_id'] == 2) {
                        $product_name = $p['design_name'] ?? 'Back Case';
                    } else {
                        $product_name = $p['model_name'] ?? 'Product';
                    }
                    
                    $image_path = productImage($p['category_slug'], $p['image1']);
                    
                    $cart_button_class = '';
                    $cart_button_text = '<i class="fas fa-shopping-cart"></i> Add';
                    if ($user_id) {
                        $check_cart = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                        $check_cart->bind_param("ii", $user_id, $p['id']);
                        $check_cart->execute();
                        if ($check_cart->get_result()->num_rows > 0) {
                            $cart_button_class = 'added';
                            $cart_button_text = '<i class="fas fa-check"></i> Added';
                        }
                    }
                    
                    $in_wishlist = '';
                    $wishlist_icon = 'far fa-heart';
                    if ($user_id) {
                        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                        $check_wish->bind_param("ii", $user_id, $p['id']);
                        $check_wish->execute();
                        if ($check_wish->get_result()->num_rows > 0) {
                            $in_wishlist = ' active';
                            $wishlist_icon = 'fas fa-heart';
                        }
                    }
                ?>
                    <div class="product-card" data-product-id="<?= $p['id'] ?>">
                        <div class="product-image-wrapper">
                            <img src="<?= $image_path ?>"
                                 alt="<?= htmlspecialchars($product_name) ?>"
                                 onerror="this.src='/proglide/assets/no-image.png'"
                                 loading="lazy">
                            
                            <?php if ($p['is_popular']): ?>
                                <span class="popular-badge">
                                    <i class="fas fa-crown"></i> POPULAR
                                </span>
                            <?php endif; ?>
                            
                            <button class="wishlist-btn<?= $in_wishlist ?>" data-product-id="<?= $p['id'] ?>">
                                <i class="<?= $wishlist_icon ?>"></i>
                            </button>
                        </div>
                        
                        <div class="product-info">
                            <h4 class="product-title"><?= htmlspecialchars($product_name) ?></h4>
                            
                            <div class="product-meta">
                                <?php if ($p['material_name']): ?>
                                    <span class="product-material"><?= htmlspecialchars($p['material_name']) ?></span>
                                <?php endif; ?>
                                <span class="product-category"><?= htmlspecialchars($p['category_name']) ?></span>
                            </div>
                            
                            <div class="price-section">
                                <span class="current-price">₹<?= number_format($p['price'], 0) ?></span>
                                <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                                    <span class="original-price">₹<?= number_format($p['original_price'], 0) ?></span>
                                    <span class="discount-badge">-<?= $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($p['category_id'] == 2): ?>
                                    <button class="action-btn select-model" data-id="<?= $p['id'] ?>">
                                        <i class="fas fa-mobile-alt"></i> Select
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn add-to-cart <?= $cart_button_class ?>" data-id="<?= $p['id'] ?>">
                                        <?= $cart_button_text ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     CATEGORY WISE PRODUCTS - HORIZONTAL SCROLL (MANUAL)
     ============================================ -->
<?php foreach($categoryProducts as $cat): ?>
<section class="products-section">
    <div class="container">
        <div class="products-header">
            <h2 class="products-title">
                <i class="fas fa-box"></i>
                <?= htmlspecialchars($cat['name']) ?>
            </h2>
            <a href="products.php?cat=<?= $cat['id'] ?>" class="view-all-btn">
                View All <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        
        <!-- HORIZONTAL SCROLL CONTAINER - MANUAL SCROLL ONLY -->
        <div class="scroll-container">
            <div class="scroll-wrapper" id="category-scroll-<?= $cat['id'] ?>">
                <?php 
                $cat['products']->data_seek(0);
                while($p = $cat['products']->fetch_assoc()): 
                    $discount = 0;
                    if ($p['original_price'] && $p['original_price'] > $p['price']) {
                        $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                    }
                    
                    if ($p['category_id'] == 1) {
                        $product_name = trim(($p['model_name'] ?? 'Protector') . 
                            ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                    } elseif ($p['category_id'] == 2) {
                        $product_name = $p['design_name'] ?? 'Back Case';
                    } else {
                        $product_name = $p['model_name'] ?? 'Product';
                    }
                    
                    $image_path = productImage($cat['slug'], $p['image1']);
                    
                    $cart_button_class = '';
                    $cart_button_text = '<i class="fas fa-shopping-cart"></i> Add';
                    if ($user_id) {
                        $check_cart = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                        $check_cart->bind_param("ii", $user_id, $p['id']);
                        $check_cart->execute();
                        if ($check_cart->get_result()->num_rows > 0) {
                            $cart_button_class = 'added';
                            $cart_button_text = '<i class="fas fa-check"></i> Added';
                        }
                    }
                    
                    $in_wishlist = '';
                    $wishlist_icon = 'far fa-heart';
                    if ($user_id) {
                        $check_wish = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                        $check_wish->bind_param("ii", $user_id, $p['id']);
                        $check_wish->execute();
                        if ($check_wish->get_result()->num_rows > 0) {
                            $in_wishlist = ' active';
                            $wishlist_icon = 'fas fa-heart';
                        }
                    }
                ?>
                    <div class="product-card" data-product-id="<?= $p['id'] ?>">
                        <div class="product-image-wrapper">
                            <img src="<?= $image_path ?>"
                                 alt="<?= htmlspecialchars($product_name) ?>"
                                 onerror="this.src='/proglide/assets/no-image.png'"
                                 loading="lazy">
                            
                            <?php if ($p['is_popular']): ?>
                                <span class="popular-badge">
                                    <i class="fas fa-crown"></i> POPULAR
                                </span>
                            <?php endif; ?>
                            
                            <button class="wishlist-btn<?= $in_wishlist ?>" data-product-id="<?= $p['id'] ?>">
                                <i class="<?= $wishlist_icon ?>"></i>
                            </button>
                        </div>
                        
                        <div class="product-info">
                            <h4 class="product-title"><?= htmlspecialchars($product_name) ?></h4>
                            
                            <div class="product-meta">
                                <?php if ($p['material_name']): ?>
                                    <span class="product-material"><?= htmlspecialchars($p['material_name']) ?></span>
                                <?php endif; ?>
                                <span class="product-category"><?= htmlspecialchars($p['category_name']) ?></span>
                            </div>
                            
                            <div class="price-section">
                                <span class="current-price">₹<?= number_format($p['price'], 0) ?></span>
                                <?php if ($p['original_price'] && $p['original_price'] > $p['price']): ?>
                                    <span class="original-price">₹<?= number_format($p['original_price'], 0) ?></span>
                                    <span class="discount-badge">-<?= $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($p['category_id'] == 2): ?>
                                    <button class="action-btn select-model" data-id="<?= $p['id'] ?>">
                                        <i class="fas fa-mobile-alt"></i> Select
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn add-to-cart <?= $cart_button_class ?>" data-id="<?= $p['id'] ?>">
                                        <?= $cart_button_text ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>
<?php endforeach; ?>

<!-- ============================================
     FEATURES SECTION
     ============================================ -->
<section class="features-section">
    <div class="container">
        <div class="products-header">
            <h2 class="products-title">
                <i class="fas fa-star"></i>
                Why Choose PROGLIDE?
            </h2>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Premium Protection</h3>
                <p class="feature-text">9H hardness, military grade</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-text">Above ₹499, fast delivery</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3 class="feature-title">Easy Returns</h3>
                <p class="feature-text">30-day return policy</p>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-text">Always here to help</p>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    const userId = <?= $user_id ? 'true' : 'false' ?>;
    
    // ========== PRODUCT CARD CLICK ==========
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.wishlist-btn') && !e.target.closest('.action-btn')) {
                const productId = this.dataset.productId;
                window.location.href = 'productdetails.php?id=' + productId;
            }
        });
    });
    
    // ========== ADD TO CART ==========
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.id;
            const isAdded = this.classList.contains('added');
            const originalBtn = this;
            const originalText = this.innerHTML;
            
            <?php if (!$user_id): ?>
                if (confirm('Please login to add items to cart.')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
                return;
            <?php endif; ?>
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            if (isAdded) {
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('ajax/remove_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        originalBtn.classList.remove('added');
                        originalBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add';
                        showToast('Removed from cart', 'success');
                        updateCartCount(result.cart_count);
                    } else {
                        originalBtn.innerHTML = originalText;
                        showToast(result.message || 'Failed to remove', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    originalBtn.innerHTML = originalText;
                    showToast('Network error', 'error');
                })
                .finally(() => {
                    originalBtn.disabled = false;
                });
            } else {
                fetch('ajax/add_to_cart.php?id=' + productId + '&qty=1')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            originalBtn.classList.add('added');
                            originalBtn.innerHTML = '<i class="fas fa-check"></i> Added';
                            showToast('Added to cart!', 'success');
                            updateCartCount(result.cart_count);
                        } else {
                            originalBtn.innerHTML = originalText;
                            showToast(result.message || 'Failed to add', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        originalBtn.innerHTML = originalText;
                        showToast('Network error', 'error');
                    })
                    .finally(() => {
                        originalBtn.disabled = false;
                    });
            }
        });
    });
    
    // ========== SELECT MODEL ==========
    document.querySelectorAll('.select-model').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'productdetails.php?id=' + this.dataset.id;
        });
    });
    
    // ========== WISHLIST ==========
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            const isActive = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            
            <?php if (!$user_id): ?>
                if (confirm('Please login to add to wishlist.')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
                return;
            <?php endif; ?>
            
            if (isActive) {
                fetch('ajax/remove_wishlist.php?product_id=' + productId)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            this.classList.remove('active');
                            heartIcon.className = 'far fa-heart';
                            showToast('Removed from wishlist', 'success');
                        } else {
                            showToast(result.message || 'Failed to remove', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Network error', 'error');
                    });
            } else {
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('ajax/add_to_wishlist.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.classList.add('active');
                        heartIcon.className = 'fas fa-heart';
                        showToast('Added to wishlist', 'success');
                    } else {
                        showToast(result.message || 'Failed to add', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error', 'error');
                });
            }
        });
    });
    
    // ========== IMAGE ERROR HANDLING ==========
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            if (!this.hasAttribute('data-error-handled')) {
                this.setAttribute('data-error-handled', 'true');
                this.src = '/proglide/assets/no-image.png';
            }
        });
    });
    
    // ========== TOAST NOTIFICATION ==========
    function showToast(message, type = 'info') {
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-message ${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    
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
    
    // ========== SMOOTH SCROLL FOR ANCHOR LINKS ==========
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});
</script>

</body>
</html>