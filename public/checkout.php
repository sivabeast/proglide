<?php
session_start();
require "includes/db.php";

/* =========================
   AUTH
========================= */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* =========================
   USER DETAILS
========================= */
$user = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

/* =========================
   CART ITEMS
========================= */
$stmt = $conn->prepare("
SELECT 
    c.*,
    p.price,
    p.model_name,
    p.design_name,
    mc.name AS main_category,
    pm.model_name AS phone_model
FROM cart c
JOIN products p ON p.id = c.product_id
JOIN main_categories mc ON mc.id = p.main_category_id
LEFT JOIN phone_models pm ON pm.id = c.phone_model_id
WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

/* =========================
   TOTAL
========================= */
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_charge = $subtotal > 500 ? 0 : 60;
$total = $subtotal + $delivery_charge;




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

    /* BASIC VALIDATION */
    if ($full_name === '' || $phone === '' || $address === '' || $pincode === '') {
        $error = "All fields are required";
    }

    /* UPI PROOF VALIDATION */
    $payment_proof = null;
    if (!isset($error) && $payment_method === 'upi') {

        if (empty($_FILES['payment_proof']['name'])) {
            $error = "UPI payment screenshot is required";
        } else {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "Invalid image format (jpg, png, webp only)";
            } else {
                $payment_proof = time() . '_' . $user_id . '.' . $ext;
                move_uploaded_file(
                    $_FILES['payment_proof']['tmp_name'],
                    "uploads/payments/" . $payment_proof
                );
            }
        }
    }

    /* IF ERROR → STOP */
    if (isset($error)) {
        // error will be shown in HTML
    } else {

        $payment_status = 'pending';

        $conn->begin_transaction();
        try {

            /* ORDER */
            $stmt = $conn->prepare("
                INSERT INTO orders
                (user_id,full_name,phone,address,pincode,total_amount,
                 payment_method,payment_status,payment_proof,notes)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "issssdssss",
                $user_id,
                $full_name,
                $phone,
                $address,
                $pincode,
                $total,          // d (double)
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
                (order_id,product_id,phone_model_id,quantity,price)
                VALUES (?,?,?,?,?)
            ");
            foreach ($cart_items as $item) {
                $stmt->bind_param(
                    "iiiid",
                    $order_id,
                    $item['product_id'],
                    $item['phone_model_id'],
                    $item['quantity'],
                    $item['price']
                );
                $stmt->execute();
            }

            /* CLEAR CART */
            $conn->query("DELETE FROM cart WHERE user_id=$user_id");

            $conn->commit();

            $_SESSION['order_success'] = $order_id;
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
    <style>
        body {
            background: #f6f7fb;
            padding-top: 140px;
        }

        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer
        }

        .payment-method.selected {
            border-color: #0d6efd;
            background: #eef4ff
        }

        .upi-qr {
            max-width: 200px
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <div class="container">
        <h4 class="fw-bold mb-3">Checkout</h4>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <div class="row g-3">

                <!-- LEFT -->
                <div class="col-lg-7">

                    <div class="card p-3 mb-3">
                        <h6 class="fw-bold mb-2">Shipping Details</h6>

                        <input class="form-control mb-2" name="full_name" placeholder="Full Name" required
                            value="<?= htmlspecialchars($user['name'] ?? '') ?>">

                        <input class="form-control mb-2" name="phone" placeholder="Phone Number" required>

                        <textarea class="form-control mb-2" name="address" placeholder="Full Address"
                            required></textarea>

                        <input class="form-control" name="pincode" placeholder="Pincode" required>
                    </div>

                    <div class="card p-3 mb-3">
                        <h6 class="fw-bold mb-3">Payment Method</h6>

                        <div class="payment-method selected mb-2" id="cod-wrap" onclick="selectPayment('cod')">


                            <input type="radio" name="payment_method" id="cod" value="cod" checked>
                            <label class="fw-bold ms-2">Cash on Delivery</label>
                        </div>

                        <div class="payment-method" id="upi-wrap">
    <input type="radio"
           name="payment_method"
           id="upi"
           value="upi"
           onclick="selectPayment('upi')">
    <label class="fw-bold ms-2" for="upi">UPI Payment</label>


                            <div id="upi-box" class="d-none text-center mt-3">
                                <img id="upiQR" class="upi-qr mb-2">
                                <p class="small text-muted">Pay ₹<?= $total ?></p>

                                <input type="file" name="payment_proof" class="form-control mt-2" accept="image/*">
                                <small class="text-muted">Upload payment screenshot</small>
                            </div>

                        </div>
                    </div>

                    <textarea class="form-control" name="notes" placeholder="Order notes (optional)"></textarea>

                </div>

                <!-- RIGHT -->
                <div class="col-lg-5">

                    <div class="card p-3">
                        <h6 class="fw-bold mb-3">Order Summary</h6>

                        <?php foreach ($cart_items as $c): ?>
                            <p class="mb-1">
                                <?= htmlspecialchars(
                                    strtolower($c['main_category']) === 'back case'
                                    ? $c['design_name'] . ' - ' . $c['phone_model']
                                    : $c['model_name']
                                ) ?>
                                × <?= $c['quantity'] ?>
                                <span class="float-end">₹<?= $c['price'] * $c['quantity'] ?></span>
                            </p>
                        <?php endforeach; ?>

                        <hr>
                        <p>Subtotal <span class="float-end">₹<?= $subtotal ?></span></p>
                        <p>Delivery <span class="float-end"><?= $delivery_charge ? "₹$delivery_charge" : "Free" ?></span>
                        </p>
                        <hr>
                        <h5>Total <span class="float-end">₹<?= $total ?></span></h5>

                        <button class="btn btn-dark w-100 mt-3">Place Order</button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script>
        function selectPayment(method){

    document.querySelectorAll('.payment-method')
        .forEach(p => p.classList.remove('selected'));

    if(method === 'upi'){
        document.getElementById('upi-wrap').classList.add('selected');
        document.getElementById('upi-box').classList.remove('d-none');

        document.getElementById('upiQR').src =
            "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" +
            encodeURIComponent(
                "upi://pay?pa=protectors@upi&pn=PROTECTORS&am=<?= $total ?>&cu=INR"
            );
    } else {
        document.getElementById('cod-wrap').classList.add('selected');
        document.getElementById('upi-box').classList.add('d-none');
    }
}

    </script>

    <?php include "includes/footer.php"; ?>
</body>

</html>