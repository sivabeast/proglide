<?php
include "../includes/admin_db.php";

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit;

$conn->query("DELETE FROM phone_models WHERE id = $id");
/* ======================
   REDIRECT BACK
====================== */
$redirect = $_SERVER['HTTP_REFERER'] ?? '../manage_brands_models.php';
header("Location: $redirect");
?>