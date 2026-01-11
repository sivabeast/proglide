<?php 
session_start();
require "includes/db.php";

/* =========================
   AUTHENTICATION
========================= */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$page_title = "My Orders - PROTECTORS";

/* =========================
   CANCEL ORDER LOGIC
========================= */
if (isset($_POST['cancel_order'])) {
    $order_id = (int) $_POST['cancel_order'];
    
    // Verify order belongs to user and is pending
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        if ($order['status'] === 'Pending') {
            // Update order status to Cancelled
            $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            echo "<script>alert('Order Cancelled Successfully!');</script>";
        }
    }
}

/* =========================
   FETCH ORDERS FROM DATABASE
========================= */
$stmt = $conn->prepare("
    SELECT o.*,
           (SELECT p.image 
            FROM order_items oi 
            JOIN products p ON p.id = oi.product_id 
            WHERE oi.order_id = o.id 
            LIMIT 1) AS first_image,
           (SELECT LOWER(mc.name) 
            FROM order_items oi 
            JOIN products p ON p.id = oi.product_id
            JOIN main_categories mc ON mc.id = p.main_category_id
            WHERE oi.order_id = o.id 
            LIMIT 1) AS first_category,
           (SELECT CASE 
                WHEN LOWER(mc.name) = 'back case' THEN 
                    CONCAT(COALESCE(p.design_name, ''), IF(oi.phone_model_id IS NOT NULL, CONCAT(' - ', pm.model_name), ''))
                ELSE p.model_name 
            END
            FROM order_items oi 
            JOIN products p ON p.id = oi.product_id
            JOIN main_categories mc ON mc.id = p.main_category_id
            LEFT JOIN phone_models pm ON pm.id = oi.phone_model_id
            WHERE oi.order_id = o.id 
            LIMIT 1) AS first_product
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];

while ($row = $orders_result->fetch_assoc()) {
    $isBackCase = strtolower($row['first_category'] ?? '') === 'back case';
    $folder = $isBackCase ? 'backcases' : 'protectors';
    $imagePath = $row['first_image'] 
        ? "../uploads/products/{$folder}/" . htmlspecialchars($row['first_image'])
        : 'image/sony.jpg';
    
    $orders[$row['id']] = [
        'status' => $row['status'],
        'date' => date('d M Y', strtotime($row['created_at'])),
        'first_image' => $imagePath,
        'first_product' => $row['first_product'] ?: 'Product',
        'total_amount' => $row['total_amount']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo $page_title; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>

body {
    background: #f5f5f5;
    font-family: 'Segoe UI', sans-serif;
}

/* MAIN CONTAINER */
.container-custom {
    max-width: 1150px;
    /* margin-top:5% ; */
    padding-top: 80px;
}

/* ORDER CARD */
.order-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px;
    border: 1px solid #e3e3e3;
    transition: .3s;
}
.order-card:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* FIRST PRODUCT IMAGE */
.order-img {
    width: 115px;
    height: 115px;
    border-radius: 10px;
    object-fit: cover;
    margin-right: 15px;
}

/* STATUS BADGES */
.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.status-delivered { background:#d4ffd9; color:#0f8c24; }
.status-pending { background:#fff6d1; color:#b78b00; }
.status-processing { background:#d1e7ff; color:#0066cc; }
.status-shipped { background:#e7d1ff; color:#6600cc; }
.status-outfordelivery { background:#ffe7d1; color:#cc6600; }
.status-cancelled { background:#ffd4d4; color:#b30000; }

/* VIEW BUTTON */
.view-btn {
    background:#000;
    color:#fff;
    padding: 9px 22px;
    border-radius: 8px;
    text-decoration:none;
    font-weight: 600;
}
.view-btn:hover {
    background:#333;
}

/* -------------------------------------------
   VIEW ORDER PAGE
--------------------------------------------- */

/* Stepper */
.stepper {
    position: relative;
    display: flex;
    justify-content: space-between;
    margin: 45px 0 40px;
}
.stepper::before {
    content: "";
    position: absolute;
    top: 50%;
    width: 100%;
    height: 6px;
    background: #e6e6e6;
    border-radius: 10px;
    transform: translateY(-50%);
}
.progress-line {
    position: absolute;
    top: 50%;
    left: 0;
    height: 6px;
    background: #28a745;
    border-radius: 10px;
    width: 0%;
    transform: translateY(-50%);
    transition: width 1.2s ease-out;
}
.circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #cfcfcf;
    margin: auto;
}
.completed .circle,
.active .circle {
    background: #28a745;
}

/* ORDER ITEM */
.order-item-card {
    display:flex;
    gap:15px;
    padding: 15px;
    border:1px solid #ddd;
    border-radius:12px;
    background:#fafafa;
    transition:.2s;
}
.order-item-card:hover {
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}

.order-item-img {
    width:110px;
    height:110px;
    border-radius:10px;
    object-fit:cover;
}

/* Cancel Button */
.cancel-btn {
    background:#ff4d4d;
    padding:12px 20px;
    border:none;
    color:#fff;
    border-radius:8px;
    font-weight:600;
}
.cancel-btn:hover {
    background:#b30000;
}

/* MOBILE */
@media(max-width: 768px){
    body { padding-top:30px; }

    .order-card { text-align:center; }
    .order-img { margin:0 auto 15px; }

    .order-item-card {
        flex-direction:column;
        text-align:center;
    }
    .order-item-img {
        width:150px;
        height:150px;
        margin:0 auto 10px;
    }
}

</style>
</head>

<body>

<!-- HEADER -->
<?php include 'includes/header.php'; ?>


<div class="container container-custom">

<?php
/* =========================
   VIEW ORDER PAGE
========================= */
if (isset($_GET['view'])) {
    $order_id = (int) $_GET['view'];
    
    // Fetch order details
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        header("Location: orders.php");
        exit;
    }
    
    $order = $order_result->fetch_assoc();
    
    // Fetch order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.model_name, p.design_name, p.image,
               mc.name AS main_category, pm.model_name AS phone_model
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        JOIN main_categories mc ON mc.id = p.main_category_id
        LEFT JOIN phone_models pm ON pm.id = oi.phone_model_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Status mapping for stepper
    $status_list = ["Ordered","Packed","Shipped","Out for Delivery","Delivered"];
    $status_map = [
        "Pending" => 0, 
        "Processing" => 1,
        "Shipped" => 2,
        "Out for Delivery" => 3,
        "Delivered" => 4,
        "Cancelled" => -1
    ];
    $current_step = isset($status_map[$order["status"]]) ? $status_map[$order["status"]] : -1;
?>

<a href="orders.php" class="btn btn-outline-dark mb-4">
    <i class="fa fa-arrow-left"></i> Back
</a>

<div class="bg-white p-4 rounded shadow-sm">

    <h3 class="fw-bold mb-2">Order Details: #<?php echo $order['id']; ?></h3>
    <p class="text-muted mb-4">Date: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
    <p class="mb-2"><strong>Payment Method:</strong> <?php echo strtoupper($order['payment_method'] ?? 'N/A'); ?></p>
    <p class="mb-2"><strong>Payment Status:</strong> 
        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
            <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
        </span>
    </p>
    <p class="mb-4"><strong>Total Amount:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>

    <!-- TRACKING BAR -->
    <?php if ($current_step >= 0): ?>
    <div class="stepper">
        <div class="progress-line" id="progress-bar"></div>

        <?php foreach ($status_list as $i => $st): ?>
            <div class="text-center <?php echo ($i < $current_step ? 'completed' : ($i == $current_step ? 'active' : '')); ?>">
                <div class="circle"></div>
                <small><?php echo $st; ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        let current = <?php echo $current_step; ?>;
        let percent = (current / 4) * 100;
        document.getElementById("progress-bar").style.width = percent + "%";
    });
    </script>
    <?php else: ?>
        <p class="text-danger fw-bold mb-4">This order has been cancelled.</p>
    <?php endif; ?>

    <!-- ITEMS LIST -->
    <h4 class="fw-bold mt-4 mb-3">Items</h4>

    <?php foreach ($order_items as $item): 
        $isBackCase = strtolower($item['main_category'] ?? '') === 'back case';
        $folder = $isBackCase ? 'backcases' : 'protectors';
        $imagePath = $item['image'] 
            ? "../uploads/products/{$folder}/" . htmlspecialchars($item['image'])
            : 'image/sony.jpg';
    ?>
    <div class="order-item-card mb-3">
        <img src="<?php echo $imagePath; ?>" class="order-item-img" alt="Product">
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">
                <?php 
                echo htmlspecialchars(
                    strtolower($item['main_category']) === 'back case'
                    ? ($item['design_name'] . (isset($item['phone_model']) ? ' - ' . $item['phone_model'] : ''))
                    : $item['model_name']
                ); 
                ?>
            </h5>
            <p class="mb-1">Qty: <?php echo $item['quantity']; ?></p>
            <p class="fw-bold mb-0">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- CANCEL ORDER -->
    <?php if ($order["status"] == "Pending"): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Are you sure you want to cancel this order?');">
            <button type="submit" name="cancel_order" value="<?php echo $order['id']; ?>" class="cancel-btn">
                Cancel Order
            </button>
        </form>
    <?php elseif ($order["status"] == "Cancelled"): ?>
        <p class="text-danger fw-bold mt-3">This order has been cancelled.</p>
    <?php endif; ?>

</div>

<?php exit; } ?>

<!-- =========================
   DEFAULT ORDER LIST PAGE
========================= -->

<h3 class="fw-bold mb-4">My Orders</h3>

<?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <p class="text-muted">You have no orders yet.</p>
        <a href="index.php" class="btn btn-dark mt-3">Start Shopping</a>
    </div>
<?php else: ?>
    <?php foreach ($orders as $id => $o): ?>
    <div class="order-card mb-3 shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="<?php echo $o['first_image']; ?>" class="order-img shadow-sm" alt="Product">
            </div>

            <div class="col-md-7">
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($o['first_product']); ?></h5>
                <p class="mb-1"><b>Order:</b> #<?php echo $id; ?></p>
                <p class="mb-1"><b>Date:</b> <?php echo $o['date']; ?></p>
                <p class="mb-1"><b>Total:</b> ₹<?php echo number_format($o['total_amount'], 2); ?></p>

                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $o['status'])); ?>">
                    <?php echo htmlspecialchars($o['status']); ?>
                </span>
            </div>

            <div class="col-md-3 text-end">
                <a href="orders.php?view=<?php echo $id; ?>" class="view-btn">VIEW</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>
<!-- FOOTER LOAD -->
<?php include 'includes/footer.php'; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

</body>
</html>
