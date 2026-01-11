<?php
session_start();
require "includes/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Get order ID from session
$order_id = isset($_SESSION['order_id']) ? (int) $_SESSION['order_id'] : 0;

if (!$order_id) {
    header("Location: cart.php");
    exit;
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.name AS user_name, u.email
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: cart.php");
    exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT 
        oi.*,
        p.model_name,
        p.design_name,
        p.image,
        mc.name AS main_category,
        pm.model_name AS phone_model
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN main_categories mc ON mc.id = p.main_category_id
    LEFT JOIN phone_models pm ON pm.id = oi.phone_model_id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Clear order_id from session
unset($_SESSION['order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Successful</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f7fb;
            padding-top: 120px;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .success-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .success-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon i {
            font-size: 40px;
            color: #28a745;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }

        .order-item {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .btn-primary {
            background: #0d6efd;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-primary {
            border: 2px solid #0d6efd;
            color: #0d6efd;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 90px;
            }

            .success-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="container success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            
            <h2 class="fw-bold mb-2">Order Placed Successfully!</h2>
            <p class="text-muted mb-4">Thank you for your order. We've received your order and will process it soon.</p>
            
            <div class="order-details">
                <h5 class="fw-bold mb-3">Order Details</h5>
                
                <div class="mb-3">
                    <strong>Order ID:</strong> #<?= $order['id'] ?><br>
                    <strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?><br>
                    <strong>Payment Method:</strong> <?= strtoupper($order['payment_method']) ?><br>
                    <strong>Payment Status:</strong> 
                    <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>

                <hr>

                <h6 class="fw-bold mb-3">Order Items:</h6>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <strong>
                            <?= htmlspecialchars(
                                strtolower($item['main_category']) === 'back case'
                                ? ($item['design_name'] . (isset($item['phone_model']) ? ' - ' . $item['phone_model'] : ''))
                                : $item['model_name']
                            ) ?>
                        </strong>
                        <span class="float-end">
                            × <?= $item['quantity'] ?> = ₹<?= $item['price'] * $item['quantity'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>

                <hr>

                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total Amount:</span>
                    <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>

            <div class="mt-4">
                <a href="orders.php" class="btn btn-primary me-2">
                    <i class="fa-solid fa-list me-1"></i> View My Orders
                </a>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fa-solid fa-home me-1"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
</body>
</html>
