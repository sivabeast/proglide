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
<title>PROGLIDE | Premium Phone Accessories</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
<meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

<style>
/* ============================================
   PROGLIDE - INDEX PAGE WITH PRODUCTS.PLY STYLE CARDS
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
}

/* ============================================
   HERO SECTION - DESKTOP ONLY
   ============================================ */
.hero-desktop-only {
    display: block;
    background: linear-gradient(145deg, #0f172a, #1e293b);
    color: white;
    padding: 40px 0 60px;
    position: relative;
    overflow: hidden;
}

@media (max-width: 768px) {
    .hero-desktop-only {
        display: none;
    }
}

.hero-badge {
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

.hero-title {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 20px;
}

.hero-title span {
    color: var(--primary);
}

.hero-subtitle {
    font-size: 1.1rem;
    color: #94a3b8;
    margin-bottom: 30px;
    max-width: 500px;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    max-width: 600px;
    margin-top: 40px;
}

.stat-card {
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 20px 15px;
    text-align: center;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 800;
    color: white;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.8rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ============================================
   CATEGORIES SECTION - HORIZONTAL SCROLL
   ============================================ */
.categories-section {
    background: var(--dark-card);
    padding: 25px 0;
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
    border-bottom: 1px solid var(--dark-border);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 0 20px;
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
    color: var(--primary);
    font-size: 1.2rem;
}

.view-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 8px 16px;
    background: var(--primary-light);
    border-radius: 30px;
    transition: var(--transition);
}

.view-all-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateX(5px);
}

.view-all-btn i {
    font-size: 0.8rem;
    transition: var(--transition);
}

.view-all-btn:hover i {
    transform: translateX(3px);
}

/* Horizontal Scroll Container */
.categories-scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) var(--dark-border);
    padding: 5px 20px 15px 20px;
    scroll-behavior: smooth;
}

.categories-scroll-container::-webkit-scrollbar {
    height: 6px;
}

.categories-scroll-container::-webkit-scrollbar-track {
    background: var(--dark-border);
    border-radius: 10px;
}

.categories-scroll-container::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 10px;
}

.categories-scroll-container::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Categories Wrapper - Horizontal Flex */
.categories-wrapper {
    display: flex;
    gap: 15px;
    padding: 5px 0;
    width: max-content;
}

/* Category Item - Horizontal Card */
.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: var(--text-primary);
    padding: 15px 12px;
    border-radius: var(--radius);
    transition: var(--transition);
    background: var(--dark-hover);
    border: 1px solid var(--dark-border);
    min-width: 110px;
    position: relative;
    overflow: hidden;
}

.category-item:hover {
    border-color: var(--primary);
    transform: translateY(-4px);
    box-shadow: var(--shadow);
    background: var(--dark-card);
}

.category-item img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 10px;
    padding: 8px;
    background: var(--dark-card);
    border-radius: 50%;
    transition: var(--transition);
}

.category-item:hover img {
    transform: scale(1.1);
}

.category-item span {
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    line-height: 1.3;
    margin-bottom: 4px;
}

.category-count {
    font-size: 0.7rem;
    color: var(--primary);
    background: var(--primary-light);
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 600;
}

/* ============================================
   PRODUCTS SECTION - HORIZONTAL SCROLL
   PRODUCTS.PLY STYLE CARDS
   ============================================ */
.products-section {
    background: white;
    padding: 25px 0;
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
}

.products-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    margin-bottom: 20px;
}

.products-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: black;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.products-title i {
    color: var(--primary);
    font-size: 1.1rem;
}

/* Scroll Container */
.scroll-container {
    position: relative;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 5px 20px 15px 20px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) var(--dark-border);
    scroll-behavior: smooth;
}

.scroll-container::-webkit-scrollbar {
    height: 6px;
}

.scroll-container::-webkit-scrollbar-track {
    background: var(--dark-border);
    border-radius: 10px;
}

.scroll-container::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 10px;
}

.scroll-wrapper {
    display: flex;
    gap: 20px;
    width: max-content;
    padding: 5px 0;
}

/* Product Card - Products.php Style */
.product-card {
    width: 260px;
    min-width: 260px;
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
.product-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 80%;
    background: white;
    overflow: hidden;
}

.product-image-wrapper img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image-wrapper img {
    transform: scale(1.05);
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

/* Wishlist Button */
.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    border-radius: 50%;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    z-index: 10;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.wishlist-btn:hover {
    background: #ff4d4d;
    color: white;
    border-color: #ff4d4d;
    transform: scale(1.1);
}

.wishlist-btn.active {
    background: #ff4d4d;
    color: white;
    border-color: #ff4d4d;
}

/* Product Info */
.product-info {
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

/* Material Type - Only this, no category */
.product-material {
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

/* Category tag - HIDDEN */
.product-category {
    display: none;
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

.discount-badge {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--success);
    background: rgba(76, 175, 80, 0.1);
    padding: 3px 8px;
    border-radius: 20px;
}

/* Action Buttons */
.product-actions {
    margin-top: auto;
    display: flex;
    width: 100%;
}

.action-btn {
    width: 100%;
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    border: none;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.add-to-cart {
    background: var(--primary);
    color: white;
}

.add-to-cart:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.add-to-cart.added {
    background: var(--success);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.select-model {
    background: linear-gradient(135deg, var(--info), #1976d2);
    color: white;
}

.select-model:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

/* ============================================
   FEATURES SECTION
   ============================================ */
.features-section {
    background: var(--dark-card);
    padding: 40px 0;
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    padding: 0 20px;
}

.feature-item {
    text-align: center;
    padding: 25px 15px;
    background: var(--dark-hover);
    border-radius: var(--radius);
    transition: var(--transition);
    border: 1px solid var(--dark-border);
}

.feature-item:hover {
    border-color: var(--primary);
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    color: white;
    font-size: 1.4rem;
}

.feature-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.feature-text {
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.5;
    margin: 0;
}

/* ============================================
   TOAST NOTIFICATION
   ============================================ */
.toast-message {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 9999;
    background: var(--dark-card);
    color: var(--text-primary);
    padding: 14px 20px;
    border-radius: var(--radius);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow);
    animation: slideIn 0.3s ease;
    max-width: 350px;
    border-left: 4px solid var(--primary);
}

.toast-message.success {
    border-left-color: var(--success);
}

.toast-message.error {
    border-left-color: var(--danger);
}

.toast-message i {
    font-size: 1.1rem;
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

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */

/* Tablet (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .product-card {
        width: 240px;
        min-width: 240px;
    }
    
    .category-item {
        min-width: 100px;
    }
}

/* Mobile (max-width: 767px) */
@media (max-width: 767px) {
    .hero-desktop-only {
        display: none;
    }
    
    .categories-section {
        padding: 15px 0;
    }
    
    .section-header {
        padding: 0 15px;
        margin-bottom: 15px;
    }
    
    .section-title {
        font-size: 1.2rem;
    }
    
    .section-title i {
        font-size: 1rem;
    }
    
    .view-all-btn {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    .categories-scroll-container {
        padding: 5px 15px 12px 15px;
    }
    
    .categories-wrapper {
        gap: 12px;
    }
    
    .category-item {
        min-width: 95px;
        padding: 12px 8px;
    }
    
    .category-item img {
        width: 50px;
        height: 50px;
        padding: 6px;
    }
    
    .category-item span {
        font-size: 0.75rem;
    }
    
    .category-count {
        font-size: 0.65rem;
        padding: 2px 6px;
    }
    
    .products-section {
        padding: 15px 0;
    }
    
    .products-header {
        padding: 0 15px;
        margin-bottom: 15px;
    }
    
    .products-title {
        font-size: 1.1rem;
    }
    
    .scroll-container {
        padding: 5px 15px 12px 15px;
    }
    
    .scroll-wrapper {
        gap: 15px;
    }
    
    .product-card {
        width: 220px;
        min-width: 220px;
    }
    
    .product-image-wrapper {
        padding-top: 75%;
    }
    
    .product-info {
        padding: 14px;
    }
    
    .product-title {
        font-size: 0.9rem;
        min-height: 42px;
    }
    
    .product-material {
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
    
    .discount-badge {
        font-size: 0.6rem;
        padding: 2px 6px;
    }
    
    .action-btn {
        padding: 10px 12px;
        font-size: 0.75rem;
        min-height: 40px;
    }
    
    .action-btn i {
        font-size: 0.75rem;
    }
    
    .wishlist-btn {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
    
    .features-section {
        padding: 30px 0;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 0 15px;
    }
    
    .feature-item {
        padding: 20px 10px;
    }
    
    .feature-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
        margin-bottom: 12px;
    }
    
    .feature-title {
        font-size: 0.9rem;
    }
    
    .feature-text {
        font-size: 0.7rem;
    }
    
    .toast-message {
        left: 15px;
        right: 15px;
        max-width: none;
    }
}

/* Small Mobile (max-width: 375px) */
@media (max-width: 375px) {
    .category-item {
        min-width: 85px;
        padding: 10px 6px;
    }
    
    .category-item img {
        width: 45px;
        height: 45px;
    }
    
    .category-item span {
        font-size: 0.7rem;
    }
    
    .product-card {
        width: 200px;
        min-width: 200px;
    }
    
    .product-info {
        padding: 12px;
    }
    
    .product-title {
        font-size: 0.85rem;
        min-height: 38px;
    }
    
    .action-btn {
        padding: 8px 10px;
        font-size: 0.7rem;
        min-height: 38px;
    }
    
    /* Hide text on very small screens */
    .action-btn span {
        display: none;
    }
    
    .action-btn i {
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
    .category-item:hover,
    .feature-item:hover {
        transform: none;
    }
    
    .action-btn:hover {
        transform: none;
    }
    
    .action-btn:active {
        transform: scale(0.98);
        opacity: 0.9;
    }
}

/* ============================================
   ANIMATIONS
   ============================================ */
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

.product-card,
.category-item,
.feature-item {
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
     CATEGORIES - HORIZONTAL SCROLL
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
        
        <div class="categories-scroll-container">
            <div class="categories-wrapper">
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
    </div>
</section>

<!-- ============================================
     POPULAR PRODUCTS - HORIZONTAL SCROLL
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
        
        <div class="scroll-container">
            <div class="scroll-wrapper">
                <?php 
                $popular->data_seek(0);
                while($p = $popular->fetch_assoc()): 
                    $discount = 0;
                    if ($p['original_price'] && $p['original_price'] > $p['price']) {
                        $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                    }
                    
                    // Product name based on category
                    if ($p['category_id'] == 1) {
                        $product_name = trim(($p['model_name'] ?? 'Protector') . 
                            ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                    } elseif ($p['category_id'] == 2) {
                        $product_name = $p['design_name'] ?? 'Back Case';
                    } else {
                        $product_name = $p['model_name'] ?? 'Product';
                    }
                    
                    $image_path = productImage($p['category_slug'], $p['image1']);
                    
                    // Cart button status
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
                    
                    // Wishlist status
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
                            
                            <?php if ($p['material_name']): ?>
                                <span class="product-material"><?= htmlspecialchars($p['material_name']) ?></span>
                            <?php endif; ?>
                            
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
     CATEGORY WISE PRODUCTS - HORIZONTAL SCROLL
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
        
        <div class="scroll-container">
            <div class="scroll-wrapper">
                <?php 
                $cat['products']->data_seek(0);
                while($p = $cat['products']->fetch_assoc()): 
                    $discount = 0;
                    if ($p['original_price'] && $p['original_price'] > $p['price']) {
                        $discount = round((($p['original_price'] - $p['price']) / $p['original_price']) * 100);
                    }
                    
                    // Product name based on category
                    if ($p['category_id'] == 1) {
                        $product_name = trim(($p['model_name'] ?? 'Protector') . 
                            ($p['variant_name'] ? " ({$p['variant_name']})" : ""));
                    } elseif ($p['category_id'] == 2) {
                        $product_name = $p['design_name'] ?? 'Back Case';
                    } else {
                        $product_name = $p['model_name'] ?? 'Product';
                    }
                    
                    $image_path = productImage($cat['slug'], $p['image1']);
                    
                    // Cart button status
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
                    
                    // Wishlist status
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
                            
                            <?php if ($p['material_name']): ?>
                                <span class="product-material"><?= htmlspecialchars($p['material_name']) ?></span>
                            <?php endif; ?>
                            
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