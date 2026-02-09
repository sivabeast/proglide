<?php
require "includes/db.php";
require "includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    // User not logged in
    $user_id = null;
} else {
    $user_id = (int) $_SESSION['user_id'];
}

/* =========================
   FETCH CART ITEMS (DB SAFE)
========================= */
$sql = "
SELECT
    c.id AS cart_id,
    c.quantity,
    p.id AS product_id,
    p.price,
    p.image1 AS image,
    p.model_name,
    p.design_name,
    cat.name AS main_category,
    mt.name AS type_name,
    pm.model_name AS phone_model
FROM cart c
JOIN products p ON p.id = c.product_id
JOIN categories cat ON cat.id = p.category_id
LEFT JOIN material_types mt ON mt.id = p.material_type_id
LEFT JOIN phone_models pm ON pm.id = c.phone_model_id
WHERE c.user_id = ?
ORDER BY c.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$subtotal = 0;
$total_items = 0;

while ($row = $res->fetch_assoc()) {
    $row['total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['total'];
    $total_items += $row['quantity'];
    $items[] = $row;
}

/* DELIVERY RULE */
$delivery = ($subtotal > 0 && $subtotal < 299) ? 0 : 0;
$grand_total = $subtotal + $delivery;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* =========================
   GLOBAL
========================= */
        * {
            box-sizing: border-box
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f5f7fa;
            padding-top: 200px;

        }

        .container {
            margin-bottom: 10%;
            margin-top: 5%;
        }

        /* =========================
   TABLE SCROLL WRAPPER
========================= */
        .cart-table-wrapper {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            overflow: hidden;
        }

        /* Vertical scroll */
        .cart-table-scroll {
            max-height: 420px;
            /* 🔥 vertical scroll height */
            overflow-y: auto;
            overflow-x: auto;
            /* 🔥 horizontal scroll */
        }

        /* =========================
   TABLE
========================= */
        .cart-table {
            min-width: 800px;
            /* 🔥 force horizontal scroll */
            margin: 0;
        }

        .cart-table thead th {
            position: sticky;
            top: 0;
            background: #1f2428;
            color: #fff;
            z-index: 2;
            text-align: center;
            white-space: nowrap;
        }

        .cart-table td {
            vertical-align: middle;
            text-align: center;
        }

        /* =========================
   PRODUCT CELL
========================= */
        .cart-product {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .cart-title {
            font-weight: 600;
            font-size: 15px;
        }

        .cart-meta {
            font-size: 13px;
            color: #6c757d;
        }

        /* =========================
   QTY
========================= */
        .qty-box {
            display: inline-flex;
            border: 1px solid #ccc;
            border-radius: 6px;
            overflow: hidden;
        }

        .qty-btn {
            border: none;
            background: #f1f1f1;
            padding: 5px 10px;
            font-weight: bold;
        }

        .qty-input {
            width: 45px;
            border: none;
            text-align: center;
            font-weight: 600;
        }

        /* =========================
   REMOVE
========================= */
        .remove-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
        }

        /* =========================
   SUMMARY
========================= */
        .summary-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            position: sticky;
            top: 150px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .summary-total {
            font-size: 18px;
            font-weight: 700;
        }

        /* =========================
   RESPONSIVE
========================= */
        @media(max-width:992px) {
            body {
                padding-top: 160px
            }

            .summary-box {
                position: static;
                margin-top: 20px
            }
        }


        /* =========================
   FORCE HORIZONTAL SCROLL (MOBILE)
========================= */

        /* wrapper scroll */
        @media (max-width: 768px) {

            /* table wrapper */
            .col-lg-8 {
                overflow-x: auto;
            }

            table {
                min-width: 900px;
                /* 🔥 important */
            }

            /* smooth scrolling */
            .col-lg-8::-webkit-scrollbar {
                height: 6px;
            }

            .col-lg-8::-webkit-scrollbar-thumb {
                background: #bbb;
                border-radius: 10px;
            }

            .col-lg-8::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
        }

        /* =========================
   KEEP HEADER STICKY
========================= */
        .cart-table thead th,
        table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        /* =========================
   PREVENT TEXT BREAK
========================= */
        table th,
        table td {
            white-space: nowrap;
        }

        /* =========================
   MOBILE PRODUCT ALIGNMENT
========================= */
        @media (max-width: 576px) {

            body {
                padding-top: 140px;
            }

            .cart-img {
                width: 50px;
                height: 50px;
            }

            .qty-box {
                transform: scale(0.9);
            }

            .remove-btn {
                padding: 4px 8px;
            }
        }
    </style>
</head>

<body>

    <?php include "includes/header.php"; ?>


    <div class="container">
        <div class="back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i>
        </div>
        <h3 class="fw-bold mb-4">
            My Cart<?php if (isset($_SESSION['user_id'])): ?> <span
                    class="badge bg-primary"><?= $total_items ?></span><?php endif; ?>
        </h3>

        <div class="row g-4">
            <?php if (isset($_SESSION['user_id'])): ?>

                <!-- ================= CART ================= -->
                <div class="col-lg-8">
                    <?php if ($items): ?>
                        <table class="table bg-white shadow-sm align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach ($items as $it):
                                    $isBackCase = strtolower($it['main_category']) === 'back case';
                                    $folder = $isBackCase ? 'backcases' : 'protectors';

                                    $title = $isBackCase
                                        ? $it['design_name']
                                        : $it['model_name'] . " (" . strtoupper($it['type_name']) . ")";
                                    ?>

                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($it['image']) ?>"
                                                    class="cart-img">
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($title) ?></div>
                                                    <?php if ($isBackCase && $it['phone_model']): ?>
                                                        <small class="text-muted">Model:
                                                            <?= htmlspecialchars($it['phone_model']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <td>₹<?= number_format($it['price'], 2) ?></td>

                                        <td>
                                            <div class="qty-box">
                                                <button class="qty-btn" onclick="updateQty(<?= $it['cart_id'] ?>,-1)">−</button>
                                                <input class="qty-input" value="<?= $it['quantity'] ?>" readonly>
                                                <button class="qty-btn" onclick="updateQty(<?= $it['cart_id'] ?>,1)">+</button>
                                            </div>
                                        </td>

                                        <td>₹<?= number_format($it['total'], 2) ?></td>

                                        <td>
                                            <form method="post" onsubmit="return confirm('Remove item from cart?')">
                                                <input type="hidden" name="remove_cart_id" value="<?= $it['cart_id'] ?>">
                                                <button type="submit" class="remove-btn">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>


                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-5">
                            <i class="bi bi-cart-x" style="font-size: 3rem; color: #0d6efd;"></i>
                            <p class="mt-3 mb-3">Your cart is empty.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-shop"></i> Continue Shopping
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- ================= SUMMARY ================= -->
                <div class="col-lg-4">
                    <div class="summary-box shadow-sm">

                        <h5 class="fw-bold">Summary</h5>

                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span>₹<?= number_format($subtotal, 2) ?></span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Delivery</span>
                            <span><?= $delivery ? "₹free" : "FREE" ?></span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span>₹<?= number_format($grand_total, 2) ?></span>
                        </div>

                        <a href="checkout.php" class="btn btn-primary w-100 mt-3">
                            Proceed to Checkout
                        </a>

                    </div>
                </div>
            <?php else: ?>

                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-cart-x" style="font-size: 3rem; color: #0d6efd;"></i>
                    <p class="mt-3 mb-3">Please login to view your cart</p>

                    <a href="login.php" class="btn btn-primary">
                        please login...
                        <i class="bi bi-box-arrow-in-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>


    <script>
        function updateQty(cartId, delta) {
            fetch("ajax/update_cart_qty.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ cart_id: cartId, delta: delta })
            })
                .then(r => r.json())
                .then(d => {
                    if (d.success) location.reload();
                    else alert(d.message || "Error");
                });
        }



    </script>

</body>

</html>