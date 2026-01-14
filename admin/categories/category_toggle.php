<?php
require "../includes/admin_db.php";

$field = $_POST['field'];
$id = (int)$_POST['id'];

if (!in_array($field,['status','show_on_home'])) exit;

$conn->query("
    UPDATE categories
    SET $field = IF($field=1,0,1)
    WHERE id = $id
");
echo "ok";
?>