<?php
session_start();
require "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* =========================
   FETCH WISHLIST
========================= */
$watchlist = [];

$sql = "
SELECT 
    p.id,
    p.model_name,
    p.design_name,
    p.price,
    p.image1 AS image,
    cat.name AS main_category
FROM wishlist w
JOIN products p ON p.id = w.product_id
JOIN categories cat ON cat.id = p.category_id
WHERE w.user_id = ?
ORDER BY w.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $watchlist[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Watchlist</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{
    background:#f9fafb;
    font-family:system-ui,Arial;
    padding-top:140px;
}

.page-container{
    max-width:1300px;
    margin:auto;
    padding:20px;
}

.watchlist-title{
    font-size:26px;
    font-weight:700;
    margin-bottom:25px;
}

.watch-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
    gap:24px;
}

.watch-card{
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    transition:.25s;
}

.watch-card:hover{
    transform:translateY(-5px);
}

.watch-card img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.card-body{
    padding:14px;
    text-align:center;
}

.product-name{
    font-weight:600;
    font-size:15px;
    margin-bottom:6px;
}

.product-price{
    color:#ef4444;
    font-weight:600;
    margin-bottom:12px;
}

.card-buttons{
    display:flex;
    justify-content:center;
    gap:10px;
}

.btn-love{
    border:1px solid #ef4444;
    background:none;
    color:#ef4444;
    padding:7px 10px;
    border-radius:6px;
    cursor:pointer;
}

.btn-cart{
    background:#000;
    color:#fff;
    border:none;
    padding:7px 14px;
    border-radius:6px;
    cursor:pointer;
}
</style>
</head>

<body>

<?php include "includes/header.php"; ?>

<div class="page-container">

<div class="watchlist-title">My Watchlist</div>

<?php if(empty($watchlist)): ?>
    <p>Your watchlist is empty.</p>
<?php endif; ?>

<div class="watch-grid">

<?php foreach($watchlist as $item):

$isBackCase = strtolower($item['main_category']) === 'back case';
$folder = $isBackCase ? 'backcases' : 'protectors';

$name = $isBackCase
    ? ($item['design_name'] ?: 'Design')
    : ($item['model_name'] ?: 'Protector');
?>

<div class="watch-card">

<img src="../uploads/products/<?= $folder ?>/<?= htmlspecialchars($item['image']) ?>">

<div class="card-body">

<div class="product-name"><?= htmlspecialchars($name) ?></div>
<div class="product-price">₹<?= number_format($item['price'],2) ?></div>

<div class="card-buttons">

<a href="ajax/remove_to_wishlist.php?id=<?= $item['id'] ?>" class="btn-love">
    <i class="fa-solid fa-heart"></i>
</a>

<?php if($isBackCase): ?>
    <a href="productdetails.php?id=<?= $item['id'] ?>" class="btn-cart">
        Select Model
    </a>
<?php else: ?>
    <a href="ajax/add_to_cart.php?id=<?= $item['id'] ?>&qty=1" class="btn-cart">
        Add to Cart
    </a>
<?php endif; ?>

</div>

</div>
</div>

<?php endforeach; ?>

</div>
</div>

<?php include "includes/footer.php"; ?>
<?php include "includes/mobile_bottom_nav.php"; ?>

</body>
</html>
