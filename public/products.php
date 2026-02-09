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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo $category_name ? htmlspecialchars($category_name) . ' | ' : '' ?>Products | PROGLIDE</title>
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- CSS -->
    <style>
        /* ============================================
           PRODUCTS PAGE STYLES - FULLY RESPONSIVE
        ============================================ */
        
        /* Base Reset for Mobile */
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--secondary);
            color: var(--text-light);
            line-height: 1.5;
            overflow-x: hidden;
            padding-top: 70px; /* Header height */
        }
        
        /* Main Container */
        .pcontainer {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
            width: 100%;
            min-height: calc(100vh - 140px);
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 25px;
            padding: 0 5px;
            text-align: center;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 8px;
            line-height: 1.2;
        }
        
        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Search Bar */
        .search-section {
            margin-bottom: 25px;
            width: 100%;
        }
        
        .search-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-bar {
            width: 100%;
            padding: 14px 50px 14px 20px;
            background: var(--secondary-light);
            border: 2px solid var(--border);
            border-radius: 50px;
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            -webkit-appearance: none;
            appearance: none;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.15);
        }
        
        .search-bar::placeholder {
            color: var(--text-muted);
        }
        
        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
        }
        
        /* Filter Toggle Button - Mobile */
        .filter-toggle-container {
            display: none;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .filter-toggle-btn {
            width: 100%;
            padding: 14px 20px;
            background: var(--secondary-light);
            border: 2px solid var(--primary);
            color: var(--primary);
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .filter-toggle-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .filter-toggle-btn i {
            font-size: 1rem;
        }
        
        /* Main Layout */
        .page-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            width: 100%;
            min-height: 500px;
        }
        
        /* Filter Sidebar */
        .filter-sidebar {
            background: var(--secondary-light);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--border);
            height: fit-content;
            position: sticky;
            top: 90px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .filter-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .filter-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .filter-sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }
        
        .filter-group {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .filter-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .filter-title i {
            color: var(--primary);
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Filter Elements */
        .filter-select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 0.95rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ff6b35' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .filter-select:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .price-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .price-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .price-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .price-input::placeholder {
            color: var(--text-muted);
        }
        
        
        /* Remove number input spinners */
        .price-input::-webkit-outer-spin-button,
        .price-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .price-input[type=number] {
            -moz-appearance: textfield;
        }
        
        /* Loading Indicators */
        .loading-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 10px;
            text-align: center;
        }
        
        .loading-indicator i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Filter Buttons */
        .filter-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 25px;
        }
        
        .filter-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }
        
        .filter-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .filter-btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #d4491e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
        }
        
        .filter-btn-secondary {
            background: var(--secondary);
            color: var(--text-light);
            border: 1px solid var(--border);
        }
        
        .filter-btn-secondary:hover {
            background: var(--border);
            transform: translateY(-2px);
        }
        
        /* Close Filter Button (Mobile) */
        .close-filter-btn {
            display: none;
            width: 100%;
            padding: 14px;
            background: var(--secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-light);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 15px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: inherit;
        }
        .close-filter-btn:hover {
            background: var(--border);
            transform: translateY(-2px);
        }
        
      /* ================================
   PRODUCTS GRID
================================ */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
    width: 100%;
}

/* ================================
   PRODUCT CARD
================================ */
.product-card {
    background: #111;
    border-radius: 16px;
    border: 1px solid #222;
    display: flex;
    flex-direction: column;
    height: 420px;              /* ✅ ALL CARDS SAME HEIGHT */
    overflow: hidden;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-6px);
    border-color: #ff6b35;
    box-shadow: 0 10px 30px rgba(255, 107, 53, 0.25);
}

/* ================================
   IMAGE SECTION
================================ */
.product-image-container {
    height: 180px;              /* ✅ FIXED IMAGE AREA */
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 15px;
}

.product-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Wishlist */
.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(0,0,0,0.7);
    border: 1px solid #333;
    color: #fff;
    cursor: pointer;
}

/* Popular Badge */
.popular-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ff6b35;
    color: #fff;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: bold;
}

/* ================================
   PRODUCT INFO
================================ */
.product-info {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;               /* ✅ PUSH BUTTON DOWN */
}

/* Title */
.product-title {
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 6px;
    line-height: 1.3;
    min-height: 40px;           /* ✅ SAME HEIGHT TITLE */
}

/* Material */
.product-material {
    font-size: 13px;
    color: #ff6b35;
    margin-bottom: 4px;
}

/* Category */
.product-category {
    font-size: 12px;
    color: #aaa;
    margin-bottom: 8px;
}

/* ================================
   PRICE
================================ */
.price-section {
    margin-top: auto;           /* ✅ STAYS ABOVE BUTTON */
    margin-bottom: 10px;
}

.current-price {
    font-size: 18px;
    font-weight: bold;
    color: #00e676;
}

.original-price {
    font-size: 13px;
    color: #888;
    text-decoration: line-through;
    margin-left: 6px;
}

/* ================================
   ACTION BUTTON
================================ */
.product-action {
    margin-top: auto;
}

.action-btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

/* Add to Cart */
.add-to-cart-btn {
    background: linear-gradient(135deg, #ff6b35, #ff3d00);
    color: #fff;
}

.add-to-cart-btn:hover {
    background: linear-gradient(135deg, #ff3d00, #ff6b35);
}

/* Select Model */
.select-model-btn {
    background: #222;
    color: #fff;
    border: 1px solid #333;
}

.select-model-btn:hover {
    background: #ff6b35;
    border-color: #ff6b35;
}

/* ================================
   MOBILE FIX
================================ */
@media (max-width: 600px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .product-card {
        height: 360px;
    }

    .product-image-container {
        height: 150px;
    }

    .product-title {
        font-size: 14px;
    }

    .current-price {
        font-size: 16px;
    }
}

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
            width: 100%;
            grid-column: 1 / -1;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        .loading-state p {
            color: var(--text-muted);
            font-size: 1rem;
        }
        
        /* No Products State */
        .no-products {
            text-align: center;
            padding: 60px 20px;
            width: 100%;
            grid-column: 1 / -1;
        }
        
        .no-products i {
            font-size: 3.5rem;
            color: var(--text-muted);
            opacity: 0.3;
            margin-bottom: 20px;
        }
        
        .no-products h3 {
            color: var(--text-light);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .no-products p {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
            flex-wrap: wrap;
            padding: 20px 0;
        }
        
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 5px;
            background: var(--secondary-light);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .page-link:hover:not(.active):not(.disabled) {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .page-link.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            cursor: default;
        }
        
        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-dots {
            color: var(--text-muted);
            padding: 0 10px;
            font-size: 0.95rem;
        }
        
        /* Search Results Info */
        .search-results-info {
            margin-bottom: 20px;
            color: var(--text-muted);
            font-size: 0.95rem;
            padding: 0 5px;
        }
        
        /* Filter Overlay (Mobile) */
        .filter-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .filter-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--secondary-light);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 12px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-light);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 350px;
            min-width: 300px;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-success {
            border-left-color: var(--success);
        }
        
        .notification-error {
            border-left-color: var(--danger);
        }
        
        .notification-info {
            border-left-color: var(--primary);
        }
        
        .notification i {
            font-size: 1.3rem;
        }
        
        .notification-success i {
            color: var(--success);
        }
        
        .notification-error i {
            color: var(--danger);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .notification-message {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        /* Cart Count Animation */
        .cart-count.updated {
            animation: countBounce 0.5s ease;
        }
        
        @keyframes countBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        
        /* ============================================
           RESPONSIVE BREAKPOINTS
        ============================================ */
        
        /* Large Desktop (1200px and above) */
        @media (min-width: 1200px) {
            .pcontainer {
                padding: 20px;
            }
            
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 25px;
            }
        }
        
        /* Desktop (992px to 1199px) */
        @media (max-width: 1199px) and (min-width: 992px) {
            .page-layout {
                grid-template-columns: 260px 1fr;
                gap: 20px;
            }
            
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            
            .product-image-container {
                height: 180px;
            }
            
            .product-card {
                height: 360px;
            }
        }
        
        /* Tablet (768px to 991px) */
        @media (max-width: 991px) {
            .pcontainer {
                padding: 15px;
            }
            
            .page-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .filter-toggle-container {
                display: block;
            }
            
            .filter-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 320px;
                max-width: 85vw;
                height: 100vh;
                z-index: 9999;
                overflow-y: auto;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                margin-top: 0;
                border-radius: 0 20px 20px 0;
                border-left: none;
                padding: 25px;
                box-shadow: 5px 0 40px rgba(0, 0, 0, 0.5);
            }
            
            .filter-sidebar.active {
                transform: translateX(100%);
                left: 0;
            }
            
            .close-filter-btn {
                display: flex;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 18px;
            }
            
            .product-image-container {
                height: 170px;
            }
            
            .product-card {
                height: 340px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        /* Large Mobile (576px to 767px) */
        @media (max-width: 767px) {
            body {
                padding-top: 60px;
            }
            
            .pcontainer {
                padding: 12px;
            }
            
            .page-header {
                margin-bottom: 20px;
            }
            
            .page-title {
                font-size: 1.4rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
            
            .search-bar {
                padding: 12px 45px 12px 18px;
                font-size: 0.95rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 15px;
            }
            
            .product-image-container {
                height: 160px;
                padding: 15px;
            }
            
            .product-card {
                height: 320px;
            }
            
            .product-info {
                padding: 16px;
            }
            
            .product-title {
                font-size: 1rem;
                min-height: 32px;
            }
            
            .current-price {
                font-size: 1.2rem;
            }
            
            .original-price {
                font-size: 0.9rem;
            }
            
            .action-btn {
                padding: 10px;
                font-size: 0.85rem;
            }
            
            .wishlist-btn {
                width: 36px;
                height: 36px;
                font-size: 0.95rem;
            }
            
            .popular-badge {
                font-size: 0.7rem;
                padding: 5px 12px;
            }
            
            .page-link {
                min-width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }
        }
        
        /* Small Mobile (375px to 575px) */
        @media (max-width: 575px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .product-image-container {
                height: 140px;
                padding: 12px;
            }
            
            .product-card {
                height: 300px;
            }
            
            .product-info {
                padding: 14px;
            }
            
            .product-title {
                font-size: 0.95rem;
                min-height: 30px;
            }
            
            .product-material {
                font-size: 0.8rem;
            }
            
            .product-category {
                font-size: 0.75rem;
            }
            
            .current-price {
                font-size: 1.1rem;
            }
            
            .action-btn {
                padding: 8px;
                font-size: 0.8rem;
            }
            
            .filter-sidebar {
                width: 300px;
                padding: 20px;
            }
            
            .page-title {
                font-size: 1.3rem;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                max-width: none;
                min-width: auto;
            }
        }
        
        /* Extra Small Mobile (under 375px) */
        @media (max-width: 374px) {
            .pcontainer {
                padding: 10px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .product-image-container {
                height: 160px;
            }
            
            .product-card {
                height: 320px;
            }
            
            .filter-sidebar {
                width: 280px;
                padding: 18px;
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .search-bar {
                font-size: 0.9rem;
                padding: 10px 40px 10px 15px;
            }
        }
        
        /* iOS Safari Specific Fixes */
        @supports (-webkit-touch-callout: none) {
            .filter-sidebar {
                padding-bottom: 80px; /* Safe area for home indicator */
            }
            
            .search-bar {
                font-size: 16px; /* Prevent zoom on focus */
            }
        }
        
        /* Android Chrome Specific Fixes */
        @media screen and (-webkit-min-device-pixel-ratio: 0) and (min-resolution: .001dpcm) {
            .filter-select {
                font-size: 16px; /* Prevent zoom */
            }
            
            .price-input {
                font-size: 16px; /* Prevent zoom */
            }
        }
        
        /* High DPI Screens */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .product-image {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }
        
        /* Print Styles */
        @media print {
            .filter-toggle-container,
            .filter-sidebar,
            .wishlist-btn,
            .action-btn {
                display: none !important;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }
            
            .product-card {
                break-inside: avoid;
                border: 1px solid #000 !important;
                box-shadow: none !important;
                height: auto !important;
            }
        }
        
        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .product-card:hover {
                transform: none !important;
            }
            
            .product-image {
                transition: none !important;
            }
        }
        
        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --secondary: #0a0a0a;
                --secondary-light: #151515;
                --border: #262626;
            }
        }
        
        /* Light Mode Support */
        @media (prefers-color-scheme: light) {
            :root {
                --secondary: #f5f5f5;
                --secondary-light: #ffffff;
                --text-light: #333333;
                --text-muted: #666666;
                --border: #e0e0e0;
            }
            
            .product-card {
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            
            .filter-sidebar {
                box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Landscape Orientation */
        @media (orientation: landscape) and (max-height: 600px) {
            .filter-sidebar {
                max-height: 90vh;
            }
            
            .product-image-container {
                height: 120px;
            }
            
            .product-card {
                height: 280px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php if(file_exists("includes/header.php")) include "includes/header.php"; ?>

    <div class="pcontainer">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo htmlspecialchars($category_name);
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>
            <p class="page-subtitle">
                <?php 
                if ($category_name && $category_id !== 'all') {
                    echo 'Browse our collection of ' . htmlspecialchars($category_name);
                } else {
                    echo 'Discover our premium collection of protectors and cases';
                }
                ?>
            </p>
        </div>

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
            <!-- Filter Sidebar -->
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

                <!-- Material Type Filter -->
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

                <!-- Variant Type Filter -->
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

                <!-- Price Range -->
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-tag"></i>
                        Price Range
                    </h3>
                    <div class="price-inputs">
                        <input type="number" name="price_min" placeholder="Min" min="0" step="0.01" 
                               class="price-input" aria-label="Minimum price">
                        <input type="number" name="price_max" placeholder="Max" min="0" step="0.01" 
                               class="price-input" aria-label="Maximum price">
                    </div>
                </div>

                <!-- Sort Options -->
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

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="filter-btn filter-btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="filter-btn filter-btn-secondary" id="resetFiltersBtn">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
                    
                    <!-- Close Button for Mobile -->
                    <button type="button" class="close-filter-btn" id="closeFiltersBtn">
                        <i class="fas fa-times"></i> Close Filters
                    </button>
                </div>
            </form>

            <!-- Products Section -->
            <div class="products-section">
                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="loading-state" style="display: none;" aria-live="polite" aria-busy="true">
                    <div class="loading-spinner" aria-hidden="true"></div>
                    <p>Loading products...</p>
                </div>

                <!-- Search Results Info -->
                <div id="searchResultsInfo" class="search-results-info" style="display: none;"></div>

                <!-- Products Grid -->
                <div class="products-grid" id="productGrid" role="list" aria-label="Products list">
                    <!-- Products will be loaded here via AJAX -->
                </div>

                <!-- Pagination -->
                <div id="pagination" class="pagination-container" role="navigation" aria-label="Pagination"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php if(file_exists("includes/footer.php")) include "includes/footer.php"; ?>

    <script>
    // ============================================
    // PRODUCTS PAGE JAVASCRIPT - FULLY RESPONSIVE
    // ============================================
    
    // State Management
    const state = {
        currentPage: 1,
        currentSearch: '',
        isLoading: false,
        currentCategoryId: '<?= $category_id ?>',
        isMobileView: window.innerWidth <= 991,
        totalProducts: 0,
        searchTimeout: null
    };
    
    // DOM Elements
    const elements = {
        searchInput: document.getElementById('searchInput'),
        filterForm: document.getElementById('filterForm'),
        categoryFilter: document.getElementById('categoryFilter'),
        materialFilter: document.getElementById('materialFilter'),
        variantFilter: document.getElementById('variantFilter'),
        productGrid: document.getElementById('productGrid'),
        pagination: document.getElementById('pagination'),
        loadingIndicator: document.getElementById('loadingIndicator'),
        searchResultsInfo: document.getElementById('searchResultsInfo'),
        filterToggle: document.getElementById('filterToggle'),
        filterSidebar: document.querySelector('.filter-sidebar'),
        filterOverlay: document.getElementById('filterOverlay'),
        closeFiltersBtn: document.getElementById('closeFiltersBtn')
    };
    
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        initializePage();
        setupEventListeners();
        handleResize();
        window.addEventListener('resize', handleResize);
    });
    
    // Initialize page state
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
    
    // Setup event listeners
    function setupEventListeners() {
        // Search input with debounce
        elements.searchInput.addEventListener('input', handleSearchInput);
        
        // Filter form submission
        elements.filterForm.addEventListener('submit', handleFilterSubmit);
        
        // Category change
        elements.categoryFilter.addEventListener('change', handleCategoryChange);
        
        // Mobile filter toggle
        elements.filterToggle.addEventListener('click', toggleFilterSidebar);
        elements.filterOverlay.addEventListener('click', closeFilterSidebar);
        elements.closeFiltersBtn.addEventListener('click', closeFilterSidebar);
        
        // Escape key to close filters
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && state.isMobileView && elements.filterSidebar.classList.contains('active')) {
                closeFilterSidebar();
            }
        });
        
        // Handle browser back/forward
        window.addEventListener('popstate', handlePopState);
    }
    
    // Handle window resize
    function handleResize() {
        state.isMobileView = window.innerWidth <= 991;
        
        // Close filter sidebar if switching to desktop view
        if (!state.isMobileView && elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        }
    }
    
    // Search input handler with debounce
    function handleSearchInput(e) {
        clearTimeout(state.searchTimeout);
        const query = e.target.value.trim();
        
        state.searchTimeout = setTimeout(() => {
            if (query.length >= 2 || query === '') {
                state.currentSearch = query;
                state.currentPage = 1;
                
                if (query) {
                    // Close filters on mobile when searching
                    if (state.isMobileView) {
                        closeFilterSidebar();
                    }
                    
                    performSearch(query);
                } else {
                    // Clear search and show filtered products
                    elements.searchResultsInfo.style.display = 'none';
                    loadProducts(1);
                }
            }
        }, 500);
    }
    
    // Perform search
    function performSearch(query) {
        showLoading(true);
        
        fetch(`ajax/search_products.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('Search failed');
                return response.text();
            })
            .then(html => {
                elements.productGrid.innerHTML = html;
                elements.pagination.innerHTML = '';
                elements.searchResultsInfo.style.display = 'block';
                elements.searchResultsInfo.textContent = `Found ${countProductsInGrid()} products matching "${query}"`;
                showLoading(false);
                
                // Setup product interactions for search results
                setupProductInteractions();
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Search failed. Please try again.');
                showLoading(false);
            });
    }
    
    // Count products in grid
    function countProductsInGrid() {
        return elements.productGrid.querySelectorAll('.product-card').length;
    }
    
    // Filter form submission
    function handleFilterSubmit(e) {
        e.preventDefault();
        
        // Close filter sidebar on mobile
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        // Reset search state
        state.currentSearch = '';
        elements.searchInput.value = '';
        elements.searchResultsInfo.style.display = 'none';
        
        // Load products with filters
        loadProducts(1);
    }
    
    // Category change handler
    function handleCategoryChange() {
        const categoryId = this.value;
        state.currentCategoryId = categoryId;
        
        // Load category-specific filters
        loadCategoryFilters(categoryId);
        
        // Update URL
        updateUrlWithCategory(categoryId);
        
        // Reset dependent filters
        elements.materialFilter.value = 'all';
        elements.variantFilter.value = 'all';
        
        // Update page title
        updatePageTitle(categoryId);
        
        // Load products
        loadProducts(1);
    }
    
    // Load category-specific filters
    function loadCategoryFilters(categoryId) {
        if (!categoryId || categoryId === 'all') {
            elements.materialFilter.innerHTML = '<option value="all">All Materials</option>';
            elements.materialFilter.disabled = false;
            elements.variantFilter.innerHTML = '<option value="all">All Variants</option>';
            elements.variantFilter.disabled = false;
            return;
        }
        
        // Show loading
        document.getElementById('materialLoading').style.display = 'block';
        elements.materialFilter.disabled = true;
        document.getElementById('variantLoading').style.display = 'block';
        elements.variantFilter.disabled = true;
        
        // Load materials and variants in parallel
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
            elements.materialFilter.innerHTML = '<option value="all">Error loading</option>';
            elements.materialFilter.disabled = false;
            elements.variantFilter.innerHTML = '<option value="all">Error loading</option>';
            elements.variantFilter.disabled = false;
        })
        .finally(() => {
            document.getElementById('materialLoading').style.display = 'none';
            document.getElementById('variantLoading').style.display = 'none';
        });
    }
    
    // Update material filter
    function updateMaterialFilter(materials) {
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
    
    // Update variant filter
    function updateVariantFilter(variants) {
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
    
    // Update URL with category
    function updateUrlWithCategory(categoryId) {
        const url = new URL(window.location);
        
        if (categoryId === 'all') {
            url.searchParams.delete('cat');
        } else {
            url.searchParams.set('cat', categoryId);
        }
        
        window.history.pushState({ categoryId }, '', url);
    }
    
    // Update page title
    function updatePageTitle(categoryId) {
        const pageTitle = document.querySelector('.page-title');
        const pageSubtitle = document.querySelector('.page-subtitle');
        const categoryName = elements.categoryFilter.options[elements.categoryFilter.selectedIndex].text;
        
        if (categoryId === 'all') {
            pageTitle.textContent = 'All Products';
            pageSubtitle.textContent = 'Discover our premium collection of protectors and cases';
        } else {
            pageTitle.textContent = categoryName;
            pageSubtitle.textContent = `Browse our collection of ${categoryName}`;
        }
    }
    
    // Load products with AJAX
    function loadProducts(page = 1) {
        if (state.isLoading) return;
        
        state.isLoading = true;
        state.currentPage = page;
        
        showLoading(true);
        elements.productGrid.innerHTML = '';
        
        const formData = new FormData(elements.filterForm);
        formData.append('page', page);
        
        // If searching, don't use filters
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
            elements.pagination.innerHTML = data.pagination;
            state.totalProducts = data.total;
            
            // Setup product card interactions
            setupProductInteractions();
            
            // Setup pagination click handlers
            setupPaginationHandlers();
            
            // Announce results to screen readers
            announceResults(data.total);
            
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
    
    // Setup product card interactions
    function setupProductInteractions() {
        // Product card click (redirect to product details)
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', handleProductCardClick);
        });
        
        // Prevent wishlist and action button clicks from triggering card click
        document.querySelectorAll('.wishlist-btn, .action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
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
            btn.addEventListener('click', handleSelectModelClick);
        });
    }
    
    // Product card click handler
    function handleProductCardClick(e) {
        // Don't redirect if clicking on buttons
        if (e.target.closest('.wishlist-btn') || e.target.closest('.action-btn')) {
            return;
        }
        
        const productCard = e.currentTarget;
        const productId = productCard.dataset.productId;
        
        // Redirect to product details page
        window.location.href = 'productdetails.php?id=' + productId;
    }
    
    // Setup pagination handlers
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
    
    // Wishlist click handler
    function handleWishlistClick(e) {
        const wishlistBtn = e.currentTarget;
        const productId = wishlistBtn.dataset.productId;
        const heartIcon = wishlistBtn.querySelector('i');
        
        // Check if user is logged in
        if (!isUserLoggedIn()) {
            showLoginAlert('Please login to add items to wishlist');
            return;
        }
        
        // Toggle wishlist state
        const isActive = wishlistBtn.classList.contains('active');
        
        if (isActive) {
            removeFromWishlist(productId, wishlistBtn);
        } else {
            addToWishlist(productId, wishlistBtn);
        }
        
        // Toggle UI state
        heartIcon.classList.toggle('far');
        heartIcon.classList.toggle('fas');
        wishlistBtn.classList.toggle('active');
    }
    
    // Add to Wishlist
    function addToWishlist(productId, button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            showNotification('Added to wishlist!', 'success');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }, 500);
    }
    
    // Remove from Wishlist
    function removeFromWishlist(productId, button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            showNotification('Removed from wishlist', 'info');
            button.innerHTML = originalHTML;
            button.disabled = false;
        }, 500);
    }
    
    // Add to Cart click handler
 
function handleAddToCartClick(e) {
    e.preventDefault();

    const btn = e.currentTarget;
    const productId = btn.dataset.productId;

    // already added → remove
    if (btn.classList.contains('added')) {
        removeFromCart(productId, btn);
    } else {
        addToCart(productId, btn);
    }
}
// bind AFTER products loaded
function setupAddToCartButtons() {
    document.querySelectorAll(".add-to-cart-btn").forEach(btn => {
        btn.removeEventListener("click", handleAddToCartClick);
        btn.addEventListener("click", handleAddToCartClick);
    });
}


    //add to cart
   function addToCart(productId, button) {
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Use GET request to match add_to_cart.php endpoint
    fetch(`ajax/add_to_cart.php?id=${productId}&qty=1`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            button.classList.add('added');
            button.innerHTML = 'ADDED';
            updateCartCount(data.cart_count);
            showNotification(data.message, 'success');
        } else {
            // Check if it's a redirect (user not logged in)
            if (data.redirect) {
                if (confirm('Please login to add items to cart.\n\nDo you want to login?')) {
                    window.location.href = data.redirect;
                }
            } else {
                showNotification(data.message || 'Failed to add to cart', 'error');
            }
            button.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Add to cart failed. Please check your connection.', 'error');
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
            button.innerHTML = 'Add to Cart';
            updateCartCount(data.cart_count);
            showNotification(data.message, 'info');
        } else {
            showNotification(data.message || 'Failed to remove from cart', 'error');
            button.innerHTML = originalHTML;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Failed to remove from cart. Please check your connection.', 'error');
        button.innerHTML = originalHTML;
    })
    .finally(() => {
        button.disabled = false;
    });
}


    
    // Select Model click handler
    function handleSelectModelClick(e) {
        const selectModelBtn = e.currentTarget;
        const productId = selectModelBtn.dataset.productId;
        
        // Redirect to product details page for model selection
        window.location.href = 'productdetails.php?id=' + productId + '&select_model=1';
    }
    
    // Reset filters
    function resetFilters() {
        elements.filterForm.reset();
        elements.searchInput.value = '';
        state.currentSearch = '';
        state.currentCategoryId = '';
        
        loadCategoryFilters('all');
        
        // Update URL
        const url = new URL(window.location);
        url.searchParams.delete('cat');
        window.history.pushState({}, '', url);
        
        // Reset page title
        updatePageTitle('all');
        
        // Close filters on mobile
        if (state.isMobileView) {
            closeFilterSidebar();
        }
        
        loadProducts(1);
    }
    
    // Filter sidebar functions
    function toggleFilterSidebar() {
        if (elements.filterSidebar.classList.contains('active')) {
            closeFilterSidebar();
        } else {
            openFilterSidebar();
        }
    }
    
    function openFilterSidebar() {
        elements.filterSidebar.classList.add('active');
        elements.filterOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeFilterSidebar() {
        elements.filterSidebar.classList.remove('active');
        elements.filterOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Handle popstate (browser back/forward)
    function handlePopState() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryId = urlParams.get('cat') || '';
        
        if (categoryId && categoryId !== 'all') {
            elements.categoryFilter.value = categoryId;
            loadCategoryFilters(categoryId);
        } else {
            elements.categoryFilter.value = 'all';
            loadCategoryFilters('all');
        }
        
        loadProducts(1);
    }
    
    // Check if user is logged in
    function isUserLoggedIn() {
        // Implement your login check logic here
        // For now, return true for demo purposes
        return <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    }
    
    // Show login alert
    function showLoginAlert(message) {
        if (confirm(message + '\n\nDo you want to login?')) {
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        }
    }
    
    // Show loading state
    function showLoading(show) {
        elements.loadingIndicator.style.display = show ? 'block' : 'none';
    }
    
    // Show error message
    function showError(message) {
        elements.productGrid.innerHTML = `
            <div class="no-products">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${message}</p>
            </div>
        `;
    }
    
    // Show notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <div class="notification-content">
                <div class="notification-title">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</div>
                <div class="notification-message">${message}</div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Update cart count
    function updateCartCount(count = null) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            if (count !== null) {
                cartCountElement.textContent = count;
            } else {
                const currentCount = parseInt(cartCountElement.textContent) || 0;
                cartCountElement.textContent = currentCount + 1;
            }
            cartCountElement.classList.add('updated');
            setTimeout(() => cartCountElement.classList.remove('updated'), 500);
        }
    }
    
    // Announce results to screen readers
    function announceResults(count) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.style.position = 'absolute';
        announcement.style.left = '-9999px';
        announcement.textContent = `Loaded ${count} products`;
        document.body.appendChild(announcement);
        setTimeout(() => announcement.remove(), 1000);
    }
    
    // Prevent form submission on enter in search
    elements.searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>