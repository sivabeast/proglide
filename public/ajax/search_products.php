<?php
session_start();
require "../includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    exit; // JS will restore original products
}

/* =========================
   SEARCH QUERY
========================= */
$stmt = $conn->prepare("
    SELECT 
        p.*,
        ct.type_name,
        mc.name AS main_category
    FROM products p
    JOIN main_categories mc ON mc.id = p.main_category_id
    LEFT JOIN category_types ct ON ct.id = p.category_type_id
    WHERE (p.model_name LIKE ? OR p.design_name LIKE ?)
    ORDER BY p.created_at DESC
    LIMIT 20
");

$like = $q . "%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();

/* =========================
   OUTPUT FULL PRODUCT CARDS
========================= */
while ($p = $res->fetch_assoc()):

    $isBackCase = strtolower($p['main_category']) === 'back case';
    $folder = $isBackCase ? 'backcases' : 'protectors';

    $title = $isBackCase
        ? ($p['design_name'] ?? 'Design')
        : $p['model_name'];

    /* ---------- WISHLIST CHECK ---------- */
    $inWishlist = false;
    if ($user_id) {
        $w = $conn->prepare(
            "SELECT id FROM wishlist WHERE user_id=? AND product_id=?"
        );
        $w->bind_param("ii", $user_id, $p['id']);
        $w->execute();
        $inWishlist = $w->get_result()->num_rows > 0;
    }

    /* ---------- CART CHECK (PROTECTOR ONLY) ---------- */
    $inCart = false;
    if ($user_id && !$isBackCase) {
        $c = $conn->prepare(
            "SELECT id FROM cart
             WHERE user_id=? AND product_id=? AND phone_model_id IS NULL"
        );
        $c->bind_param("ii", $user_id, $p['id']);
        $c->execute();
        $inCart = $c->get_result()->num_rows > 0;
    }
?>
<div class="card">

    <a href="productdetails.php?id=<?= $p['id'] ?>">
        <img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($p['image']) ?>">
        <div class="card-body">
            <div class="title">
                <?= htmlspecialchars($title) ?>
            </div>

            <?php if (!$isBackCase && !empty($p['type_name'])): ?>
                <div class="ptype">( <?= ucfirst($p['type_name']) ?> )</div>
            <?php endif; ?>

            <div class="price">₹<?= number_format($p['price'], 2) ?></div>
        </div>
    </a>

    <div class="card-actions">

        <!-- ❤️ WISHLIST -->
        <a class="wish-btn <?= $inWishlist ? 'active' : '' ?>"
           href="ajax/<?= $inWishlist ? 'remove' : 'add' ?>_to_wishlist.php?id=<?= $p['id'] ?>">
            <i class="fa-heart <?= $inWishlist ? 'fa-solid' : 'fa-regular' ?>"></i>
        </a>

        <!-- 🛒 CART / SELECT MODEL -->
        <?php if ($isBackCase): ?>

            <a href="productdetails.php?id=<?= $p['id'] ?>" class="btn">
                Select Model
            </a>

        <?php else: ?>

            <?php if (!$user_id): ?>
                <a href="login.php" class="btn">Add to Cart</a>

            <?php elseif ($inCart): ?>
                <span class="btn added">ADDED</span>

            <?php else: ?>
                <a href="ajax/add_to_cart.php?id=<?= $p['id'] ?>" class="btn">
                    Add to Cart
                </a>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>
<?php endwhile; ?>
