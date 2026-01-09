<?php
include "../includes/admin_auth.php";
include "../includes/admin_db.php";

if (!isset($_GET['id'])) {
    header("Location: products_list.php");
    exit;
}

$id = (int)$_GET['id'];

/* GET PRODUCT IMAGE */
$stmt = $conn->prepare(
    "SELECT image FROM products WHERE id = ? LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    die("Product not found");
}

$product = $res->fetch_assoc();
$image   = $product['image'];

/* DELETE PRODUCT */
$del = $conn->prepare("DELETE FROM products WHERE id = ?");
$del->bind_param("i", $id);

if ($del->execute()) {

    /* DELETE IMAGE FILE */
    $path = "../../uploads/products/" . $image;
    if ($image && file_exists($path)) {
        unlink($path);
    }

    header("Location: products_list.php?deleted=1");
    exit;
} else {
    die("Delete failed");
}
