<?php
session_start();
require "includes/db.php";

/* =========================
   ERROR REPORTING
========================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* =========================
   USER
========================= */
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

/* =========================
   DETECT FLOW
========================= */
$isBuyNow = isset($_GET['buy_now']) && $_GET['buy_now'] == 1;
$items = [];

/* =========================
   BUY NOW FLOW
========================= */
if ($isBuyNow) {

    if (!isset($_GET['id'], $_GET['qty'])) {
        die("Invalid product");
    }

    $product_id = (int) $_GET['id'];
    $qty = max(1, (int) $_GET['qty']);
    $model_id = isset($_GET['model_id']) ? (int) $_GET['model_id'] : null;
    
    $stmt = $conn->prepare("
        SELECT 
            p.id AS product_id,
            p.price,
            p.model_name,
            p.design_name,
            p.image1,
            c.name AS main_category,
            c.slug AS category_slug,
            ? AS quantity,
            ? AS phone_model_id,
            pm.model_name AS phone_model
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN phone_models pm ON pm.id = ?
        WHERE p.id = ? AND p.status = 1
    ");
    $stmt->bind_param("iiii", $qty, $model_id, $model_id, $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        die("Invalid product");
    }

    $items[] = $row;
}

/* =========================
   CART FLOW
========================= */ else {

    $stmt = $conn->prepare("
        SELECT 
            c.quantity,
            p.id AS product_id,
            p.price,
            p.model_name,
            p.design_name,
            p.image1,
            cat.name AS main_category,
            cat.slug AS category_slug,
            c.phone_model_id,
            pm.model_name AS phone_model
        FROM cart c
        JOIN products p ON p.id = c.product_id
        JOIN categories cat ON cat.id = p.category_id
        LEFT JOIN phone_models pm ON pm.id = c.phone_model_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!$items) {
        header("Location: cart.php");
        exit;
    }
}

/* =========================
   TOTAL
========================= */
$subtotal = 0;
foreach ($items as $i) {
    $subtotal += $i['price'] * $i['quantity'];
}
$delivery = 0;
$total = $subtotal + $delivery;

/* =========================
   PLACE ORDER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $pincode = trim($_POST['pincode']);
    $notes = trim($_POST['notes']);
    $payment_method = $_POST['payment_method'];

    if (!$full_name || !$phone || !$address || !$pincode) {
        $error = "All fields are required";
    }

    /* UPI */
    $payment_proof = null;
    if (!isset($error) && $payment_method === 'upi') {

        if (empty($_FILES['payment_proof']['name'])) {
            $error = "UPI payment screenshot is required";
        } else {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = "Invalid image format (jpg, png, webp only)";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/proglide/uploads/payments/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $payment_proof = time() . '_' . $user_id . '.' . $ext;
                
                if (move_uploaded_file(
                    $_FILES['payment_proof']['tmp_name'],
                    $upload_dir . $payment_proof
                )) {
                    // Store only filename in database
                    $payment_proof = $payment_proof;
                } else {
                    $error = "Failed to upload payment screenshot";
                }
            }
        }
    }

    if (!isset($error)) {

        $conn->begin_transaction();
        try {

            /* INSERT ORDER */
            $stmt = $conn->prepare("
                INSERT INTO orders 
                (user_id, total_amount, status, payment_status, payment_method, created_at) 
                VALUES (?, ?, 'Pending', ?, ?, NOW())
            ");
            
            $payment_status = ($payment_method === 'upi') ? 'paid' : 'pending';
            
            $stmt->bind_param("idss", $user_id, $total, $payment_status, $payment_method);
            
            if (!$stmt->execute()) {
                throw new Exception("Order insert failed: " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;

            /* INSERT SHIPPING ADDRESS - Using order_addresses table */
            $stmt = $conn->prepare("
                INSERT INTO order_addresses 
                (order_id, full_name, phone, address, pincode, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "isssss",
                $order_id,
                $full_name,
                $phone,
                $address,
                $pincode,
                $notes
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Address insert failed: " . $stmt->error);
            }

            /* INSERT ORDER ITEMS */
            $stmt = $conn->prepare("
                INSERT INTO order_items 
                (order_id, product_id, phone_model_id, quantity, price) 
                VALUES (?,?,?,?,?)
            ");
            
            foreach ($items as $i) {
                $phone_model_id = !empty($i['phone_model_id']) ? $i['phone_model_id'] : null;
                $stmt->bind_param(
                    "iiiid",
                    $order_id,
                    $i['product_id'],
                    $phone_model_id,
                    $i['quantity'],
                    $i['price']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Order items insert failed: " . $stmt->error);
                }
            }

            /* INSERT PAYMENT DETAILS IF UPI */
            if ($payment_method === 'upi' && $payment_proof) {
                // You might want to create a payments table or add to orders
                // For now, we'll update the orders table with payment proof
                $stmt = $conn->prepare("
                    UPDATE orders SET payment_proof = ? WHERE id = ?
                ");
                
                $stmt->bind_param("si", $payment_proof, $order_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Payment update failed: " . $stmt->error);
                }
            }

            /* CLEAR CART ONLY IF CART FLOW */
            if (!$isBuyNow) {
                $conn->query("DELETE FROM cart WHERE user_id=$user_id");
            }

            $conn->commit();
            
            // Store order ID in session for success page
            $_SESSION['order_id'] = $order_id;
            header("Location: order_success.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order failed: " . $e->getMessage();
        }
    }
}

// Helper function to get product image
function getProductImage($item) {
    if (empty($item['image1'])) {
        return "/proglide/assets/no-image.png";
    }
    
    $category_slug = $item['category_slug'] ?? 'general';
    $image_path = "/proglide/uploads/products/" . $category_slug . "/" . $item['image1'];
    
    // Check if file exists (optional - you might want to implement this)
    return $image_path;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Checkout | PROGLIDE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/proglide/image/logo.png">

    <style>
        body {
            background: #f8f9fa;
            padding-top: 120px;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .container {
            max-width: 1200px;
        }

        .checkout-header {
            margin-bottom: 30px;
        }

        .checkout-header h4 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
        }

        .card-header i {
            color: #FF6B35;
            margin-right: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-label i {
            color: #FF6B35;
            width: 20px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Payment Methods */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #FF6B35;
            background: #fff9f7;
        }

        .payment-method.selected {
            border-color: #FF6B35;
            background: #fff9f7;
        }

        .payment-method input[type="radio"] {
            accent-color: #FF6B35;
            transform: scale(1.1);
            margin-right: 12px;
        }

        .payment-method label {
            font-weight: 600;
            color: #333;
            cursor: pointer;
        }

        .payment-method small {
            display: block;
            color: #666;
            margin-top: 5px;
            margin-left: 30px;
        }

        /* UPI Section */
        #upi-box {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #FF6B35;
        }

        .upi-details {
            text-align: center;
            margin-bottom: 20px;
        }

        .upi-id {
            font-size: 20px;
            font-weight: 700;
            color: #FF6B35;
            background: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            border: 2px dashed #FF6B35;
            margin: 10px 0;
        }

        .copy-btn {
            background: #FF6B35;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }

        .copy-btn i {
            margin-right: 5px;
        }

        .upi-app-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px 0;
        }

        .upi-app-btn {
            flex: 1;
            min-width: 120px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .upi-app-btn:hover {
            border-color: #FF6B35;
            background: #fff9f7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.1);
        }

        .upi-app-btn i {
            font-size: 20px;
            color: #FF6B35;
            margin-bottom: 5px;
        }

        .upload-section {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #FF6B35;
        }

        .upload-section label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        .upload-section input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
        }

        .payment-confirmed {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .payment-confirmed.show {
            display: flex;
        }

        .payment-confirmed i {
            font-size: 20px;
        }

        /* Order Summary */
        .summary-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }

        .summary-item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .summary-item-details {
            flex: 1;
        }

        .summary-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .summary-item-meta {
            font-size: 13px;
            color: #666;
        }

        .summary-item-price {
            font-weight: 700;
            color: #FF6B35;
        }

        .summary-totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }

        .summary-row.total span:last-child {
            color: #FF6B35;
        }

        .place-order-btn {
            background: linear-gradient(135deg, #FF6B35, #FF8E53);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .error-message i {
            margin-right: 10px;
        }

        /* Responsive */
        @media (max-width: 991px) {
            body {
                padding-top: 90px;
            }

            .checkout-header h4 {
                font-size: 20px;
            }

            .card-header {
                padding: 15px 20px;
            }

            .card-body {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .upi-app-buttons {
                flex-direction: column;
            }

            .upi-app-btn {
                width: 100%;
            }

            .summary-item {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .summary-item-image {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 480px) {
            .upi-id {
                font-size: 16px;
                display: block;
            }

            .copy-btn {
                margin-left: 0;
                margin-top: 10px;
                display: block;
                width: 100%;
            }

            .place-order-btn {
                font-size: 16px;
                padding: 12px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include "includes/header.php"; ?>

    <div class="container">
        <div class="checkout-header">
            <h4><i class="fas fa-lock me-2" style="color: #FF6B35;"></i> Secure Checkout</h4>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="checkoutForm">
            <div class="row g-4">
                <!-- LEFT COLUMN - Shipping & Payment -->
                <div class="col-lg-7">
                    <!-- Shipping Details Card -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-truck"></i> Shipping Details
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-user"></i> Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-phone"></i> Phone Number *</label>
                                        <input type="tel" class="form-control" name="phone" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Full Address *</label>
                                        <textarea class="form-control" name="address" required></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-mail-bulk"></i> Pincode *</label>
                                        <input type="text" class="form-control" name="pincode" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-sticky-note"></i> Order Notes (Optional)</label>
                                        <input type="text" class="form-control" name="notes" placeholder="Any special instructions">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Card -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </div>
                        <div class="card-body">
                            <div class="payment-methods">
                                <!-- COD -->
                                <div class="payment-method selected" id="cod-wrap" onclick="selectPayment('cod')">
                                    <input type="radio" name="payment_method" id="cod" value="cod" checked>
                                    <label for="cod">Cash on Delivery (COD)</label>
                                    <small>Pay when you receive your order</small>
                                </div>

                                <!-- UPI -->
                                <div class="payment-method" id="upi-wrap" onclick="selectPayment('upi')">
                                    <input type="radio" name="payment_method" id="upi" value="upi">
                                    <label for="upi">UPI Payment</label>
                                    <small>Pay via Google Pay, PhonePe, Paytm, etc.</small>

                                    <div id="upi-box" class="d-none">
                                        <div class="upi-details">
                                            <p class="mb-2">Pay to this UPI ID:</p>
                                            <div class="upi-id" id="upiId">
                                                sivabeast123123@okaxis
                                                <button type="button" class="copy-btn" onclick="copyUPI()">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                            <p class="text-muted small mt-2">Amount: ₹<?= number_format($total, 2) ?></p>
                                        </div>

                                        <div class="upi-app-buttons">
                                            <div class="upi-app-btn" onclick="openUpiApp('gpay')">
                                                <i class="fab fa-google-pay"></i>
                                                <div>Google Pay</div>
                                            </div>
                                            <div class="upi-app-btn" onclick="openUpiApp('phonepe')">
                                                <i class="fas fa-mobile-alt"></i>
                                                <div>PhonePe</div>
                                            </div>
                                            <div class="upi-app-btn" onclick="openUpiApp('paytm')">
                                                <i class="fas fa-mobile-alt"></i>
                                                <div>Paytm</div>
                                            </div>
                                            <div class="upi-app-btn" onclick="openUpiApp('other')">
                                                <i class="fas fa-qrcode"></i>
                                                <div>Other UPI</div>
                                            </div>
                                        </div>

                                        <div class="upload-section">
                                            <label><i class="fas fa-cloud-upload-alt"></i> Upload Payment Screenshot *</label>
                                            <input type="file" name="payment_proof" id="paymentProof" accept="image/*">
                                            <small class="text-muted d-block mt-2">Upload screenshot after payment (JPG, PNG, WEBP only)</small>
                                        </div>

                                        <div class="payment-confirmed" id="paymentConfirmed">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Payment screenshot uploaded! You can now place your order.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN - Order Summary -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-shopping-bag"></i> Order Summary
                        </div>
                        <div class="card-body">
                            <?php foreach ($items as $item): 
                                $product_name = $item['design_name'] ?? $item['model_name'] ?? 'Product';
                                if (!empty($item['phone_model'])) {
                                    $product_name .= ' - ' . $item['phone_model'];
                                }
                                $item_total = $item['price'] * $item['quantity'];
                            ?>
                            <div class="summary-item">
                                <div class="summary-item-image">
                                    <img src="<?= getProductImage($item) ?>" 
                                         alt="<?= htmlspecialchars($product_name) ?>"
                                         onerror="this.src='/proglide/assets/no-image.png'">
                                </div>
                                <div class="summary-item-details">
                                    <div class="summary-item-name"><?= htmlspecialchars($product_name) ?></div>
                                    <div class="summary-item-meta">Qty: <?= $item['quantity'] ?></div>
                                    <div class="summary-item-price">₹<?= number_format($item_total, 2) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <div class="summary-totals">
                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span>₹<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Delivery</span>
                                    <span><?= $delivery ? '₹' . number_format($delivery, 2) : 'Free' ?></span>
                                </div>
                                <div class="summary-row total">
                                    <span>Total</span>
                                    <span>₹<?= number_format($total, 2) ?></span>
                                </div>
                            </div>

                            <button type="submit" class="place-order-btn" id="placeOrderBtn">
                                <i class="fas fa-lock me-2"></i> Place Order
                            </button>

                            <p class="text-muted small text-center mt-3">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your information is secure and encrypted
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Payment state
        let paymentMethod = 'cod';
        let paymentConfirmed = false;
        const upiId = 'sivabeast123123@okaxis';
        const totalAmount = <?= $total ?>;

        document.addEventListener('DOMContentLoaded', function() {
            selectPayment('cod');
            
            const paymentProof = document.getElementById('paymentProof');
            if (paymentProof) {
                paymentProof.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        paymentConfirmed = true;
                        document.getElementById('paymentConfirmed').classList.add('show');
                    } else {
                        paymentConfirmed = false;
                        document.getElementById('paymentConfirmed').classList.remove('show');
                    }
                });
            }
        });

        function selectPayment(method) {
            paymentMethod = method;
            
            document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
            
            if (method === 'upi') {
                document.getElementById('upi-wrap').classList.add('selected');
                document.getElementById('upi').checked = true;
                document.getElementById('upi-box').classList.remove('d-none');
            } else {
                document.getElementById('cod-wrap').classList.add('selected');
                document.getElementById('cod').checked = true;
                document.getElementById('upi-box').classList.add('d-none');
            }
        }

        function copyUPI() {
            navigator.clipboard.writeText(upiId).then(function() {
                alert('UPI ID copied to clipboard!');
            });
        }

        function openUpiApp(app) {
            let upiLink = '';
            const amount = totalAmount.toFixed(2);
            const name = 'PROGLIDE';
            
            switch(app) {
                case 'gpay':
                    upiLink = `tez://upi/pay?pa=${upiId}&pn=${name}&am=${amount}&cu=INR`;
                    break;
                case 'phonepe':
                    upiLink = `phonepe://pay?pa=${upiId}&pn=${name}&am=${amount}&cu=INR`;
                    break;
                case 'paytm':
                    upiLink = `paytmmp://pay?pa=${upiId}&pn=${name}&am=${amount}&cu=INR`;
                    break;
                default:
                    upiLink = `upi://pay?pa=${upiId}&pn=${name}&am=${amount}&cu=INR`;
            }
            
            window.location.href = upiLink;
            
            // Fallback
            setTimeout(function() {
                if (document.hasFocus()) {
                    alert('If app didn\'t open, please use UPI ID: ' + upiId);
                }
            }, 1000);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>