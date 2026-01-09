<?php 
$page_title = "My Orders - PROTECTORS";

/* --------------------------------------------------
   DUMMY ORDER LIST (Later MySQL Integration)
-------------------------------------------------- */

$orders = [

    "ORD1021" => [
        "status" => "Delivered",
        "date" => "12 Dec 2024",
        "items" => [
            ["product"=>"VIVO T2 (MATTE) Tempered Glass", "qty"=>1, "price"=>300, "image"=>"image/sony.jpg"],
            ["product"=>"Premium Back Case Black", "qty"=>1, "price"=>150, "image"=>"image/sony.jpg"]
        ]
    ],

    "ORD987" => [
        "status" => "Pending",
        "date" => "10 Dec 2024",
        "items" => [
            ["product"=>"VIVO Y20 Privacy Glass", "qty"=>1, "price"=>350, "image"=>"image/sony.jpg"]
        ]
    ],

];
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
    padding-top: 160px;
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
<!-- CATEGORIES -->         
<?php include 'includes/categories.php'; ?>

<div class="container container-custom">

<?php
/* --------------------------------------------------
   CANCEL ORDER LOGIC
-------------------------------------------------- */
if (isset($_POST['cancel_order'])) {
    $id = $_POST['cancel_order'];
    $orders[$id]['status'] = "Cancelled";
    echo "<script>alert('Order Cancelled Successfully!');</script>";
}

/* --------------------------------------------------
   VIEW ORDER PAGE
-------------------------------------------------- */
if (isset($_GET['view'])) {

    $order_id = $_GET['view'];
    $order = $orders[$order_id];

    $status_list = ["Ordered","Packed","Shipped","Out for Delivery","Delivered"];
    $status_map = [
        "Pending" => 0, 
        "Packed" => 1,
        "Shipped" => 2,
        "Out for Delivery" => 3,
        "Delivered" => 4,
        "Cancelled" => -1
    ];
    $current_step = $status_map[$order["status"]];
?>

<a href="orders.php" class="btn btn-outline-dark mb-4">
    <i class="fa fa-arrow-left"></i> Back
</a>

<div class="bg-white p-4 rounded shadow-sm">

    <h3 class="fw-bold mb-2">Order Details: <?php echo $order_id; ?></h3>
    <p class="text-muted mb-4">Date: <?php echo $order["date"]; ?></p>

    <!-- TRACKING BAR -->
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

    <!-- ITEMS LIST -->
    <h4 class="fw-bold mt-4 mb-3">Items</h4>

    <?php foreach ($order["items"] as $it): ?>
    <div class="order-item-card mb-3">
        <img src="<?php echo $it['image']; ?>" class="order-item-img">
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1"><?php echo $it['product']; ?></h5>
            <p class="mb-1">Qty: <?php echo $it['qty']; ?></p>
            <p class="fw-bold mb-0">Rs <?php echo $it['price']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- CANCEL ORDER -->
    <?php if ($order["status"] == "Pending"): ?>
        <form method="post" class="mt-3">
            <button type="submit" name="cancel_order" value="<?php echo $order_id; ?>" class="cancel-btn">
                Cancel Order
            </button>
        </form>
    <?php elseif ($order["status"] == "Cancelled"): ?>
        <p class="text-danger fw-bold mt-3">This order has been cancelled.</p>
    <?php endif; ?>

</div>

<?php exit; } ?>

<!-- --------------------------------------------------
   DEFAULT ORDER LIST PAGE
-------------------------------------------------- -->

<h3 class="fw-bold mb-4">My Orders</h3>

<?php foreach ($orders as $id => $o): ?>

<div class="order-card mb-3 shadow-sm">

    <div class="row align-items-center">

        <div class="col-md-2 text-center">
            <img src="<?php echo $o['items'][0]['image']; ?>" class="order-img shadow-sm">
        </div>

        <div class="col-md-7">
            <h5 class="fw-bold mb-1"><?php echo $o['items'][0]['product']; ?></h5>
            <p class="mb-1"><b>Order:</b> <?php echo $id; ?></p>
            <p class="mb-1"><b>Date:</b> <?php echo $o['date']; ?></p>

            <span class="status-badge status-<?php echo strtolower($o['status']); ?>">
                <?php echo $o['status']; ?>
            </span>
        </div>

        <div class="col-md-3 text-end">
            <a href="orders.php?view=<?php echo $id; ?>" class="view-btn">VIEW</a>
        </div>

    </div>
</div>

<?php endforeach; ?>

</div>
<!-- FOOTER LOAD -->
<?php include 'includes/footer.php'; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

</body>
</html>
