<?php
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();
/* =========================
   REMOVE ITEM (SAME PAGE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {

    $cart_id = (int) $_POST['remove_cart_id'];

    $del = $conn->prepare(
        "DELETE FROM cart WHERE id = ? AND user_id = ?"
    );
    $del->bind_param("ii", $cart_id, $user_id);
    $del->execute();

    /* 🔥 POST → REDIRECT → GET (IMPORTANT) */
    header("Location: cart.php");
    exit;
}

?>
