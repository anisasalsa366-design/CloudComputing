<?php
$host = getenv('DB_HOST') ?: 'db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'rootCC2026!';
$dbname = getenv('DB_NAME') ?: 'DB_Pos';

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $dbname
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}