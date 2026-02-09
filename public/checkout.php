<?php
session_start();
require "includes/db.php";

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
            c.name AS main_category,
            ? AS quantity,
            ? AS phone_model_id,
            pm.model_name AS phone_model
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN phone_models pm ON pm.id = ?
        WHERE p.id = ?
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
            cat.name AS main_category,
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
                if (!is_dir("uploads/payments")) {
                    mkdir("uploads/payments", 0777, true);
                }
                $payment_proof = time() . '_' . $user_id . '.' . $ext;
                move_uploaded_file(
                    $_FILES['payment_proof']['tmp_name'],
                    "uploads/payments/" . $payment_proof
                );
            }
        }
    }

    if (!isset($error)) {

        $conn->begin_transaction();
        try {

            /* ORDER */
            $stmt = $conn->prepare("
                INSERT INTO orders
                (user_id, full_name, phone, address, pincode,
                 total_amount, payment_method, payment_status,
                 payment_proof, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            // Set payment status to 'paid' for UPI orders (no admin verification needed)
            $payment_status = ($payment_method === 'upi') ? 'paid' : 'pending';

            $stmt->bind_param(
                "issssdssss",
                $user_id,
                $full_name,
                $phone,
                $address,
                $pincode,
                $total,
                $payment_method,
                $payment_status,
                $payment_proof,
                $notes
            );
            $stmt->execute();
            $order_id = $conn->insert_id;

            /* ORDER ITEMS */
            $stmt = $conn->prepare("
                INSERT INTO order_items
                (order_id, product_id, phone_model_id, quantity, price)
                VALUES (?,?,?,?,?)
            ");
            foreach ($items as $i) {
                $stmt->bind_param(
                    "iiiid",
                    $order_id,
                    $i['product_id'],
                    $i['phone_model_id'],
                    $i['quantity'],
                    $i['price']
                );
                $stmt->execute();
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
            $error = "Order failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Checkout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f7fb;
            padding-top: 120px;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .container {
            max-width: 1100px;
        }

        .card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 12px;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
        }

        .payment-method {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 12px;
        }

        .payment-method:hover {
            border-color: #999;
        }

        .payment-method.selected {
            border-color: #0d6efd;
            background: #eef4ff;
        }

        .payment-method input[type="radio"] {
            accent-color: #0d6efd;
        }

        #upi-box {
            border-top: 1px dashed #ccc;
            margin-top: 15px;
            padding-top: 15px;
        }

        .upi-qr {
            max-width: 200px;
            width: 200px;
            height: 200px;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background: #fff;
            display: block;
            margin: 0 auto;
        }

        .upi-app-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .upi-app-btn {
            flex: 1;
            min-width: 100px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            color: #333;
        }

        .upi-app-btn:hover {
            border-color: #0d6efd;
            background: #eef4ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }

        .payment-confirmed {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            display: none;
        }

        .payment-confirmed.show {
            display: block;
        }

        .payment-confirmed i {
            color: #28a745;
            margin-right: 8px;
        }

        .btn-dark:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-dark:disabled:hover {
            background: #ccc;
        }

        @media (max-width: 991px) {
            body {
                padding-top: 90px;
            }

            .container {
                padding: 0 15px;
            }
        }

        @media (max-width: 480px) {
            .upi-app-btn {
                min-width: 80px;
                padding: 10px 8px;
                font-size: 12px;
            }

            .upi-qr {
                max-width: 180px;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <div class="container">
        <h4 class="fw-bold mb-4">Checkout</h4>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="checkoutForm">
            <div class="row g-3">

                <!-- LEFT -->
                <div class="col-lg-7">
                    <div class="card p-3 mb-3">
                        <h6 class="fw-bold mb-3">Shipping Details</h6>
                        <input class="form-control mb-2" name="full_name" placeholder="Full Name" required
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                        <input class="form-control mb-2" name="phone" placeholder="Phone Number" required>
                        <textarea class="form-control mb-2" name="address" placeholder="Full Address" required></textarea>
                        <input class="form-control" name="pincode" placeholder="Pincode" required>
                    </div>

                    <div class="card p-3 mb-3">
                        <h6 class="fw-bold mb-3">Payment Method</h6>

                        <div class="payment-method selected" id="cod-wrap" onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" id="cod" value="cod" checked>
                            <label class="fw-bold ms-2" for="cod">Cash on Delivery</label>
                        </div>

                        <div class="payment-method" id="upi-wrap" onclick="selectPayment('upi')">
                            <input type="radio" name="payment_method" id="upi" value="upi">
                            <label class="fw-bold ms-2" for="upi">UPI Payment</label>

                            <div id="upi-box" class="d-none">
                                <!-- Desktop: QR Code -->
                                <div id="desktopUpi" class="text-center d-none">
                                    <p class="fw-bold mb-2">Scan QR code to pay ₹<?= number_format($total, 2) ?></p>
                                    <img id="upiQR" class="upi-qr mb-2" alt="UPI QR Code" style="display: block; margin: 0 auto;">
                                    <p class="fw-bold mb-2" style="font-size: 16px;">UPI ID: <span id="upiIdDesktop" onclick="openUpiApp('gpay', event)" style="color: #0d6efd; cursor: pointer; text-decoration: underline; user-select: none;" title="Click to open GPay with amount">sivabeast123123@okaxis</span></p>
                                    <p class="small text-muted mb-3">After payment, upload screenshot below</p>
                                    <input type="file" name="payment_proof" id="paymentProof" class="form-control" accept="image/*">
                                    <small class="text-muted">Upload payment screenshot (Required)</small>
                                </div>

                                <!-- Mobile: QR Code + UPI App Links -->
                                <div id="mobileUpi" class="d-none">
                                    <p class="fw-bold mb-2 text-center">Pay ₹<?= number_format($total, 2) ?></p>
                                    
                                    <!-- QR Code for Mobile -->
                                    <div class="text-center mb-3">
                                        <p class="small text-muted mb-2">Scan QR code with any UPI app</p>
                                        <img id="upiQRMobile" class="upi-qr mb-2" alt="UPI QR Code" style="display: block; margin: 0 auto;">
                                        <p class="fw-bold mb-2" style="font-size: 16px;">UPI ID: <span id="upiIdMobile" onclick="openUpiApp('gpay', event)" style="color: #0d6efd; cursor: pointer; text-decoration: underline; user-select: none;" title="Click to open GPay with amount">sivabeast123123@okaxis</span></p>
                                    </div>
                                    
                                    <p class="small text-muted mb-3 text-center">Or choose your UPI app (Amount will be pre-filled)</p>
                                    <div class="upi-app-buttons">
                                        <div class="upi-app-btn" onclick="openUpiApp('gpay', event)">
                                            <div>GPay</div>
                                        </div>
                                        <div class="upi-app-btn" onclick="openUpiApp('phonepe', event)">
                                            <div>PhonePe</div>
                                        </div>
                                        <div class="upi-app-btn" onclick="openUpiApp('paytm', event)">
                                            <div>Paytm</div>
                                        </div>
                                        <div class="upi-app-btn" onclick="openUpiApp('upi', event)">
                                            <div>Any UPI</div>
                                        </div>
                                    </div>
                                    <p class="small text-muted mt-3 mb-2 text-center">After payment, upload screenshot below</p>
                                    <input type="file" name="payment_proof" id="paymentProofMobile" class="form-control" accept="image/*">
                                    <small class="text-muted">Upload payment screenshot (Required)</small>
                                </div>

                                <!-- Payment Confirmation Message -->
                                <div class="payment-confirmed" id="paymentConfirmed">
                                    <i class="fa-solid fa-check-circle"></i>
                                    <span>Payment screenshot uploaded! You can now place your order.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <textarea class="form-control" name="notes" placeholder="Order notes (optional)"></textarea>
                </div>

                <!-- RIGHT -->
                <div class="col-lg-5">
                    <div class="card p-3">
                        <h6 class="fw-bold mb-3">Order Summary</h6>

                        <?php foreach ($items as $c): ?>
                            <p class="mb-2">
                                <?= htmlspecialchars(
                                    strtolower($c['main_category']) === 'back case'
                                    ? ($c['design_name'] . (isset($c['phone_model']) ? ' - ' . $c['phone_model'] : ''))
                                    : $c['model_name']
                                ) ?>
                                × <?= $c['quantity'] ?>
                                <span class="float-end">₹<?= $c['price'] * $c['quantity'] ?></span>
                            </p>
                        <?php endforeach; ?>

                        <hr>
                        <p>Subtotal <span class="float-end">₹<?= $subtotal ?></span></p>
                        <p>Delivery <span class="float-end"><?= $delivery ? "₹$delivery" : "Free" ?></span></p>
                        <hr>
                        <h5>Total <span class="float-end">₹<?= $total ?></span></h5>

                        <button type="submit" class="btn btn-dark w-100 mt-3" id="placeOrderBtn">Place Order</button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
        // Device detection
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // Payment state
        let paymentMethod = 'cod';
        let paymentConfirmed = false;
        const totalAmount = <?= $total ?>;
        const upiId = 'sivabeast123123@okaxis';
        const merchantName = 'PROGLIDE';
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize button state
            updateOrderButton();
            
            // Handle screenshot upload
            const paymentProof = document.getElementById('paymentProof');
            const paymentProofMobile = document.getElementById('paymentProofMobile');
            
            if (paymentProof) {
                paymentProof.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        confirmPayment();
                    }
                });
            }
            
            if (paymentProofMobile) {
                paymentProofMobile.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        confirmPayment();
                    }
                });
            }
            
            // Form will submit naturally to PHP - no JavaScript validation needed
        });
        
        function selectPayment(method) {
            paymentMethod = method;
            paymentConfirmed = false;
            
            document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
            
            if (method === 'upi') {
                document.getElementById('upi-wrap').classList.add('selected');
                document.getElementById('upi').checked = true;
                document.getElementById('upi-box').classList.remove('d-none');
                
                // Generate QR code - Simplified format to avoid "could not load banking name" error
                const upiLink = `upi://pay?pa=${upiId}&pn=${merchantName}&am=${totalAmount.toFixed(2)}&cu=INR`;
                
                // Show desktop or mobile UI
                if (isMobile) {
                    document.getElementById('desktopUpi').classList.add('d-none');
                    document.getElementById('mobileUpi').classList.remove('d-none');
                    
                    // Generate QR code for mobile
                    const qrImgMobile = document.getElementById('upiQRMobile');
                    const qrCodeUrlMobile = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(upiLink);
                    qrImgMobile.src = qrCodeUrlMobile;
                    qrImgMobile.style.display = 'block';
                    qrImgMobile.style.visibility = 'visible';
                    
                    // Fallback to Google Charts if first one fails
                    qrImgMobile.onerror = function() {
                        console.log('Trying Google Charts API as fallback...');
                        this.src = "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" + encodeURIComponent(upiLink);
                    };
                } else {
                    document.getElementById('desktopUpi').classList.remove('d-none');
                    document.getElementById('mobileUpi').classList.add('d-none');
                    
                    // Generate QR code for desktop
                    const qrImg = document.getElementById('upiQR');
                    const qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(upiLink);
                    qrImg.src = qrCodeUrl;
                    qrImg.style.display = 'block';
                    qrImg.style.visibility = 'visible';
                    
                    // Fallback to Google Charts if first one fails
                    qrImg.onerror = function() {
                        console.log('Trying Google Charts API as fallback...');
                        this.src = "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" + encodeURIComponent(upiLink);
                    };
                }
            } else {
                document.getElementById('cod-wrap').classList.add('selected');
                document.getElementById('cod').checked = true;
                document.getElementById('upi-box').classList.add('d-none');
                paymentConfirmed = true; // COD doesn't need confirmation
            }
            
            // Always update button state after payment method change
            updateOrderButton();
        }
        
        function openUpiApp(app, event) {
            if (event) {
                event.stopPropagation();
            }
            
            let upiLink = '';
            const amount = totalAmount.toFixed(2);
            // Standard UPI format - same as QR code to ensure consistency
            const baseLink = `upi://pay?pa=${upiId}&pn=${merchantName}&am=${amount}&cu=INR`;
            
            switch(app) {
                case 'gpay':
                    // GPay correct format - use standard UPI link (GPay handles it)
                    upiLink = `upi://pay?pa=${upiId}&pn=${merchantName}&am=${amount}&cu=INR`;
                    break;
                case 'phonepe':
                    // PhonePe format
                    upiLink = `phonepe://pay?pa=${upiId}&pn=${merchantName}&am=${amount}&cu=INR`;
                    break;
                case 'paytm':
                    // Paytm format
                    upiLink = `paytmmp://pay?pa=${upiId}&pn=${merchantName}&am=${amount}&cu=INR`;
                    break;
                default:
                    upiLink = baseLink;
            }
            
            // Open UPI app - try direct link first, fallback to standard UPI
            try {
                window.location.href = upiLink;
                // If app doesn't open, try standard UPI link after a delay
                setTimeout(function() {
                    // Check if still on same page (app didn't open)
                    if (document.hasFocus()) {
                        window.location.href = baseLink;
                    }
                }, 500);
            } catch(e) {
                console.error('Error opening UPI app:', e);
                window.location.href = baseLink;
            }
        }
        
        function confirmPayment() {
            paymentConfirmed = true;
            document.getElementById('paymentConfirmed').classList.add('show');
            updateOrderButton();
        }
        
        function updateOrderButton() {
            const orderBtn = document.getElementById('placeOrderBtn');
            
            if (!orderBtn) return;
            
            // Always enable button - PHP will handle validation
            orderBtn.disabled = false;
            
            // Update text as visual hint only (doesn't prevent submission)
            if (paymentMethod === 'cod') {
                orderBtn.textContent = 'Place Order';
            } else if (paymentMethod === 'upi') {
                if (paymentConfirmed) {
                    orderBtn.textContent = 'Place Order';
                } else {
                    orderBtn.textContent = 'Place Order (Upload screenshot required)';
                }
            }
        }
    </script>

    <?php include "includes/footer.php"; ?>
</body>

</html>
