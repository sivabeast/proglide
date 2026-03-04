<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";
include "includes/header.php";
include "includes/sidebar.php";

// Page title for header
$page_title = "Dashboard";
$page_icon = "fas fa-tachometer-alt";

// Get dashboard statistics from proglide.sql tables only
$total_products = 0;
$total_orders = 0;
$total_users = 0;
$revenue = 0;
$pending_orders = 0;

// Get total products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
if ($result) {
    $total_products = $result->fetch_assoc()['total'];
}

// Get total orders and pending orders
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM orders");
if ($result) {
    $row = $result->fetch_assoc();
    $total_orders = $row['total'];
    $pending_orders = $row['pending'] ?? 0;
}

// Get total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $total_users = $result->fetch_assoc()['total'];
}

// Get total revenue from paid orders
$result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'");
if ($result) {
    $revenue = $result->fetch_assoc()['total'];
}

// Get recent orders - LAST 5 ONLY
$recent_orders = $conn->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get popular products with images
$popular_products = $conn->query("
    SELECT p.*, 
           c.name as category_name,
           c.slug as category_slug,
           mt.name as material_name,
           vt.name as variant_name
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    LEFT JOIN material_types mt ON p.material_type_id = mt.id
    LEFT JOIN variant_types vt ON p.variant_type_id = vt.id
    WHERE p.is_popular = 1 
    ORDER BY p.created_at DESC
    LIMIT 5
");

// Function to get product image path
function getProductImage($product) {
    if (empty($product['image1'])) {
        return "/proglide/assets/no-image.png";
    }
    
    $category_slug = $product['category_slug'] ?? 'general';
    
    // Try new path with category slug
    $new_path = "/proglide/uploads/products/" . $category_slug . "/" . $product['image1'];
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $new_path)) {
        return $new_path;
    }
    
    // Try old path
    $old_path = "/proglide/uploads/products/" . $product['image1'];
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $old_path)) {
        return $old_path;
    }
    
    return "/proglide/assets/no-image.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard | PROGLIDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #FF6B35;
            --primary-dark: #e55a2b;
            --primary-light: rgba(255, 107, 53, 0.1);
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
            --sidebar-width: 260px;
            --header-height: 70px;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary)!important;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--dark-card);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid var(--dark-border);
            padding: 30px 0;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--dark-border);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .logo {
            padding: 0 25px;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, #FF8E53 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 25px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            font-size: 0.95rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-primary);
            background: var(--primary-light);
            border-right: 3px solid var(--primary);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-link span {
            flex: 1;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Header */
        .header {
            height: var(--header-height);
            background: var(--dark-card);
            border-bottom: 1px solid var(--dark-border);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background: var(--dark-hover);
        }

        .header-left h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-left h2 i {
            color: var(--primary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 15px;
            background: var(--dark-hover);
            border-radius: 40px;
            cursor: pointer;
            transition: var(--transition);
        }

        .admin-profile:hover {
            background: var(--dark-border);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #FF8E53);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
        }

        .admin-info {
            line-height: 1.4;
        }

        .admin-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .admin-info p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .logout-btn {
            padding: 8px 20px;
            background: transparent;
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: var(--primary)!important;
            color: white;
            border-color: var(--primary);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        /* Stats Grid - 2 per row on mobile */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 25px;
            border: 1px solid var(--dark-border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), #FF8E53);
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 1.3rem;
        }

        .stat-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--text-primary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-trend {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--success);
        }

        .stat-trend.warning {
            color: var(--warning);
        }

        /* Section Styles */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: var(--primary);
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .view-all:hover {
            gap: 10px;
        }

        /* Quick Actions - Horizontal Scroll */
        .quick-actions {
            margin-bottom: 40px;
        }

        .actions-scroll-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--dark-border);
            padding: 5px 0 15px 0;
            scroll-behavior: smooth;
        }

        .actions-scroll-container::-webkit-scrollbar {
            height: 6px;
        }

        .actions-scroll-container::-webkit-scrollbar-track {
            background: var(--dark-border);
            border-radius: 10px;
        }

        .actions-scroll-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .actions-grid {
            display: flex;
            gap: 20px;
            width: max-content;
            padding: 5px 0;
        }

        .action-card {
            width: 220px;
            min-width: 220px;
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--dark-border);
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .action-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-card h3 {
            font-size: 1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .action-card p {
            color: var(--text-secondary);
            font-size: 0.8rem;
            line-height: 1.5;
        }

        /* Recent Orders Table */
        .recent-orders {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            margin-bottom: 40px;
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 20px;
            background: var(--dark-hover);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--dark-border);
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--dark-hover);
        }

        .order-status, .payment-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending, .payment-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }

        .status-processing {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }

        .status-delivered, .payment-paid {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-cancelled, .payment-failed {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Popular Products with Images */
        .popular-products {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            overflow: hidden;
        }

        .product-list {
            padding: 20px;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid var(--dark-border);
            transition: var(--transition);
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item:hover {
            background: var(--dark-hover);
        }

        .product-image {
            width: 60px;
            height: 60px;
            background: var(--dark-border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image i {
            font-size: 1.5rem;
            color: var(--primary);
            opacity: 0.5;
        }

        .product-info {
            flex: 1;
        }

        .product-info h4 {
            font-size: 1rem;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .product-info p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-info p span {
            background: var(--dark-hover);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }

        .product-price {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
            text-align: right;
        }

        .original-price {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: line-through;
            margin-right: 8px;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--dark-card), var(--dark-hover));
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--dark-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .welcome-text h1 span {
            color: var(--primary);
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .welcome-stats {
            display: flex;
            gap: 20px;
        }

        .welcome-stat {
            text-align: center;
            padding: 0 15px;
            border-right: 1px solid var(--dark-border);
        }

        .welcome-stat:last-child {
            border-right: none;
        }

        .welcome-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .welcome-stat .label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }

            .admin-info {
                display: none;
            }

            .logout-btn span {
                display: none;
            }

            .logout-btn {
                padding: 10px 15px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                margin-bottom: 15px;
            }

            .section-header h2 {
                font-size: 1.1rem;
            }

            .action-card {
                width: 180px;
                min-width: 180px;
                padding: 15px;
            }

            .welcome-section {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .welcome-stats {
                width: 100%;
                justify-content: space-around;
            }

            .welcome-stat {
                padding: 0 10px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-content {
                padding: 15px;
            }

            .stats-grid {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 1.3rem;
            }

            .stat-label {
                font-size: 0.7rem;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
                margin-bottom: 10px;
            }

            .action-card {
                width: 160px;
                min-width: 160px;
                padding: 12px;
            }

            .welcome-text h1 {
                font-size: 1.4rem;
            }

            .welcome-stat .value {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header and Sidebar are included automatically -->
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header is already included via includes/header.php -->
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>!</h1>
                    <p>Here's what's happening with your store today.</p>
                </div>
                <div class="welcome-stats">
                    <div class="welcome-stat">
                        <div class="value"><?php echo $total_products; ?></div>
                        <div class="label">Products</div>
                    </div>
                    <div class="welcome-stat">
                        <div class="value"><?php echo $total_orders; ?></div>
                        <div class="label">Orders</div>
                    </div>
                    <div class="welcome-stat">
                        <div class="value"><?php echo $total_users; ?></div>
                        <div class="label">Customers</div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid - 2 per row on mobile -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_products; ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-check-circle"></i>
                            In Stock
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-trend <?php echo $pending_orders > 0 ? 'warning' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <?php echo $pending_orders; ?> Pending
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_users; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-user-check"></i>
                            Active
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-details">
                        <div class="stat-info">
                            <div class="stat-value">₹<?php echo number_format($revenue); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            Paid Orders
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions - Horizontal Scroll -->
            <div class="quick-actions">
                <div class="section-header">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                </div>
                <div class="actions-scroll-container">
                    <div class="actions-grid">
                        <a href="add_product.php" class="action-card">
                            <i class="fas fa-plus-circle"></i>
                            <h3>Add Product</h3>
                            <p>Add new products to store</p>
                        </a>
                        
                        <a href="products.php" class="action-card">
                            <i class="fas fa-edit"></i>
                            <h3>Manage Products</h3>
                            <p>Edit or delete products</p>
                        </a>
                        
                        <a href="categories.php" class="action-card">
                            <i class="fas fa-tags"></i>
                            <h3>Categories</h3>
                            <p>Manage product categories</p>
                        </a>
                        
                        <a href="orders.php" class="action-card">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>View Orders</h3>
                            <p>Check and manage orders</p>
                        </a>
                        
                        <a href="brands.php" class="action-card">
                            <i class="fas fa-tag"></i>
                            <h3>Brands</h3>
                            <p>Manage phone brands</p>
                        </a>
                        
                        <a href="phone_models.php" class="action-card">
                            <i class="fas fa-mobile-alt"></i>
                            <h3>Phone Models</h3>
                            <p>Add phone models</p>
                        </a>
                        
                        <a href="material_types.php" class="action-card">
                            <i class="fas fa-cube"></i>
                            <h3>Materials</h3>
                            <p>Manage material types</p>
                        </a>
                        
                        <a href="variant_types.php" class="action-card">
<i class="fas fa-swatchbook"></i>
                            <h3>Variants</h3>
                            <p>Manage variants</p>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders - LAST 5 ONLY -->
            <div class="recent-orders">
                <div class="section-header" style="padding: 20px 20px 0 20px;">
                    <h2><i class="fas fa-clock"></i> Recent Orders (Last 5)</h2>
                    <a href="orders.php" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="payment-status payment-<?php echo strtolower($order['payment_status']); ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" style="color: var(--primary);">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-muted);">No orders yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Popular Products with Images -->
            <div class="popular-products">
                <div class="section-header" style="padding: 20px 20px 0 20px;">
                    <h2><i class="fas fa-star"></i> Popular Products</h2>
                    <a href="products.php?popular=1" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="product-list">
                    <?php if ($popular_products && $popular_products->num_rows > 0): ?>
                        <?php while($product = $popular_products->fetch_assoc()): 
                            $product_name = $product['design_name'] ?? $product['model_name'] ?? 'Product';
                            $image_path = getProductImage($product);
                            $discount = 0;
                            if (!empty($product['original_price']) && $product['original_price'] > $product['price']) {
                                $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                            }
                        ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <?php if ($image_path != "/proglide/assets/no-image.png"): ?>
                                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product_name); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-box"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product_name); ?></h4>
                                    <p>
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                        <?php if (!empty($product['material_name'])): ?>
                                            <span><?php echo htmlspecialchars($product['material_name']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="product-price">
                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <span class="original-price">₹<?php echo number_format($product['original_price']); ?></span>
                                    <?php endif; ?>
                                    ₹<?php echo number_format($product['price']); ?>
                                    <?php if ($discount > 0): ?>
                                        <br><small style="color: var(--success); font-size: 0.7rem;">-<?php echo $discount; ?>%</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-box-open" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
                            <p style="color: var(--text-muted);">No popular products yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && 
                sidebar && !sidebar.contains(e.target) && 
                menuToggle && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Responsive resize handler
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992 && sidebar) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>