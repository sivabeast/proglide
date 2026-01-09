<?php
include "../includes/admin_db.php";

/* Pending orders */
$pending = $conn->query("
    SELECT id, user_id, total_amount, created_at
    FROM orders
    WHERE status='Pending'
    ORDER BY created_at DESC
    LIMIT 5
");

/* Processing orders */
$processing = $conn->query("
    SELECT id, user_id, total_amount, created_at
    FROM orders
    WHERE status='Processing'
    ORDER BY created_at DESC
    LIMIT 5
");

$data = [
    'pending' => [],
    'processing' => []
];

while ($row = $pending->fetch_assoc()) {
    $data['pending'][] = $row;
}

while ($row = $processing->fetch_assoc()) {
    $data['processing'][] = $row;
}

echo json_encode($data);
