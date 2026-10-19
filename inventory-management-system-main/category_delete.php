<?php

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: categories.php');
    exit;
}

$id = $_POST['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        set_flash('success', 'Category deleted.');
    } catch (PDOException $e) {
        set_flash('error', 'This category still has products assigned to it. Reassign them before deleting.');
    }
} else {
    set_flash('error', 'Category not found.');
}

header('Location: categories.php');
exit;