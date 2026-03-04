<?php
// ajax/get_brand.php
session_start();
require "../includes/db.php";

header('Content-Type: application/json');

// Get search term if provided
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    // Search brands
    $stmt = $conn->prepare("SELECT id, name FROM brands WHERE status = 1 AND name LIKE ? ORDER BY name");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm);
} else {
    // Get all brands
    $stmt = $conn->prepare("SELECT id, name FROM brands WHERE status = 1 ORDER BY name");
}

$stmt->execute();
$result = $stmt->get_result();

$brands = [];
while ($row = $result->fetch_assoc()) {
    $brands[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars($row['name'])
    ];
}

// If no brands found, show default message
if (empty($brands)) {
    $brands[] = [
        'id' => 0,
        'name' => 'No brands found'
    ];
}

echo json_encode([
    'success' => true,
    'brands' => $brands
]);

$conn->close();
?>