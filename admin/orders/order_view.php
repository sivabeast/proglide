<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

$order_id = (int) ($_GET['id'] ?? 0);

/* =====================
   FETCH ORDER
===================== */
$orderSql = "
SELECT o.*, u.name, u.email
FROM orders o
JOIN users u ON u.id = o.user_id
WHERE o.id = ?
";
$stmt = $conn->prepare($orderSql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

/* =====================
   UPDATE STATUS
===================== */
if (isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];

    $up = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $up->bind_param("si", $newStatus, $order_id);
    $up->execute();

    header("Location: order_view.php?id=" . $order_id);
    exit;
}

/* =====================
   FETCH ORDER ITEMS
===================== */
$itemSql = "
SELECT 
    oi.quantity,
    oi.price,

    p.model_name,
    p.design_name,

    mc.name AS main_category,
    ct.type_name,

    pm.model_name AS phone_model,
    b.name AS brand_name,
    dc.name AS design_category

FROM order_items oi
JOIN products p ON p.id = oi.product_id
JOIN main_categories mc ON mc.id = p.main_category_id
JOIN category_types ct ON ct.id = p.category_type_id
JOIN phone_models pm ON pm.id = oi.phone_model_id
JOIN brands b ON b.id = pm.brand_id
LEFT JOIN design_categories dc ON dc.id = p.design_category_id

WHERE oi.order_id = ?
";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $order_id);
$itemStmt->execute();
$result = $itemStmt->get_result();

/* =====================
   SPLIT ITEMS
===================== */
$protectors = [];
$backcases = [];

while ($row = $result->fetch_assoc()) {
    if ($row['main_category'] === 'Protector') {
        $protectors[] = $row;
    } else {
        $backcases[] = $row;
    }
}

/* =====================
   STATUS STEP
===================== */
$statusSteps = [
    "Pending" => 1,
    "Processing" => 2,
    "Shipped" => 3,
    "Delivered" => 4
];
$currentStep = $statusSteps[$order['status']] ?? 1;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Order #<?= $order_id ?> | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: radial-gradient(circle at top, #020617, #000);
            color: #f9fafb;
            font-family: Poppins, sans-serif;
        }

        .container {
            padding-left: 280px
        }

        @media(max-width:992px) {
            .container {
                padding-left: 16px
            }
        }

        .card {
            background: #111827;
            border: 1px solid #374151;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .table th {
            background: #020617;
            color: #9ca3af;
            font-size: 12px;
            text-transform: uppercase;
        }

        .table td {
            border-color: #374151;
            white-space: nowrap;
            background-color: transparent;
            color: var(--text);
        }

        .order-table-scroll {
            overflow-x: auto;
        }

        .order-table-scroll table {
            min-width: 900px;
        }
    </style>
</head>

<body>

    <?php include "../includes/sidebar.php"; ?>
    <?php include "../includes/header.php"; ?>

    <div class="container py-5">

        <a href="orders_list.php" class="btn btn-outline-light btn-sm mb-3">
            ← Back to Orders
        </a>

        <!-- ORDER INFO -->
        <div class="card">
            <h4 style="color:#38bdf8">Order #<?= $order['id'] ?></h4>
            <p class="text-muted">
                <strong>User:</strong> <?= htmlspecialchars($order['name']) ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?><br>
                <strong>Total:</strong> ₹ <?= $order['total_amount'] ?><br>
                <strong>Status:</strong> <?= $order['status'] ?>
            </p>

            <div class="progress">
                <div class="progress-bar bg-success" style="width:<?= ($currentStep / 4) * 100 ?>%"></div>
            </div>
        </div>

        <!-- UPDATE STATUS -->
        <div class="card">
            <form method="post" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-select bg-transparent text-white">
                        <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button name="update_status" class="btn btn-primary w-100">
                        Update Status
                    </button>
                </div>
            </form>
        </div>

        <!-- ================= PROTECTORS ================= -->
        <?php if ($protectors): ?>
            <div class="card">
                <h5>🛡️ Protectors</h5>
                <div class="order-table-scroll">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Model Name</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($protectors as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['model_name']) ?></td>
                                    <td><?= strtoupper($p['type_name']) ?></td>
                                    <td><?= $p['quantity'] ?></td>
                                    <td>₹<?= $p['price'] ?></td>
                                    <td>₹<?= $p['price'] * $p['quantity'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================= BACK CASES ================= -->
        <?php if ($backcases): ?>
            <div class="card">
                <h5>📱 Back Cases</h5>
                <div class="order-table-scroll">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Design</th>
                                <th>Design Category</th>
                                <th>Brand</th>
                                <th>Mobile Model</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backcases as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['design_name']) ?></td>
                                    <td><?= $b['design_category'] ?? '-' ?></td>
                                    <td><?= $b['brand_name'] ?></td>
                                    <td><?= $b['phone_model'] ?></td>
                                    <td><?= strtoupper($b['type_name']) ?></td>
                                    <td><?= $b['quantity'] ?></td>
                                    <td>₹<?= $b['price'] ?></td>
                                    <td>₹<?= $b['price'] * $b['quantity'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>