<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_users.php?error=invalid_id');
    exit;
}

$user_id = (int)$_GET['id'];
header("Location: ../actions/admin_actions.php?action=delete_user&id=$user_id");
exit;
