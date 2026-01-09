<?php
session_start();
require "../includes/db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$cart_id = (int)($data['cart_id'] ?? 0);
$delta   = (int)($data['delta'] ?? 0);

if ($cart_id <= 0 || $delta == 0) {
    echo json_encode(["success" => false]);
    exit;
}

/* UPDATE QTY */
$stmt = $conn->prepare("
    UPDATE cart
    SET quantity = GREATEST(quantity + ?, 1)
    WHERE id = ?
");

$stmt->bind_param("ii", $delta, $cart_id);
$ok = $stmt->execute();

echo json_encode(["success" => $ok]);
