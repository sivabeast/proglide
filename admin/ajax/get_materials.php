<?php
require_once "../includes/db.php";
checkAdminAuth();

header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? '';

if (empty($category_id)) {
    echo json_encode([]);
    exit;
}

// Get materials for specific category
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

$stmt->close();
$conn->close();
?>