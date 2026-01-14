<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products_list.php");
    exit;
}

$product_id = (int) $_GET['id'];

/* =========================
   FETCH PRODUCT DETAILS
========================= */
$stmt = $conn->prepare("
    SELECT image, main_category_id
    FROM products
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    die("Product not found");
}

$product = $res->fetch_assoc();
$image = $product['image'];
$main_category_id = (int)$product['main_category_id'];

/* =========================
   DELETE DEPENDENT DATA
========================= */
/* These tables EXIST in your DB */
$conn->query("DELETE FROM cart WHERE product_id = $product_id");
$conn->query("DELETE FROM wishlist WHERE product_id = $product_id");
$conn->query("DELETE FROM order_items WHERE product_id = $product_id");

/* =========================
   DELETE PRODUCT
========================= */
$del = $conn->prepare("DELETE FROM products WHERE id = ?");
$del->bind_param("i", $product_id);

if (!$del->execute()) {
    die("Product delete failed");
}

/* =========================
   DELETE IMAGE FILE
========================= */
$folder = ($main_category_id === 1)
    ? "../../uploads/products/protectors/"
    : "../../uploads/products/backcases/";

$path = $folder . $image;

if ($image && file_exists($path)) {
    unlink($path);
}

/* =========================
   REDIRECT
========================= */
header("Location: add_protector.php?deleted=1");
exit;
