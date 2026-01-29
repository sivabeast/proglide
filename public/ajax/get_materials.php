<?php
// ajax/get_materials.php
require "../includes/db.php";

$category_id = $_GET['category_id'] ?? '';

header('Content-Type: application/json');

if (!$category_id || $category_id === 'all') {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, name FROM material_types WHERE category_id = ? AND status = 1 ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$materials = [];
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

echo json_encode($materials);

$conn->close();
?>