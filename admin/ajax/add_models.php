<?php
include "../includes/admin_db.php";

$data = json_decode(file_get_contents("php://input"), true);

$brand_id = (int)($data['brand_id'] ?? 0);
$models   = $data['models'] ?? [];

if (!$brand_id || empty($models)) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("
    INSERT IGNORE INTO phone_models (brand_id, model_name)
    VALUES (?, ?)
");

foreach ($models as $m) {
    $name = trim($m);
    if ($name === '') continue;

    $stmt->bind_param("is", $brand_id, $name);
    $stmt->execute();
}
?>