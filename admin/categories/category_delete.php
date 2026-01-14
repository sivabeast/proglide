<?php
require "../includes/admin_auth.php";
require "../includes/admin_db.php";

$id = (int)$_GET['id'];

$res = $conn->query("SELECT image FROM categories WHERE id=$id");
$c = $res->fetch_assoc();

$conn->query("DELETE FROM categories WHERE id=$id");

if ($c && $c['image']) {
    $path = "../../uploads/categories/".$c['image'];
    if (file_exists($path)) unlink($path);
}

header("Location: categories.php");
exit;
?>