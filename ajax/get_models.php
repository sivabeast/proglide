<?php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

$brand_id = $_GET['brand_id'] ?? 0;

if (!$brand_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Brand ID required'
    ]);
    exit;
}

$sql = "SELECT id, model_name 
        FROM phone_models 
        WHERE brand_id = ? AND status = 1 
        ORDER BY model_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();

$models = [];
while ($row = $result->fetch_assoc()) {
    $models[] = $row;
}

echo json_encode([
    'success' => true,
    'models' => $models
]);

$conn->close();
?>