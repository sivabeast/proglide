<?php
include "includes/admin_db.php";

$username = "admin";
$email = "admin@protectors.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO admins (username, email, password) VALUES (?,?,?)"
);
$stmt->bind_param("sss", $username, $email, $password);
$stmt->execute();

echo "Admin created successfully";
