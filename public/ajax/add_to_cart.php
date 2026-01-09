<?php
session_start();
require "../includes/db.php";

$user_id = $_SESSION['user_id'] ?? null;
$session = session_id();

$product_id = (int)($_GET['id'] ?? 0);
$qty        = max(1, (int)($_GET['qty'] ?? 1));
$model_id   = isset($_GET['model_id']) ? (int)$_GET['model_id'] : null;

if (!$product_id) {
    header("Location: ../products.php");
    exit;
}

/* =========================
   CHECK EXISTING CART ITEM
   (PRODUCT + MODEL BASED)
========================= */
if ($user_id) {
    $check = $conn->prepare("
        SELECT id, quantity
        FROM cart
        WHERE user_id = ?
          AND product_id = ?
          AND (phone_model_id <=> ?)
    ");
    $check->bind_param("iii", $user_id, $product_id, $model_id);
} else {
    $check = $conn->prepare("
        SELECT id, quantity
        FROM cart
        WHERE session_id = ?
          AND product_id = ?
          AND (phone_model_id <=> ?)
    ");
    $check->bind_param("sii", $session, $product_id, $model_id);
}

$check->execute();
$res = $check->get_result();

/* =========================
   UPDATE OR INSERT
========================= */
if ($row = $res->fetch_assoc()) {

    // SAME DESIGN + SAME MODEL → increase qty
    $newQty = $row['quantity'] + $qty;

    $up = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
    $up->bind_param("ii", $newQty, $row['id']);
    $up->execute();

} else {

    // NEW MODEL → NEW ROW
    if ($user_id) {
        $ins = $conn->prepare("
            INSERT INTO cart (user_id, product_id, phone_model_id, quantity)
            VALUES (?,?,?,?)
        ");
        $ins->bind_param("iiii", $user_id, $product_id, $model_id, $qty);
    } else {
        $ins = $conn->prepare("
            INSERT INTO cart (session_id, product_id, phone_model_id, quantity)
            VALUES (?,?,?,?)
        ");
        $ins->bind_param("siii", $session, $product_id, $model_id, $qty);
    }

    $ins->execute();
}

/* =========================
   RETURN BACK
========================= */
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../products.php'));
exit;
