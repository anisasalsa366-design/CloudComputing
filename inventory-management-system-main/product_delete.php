<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: products.php');
    exit;
}

$id = $_POST['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('success', 'Product deleted.');
} else {
    set_flash('error', 'Product not found.');
}

header('Location: products.php');
exit;