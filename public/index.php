<?php
session_start();

require_once '../includes/head.php';

$page_title = "Accueil - PlanningCo";

require_once '../includes/header.php';
?>

<div class="text-center max-w-xl mx-auto mt-16">
    <h1 class="text-4xl font-bold mb-6 text-indigo-700">Bienvenue sur PlanningCo</h1>
    <p class="text-lg mb-8 text-gray-700">
        Simplifiez la gestion des plannings de vos employés avec notre outil intuitif.
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" class="inline-block bg-indigo-600 text-white py-3 px-6 rounded hover:bg-indigo-700 transition">
            Accéder au tableau de bord
        </a>
    <?php else: ?>
        <a href="login.php" class="inline-block bg-indigo-600 text-white py-3 px-6 rounded hover:bg-indigo-700 transition">
            Se connecter
        </a>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>