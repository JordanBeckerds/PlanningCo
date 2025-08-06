<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_users.php?error=invalid_id');
    exit;
}

$user_id = (int)$_GET['id'];

// Appeler l'action en POST via formulaire ou GET en redirection
header("Location: ../actions/admin_actions.php?action=delete_user&id=$user_id");
exit;
