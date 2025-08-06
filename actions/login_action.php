<?php
session_start();

require_once '../includes/db.php';  // connexion PDO $pdo

// Sécurité basique : vérifie que les champs existent
if (!isset($_POST['email'], $_POST['password'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header('Location: ../public/login.php');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

try {
    // Préparation de la requête pour récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Authentification réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirection selon rôle (exemple)
        if ($user['role'] === 'admin') {
            header('Location: ../public/dashboard.php');
        } elseif ($user['role'] === 'employee') {
            header('Location: ../public/dashboard.php');
        } else {
            header('Location: ../public/schedule.php');
        }
        exit;
    } else {
        // Échec
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        header('Location: ../public/login.php');
        exit;
    }
} catch (PDOException $e) {
    // Gestion d'erreur basique
    $_SESSION['error'] = "Erreur serveur, veuillez réessayer plus tard.";
    header('Location: ../public/login.php');
    exit;
}