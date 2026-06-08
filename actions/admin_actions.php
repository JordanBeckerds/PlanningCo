<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$action = $_GET['action'] ?? '';

if ($action === 'delete_user') {
    $user_id = $_GET['id'] ?? null;
    if (!is_numeric($user_id)) {
        header('Location: ../public/manage_users.php?error=invalid_id');
        exit;
    }
    $user_id = (int)$user_id;

    if ($user_id === (int)$_SESSION['user_id']) {
        header('Location: ../public/manage_users.php?error=cannot_delete_self');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        header('Location: ../public/manage_users.php?success=user_deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: ../public/manage_users.php?error=delete_failed');
        exit;
    }

} elseif ($action === 'create_shift') {
    $name = trim($_POST['name'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_night = isset($_POST['is_night']) ? 1 : 0;

    if ($name === '' || $start_time === '' || $end_time === '') {
        header('Location: ../public/create_shift.php?error=missing_fields');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO shifts (name, start_time, end_time, is_night) VALUES (:name, :start_time, :end_time, :is_night)");
        $stmt->execute(['name' => $name, 'start_time' => $start_time, 'end_time' => $end_time, 'is_night' => $is_night]);
        header('Location: ../public/manage_shifts.php?success=shift_created');
        exit;
    } catch (PDOException $e) {
        header('Location: ../public/create_shift.php?error=insert_failed');
        exit;
    }

} elseif ($action === 'edit_shift') {
    $shift_id = $_GET['id'] ?? null;

    if (!is_numeric($shift_id)) {
        header('Location: ../public/manage_shifts.php?error=invalid_id');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_night = isset($_POST['is_night']) ? 1 : 0;

    if (empty($name) || empty($start_time) || empty($end_time)) {
        header("Location: ../public/edit_shift.php?id=$shift_id&error=missing_fields");
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE shifts SET name = :name, start_time = :start_time, end_time = :end_time, is_night = :is_night WHERE id = :id");
        $stmt->execute(['name' => $name, 'start_time' => $start_time, 'end_time' => $end_time, 'is_night' => $is_night, 'id' => $shift_id]);
        header('Location: ../public/manage_shifts.php?success=shift_updated');
        exit;
    } catch (PDOException $e) {
        header("Location: ../public/edit_shift.php?id=$shift_id&error=update_failed");
        exit;
    }

} elseif ($action === 'delete_shift') {
    $shift_id = $_GET['id'] ?? null;

    if (!is_numeric($shift_id)) {
        header('Location: ../public/manage_shifts.php?error=invalid_id');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = :id");
        $stmt->execute(['id' => (int)$shift_id]);
        header('Location: ../public/manage_shifts.php?success=shift_deleted');
        exit;
    } catch (PDOException $e) {
        header('Location: ../public/manage_shifts.php?error=delete_failed');
        exit;
    }

} else {
    header('Location: ../public/manage_users.php?error=unknown_action');
    exit;
}
