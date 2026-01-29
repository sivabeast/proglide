<?php
require "../includes/db.php";

header('Content-Type: text/html; charset=utf-8');

$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;

if ($brand_id <= 0) {
    echo '<option value="">Select Model</option>';
    exit;
}

// Use prepared statement
$stmt = $conn->prepare("
    SELECT id, model_name 
    FROM phone_models 
    WHERE brand_id = ? AND status = 1 
    ORDER BY model_name
");
$stmt->bind_param("i", $brand_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<option value="">No models found</option>';
} else {
    while ($row = $result->fetch_assoc()) {
        $id = htmlspecialchars($row['id']);
        $name = htmlspecialchars($row['model_name']);
        echo "<option value='{$id}'>{$name}</option>";
    }
}

$stmt->close();
$conn->close();
?>