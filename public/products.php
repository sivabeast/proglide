<?php
session_start();
require "includes/db.php";

$category_id = $_GET['cat'] ?? '';
$category_name = '';

// Get category name for display
if ($category_id && $category_id !== 'all') {
    $cat_query = $conn->prepare("SELECT name FROM categories WHERE id = ? AND status = 1");
    $cat_query->bind_param("i", $category_id);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    if ($cat_result->num_rows > 0) {
        $category_data = $cat_result->fetch_assoc();
        $category_name = $category_data['name'];
    }
}

$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo $category_name ? htmlspecialchars($category_name) . ' | ' : '' ?>Products | PROGLIDE</title>
    <meta name="description" content="Premium phone protectors, cases, and accessories. 9H hardness, privacy screens, and stylish back cases.">
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- <link rel="stylesheet" href="public/style/products.css"> -->
    <style>
     /* ============================================
   PRODUCTS PAGE - COMPLETE CSS WITH FIXED BUTTONS
   ============================================ */

:root {
    --primary: #FF6B35;
    --primary-dark: #e55a2b;
    --primary-light: rgba(255, 107, 53, 0.1);
    --secondary: #FF8E53;
    --dark-bg: #302f2f;
    --dark-card: #ffffff;
    --dark-border: #e0e0e0;
    --dark-hover: #f8f8f8;
    --text-primary: #c0c0c0;
    --text-secondary: #b0b0b0;
    --text-muted: #999999;
    --success: #4CAF50;
    --warning: #FFC107;
    --danger: #F44336;
    --info: #2196F3;
    --radius: 12px;
    --radius-sm: 8px;
    --radius-lg: 16px;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
    --shadow-hover: 0 8px 24px rgba(255, 107, 53, 0.15);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:  #ffffff !important;
    color: var(--text-primary);
    line-height: 1.5;
    overflow-x: hidden;
    padding-top: 80px;
}

/* Page Header */
.page-header-section {
    background: linear-gradient(145deg, #0f172a, #1e293b);
    padding: 30px 0;
    margin-bottom: 25px;
    text-align: center;
    border-bottom: 1px solid var(--dark-border);
}

.page-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-light);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.8rem;
    color: var(--primary);
    margin-bottom: 15px;
    font-weight: 600;
}

.page-header-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 8px;
}

.page-header-subtitle {
    font-size: 1rem;
    color: var(--text-secondary);
    max-width: 600px;
    margin: 0 auto;
}

/* Search Section */
.search-section {
    max-width: 600px;
    margin: 0 auto 25px;
}

.search-container {
    position: relative;
    width: 100%;
}

.search-bar {
    width: 100%;
    padding: 12px 20px 12px 45px;
    background: #f1f1f1;
    border: 1px solid #000;
    border-radius: 50px;
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: var(--transition);
}

.search-bar:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.search-bar::placeholder {
    color: var(--text-muted);
}

.search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 1rem;
}

/* Filter Toggle (Mobile) */
.filter-toggle-container {
    display: none;
    margin-bottom: 20px;
}

.filter-toggle-btn {
    width: 100%;
    padding: 12px;
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: var(--transition);
}

.filter-toggle-btn:hover {
    background: var(--dark-hover);
    border-color: var(--primary);
}

.filter-toggle-btn i {
    color: var(--primary);
}

/* Filter Overlay (Mobile) */
.filter-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    backdrop-filter: blur(3px);
}

.filter-overlay.active {
    display: block;
}

/* Page Layout */
.page-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 25px;
    position: relative;
}

/* Filter Sidebar */
.filter-sidebar {
    background: #000;
    border-radius: var(--radius);
    border: 1px solid var(--dark-border);
    padding: 20px;
    height: fit-content;
    transition: var(--transition);
}

.filter-group {
    margin-bottom: 20px;
    border-bottom: 1px solid var(--dark-border);
    padding-bottom: 15px;
}

.filter-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.filter-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-title i {
    color: var(--primary);
    font-size: 0.9rem;
}

.filter-select {
    width: 100%;
    padding: 10px 12px;
    background: #0a0a0a;
    border: 1px solid #302f2f;
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 0.9rem;
    cursor: pointer;
    transition: var(--transition);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
}

.filter-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Price Range */
.price-range-container {
    display: flex;
    align-items: center;
    gap: 10px;
    
}

.price-input-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.price-input-group label {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-bottom: 4px;
    font-weight: 500;
}

.price-input {
    width: 100%;
    padding: 10px 12px;
    background: #0a0a0a;
    border: 1px solid #302f2f;
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 0.9rem;
    transition: var(--transition);
}

.price-input:focus {
    outline: none;
    border-color: var(--primary);
}

.price-separator {
    color: var(--text-muted);
    font-size: 0.8rem;
    margin-top: 20px;
}

.loading-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-top: 8px;
}

.loading-indicator i {
    color: var(--primary);
}

.filter-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 20px;
}

.filter-btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.filter-btn-primary {
    background: var(--primary);
    color: white;
}

.filter-btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

.filter-btn-secondary {
    background: var(--dark-hover);
    color: var(--text-primary);
    border: 1px solid var(--dark-border);
}

.filter-btn-secondary:hover {
    background: var(--dark-border);
}

.close-filter-btn {
    display: none;
    width: 100%;
    padding: 12px;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.close-filter-btn:hover {
    background: #d32f2f;
}

/* Products Section */
.products-section {
    min-height: 400px;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--dark-border);
    border-top-color: var(--primary);
    border-radius: 50%;
    margin: 0 auto 15px;
    animation: spin 1s linear infinite;
}

.search-results-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: var(--dark-card);
    border-radius: var(--radius);
    margin-bottom: 20px;
    color: var(--text-secondary);
    border-left: 4px solid var(--primary);
    font-size: 0.9rem;
}

.search-results-info i {
    color: var(--primary);
    font-size: 1rem;
}

/* ============================================
   PRODUCTS GRID - FIXED BUTTON ALIGNMENT
   ============================================ */
.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

/* Product Card - Fixed Height for Consistency */
.product-card {
    background: var(--dark-card);
    border-radius: var(--radius-lg);
    border: 1px solid #000;
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
    box-shadow: var(--shadow);
}

/* Product Image - Fixed Aspect Ratio */
.product-image-container {
    position: relative;
    width: 100%;
    padding-top: 80%;
    background: white;
    overflow: hidden;
    flex-shrink: 0;
}

.product-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

/* Popular Badge */
.popular-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: linear-gradient(135deg, var(--warning), #ff9800);
    color: #000;
    padding: 4px 8px;
    border-radius: 16px;
    font-size: 0.65rem;
    font-weight: 700;
    z-index: 5;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.popular-badge i {
    font-size: 0.6rem;
}

/* Wishlist Button */
.wishlist-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
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
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

/* Product Info - Flex Column for Equal Height */
.product-info {
    padding: 12px;
    display: flex;
    background: black;
    color: white;
    flex-direction: column;
    flex: 1;
}

.product-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 38px;
    line-height: 1.3;
}

.product-material {
    font-size: 0.7rem;
    color: var(--text-secondary);
    background: #252525;
    padding: 3px 8px;
    border-radius: 16px;
    display: inline-block;
    margin-bottom: 6px;
    align-self: flex-start;
}

/* Category tag - Hidden */
.product-category {
    display: none;
}

/* Price Section - Fixed Height */
.price-section {
    margin: 8px 0;
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
    min-height: 48px; /* Fixed height for price section */
}

.current-price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary);
}

.original-price {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-decoration: line-through;
}

.discount-badge {
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--success);
    background: rgba(76, 175, 80, 0.1);
    padding: 2px 6px;
    border-radius: 12px;
}

/* ============================================
   ACTION BUTTONS - FIXED ALIGNMENT
   ============================================ */
.action-buttons {
    display: flex;
    gap: 8px;
    margin-top: auto; /* Push to bottom */
    width: 100%;
}

.action-btn {
    width: 100%;
    padding: 12px 10px;
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
    flex-shrink: 0; /* Prevent button from shrinking */
}

/* Add to Cart Button */
.add-to-cart-btn {
    background: var(--primary);
    color: white;
}

.add-to-cart-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.add-to-cart-btn.added {
    background: var(--success);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

/* Select Model Button */
.select-model-btn {
    background: linear-gradient(135deg, var(--info), #1976d2);
    color: white;
}

.select-model-btn:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

/* Pagination */
.pagination-container {
    margin-top: 30px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 8px;
    background: var(--dark-card);
    border: 1px solid var(--dark-border);
    border-radius: var(--radius-sm);
    color: var(--text-secondary);
    text-decoration: none;
    transition: var(--transition);
    cursor: pointer;
    font-size: 0.85rem;
}

.page-link:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-link.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* No Products */
.no-products {
    text-align: center;
    padding: 50px 20px;
    background: var(--dark-card);
    border-radius: var(--radius);
    border: 1px solid var(--dark-border);
}

.no-products i {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: 15px;
    opacity: 0.3;
}

.no-products h3 {
    font-size: 1.3rem;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.no-products p {
    color: var(--text-secondary);
    margin-bottom: 20px;
    font-size: 0.9rem;
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 9999;
    min-width: 280px;
    max-width: 350px;
    animation: slideInRight 0.3s ease;
    border-left: 4px solid;
    box-shadow: var(--shadow);
    font-size: 0.9rem;
}

.toast-notification.alert-success {
    border-left-color: var(--success);
}

.toast-notification.alert-danger {
    border-left-color: var(--danger);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Animations */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

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

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .product-card:hover {
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
   RESPONSIVE DESIGN
   ============================================ */

/* Desktop */
@media (min-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
}

/* Small Desktop */
@media (min-width: 992px) and (max-width: 1199px) {
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
    }
    
    .page-layout {
        gap: 20px;
    }
    
    .filter-sidebar {
        width: 240px;
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 991px) {
    .page-layout {
        grid-template-columns: 220px 1fr;
        gap: 18px;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .page-header-title {
        font-size: 1.8rem;
    }
    
    .product-image-container {
        padding-top: 75%;
    }
}

/* Mobile - Fixed Buttons */
@media (max-width: 767px) {
    body {
        padding-top: 70px;
    }
    
    .page-header-section {
        padding: 25px 0;
    }
    
    .page-header-title {
        font-size: 1.6rem;
    }
    
    .page-header-subtitle {
        font-size: 0.9rem;
        padding: 0 15px;
    }
    
    .page-badge {
        font-size: 0.75rem;
        padding: 5px 12px;
    }
    
    .filter-toggle-container {
        display: block;
    }
    
    .page-layout {
        grid-template-columns: 1fr;
    }
    
    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 85%;
        max-width: 300px;
        height: 100vh;
        z-index: 1000;
        border-radius: 0;
        overflow-y: auto;
        transition: right 0.3s ease;
        background: var(--dark-card);
    }
    
    .filter-sidebar.active {
        right: 0;
    }
    
    .close-filter-btn {
        display: flex;
        margin-top: 15px;
    }
    
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .product-image-container {
        padding-top: 75%;
    }
    
    .product-info {
        padding: 10px;
    }
    
    .product-title {
        font-size: 0.8rem;
        min-height: 34px;
        margin-bottom: 4px;
    }
    
    .product-material {
        font-size: 0.65rem;
        padding: 2px 6px;
    }
    
    .price-section {
        margin: 6px 0;
        gap: 4px;
        min-height: 42px; /* Adjusted for mobile */
    }
    
    .current-price {
        font-size: 0.85rem;
    }
    
    .original-price {
        font-size: 0.65rem;
    }
    
    .discount-badge {
        font-size: 0.6rem;
        padding: 2px 4px;
    }
    
    /* Mobile Buttons - Fixed */
    .action-btn {
        padding: 10px 6px;
        font-size: 0.75rem;
        min-height: 40px;
        gap: 4px;
    }
    
    .action-btn i {
        font-size: 0.75rem;
    }
    
    .wishlist-btn {
        width: 30px;
        height: 30px;
        font-size: 0.85rem;
    }
    
    .popular-badge {
        padding: 3px 6px;
        font-size: 0.6rem;
    }
    
    .toast-notification {
        left: 15px;
        right: 15px;
        min-width: auto;
        max-width: none;
        top: 80px;
    }
}

/* Small Mobile - Hide text on buttons */
@media (max-width: 375px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .product-info {
        padding: 8px;
    }
    
    .product-title {
        font-size: 0.75rem;
        min-height: 32px;
    }
    
    .current-price {
        font-size: 0.8rem;
    }
    
    .price-section {
        min-height: 38px;
    }
    
    .action-btn {
        padding: 8px 4px;
        font-size: 0.7rem;
        min-height: 36px;
    }
    
    /* Hide text on very small screens */
    .action-btn span {
        display: none;
    }
    
    .action-btn i {
        font-size: 0.85rem;
        margin: 0;
    }
    
    .wishlist-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
}

/* Very Small Mobile - Icon only */
@media (max-width: 320px) {
    .action-btn {
        padding: 6px 3px;
    }
    
    .action-btn i {
        font-size: 0.8rem;
    }
}   
    </style>
</head>
<body>
    <!-- Header -->
    <?php if(file_exists("includes/header.php")) include "includes/header.php"; ?>

    <!-- Page Header -->
    <section class="page-header-section">
        <div class="container text-center">
            <div class="page-badge">
                <i class="fas fa-bolt"></i>
                Premium Quality • 9H Hardness • Free Shipping
            </div>
            <h1 class="page-header-title">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo htmlspecialchars($category_name);
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>
            <p class="page-header-subtitle">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo 'Browse our collection of ' . htmlspecialchars($category_name);
                } else {
                    echo 'Discover our premium collection of protectors and cases';
                }
                ?>
            </p>
        </div>
    </section>

    <div class="container mb-5">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-bar" 
                       placeholder="Search products by name, material, or category..." 
                       autocomplete="off" aria-label="Search products">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>

        <!-- Filter Toggle (Mobile Only) -->
        <div class="filter-toggle-container">
            <button id="filterToggle" class="filter-toggle-btn" type="button" aria-label="Toggle filters">
                <i class="fas fa-filter"></i> Filters
            </button>
        </div>

        <!-- Filter Overlay (Mobile) -->
        <div class="filter-overlay" id="filterOverlay"></div>

        <div class="page-layout">
            <!-- Filter Sidebar - FIXED PRICE RANGE -->
            <form id="filterForm" class="filter-sidebar">
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-layer-group"></i>
                        Category
                    </h3>
                    <select name="category_id" id="categoryFilter" class="filter-select" aria-label="Select category">
                        <option value="all">All Categories</option>
                        <?php
                        $cat_query = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name");
                        while ($cat = $cat_query->fetch_assoc()) {
                            $selected = ($cat['id'] == $category_id) ? 'selected' : '';
                            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-gem"></i>
                        Material Type
                    </h3>
                    <div id="materialFilterContainer">
                        <select name="material_type_id" id="materialFilter" class="filter-select" 
                                <?php echo $category_id && $category_id !== 'all' ? '' : 'disabled'; ?> 
                                aria-label="Select material type">
                            <option value="all"><?php echo ($category_id && $category_id !== 'all') ? 'All Materials' : 'Select Category First'; ?></option>
                        </select>
                        <div class="loading-indicator" id="materialLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading materials...
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-palette"></i>
                        Design/Variant
                    </h3>
                    <div id="variantFilterContainer">
                        <select name="variant_type_id" id="variantFilter" class="filter-select" 
                                <?php echo $category_id && $category_id !== 'all' ? '' : 'disabled'; ?> 
                                aria-label="Select design/variant">
                            <option value="all"><?php echo ($category_id && $category_id !== 'all') ? 'All Variants' : 'Select Category First'; ?></option>
                        </select>
                        <div class="loading-indicator" id="variantLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Loading variants...
                        </div>
                    </div>
                </div>

                <!-- FIXED: Price Range with proper layout -->
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-tag"></i>
                        Price Range
                    </h3>
                    <div class="price-range-container">
                        <div class="price-input-group">
                            <label>Min (₹)</label>
                            <input type="number" name="price_min" id="price_min" placeholder="0" min="0" step="10" 
                                   class="price-input" aria-label="Minimum price">
                        </div>
                        <span class="price-separator">—</span>
                        <div class="price-input-group">
                            <label>Max (₹)</label>
                            <input type="number" name="price_max" id="price_max" placeholder="Any" min="0" step="10" 
                                   class="price-input" aria-label="Maximum price">
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-sort"></i>
                        Sort By
                    </h3>
                    <select name="sort" id="sortFilter" class="filter-select" aria-label="Sort products by">
                        <option value="new">Newest First</option>
                        <option value="popular">Most Popular</option>
                        <option value="low">Price: Low to High</option>
                        <option value="high">Price: High to Low</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="filter-btn filter-btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="filter-btn filter-btn-secondary" id="resetFiltersBtn">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
                    
                    <button type="button" class="close-filter-btn" id="closeFiltersBtn">
                        <i class="fas fa-times"></i> Close Filters
                    </button>
                </div>
            </form>

            <!-- Products Section -->
            <div class="products-section">
                <div id="loadingIndicator" class="loading-state" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Loading products...</p>
                </div>

                <div id="searchResultsInfo" class="search-results-info" style="display: none;"></div>

                <div class="products-grid" id="productGrid">
                    <!-- Products will be loaded here via AJAX -->
                </div>

                <div id="pagination" class="pagination-container"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php if(file_exists("includes/footer.php")) include "includes/footer.php"; ?>
    <?php if(file_exists("includes/mobile_bottom_nav.php")) include "includes/mobile_bottom_nav.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // ============================================
    // PRODUCTS PAGE JAVASCRIPT
    // ============================================
    
    const state = {
        currentPage: 1,
        currentSearch: '',
        isLoading: false,
        currentCategoryId: '<?= $category_id ?>',
        isMobileView: window.innerWidth <= 991,
        totalProducts: 0,
        searchTimeout: null,
        userId: <?= $user_id ? 'true' : 'false' ?>
    };
    
    const elements = {
        searchInput: document.getElementById('searchInput'),
        filterForm: document.getElementById('filterForm'),
        categoryFilter: document.getElementById('categoryFilter'),
        materialFilter: document.getElementById('materialFilter'),
        variantFilter: document.getElementById('variantFilter'),
        priceMin: document.getElementById('price_min'),
        priceMax: document.getElementById('price_max'),
        productGrid: document.getElementById('productGrid'),
        pagination: document.getElementById('pagination'),
        loadingIndicator: document.getElementById('loadingIndicator'),
        searchResultsInfo: document.getElementById('searchResultsInfo'),
        filterToggle: document.getElementById('filterToggle'),
        filterSidebar: document.querySelector('.filter-sidebar'),
        filterOverlay: document.getElementById('filterOverlay'),
        closeFiltersBtn: document.getElementById('closeFiltersBtn')
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        initializePage();
        setupEventListeners();
        handleResize();
        window.addEventListener('resize', handleResize);
    });
    
    function initializePage() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('cat');
        
        if (categoryId && categoryId !== 'all') {
            elements.categoryFilter.value = categoryId;
            loadCategoryFilters(categoryId);
            loadProducts(1);
        } else {
            loadCategoryFilters('all');
            loadProducts(1);
        }
    }
    
    function setupEventListeners() {
        elements.searchInput.addEventListener('input', handleSearchInput);
        elements.filterForm.addEventListener('submit', handleFilterSubmit);
        elements.categoryFilter.addEventListener('change', handleCategoryChange);
        
        if (elements.filterToggle) {
            elements.filterToggle.addEventListener('click', toggleFilterSidebar);
        }
        
        if (elements.filterOverlay) {
            elements.filterOverlay.addEventListener('click', closeFilterSidebar);
        }
        
        if (elements.closeFiltersBtn) {
            elements.closeFiltersBtn.addEventListener('click', closeFilterSidebar);
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && state.isMobileView && elements.filterSidebar && elements.filterSidebar.classList.contains('active')) {
                closeFilterSidebar();
            }
        });
        
        window.addEventListener('popstate', handlePopState);
    }
    
    function handleResize() {
        state.isMobileView = window.innerWidth <= 991;
        if (!state.isMobileView && elements.filterSidebar && elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        }
    }
    
    function handleSearchInput(e) {
        clearTimeout(state.searchTimeout);
        const query = e.target.value.trim();
        
        state.searchTimeout = setTimeout(() => {
            if (query.length >= 2 || query === '') {
                state.currentSearch = query;
                state.currentPage = 1;
                
                if (query) {
                    if (state.isMobileView) {
                        closeFilterSidebar();
                    }
                    performSearch(query);
                } else {
                    if (elements.searchResultsInfo) {
                        elements.searchResultsInfo.style.display = 'none';
                    }
                    loadProducts(1);
                }
            }
        }, 500);
    }
    
    function performSearch(query) {
        showLoading(true);
        
        fetch(`ajax/search_products.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Search failed');
                return response.text();
            })
            .then(html => {
                elements.productGrid.innerHTML = html;
                if (elements.pagination) {
                    elements.pagination.innerHTML = '';
                }
                if (elements.searchResultsInfo) {
                    elements.searchResultsInfo.style.display = 'flex';
                    elements.searchResultsInfo.innerHTML = `
                        <i class="fas fa-search"></i>
                        Found ${countProductsInGrid()} products matching "${query}"
                    `;
                }
                showLoading(false);
                setupProductInteractions();
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Search failed. Please try again.');
                showLoading(false);
            });
    }
    
    function countProductsInGrid() {
        return elements.productGrid.querySelectorAll('.product-card').length;
    }
    
    function handleFilterSubmit(e) {
        e.preventDefault();
        
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        state.currentSearch = '';
        if (elements.searchInput) {
            elements.searchInput.value = '';
        }
        if (elements.searchResultsInfo) {
            elements.searchResultsInfo.style.display = 'none';
        }
        
        loadProducts(1);
    }
    
    function handleCategoryChange() {
        const categoryId = this.value;
        state.currentCategoryId = categoryId;
        
        loadCategoryFilters(categoryId);
        
        const url = new URL(window.location);
        if (categoryId === 'all') {
            url.searchParams.delete('cat');
        } else {
            url.searchParams.set('cat', categoryId);
        }
        window.history.pushState({ categoryId }, '', url);
        
        if (elements.materialFilter) elements.materialFilter.value = 'all';
        if (elements.variantFilter) elements.variantFilter.value = 'all';
        
        updatePageTitle(categoryId);
        loadProducts(1);
    }
    
    function updatePageTitle(categoryId) {
        const pageTitle = document.querySelector('.page-header-title');
        const pageSubtitle = document.querySelector('.page-header-subtitle');
        
        if (!pageTitle || !pageSubtitle) return;
        
        const categoryName = elements.categoryFilter.options[elements.categoryFilter.selectedIndex].text;
        
        if (categoryId === 'all') {
            pageTitle.textContent = 'All Products';
            pageSubtitle.textContent = 'Discover our premium collection of protectors and cases';
            document.title = 'Products | PROGLIDE';
        } else {
            pageTitle.textContent = categoryName;
            pageSubtitle.textContent = `Browse our collection of ${categoryName}`;
            document.title = `${categoryName} | PROGLIDE`;
        }
    }
    
    function loadCategoryFilters(categoryId) {
        if (!categoryId || categoryId === 'all') {
            if (elements.materialFilter) {
                elements.materialFilter.innerHTML = '<option value="all">All Materials</option>';
                elements.materialFilter.disabled = false;
            }
            if (elements.variantFilter) {
                elements.variantFilter.innerHTML = '<option value="all">All Variants</option>';
                elements.variantFilter.disabled = false;
            }
            return;
        }
        
        const materialLoading = document.getElementById('materialLoading');
        const variantLoading = document.getElementById('variantLoading');
        
        if (materialLoading) materialLoading.style.display = 'flex';
        if (elements.materialFilter) elements.materialFilter.disabled = true;
        if (variantLoading) variantLoading.style.display = 'flex';
        if (elements.variantFilter) elements.variantFilter.disabled = true;
        
        Promise.all([
            fetch(`ajax/get_materials.php?category_id=${encodeURIComponent(categoryId)}`),
            fetch(`ajax/get_variants.php?category_id=${encodeURIComponent(categoryId)}`)
        ])
        .then(responses => Promise.all(responses.map(r => {
            if (!r.ok) throw new Error('Network error');
            return r.json();
        })))
        .then(([materials, variants]) => {
            updateMaterialFilter(materials);
            updateVariantFilter(variants);
        })
        .catch(error => {
            console.error('Error loading filters:', error);
            if (elements.materialFilter) {
                elements.materialFilter.innerHTML = '<option value="all">Error loading</option>';
                elements.materialFilter.disabled = false;
            }
            if (elements.variantFilter) {
                elements.variantFilter.innerHTML = '<option value="all">Error loading</option>';
                elements.variantFilter.disabled = false;
            }
        })
        .finally(() => {
            if (materialLoading) materialLoading.style.display = 'none';
            if (variantLoading) variantLoading.style.display = 'none';
        });
    }
    
    function updateMaterialFilter(materials) {
        if (!elements.materialFilter) return;
        
        elements.materialFilter.innerHTML = '<option value="all">All Materials</option>';
        
        if (materials && materials.length > 0) {
            materials.forEach(material => {
                const option = document.createElement('option');
                option.value = material.id;
                option.textContent = material.name;
                elements.materialFilter.appendChild(option);
            });
        } else {
            elements.materialFilter.innerHTML = '<option value="all">No materials available</option>';
        }
        
        elements.materialFilter.disabled = false;
    }
    
    function updateVariantFilter(variants) {
        if (!elements.variantFilter) return;
        
        elements.variantFilter.innerHTML = '<option value="all">All Variants</option>';
        
        if (variants && variants.length > 0) {
            variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.id;
                option.textContent = variant.name;
                elements.variantFilter.appendChild(option);
            });
        } else {
            elements.variantFilter.innerHTML = '<option value="all">No variants available</option>';
        }
        
        elements.variantFilter.disabled = false;
    }
    
    function loadProducts(page = 1) {
        if (state.isLoading) return;
        
        state.isLoading = true;
        state.currentPage = page;
        
        showLoading(true);
        elements.productGrid.innerHTML = '';
        
        const formData = new FormData(elements.filterForm);
        formData.append('page', page);
        
        if (state.currentSearch) {
            ['category_id', 'material_type_id', 'variant_type_id', 'price_min', 'price_max', 'sort'].forEach(field => {
                formData.delete(field);
            });
        }
        
        fetch('ajax/load_products.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            elements.productGrid.innerHTML = data.html;
            if (elements.pagination) {
                elements.pagination.innerHTML = data.pagination;
            }
            state.totalProducts = data.total;
            
            setupProductInteractions();
            setupPaginationHandlers();
            
            showLoading(false);
            state.isLoading = false;
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showError('Failed to load products. Please check your connection and try again.');
            showLoading(false);
            state.isLoading = false;
        });
    }
    
    function setupProductInteractions() {
        // Product card click
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (!e.target.closest('.wishlist-btn') && !e.target.closest('.action-btn')) {
                    const productId = this.dataset.productId;
                    window.location.href = 'productdetails.php?id=' + productId;
                }
            });
        });
        
        // Wishlist buttons
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', handleWishlistClick);
        });
        
        // Add to Cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', handleAddToCartClick);
        });
        
        // Select Model buttons
        document.querySelectorAll('.select-model-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const productId = this.dataset.productId;
                window.location.href = 'productdetails.php?id=' + productId;
            });
        });
    }
    
    function setupPaginationHandlers() {
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.classList.contains('disabled') || this.classList.contains('active')) {
                    return;
                }
                
                const page = this.dataset.page;
                if (page) {
                    loadProducts(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }
    
    function handleWishlistClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const productId = btn.dataset.productId;
        const heartIcon = btn.querySelector('i');
        
        if (!state.userId) {
            if (confirm('Please login to add items to wishlist.\n\nDo you want to login?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        }
        
        const isActive = btn.classList.contains('active');
        
        if (isActive) {
            fetch('ajax/remove_wishlist.php?product_id=' + productId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.classList.remove('active');
                        heartIcon.className = 'far fa-heart';
                        showToast('Removed from wishlist', 'success');
                    } else {
                        showToast(data.message || 'Failed to remove from wishlist', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('Failed to remove from wishlist. Please check your connection.', 'error');
                });
        } else {
            const formData = new FormData();
            formData.append('product_id', productId);
            
            fetch('ajax/add_to_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.classList.add('active');
                    heartIcon.className = 'fas fa-heart';
                    showToast('Added to wishlist!', 'success');
                } else {
                    showToast(data.message || 'Failed to add to wishlist', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Failed to add to wishlist. Please check your connection.', 'error');
            });
        }
    }
    
    function handleAddToCartClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.currentTarget;
        const productId = btn.dataset.productId;
        
        if (!state.userId) {
            if (confirm('Please login to add items to cart.\n\nDo you want to login?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        }
        
        if (btn.classList.contains('added')) {
            removeFromCart(productId, btn);
        } else {
            addToCart(productId, btn);
        }
    }
    
    function addToCart(productId, button) {
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(`ajax/add_to_cart.php?id=${productId}&qty=1`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                button.classList.add('added');
                button.innerHTML = '<i class="fas fa-check"></i> Added';
                button.style.background = '#10b981';
                button.style.color = '#fff';
                updateCartCount(data.cart_count);
                showToast('Product added to cart!', 'success');
            } else {
                showToast(data.message || 'Failed to add to cart', 'error');
                button.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Add to cart failed. Please check your connection.', 'error');
            button.innerHTML = originalText;
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    function removeFromCart(productId, button) {
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('product_id', productId);
        
        fetch('ajax/remove_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                button.classList.remove('added');
                button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add';
                button.style.background = '';
                button.style.color = '';
                updateCartCount(data.cart_count);
                showToast('Removed from cart', 'success');
            } else {
                showToast(data.message || 'Failed to remove from cart', 'error');
                button.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showToast('Failed to remove from cart. Please check your connection.', 'error');
            button.innerHTML = originalHTML;
        })
        .finally(() => {
            button.disabled = false;
        });
    }
    
    function resetFilters() {
        if (elements.filterForm) elements.filterForm.reset();
        if (elements.searchInput) elements.searchInput.value = '';
        state.currentSearch = '';
        state.currentCategoryId = '';
        
        loadCategoryFilters('all');
        
        const url = new URL(window.location);
        url.searchParams.delete('cat');
        window.history.pushState({}, '', url);
        
        updatePageTitle('all');
        
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        loadProducts(1);
    }
    
    function toggleFilterSidebar() {
        if (!elements.filterSidebar || !elements.filterOverlay) return;
        
        if (elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        } else {
            openFilterSidebar();
        }
    }
    
    function openFilterSidebar() {
        if (!elements.filterSidebar || !elements.filterOverlay) return;
        
        elements.filterSidebar.classList.add('active');
        elements.filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeFilterSidebar() {
        if (!elements.filterSidebar || !elements.filterOverlay) return;
        
        elements.filterSidebar.classList.remove('active');
        elements.filterOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function handlePopState() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('cat') || '';
        
        if (categoryId && categoryId !== 'all') {
            if (elements.categoryFilter) {
                elements.categoryFilter.value = categoryId;
                loadCategoryFilters(categoryId);
            }
        } else {
            if (elements.categoryFilter) {
                elements.categoryFilter.value = 'all';
                loadCategoryFilters('all');
            }
        }
        
        loadProducts(1);
    }
    
    function showLoading(show) {
        if (elements.loadingIndicator) {
            elements.loadingIndicator.style.display = show ? 'block' : 'none';
        }
    }
    
    function showError(message) {
        elements.productGrid.innerHTML = `
            <div class="no-products">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${message}</p>
            </div>
        `;
    }
    
    function showToast(message, type = 'success') {
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-notification alert alert-${type === 'success' ? 'success' : 'danger'}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                <div><strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}</div>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    
    function updateCartCount(count = null) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            if (count !== null) {
                el.textContent = count;
            } else {
                const current = parseInt(el.textContent) || 0;
                el.textContent = current + 1;
            }
            el.style.display = 'inline-flex';
        });
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
    </script>
</body>
</html>
<?php $conn->close(); ?>