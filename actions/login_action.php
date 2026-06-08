<?php
session_start();
require_once '../includes/db.php';

if (!isset($_POST['email'], $_POST['password'])) {
    $_SESSION['error'] = 'Veuillez remplir tous les champs.';
    header('Location: ../public/login.php');
    exit;
}

$email    = trim($_POST['email']);
$password = $_POST['password'];

try {
    $stmt = $pdo->prepare('
        SELECT id, name, email, password_hash, role, failed_attempts, locked_until
        FROM users WHERE email = :email LIMIT 1
    ');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = 'Email ou mot de passe incorrect.';
        header('Location: ../public/login.php');
        exit;
    }

    if ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
        $_SESSION['error'] = 'Compte verrouillé. Réessayez dans 30 minutes.';
        header('Location: ../public/login.php');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)$user['failed_attempts'] + 1;
        if ($attempts >= 5) {
            $pdo->prepare('UPDATE users SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?')
                ->execute([$attempts, $user['id']]);
            $_SESSION['error'] = 'Trop de tentatives. Compte verrouillé 30 minutes.';
        } else {
            $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE id = ?')
                ->execute([$attempts, $user['id']]);
            $_SESSION['error'] = 'Email ou mot de passe incorrect. ' . (5 - $attempts) . ' tentative(s) restante(s).';
        }
        header('Location: ../public/login.php');
        exit;
    }

    // Success — reset counter and start session
    $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?')
        ->execute([$user['id']]);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];

    header('Location: ../public/dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log('PlanningCo login: ' . $e->getMessage());
    $_SESSION['error'] = 'Erreur serveur, veuillez réessayer.';
    header('Location: ../public/login.php');
    exit;
}
