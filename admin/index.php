<?php
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Get dashboard statistics
$total_products = 0;
$total_orders = 0;
$total_users = 0;
$revenue = 0;

// Get total products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
if ($result) {
    $total_products = $result->fetch_assoc()['total'];
}

// Get total orders
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
if ($result) {
    $total_orders = $result->fetch_assoc()['total'];
}

// Get total users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $total_users = $result->fetch_assoc()['total'];
}

// Get total revenue from paid orders
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
if ($result) {
    $revenue = $result->fetch_assoc()['total'] ?? 0;
}

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Get popular products
$popular_products = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_popular = 1 AND p.status = 1 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PROGLIDE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            color: var(--text-primary);
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
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 5px;
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

        /* Quick Actions */
        .quick-actions {
            margin-bottom: 40px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            padding: 25px;
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
            font-size: 1.1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .action-card p {
            color: var(--text-secondary);
            font-size: 0.85rem;
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

        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }

        .status-processing {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }

        .status-delivered {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .payment-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .payment-paid {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .payment-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }

        .payment-failed {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Popular Products */
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
            width: 50px;
            height: 50px;
            background: var(--dark-border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
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
        }

        .product-price {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Responsive */
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
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 0 20px;
            }

            .header-left h2 {
                font-size: 1.2rem;
            }

            th, td {
                padding: 12px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-content {
                padding: 15px;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .section-header h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
<?php include "includes/header.php"; ?>   
<?php include "includes/sidebar.php"; ?>
        
<!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Stats Grid -->
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
                            <i class="fas fa-arrow-up"></i>
                            Active
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
                        <div class="stat-trend">
                            <i class="fas fa-clock"></i>
                            Pending: 0
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
                            <div class="stat-value">₹<?php echo number_format($revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            Paid Orders
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-header">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                </div>
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
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="recent-orders">
                <div class="section-header" style="padding: 20px 20px 0 20px;">
                    <h2><i class="fas fa-clock"></i> Recent Orders</h2>
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
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
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
                                        <i class="fas fa-shopping-cart" style="font-size: 2rem; color: var(--text-muted; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-muted);">No orders yet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Popular Products -->
            <div class="popular-products">
                <div class="section-header" style="padding: 20px 20px 0 20px;">
                    <h2><i class="fas fa-star"></i> Popular Products</h2>
                    <a href="products.php?popular=1" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="product-list">
                    <?php if ($popular_products && $popular_products->num_rows > 0): ?>
                        <?php while($product = $popular_products->fetch_assoc()): ?>
                            <div class="product-item">
                                <div class="product-image">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product['design_name'] ?? $product['model_name'] ?? 'Product'); ?></h4>
                                    <p><?php echo htmlspecialchars($product['category_name']); ?></p>
                                </div>
                                <div class="product-price">
                                    ₹<?php echo number_format($product['price'], 2); ?>
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
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.php')) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Responsive resize handler
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>