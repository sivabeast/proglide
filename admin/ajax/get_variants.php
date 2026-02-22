<?php
require_once "../includes/db.php";

// Function to check admin authentication
function checkAdminAuth() {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

checkAdminAuth();

header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? '';

if (empty($category_id) || !is_numeric($category_id)) {
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