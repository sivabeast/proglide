<?php
/* ======================
   SESSION START
====================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ======================
   DB CONNECTION
====================== */
require __DIR__ . "/../includes/db.php";

/* ======================
   LOGIN CHECK
====================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ======================
   PRODUCT ID CHECK
====================== */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../products.php");
    exit;
}

$user_id    = (int) $_SESSION['user_id'];
$product_id = (int) $_GET['id'];

/* ======================
   INSERT INTO WISHLIST
   (Duplicate auto ignore)
====================== */
$stmt = $conn->prepare("
    INSERT INTO wishlist (user_id, product_id)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE product_id = product_id
");

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();

/* ======================
   REDIRECT BACK
====================== */
$redirect = $_SERVER['HTTP_REFERER'] ?? '../products.php';
header("Location: $redirect");
exit;
