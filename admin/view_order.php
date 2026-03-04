<?php
session_start();
require "includes/admin_db.php";
require "includes/admin_auth.php";

// Page title for header
$page_title = "Order Details";
$page_icon = "bi bi-eye";

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.name as user_name, u.email, u.phone as user_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Get order items with product details
$stmt = $conn->prepare("
    SELECT oi.*, 
           p.design_name, p.model_name, p.image1,
           c.name as category_name,
           pm.model_name as phone_model_name,
           b.name as brand_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN phone_models pm ON oi.phone_model_id = pm.id
    LEFT JOIN brands b ON pm.brand_id = b.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();

// Get order address
$stmt = $conn->prepare("SELECT * FROM order_addresses WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$address = $stmt->get_result()->fetch_assoc();

// Get order payment
$stmt = $conn->prepare("SELECT * FROM order_payments WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

// Handle order status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'] ?? $order['payment_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $payment_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully!";
        // Refresh order data
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
    } else {
        $error_message = "Error updating order status: " . $conn->error;
    }
}

// Function to get status badge class
function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'badge-warning';
        case 'processing':
            return 'badge-info';
        case 'delivered':
            return 'badge-success';
        case 'cancelled':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Function to get payment status badge
function getPaymentBadge($status) {
    switch(strtolower($status)) {
        case 'paid':
            return 'badge-success';
        case 'pending':
            return 'badge-warning';
        case 'failed':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Function to get product image
function getProductImage($image_name) {
    if (empty($image_name)) {
        return "/proglide/assets/no-image.png";
    }
    
    $path = "/proglide/uploads/products/" . $image_name;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return $path;
    }
    
    return "/proglide/assets/no-image.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details | PROGLIDE Admin</title>
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
            margin-left: var(--sidebar-width);
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }

        /* Content */
        .content {
            padding: 30px;
        }

        /* Alerts */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            font-size: 0.9rem;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-actions h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions h2 i {
            color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: var(--dark-hover);
            color: var(--text-primary);
            border: 1px solid var(--dark-border);
        }

        .btn-secondary:hover {
            background: var(--dark-border);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3d8b40;
            transform: translateY(-2px);
        }

        /* Order Info Cards */
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 20px;
        }

        .info-card h3 {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card h3 i {
            color: var(--primary);
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--dark-border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 100px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .info-value {
            flex: 1;
            color: var(--text-primary);
            font-weight: 500;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.1);
            color: var(--danger);
        }

        .badge-info {
            background: rgba(33, 150, 243, 0.1);
            color: var(--info);
        }

        .badge-secondary {
            background: var(--dark-hover);
            color: var(--text-muted);
        }

        /* Order Items Table */
        .items-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--dark-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 i {
            color: var(--primary);
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
            padding: 15px 20px;
            background: var(--dark-hover);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--dark-border);
            color: var(--text-secondary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            background: var(--dark-hover);
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
            color: var(--text-muted);
        }

        .product-details h4 {
            color: var(--text-primary);
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .product-details p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .amount {
            font-weight: 600;
            color: var(--success);
        }

        .total-row {
            background: var(--dark-hover);
            font-weight: 600;
        }

        .total-row td {
            color: var(--text-primary);
            font-size: 1rem;
        }

        /* Address Card */
        .address-card {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 20px;
        }

        .address-details {
            line-height: 1.8;
        }

        .address-details p {
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .address-details strong {
            color: var(--text-primary);
        }

        /* Status Update Form */
        .status-form {
            background: var(--dark-card);
            border-radius: var(--radius);
            border: 1px solid var(--dark-border);
            padding: 20px;
            margin-top: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .header {
                margin-left: 0;
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

            .order-info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .header {
                padding: 0 20px;
            }

            .header-left h2 {
                font-size: 1.2rem;
            }

            .form-row {
                flex-direction: column;
                gap: 10px;
            }

            .form-group {
                width: 100%;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>   
    <?php include "includes/sidebar.php"; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Header Actions -->
            <div class="header-actions">
                <h2>
                    <i class="bi bi-eye"></i> 
                    Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </h2>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
            </div>
            
            <!-- Order Info Cards -->
            <div class="order-info-grid">
                <!-- Customer Info -->
                <div class="info-card">
                    <h3><i class="bi bi-person"></i> Customer Details</h3>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['user_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['user_phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?php echo $order['user_id']; ?></span>
                    </div>
                </div>
                
                <!-- Order Info -->
                <div class="info-card">
                    <h3><i class="bi bi-cart"></i> Order Details</h3>
                    <div class="info-row">
                        <span class="info-label">Order Date</span>
                        <span class="info-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Amount</span>
                        <span class="info-value amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge <?php echo getStatusBadge($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment</span>
                        <span class="info-value">
                            <span class="badge <?php echo getPaymentBadge($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </span>
                    </div>
                </div>
                
                <!-- Payment Info -->
                <div class="info-card">
                    <h3><i class="bi bi-credit-card"></i> Payment Details</h3>
                    <div class="info-row">
                        <span class="info-label">Method</span>
                        <span class="info-value"><?php echo strtoupper($order['payment_method'] ?? 'COD'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Transaction ID</span>
                        <span class="info-value"><?php echo $payment['transaction_id'] ?? 'N/A'; ?></span>
                    </div>
                    <?php if (!empty($payment['payment_proof'])): ?>
                    <div class="info-row">
                        <span class="info-label">Payment Proof</span>
                        <span class="info-value">
                            <a href="../uploads/payments/<?php echo $payment['payment_proof']; ?>" target="_blank" style="color: var(--primary);">
                                <i class="bi bi-file-earmark"></i> View
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="items-card">
                <div class="card-header">
                    <h3><i class="bi bi-box-seam"></i> Order Items</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            while($item = $order_items->fetch_assoc()): 
                                $product_name = $item['design_name'] ?? $item['model_name'] ?? 'Product';
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image">
                                                <?php if ($item['image1']): ?>
                                                    <img src="<?php echo getProductImage($item['image1']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product_name); ?>">
                                                <?php else: ?>
                                                    <i class="bi bi-box"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <h4><?php echo htmlspecialchars($product_name); ?></h4>
                                                <p>
                                                    <?php echo htmlspecialchars($item['category_name'] ?? 'General'); ?>
                                                    <?php if ($item['brand_name'] && $item['phone_model_name']): ?>
                                                        • <?php echo $item['brand_name']; ?> <?php echo $item['phone_model_name']; ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="amount">₹<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <!-- Totals -->
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Subtotal:</td>
                                <td>₹<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                            <?php 
                            $shipping = 0; // You can add shipping logic here
                            $tax = 0; // You can add tax logic here
                            $total = $order['total_amount'];
                            ?>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Shipping:</td>
                                <td>₹<?php echo number_format($shipping, 2); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Tax:</td>
                                <td>₹<?php echo number_format($tax, 2); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right; font-size: 1.1rem;">Total:</td>
                                <td style="font-size: 1.2rem; color: var(--success);">₹<?php echo number_format($total, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <?php if ($address): ?>
            <div class="address-card">
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <i class="bi bi-geo-alt" style="color: var(--primary);"></i>
                    Shipping Address
                </h3>
                <div class="address-details">
                    <p><strong><?php echo htmlspecialchars($address['full_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($address['address']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($address['pincode']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($address['phone']); ?></p>
                    <?php if (!empty($address['notes'])): ?>
                        <p style="margin-top: 10px; font-style: italic;">Notes: <?php echo htmlspecialchars($address['notes']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Update Order Status -->
            <div class="status-form">
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <i class="bi bi-arrow-repeat" style="color: var(--primary);"></i>
                    Update Order Status
                </h3>
                <form method="POST" class="form-row">
                    <div class="form-group">
                        <label>Order Status</label>
                        <select name="order_status" class="form-control">
                            <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control">
                            <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i>
                        Update Status
                    </button>
                </form>
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
        
        // Add active class to current page
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === 'orders.php') {
                link.classList.add('active');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
        
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