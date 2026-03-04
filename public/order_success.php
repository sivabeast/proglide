<?php
session_start();
require "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get order ID from session
$order_id = $_SESSION['order_id'] ?? 0;

if (!$order_id) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, 
           u.name as user_name,
           u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Get order address
$stmt = $conn->prepare("SELECT * FROM order_addresses WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$address = $stmt->get_result()->fetch_assoc();

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, 
           p.model_name,
           p.design_name,
           p.image1,
           pm.model_name as phone_model_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN phone_models pm ON oi.phone_model_id = pm.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Clear order ID from session
unset($_SESSION['order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success | PROGLIDE</title>
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 100px;
        }

        .success-container {
            max-width: 900px;
            margin: 0 auto 50px;
        }

        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            border: none;
        }

        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }

        .success-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .success-message {
            font-size: 18px;
            opacity: 0.9;
        }

        .order-info {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .info-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-value {
            font-size: 20px;
            font-weight: 700;
            color: #28a745;
        }

        .info-value small {
            font-size: 14px;
            color: #6c757d;
        }

        .address-section {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #28a745;
        }

        .address-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            line-height: 1.8;
        }

        .address-box p {
            margin-bottom: 5px;
        }

        .address-box strong {
            color: #333;
        }

        .items-section {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .items-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 5px;
        }

        .product-name {
            font-weight: 600;
            color: #333;
        }

        .product-detail {
            font-size: 13px;
            color: #6c757d;
        }

        .price {
            font-weight: 600;
            color: #28a745;
        }

        .total-section {
            padding: 20px 30px;
            text-align: right;
            background: #f8f9fa;
        }

        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
        }

        .action-buttons {
            padding: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35, #FF8E53);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #FF6B35;
            color: #FF6B35;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #FF6B35;
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-30px);}
            60% {transform: translateY(-15px);}
        }

        @media print {
            .action-buttons,
            .btn,
            header,
            footer {
                display: none !important;
            }
            
            body {
                padding-top: 20px;
                background: white;
            }
            
            .success-card {
                box-shadow: none;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .success-header {
                padding: 30px 20px;
            }

            .success-icon {
                font-size: 60px;
            }

            .success-title {
                font-size: 24px;
            }

            .success-message {
                font-size: 16px;
            }

            .order-info,
            .address-section,
            .items-section {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .items-table {
                font-size: 14px;
            }

            .items-table td {
                padding: 10px 8px;
            }

            .product-image {
                width: 40px;
                height: 40px;
            }

            .action-buttons {
                flex-direction: column;
                padding: 20px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include "includes/header.php"; ?>

    <div class="container success-container">
        <div class="success-card">
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="success-title">Order Placed Successfully!</h1>
                <p class="success-message">Thank you for your order. We'll process it soon.</p>
            </div>

            <!-- Order Information -->
            <div class="order-info">
                <div class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    Order Details
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Order ID</div>
                        <div class="info-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">
                            <?php 
                            $payment_method = $order['payment_method'] ?? 'cod';
                            echo $payment_method === 'upi' ? 'UPI Payment' : 'Cash on Delivery'; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value">
                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Order Status</div>
                        <div class="info-value">
                            <span class="badge bg-info">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if ($address): ?>
            <div class="address-section">
                <div class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Shipping Address
                </div>
                
                <div class="address-box">
                    <p><strong><?php echo htmlspecialchars($address['full_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($address['address']); ?></p>
                    <p>Phone: <?php echo htmlspecialchars($address['phone']); ?></p>
                    <p>Pincode: <?php echo htmlspecialchars($address['pincode']); ?></p>
                    <?php if (!empty($address['notes'])): ?>
                        <p class="mt-2"><strong>Notes:</strong> <?php echo htmlspecialchars($address['notes']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="items-section">
                <div class="section-title">
                    <i class="fas fa-shopping-bag"></i>
                    Order Items
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $product_name = $item['design_name'] ?? $item['model_name'] ?? 'Product';
                            if ($item['phone_model_name']) {
                                $product_name .= ' - ' . $item['phone_model_name'];
                            }
                            
                            // Get product image path
                            $image_path = "/assets/no-image.png";
                            if (!empty($item['image1'])) {
                                // You need to get category slug from products table
                                // For now using a generic path
                                $image_path = "/uploads/products/" . $item['image1'];
                            }
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="<?php echo $image_path; ?>" 
                                         alt="<?php echo htmlspecialchars($product_name); ?>"
                                         class="product-image"
                                         onerror="this.src='/assets/no-image.png'">
                                    <div>
                                        <div class="product-name"><?php echo htmlspecialchars($product_name); ?></div>
                                        <?php if ($item['phone_model_name']): ?>
                                            <div class="product-detail">
                                                <i class="fas fa-mobile-alt"></i> <?php echo htmlspecialchars($item['phone_model_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td class="price">₹<?php echo number_format($item['price'], 2); ?></td>
                            <td class="price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total Amount -->
            <div class="total-section">
                <h4>Total Amount: <span class="total-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span></h4>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <a href="orders.php" class="btn btn-outline-primary">
                    <i class="fas fa-clipboard-list"></i> View My Orders
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include "includes/footer.php"; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Redirect to home if no order in session after 5 seconds (optional)
        setTimeout(function() {
            // Just a safety measure - won't redirect if order details are showing
            if (!document.querySelector('.order-info')) {
                window.location.href = 'index.php';
            }
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>