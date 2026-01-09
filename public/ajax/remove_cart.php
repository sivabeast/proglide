<?php
session_start();
require __DIR__ . "/../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_GET['id'];

$stmt = $conn->prepare(
    "DELETE FROM cart WHERE user_id=? AND product_id=?"
);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
/* 🔥 REDIRECT BACK TO SAME PAGE */
$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: $redirect");
exit;
?>