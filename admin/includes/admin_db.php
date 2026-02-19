<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "proglide");

if ($conn->connect_error) {
    die("Database Connection Failed");
}
