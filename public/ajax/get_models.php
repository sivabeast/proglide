<?php
require "../includes/db.php";

$brand_id = (int)($_GET['brand_id'] ?? 0);

$res = $conn->query("
    SELECT id, model_name
    FROM phone_models
    WHERE brand_id = $brand_id
    AND status = 'active' 
    ORDER BY model_name
");

while($row = $res->fetch_assoc()){
    echo "<option value='{$row['id']}'>{$row['model_name']}</option>";
}

