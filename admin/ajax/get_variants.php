<?php
require_once "../includes/db.php";
checkAdminAuth();

header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? '';

if (empty($category_id)) {
    echo json_encode([]);
    exit;
}

// Get variants for specific category
$sql = "SELECT id, name FROM variant_types WHERE category_id = ? AND status = 1 ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$variants = [];
while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

echo json_encode($variants);

$stmt->close();
$conn->close();
?>