<?php
include "includes/admin_db.php";
include "includes/admin_auth.php";
session_start();

/* ADMIN AUTH CHECK */

$id = (int)$_GET['id'];
$status = $_GET['status'];

if (!in_array($status, ['paid','rejected'])) {
    die("Invalid status");
}

$conn->query("
UPDATE orders 
SET payment_status='$status'
WHERE id=$id
");

header("Location: orders.php");
exit;
