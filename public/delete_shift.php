<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$shift_id = $_GET['id'] ?? null;

if (!is_numeric($shift_id)) {
    header('Location: manage_shifts.php?error=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = :id");
    $stmt->execute(['id' => (int)$shift_id]);

    header('Location: manage_shifts.php?success=shift_deleted');
    exit;
} catch (PDOException $e) {
    header('Location: manage_shifts.php?error=delete_failed');
    exit;
}
