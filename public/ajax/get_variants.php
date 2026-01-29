<?php
// ajax/get_variants.php
require "../includes/db.php";

$category_id = $_GET['category_id'] ?? '';

header('Content-Type: application/json');

if (!$category_id || $category_id === 'all') {
    echo json_encode([]);
    exit;
}

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

$conn->close();
?>