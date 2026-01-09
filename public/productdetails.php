<?php
session_start();
require "includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product");
}
$product_id = (int) $_GET['id'];

/* ================= FETCH PRODUCT ================= */
$stmt = $conn->prepare("
SELECT p.*, ct.type_name,
       mc.id AS main_cat_id, mc.name AS main_category,
       dc.name AS design_category
FROM products p
JOIN category_types ct ON ct.id = p.category_type_id
JOIN main_categories mc ON mc.id = p.main_category_id
LEFT JOIN design_categories dc ON dc.id = p.design_category_id
WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product)
    die("Product not found");

$isBackCase = strtolower($product['main_category']) === 'back case';
$imgFolder = $isBackCase ? 'backcases' : 'protectors';

/* ================= VARIANTS (PROTECTOR) ================= */
$variants = [];
if (!$isBackCase && $product['model_name']) {
    $v = $conn->prepare("
        SELECT p.id, p.image, p.price, ct.type_name
        FROM products p
        JOIN category_types ct ON ct.id = p.category_type_id
        WHERE p.model_name=? AND p.main_category_id=?
        ORDER BY p.price
    ");
    $v->bind_param("si", $product['model_name'], $product['main_cat_id']);
    $v->execute();
    $variants = $v->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ================= BRANDS (BACKCASE) ================= */
$brands = [];
if ($isBackCase) {
    $brands = $conn->query(
        "SELECT id,name FROM brands WHERE status='active' ORDER BY name"
    )->fetch_all(MYSQLI_ASSOC);
}

/* ================= WISHLIST ================= */
$in_wishlist = false;
if ($user_id) {
    $in_wishlist = $conn->query(
        "SELECT 1 FROM wishlist WHERE user_id=$user_id AND product_id=$product_id"
    )->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['model_name'] ?? $product['design_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        .product-desc {
            font-size: 15px;
            line-height: 1.6
        }

        .variant-box {
            cursor: pointer;
            text-align: center
        }

        .variant-box img {
            border: 2px solid transparent;
            border-radius: 8px
        }

        .variant-box.active img {
            border-color: #000
        }
    </style>
</head>

<body>
    <?php include "includes/header.php"; ?>

    <div class="container py-4">
        <div class="row g-4">

            <div class="col-md-5">
                <img id="mainImg" src="../uploads/products/<?= $imgFolder ?>/<?= htmlspecialchars($product['image']) ?>"
                    class="img-fluid rounded shadow">
            </div>

            <div class="col-md-7">

                <?php if ($isBackCase): ?>

                    <h4><?= htmlspecialchars($product['design_name']) ?></h4>
                    <p class="product-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <h5 class="text-danger">₹<?= number_format($product['price'], 2) ?></h5>

                    <select id="brandSelect" class="form-select my-2">
                        <option value="">Select Brand</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="modelSelect" class="form-select my-2">
                        <option value="">Select Model</option>
                    </select>

                <?php else: ?>

                    <h4><?= htmlspecialchars($product['model_name']) ?> (<?= strtoupper($product['type_name']) ?>)</h4>
                    <p class="product-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                    <?php if ($variants): ?>
                        <p class="fw-bold">Choose Type</p>
                        <div class="d-flex gap-3">
                            <?php foreach ($variants as $v): ?>
                                <div class="variant-box"
                                    onclick="selectVariant(<?= $v['id'] ?>,'<?= $v['image'] ?>',<?= $v['price'] ?>,this)">
                                    <img src="../uploads/products/protectors/<?= htmlspecialchars($v['image']) ?>" width="60">
                                    <div class="small"><?= strtoupper($v['type_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h5 id="price" class="text-danger mt-3">₹<?= number_format($product['price'], 2) ?></h5>

                <?php endif; ?>

                <div class="d-flex align-items-center gap-2 mt-3">
                    <button class="btn btn-outline-dark" onclick="changeQty(-1)">−</button>
                    <span id="qty">1</span>
                    <button class="btn btn-outline-dark" onclick="changeQty(1)">+</button>
                </div>

                <?php if (!$user_id): ?>
                    <a href="login.php" class="btn btn-dark mt-3">Login to Add</a>
                <?php else: ?>
                    <button class="btn btn-dark mt-3" onclick="addToCart()">ADD TO CART</button>
                <?php endif; ?>

                <a href="ajax/<?= $in_wishlist ? 'remove' : 'add_to' ?>_wishlist.php?id=<?= $product_id ?>"
                    class="btn btn-outline-danger ms-2 mt-3">
                    <i class="fa-heart <?= $in_wishlist ? 'fa-solid' : 'fa-regular' ?>"></i>
                </a>

            </div>
        </div>
    </div>

    <?php include "includes/footer.php";include "includes/mobile_bottom_nav.php" ?>

    <script>
        let qty = 1, selectedProduct = <?= $product_id ?>;

        function changeQty(v) {
            qty = Math.max(1, qty + v);
            document.getElementById("qty").innerText = qty;
        }

        function selectVariant(id, img, price, el) {
            selectedProduct = id;
            mainImg.src = "../uploads/products/protectors/" + img;
            priceEl = document.getElementById("price");
            priceEl.innerText = "₹" + price;
            document.querySelectorAll(".variant-box").forEach(x => x.classList.remove("active"));
            el.classList.add("active");
        }

        brandSelect?.addEventListener("change", () => {
            fetch("ajax/get_models.php?brand_id=" + brandSelect.value)
                .then(r => r.text())
                .then(h => modelSelect.innerHTML = h);
        });

        function addToCart() {
            <?php if ($isBackCase): ?>
                if (!modelSelect.value) { alert("Select model"); return; }
                location.href = "ajax/add_to_cart.php?id=" + selectedProduct + "&model_id=" + modelSelect.value + "&qty=" + qty;
            <?php else: ?>
                location.href = "ajax/add_to_cart.php?id=" + selectedProduct + "&qty=" + qty;
            <?php endif; ?>
        }
    </script>

</body>

</html>