<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

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