<?php
declare(strict_types=1);
session_start();
require "includes/db.php";

/* =========================
   SECURITY & ERROR HANDLING
========================= */
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

/* =========================
   AUTH & SESSION VALIDATION
========================= */
$user_id = null;
$user_name = '';

if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    
    // Fetch user name
    try {
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("User fetch error: " . $e->getMessage());
    }
}

/* =========================
   FETCH FEATURED PRODUCTS
========================= */
$products = [];

try {
    $sql = "SELECT 
                p.id,
                p.model_name,
                p.price,
                p.image,
                p.description,
                ct.type_name,
                mc.name AS main_category,
                mc.slug AS category_slug
            FROM products p
            INNER JOIN category_types ct ON p.category_type_id = ct.id
            INNER JOIN main_categories mc ON p.main_category_id = mc.id
            WHERE p.status = 'active' 
            AND p.stock_quantity > 0
            AND p.featured = 1
            ORDER BY p.created_at DESC
            LIMIT 8";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $product_ids = [];
        $temp_products = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['price'] = (float)$row['price'];
            $row['in_cart'] = false;
            $row['in_wishlist'] = false;
            $product_ids[] = $row['id'];
            $temp_products[$row['id']] = $row;
        }
        
        /* =========================
           BATCH CHECK CART & WISHLIST
        ========================= */
        if ($user_id && !empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $types = str_repeat('i', count($product_ids));
            
            // Check cart
            $cart_stmt = $conn->prepare(
                "SELECT product_id FROM cart 
                 WHERE user_id = ? AND product_id IN ($placeholders)"
            );
            $cart_params = array_merge([$user_id], $product_ids);
            $cart_stmt->bind_param($types, ...$cart_params);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            while ($cart_row = $cart_result->fetch_assoc()) {
                $temp_products[$cart_row['product_id']]['in_cart'] = true;
            }
            $cart_stmt->close();
            
            // Check wishlist
            $wish_stmt = $conn->prepare(
                "SELECT product_id FROM wishlist 
                 WHERE user_id = ? AND product_id IN ($placeholders)"
            );
            $wish_params = array_merge([$user_id], $product_ids);
            $wish_stmt->bind_param($types, ...$wish_params);
            $wish_stmt->execute();
            $wish_result = $wish_stmt->get_result();
            
            while ($wish_row = $wish_result->fetch_assoc()) {
                $temp_products[$wish_row['product_id']]['in_wishlist'] = true;
            }
            $wish_stmt->close();
        }
        
        $products = array_values($temp_products);
    }
} catch (Exception $e) {
    error_log("Products fetch error: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>PROGLIDE | Premium Phone Protection & Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="PROGLIDE - Premium phone protectors, cases, and accessories. Military-grade protection with premium style.">
    <meta name="keywords" content="phone protector, mobile case, screen protector, 9H protector, privacy screen">
    
    <!-- Open Graph -->
    <meta property="og:title" content="PROGLIDE - Premium Phone Protection & Style">
    <meta property="og:description" content="Military-grade protection meets premium style for your devices">
    <meta property="og:type" content="website">
    
    <!-- Preload -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #000000;
            --primary-light: #1a1a1a;
            --secondary: #ffd700;
            --accent: #2563eb;
            --accent-dark: #1d4ed8;
            --danger: #ef4444;
            --success: #10b981;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-800: #1f2937;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius: 12px;
            --radius-lg: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }
        
        /* ======================
           FULLSCREEN HERO SECTION
        ====================== */
        .hero-fullscreen {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?ixlib=rb-4.0.3&auto=format&fit=crop&w=2080&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 20px;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeInUp 1s ease-out;
        }
        
        .hero-badge {
            display: inline-block;
            background: var(--secondary);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 20px;
            background: linear-gradient(45deg, #fff, var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-welcome {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--secondary);
        }
        
        .hero-cta {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        
        .btn {
            padding: 16px 32px;
            border-radius: var(--radius);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: var(--primary);
        }
        
        .btn-primary:hover {
            background: #ffed4a;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }
        
        .scroll-indicator i {
            color: white;
            font-size: 1.5rem;
            opacity: 0.7;
        }
        
        /* ======================
           FEATURES SECTION
        ====================== */
        .features {
            padding: 80px 20px;
            background: white;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 60px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--secondary);
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: var(--gray-50);
            padding: 40px 30px;
            border-radius: var(--radius-lg);
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--secondary);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--secondary), #ffed4a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: var(--primary);
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .feature-card p {
            color: var(--gray-800);
            opacity: 0.8;
        }
        
        /* ======================
           STATS SECTION
        ====================== */
        .stats {
            padding: 80px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><path fill="rgba(255,215,0,0.05)" d="M0,0L1000,0L1000,1000L0,1000Z"/></svg>');
            background-size: cover;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* ======================
           PRODUCTS SECTION
        ====================== */
        .products-section {
            padding: 80px 20px;
            background: var(--gray-50);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            color: var(--primary);
        }
        
        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .view-all:hover {
            gap: 15px;
            color: var(--accent-dark);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .product-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary);
            color: var(--primary);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .product-image-container {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition-slow);
        }
        
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 25px;
        }
        
        .product-category {
            font-size: 0.9rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .product-name {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .product-actions {
            display: flex;
            gap: 12px;
        }
        
        .action-btn {
            flex: 1;
            padding: 12px;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .wishlist-btn {
            background: var(--gray-100);
            color: var(--gray-800);
            width: 48px;
        }
        
        .wishlist-btn.active {
            background: var(--danger);
            color: white;
        }
        
        .wishlist-btn:hover:not(.active) {
            background: var(--gray-200);
        }
        
        .cart-btn {
            background: var(--primary);
            color: white;
            flex: 2;
        }
        
        .cart-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .cart-btn.added {
            background: var(--success);
        }
        
        /* ======================
           CATEGORIES SECTION
        ====================== */
        .categories-section {
            padding: 80px 20px;
            background: white;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 40px auto 0;
        }
        
        .category-card {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 40px 30px;
            border-radius: var(--radius-lg);
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,215,0,0.1), transparent);
            opacity: 0;
            transition: var(--transition);
        }
        
        .category-card:hover::before {
            opacity: 1;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--secondary);
        }
        
        .category-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .category-count {
            font-size: 0.9rem;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }
        
        /* ======================
           TESTIMONIALS
        ====================== */
        .testimonials {
            padding: 80px 20px;
            background: var(--gray-50);
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 50px auto 0;
        }
        
        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 4rem;
            color: var(--secondary);
            opacity: 0.2;
            font-family: serif;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary);
        }
        
        .author-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .author-info p {
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* ======================
           ANIMATIONS
        ====================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }
        
        .fade-in {
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }
        
        /* ======================
           RESPONSIVE
        ====================== */
        @media (max-width: 768px) {
            .hero-content {
                padding: 30px 20px;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .product-info {
                padding: 20px;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .wishlist-btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* ======================
           UTILITIES
        ====================== */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        *:focus-visible {
            outline: 2px solid var(--secondary);
            outline-offset: 2px;
        }
        
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <!-- ======================
         FULLSCREEN HERO
    ====================== -->
    <section class="hero-fullscreen" id="hero">
        <div class="hero-content">
            <span class="hero-badge">Premium Protection</span>
            
            <?php if ($user_name): ?>
                <div class="hero-welcome">
                    Welcome back, <?= $user_name ?>! 👋
                </div>
            <?php endif; ?>
            
            <h1 class="hero-title">
                Protect Your Device<br>
                <span style="font-size: 0.8em;">With Military-Grade Style</span>
            </h1>
            
            <p class="hero-subtitle">
                9H hardness protectors, privacy screens, and premium cases designed to shield your device 
                while elevating your style. Trusted by 50,000+ customers worldwide.
            </p>
            
            <div class="hero-cta">
                <a href="#products" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
                <a href="#features" class="btn btn-secondary">
                    <i class="fas fa-play-circle"></i> Watch Video
                </a>
            </div>
        </div>
        
        <a href="#features" class="scroll-indicator" aria-label="Scroll down">
            <i class="fas fa-chevron-down"></i>
        </a>
    </section>

    <!-- ======================
         FEATURES
    ====================== -->
    <section class="features" id="features">
        <h2 class="section-title">Why Choose PROGLIDE</h2>
        
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Military-Grade Protection</h3>
                <p>9H hardness rating protects against scratches, drops, and impacts</p>
            </div>
            
            <div class="feature-card fade-in" style="animation-delay: 0.1s">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Easy Installation</h3>
                <p>Bubble-free application with our precision-cut design</p>
            </div>
            
            <div class="feature-card fade-in" style="animation-delay: 0.2s">
                <div class="feature-icon">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <h3>360° Privacy</h3>
                <p>Complete privacy protection from every angle</p>
            </div>
            
            <div class="feature-card fade-in" style="animation-delay: 0.3s">
                <div class="feature-icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3>2-Year Warranty</h3>
                <p>Industry-leading warranty on all our products</p>
            </div>
        </div>
    </section>

    <!-- ======================
         STATS
    ====================== -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item fade-in">
                <span class="stat-number" data-count="50000">0</span>
                <span class="stat-label">Happy Customers</span>
            </div>
            
            <div class="stat-item fade-in" style="animation-delay: 0.1s">
                <span class="stat-number" data-count="1500">0</span>
                <span class="stat-label">Products Sold</span>
            </div>
            
            <div class="stat-item fade-in" style="animation-delay: 0.2s">
                <span class="stat-number" data-count="4.8">0</span>
                <span class="stat-label">Avg. Rating</span>
            </div>
            
            <div class="stat-item fade-in" style="animation-delay: 0.3s">
                <span class="stat-number" data-count="24">0</span>
                <span class="stat-label">Hour Support</span>
            </div>
        </div>
    </section>

    <!-- ======================
         PRODUCTS
    ====================== -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2>Featured Products</h2>
            <a href="products.php" class="view-all">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                <p style="font-size: 1.2rem; color: #666;">No featured products available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $index => $product): ?>
                    <?php
                    $folder = (strtolower($product['main_category']) === 'back case') 
                        ? 'backcases' 
                        : 'protectors';
                    $image_path = '../uploads/products/' . $folder . '/' . rawurlencode($product['image']);
                    $alt_text = htmlspecialchars($product['model_name'] . ' - ' . $product['type_name'], ENT_QUOTES, 'UTF-8');
                    ?>
                    
                    <div class="product-card fade-in" style="animation-delay: <?= $index * 0.05 ?>s;">
                        <?php if ($product['featured'] ?? false): ?>
                            <div class="product-badge">Featured</div>
                        <?php endif; ?>
                        
                        <a href="productdetails.php?id=<?= $product['id'] ?>" class="product-image-container">
                            <img src="<?= $image_path ?>" 
                                 alt="<?= $alt_text ?>" 
                                 class="product-image"
                                 loading="<?= $index < 2 ? 'eager' : 'lazy' ?>"
                                 onerror="this.src='../assets/placeholder.jpg'">
                        </a>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <?= htmlspecialchars(strtoupper($product['type_name']), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            
                            <h3 class="product-name">
                                <?= htmlspecialchars($product['model_name'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                            
                            <div class="product-price">
                                ₹ <?= number_format($product['price'], 2) ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="action-btn wishlist-btn <?= $product['in_wishlist'] ? 'active' : '' ?>"
                                        data-product-id="<?= $product['id'] ?>"
                                        aria-label="<?= $product['in_wishlist'] ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                                    <i class="fa <?= $product['in_wishlist'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                                </button>
                                
                                <button class="action-btn cart-btn <?= $product['in_cart'] ? 'added' : '' ?>"
                                        data-product-id="<?= $product['id'] ?>"
                                        aria-label="<?= $product['in_cart'] ? 'Remove from cart' : 'Add to cart' ?>">
                                    <?php if ($product['in_cart']): ?>
                                        <i class="fas fa-check"></i> ADDED
                                    <?php else: ?>
                                        <i class="fas fa-shopping-cart"></i> ADD TO CART
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ======================
         CATEGORIES
    ====================== -->
    <section class="categories-section">
        <h2 class="section-title">Shop By Category</h2>
        
        <div class="categories-grid">
            <a href="products.php?cat=9h" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>9H Protectors</h3>
                <div class="category-count">Military-grade protection</div>
            </a>
            
            <a href="products.php?cat=matte" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-sun"></i>
                </div>
                <h3>Matte Finish</h3>
                <div class="category-count">Anti-glare & fingerprint</div>
            </a>
            
            <a href="products.php?cat=privacy" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <h3>Privacy Screens</h3>
                <div class="category-count">360° privacy protection</div>
            </a>
            
            <a href="products.php?cat=back-cases" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Premium Cases</h3>
                <div class="category-count">Style & protection</div>
            </a>
        </div>
    </section>

    <!-- ======================
         TESTIMONIALS
    ====================== -->
    <section class="testimonials">
        <h2 class="section-title">What Our Customers Say</h2>
        
        <div class="testimonials-grid">
            <div class="testimonial-card fade-in">
                <p class="testimonial-text">
                    "Best screen protector I've ever used! The 9H hardness actually saved my phone from a nasty drop."
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">RS</div>
                    <div class="author-info">
                        <h4>Rahul Sharma</h4>
                        <p>Verified Customer</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in" style="animation-delay: 0.1s">
                <p class="testimonial-text">
                    "Perfect fit and bubble-free installation. The privacy feature is a game-changer!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">PP</div>
                    <div class="author-info">
                        <h4>Priya Patel</h4>
                        <p>Verified Customer</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card fade-in" style="animation-delay: 0.2s">
                <p class="testimonial-text">
                    "Premium quality at an affordable price. The customer support team is amazing!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">AK</div>
                    <div class="author-info">
                        <h4>Amit Kumar</h4>
                        <p>Verified Customer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>

    <!-- ======================
         JAVASCRIPT
    ====================== -->
    <script>
        'use strict';
        
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
        
        // Animated counter for stats
        function animateCounter(element) {
            const target = parseInt(element.getAttribute('data-count'));
            const suffix = element.textContent.includes('.') ? 1 : 0;
            const increment = target / 50;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = suffix ? target.toFixed(1) : target;
                    clearInterval(timer);
                } else {
                    element.textContent = suffix ? current.toFixed(1) : Math.floor(current);
                }
            }, 30);
        }
        
        // Initialize counters when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    document.querySelectorAll('.stat-number').forEach(counter => {
                        animateCounter(counter);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        const statsSection = document.querySelector('.stats');
        if (statsSection) statsObserver.observe(statsSection);
        
        // Wishlist functionality
        class WishlistManager {
            constructor() {
                this.buttons = document.querySelectorAll('.wishlist-btn');
                this.init();
            }
            
            init() {
                this.buttons.forEach(btn => {
                    btn.addEventListener('click', (e) => this.toggleWishlist(e));
                });
            }
            
            async toggleWishlist(e) {
                const button = e.currentTarget;
                const productId = button.dataset.productId;
                const isActive = button.classList.contains('active');
                
                button.classList.add('loading');
                
                try {
                    const endpoint = isActive 
                        ? `ajax/remove_wishlist.php?id=${productId}`
                        : `ajax/add_to_wishlist.php?id=${productId}`;
                    
                    const response = await fetch(endpoint, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (response.ok) {
                        button.classList.toggle('active');
                        const icon = button.querySelector('i');
                        
                        if (button.classList.contains('active')) {
                            icon.classList.replace('fa-regular', 'fa-solid');
                            this.showNotification('Added to wishlist!', 'success');
                        } else {
                            icon.classList.replace('fa-solid', 'fa-regular');
                            this.showNotification('Removed from wishlist', 'info');
                        }
                    }
                } catch (error) {
                    this.showNotification('Something went wrong', 'error');
                } finally {
                    button.classList.remove('loading');
                }
            }
            
            showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                `;
                
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                    color: white;
                    padding: 15px 25px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    z-index: 1000;
                    animation: slideIn 0.3s ease;
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        }
        
        // Cart functionality
        class CartManager {
            constructor() {
                this.buttons = document.querySelectorAll('.cart-btn');
                this.init();
            }
            
            init() {
                this.buttons.forEach(btn => {
                    btn.addEventListener('click', (e) => this.toggleCart(e));
                });
            }
            
            async toggleCart(e) {
                const button = e.currentTarget;
                const productId = button.dataset.productId;
                const isAdded = button.classList.contains('added');
                
                button.classList.add('loading');
                
                try {
                    const endpoint = isAdded 
                        ? `ajax/remove_cart.php?id=${productId}`
                        : `ajax/add_to_cart.php?id=${productId}`;
                    
                    const response = await fetch(endpoint, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (response.ok) {
                        button.classList.toggle('added');
                        
                        const icon = button.querySelector('i');
                        const textSpan = button.querySelector('span');
                        
                        if (button.classList.contains('added')) {
                            icon.className = 'fas fa-check';
                            textSpan.textContent = 'ADDED';
                            this.updateCartCount(1);
                        } else {
                            icon.className = 'fas fa-shopping-cart';
                            textSpan.textContent = 'ADD TO CART';
                            this.updateCartCount(-1);
                        }
                    }
                } catch (error) {
                    alert('Failed to update cart. Please try again.');
                } finally {
                    button.classList.remove('loading');
                }
            }
            
            updateCartCount(change) {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    let current = parseInt(cartCount.textContent) || 0;
                    current = Math.max(0, current + change);
                    cartCount.textContent = current;
                    cartCount.style.display = current > 0 ? 'flex' : 'none';
                }
            }
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new WishlistManager();
            new CartManager();
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                
                .loading {
                    opacity: 0.7;
                    pointer-events: none;
                    position: relative;
                }
                
                .loading::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: 20px;
                    height: 20px;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-top-color: white;
                    border-radius: 50%;
                    animation: spin 0.8s linear infinite;
                    transform: translate(-50%, -50%);
                }
                
                @keyframes spin {
                    to { transform: translate(-50%, -50%) rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const target = document.querySelector(targetId);
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>