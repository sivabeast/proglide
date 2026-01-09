<?php
session_start();
require "includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

/* =========================
   CATEGORY FILTER (PHP)
========================= */
$category = $_GET['cat'] ?? 'all';

/* =========================
   FETCH PRODUCTS
========================= */
$sql = "
SELECT 
    p.id,
    p.model_name,
    p.price,
    p.image,
    ct.type_name,
    mc.name AS main_category
FROM products p
JOIN category_types ct ON p.category_type_id = ct.id
JOIN main_categories mc ON p.main_category_id = mc.id
";

if ($category !== 'all') {
    $sql .= " WHERE ct.type_name = ?";
}

$stmt = $conn->prepare($sql);

if ($category !== 'all') {
    $stmt->bind_param("s", $category);
}

$stmt->execute();
$res = $stmt->get_result();

$products = [];

while ($row = $res->fetch_assoc()) {

    /* CART CHECK */
    $row['in_cart'] = false;
    if ($user_id) {
        $c = $conn->prepare(
            "SELECT id FROM cart WHERE user_id=? AND product_id=?"
        );
        $c->bind_param("ii", $user_id, $row['id']);
        $c->execute();
        $row['in_cart'] = $c->get_result()->num_rows > 0;
    }

    /* WISHLIST CHECK */
    $row['in_wishlist'] = false;
    if ($user_id) {
        $w = $conn->prepare(
            "SELECT id FROM wishlist WHERE user_id=? AND product_id=?"
        );
        $w->bind_param("ii", $user_id, $row['id']);
        $w->execute();
        $row['in_wishlist'] = $w->get_result()->num_rows > 0;
    }

    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Products | PROTECTORS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>

    </style>
</head>

<body>

    <?php include "includes/header.php"; ?>


    <div class="grid">
        <?php foreach ($products as $p): ?>

            <?php
            $folder = (strtolower($p['main_category']) === 'back case')
                ? 'backcases'
                : 'protectors';
            ?>

            <div class="card">

                <a href="productdetails.php?id=<?= $p['id'] ?>" class="product-link">
                    <img src="../uploads/products/<?= $folder ?>/<?= rawurlencode($p['image']) ?>">
                    <div class="name">
                        <?= htmlspecialchars($p['model_name']) ?>
                        (<?= strtoupper($p['type_name']) ?>)
                    </div>
                </a>

                <div class="body">

                    <div class="price">₹ <?= number_format($p['price'], 2) ?></div>

                    <div class="buttons">

                        <a class="btn-love <?= $p['in_wishlist'] ? 'active' : '' ?>" href="<?= $p['in_wishlist']
                                  ? 'ajax/remove_wishlist.php?id=' . $p['id']
                                  : 'ajax/add_to_wishlist.php?id=' . $p['id'] ?>">
                            <i class="fa <?= $p['in_wishlist'] ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                        </a>

                        <a class="btn-cart <?= $p['in_cart'] ? 'added' : '' ?>" href="<?= $p['in_cart']
                                  ? 'ajax/remove_cart.php?id=' . $p['id']
                                  : 'ajax/add_to_cart.php?id=' . $p['id'] ?>">
                            <?= $p['in_cart'] ? 'ADDED' : 'ADD TO CART' ?>
                        </a>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

    <?php include "includes/footer.php"; ?>
    <?php include "includes/mobile_bottom_nav.php"; ?>

</body>

</html>