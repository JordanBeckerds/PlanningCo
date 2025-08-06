<?php
session_start();

require_once '../includes/head.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Tableau de bord - PlanningCo";
$user_name = htmlspecialchars($_SESSION['user_name']);
$user_role = $_SESSION['user_role'];
?>

<div class="max-w-4xl mx-auto mt-8 px-4 sm:px-6">
    <h1 class="text-2xl sm:text-3xl font-bold mb-6 text-indigo-700">Bienvenue, <?= $user_name ?> !</h1>

    <?php if ($user_role === 'admin'): ?>
        <section class="bg-white p-4 sm:p-6 rounded shadow mb-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">Administration</h2>
            <ul class="space-y-2 text-sm sm:text-base">
                <li><a href="manage_users.php" class="text-indigo-600 hover:underline">Gestion des utilisateurs</a></li>
                <li><a href="manage_shifts.php" class="text-indigo-600 hover:underline">Gestion des horaires</a></li>
                <li><a href="assign_shift.php" class="text-indigo-600 hover:underline">Lier employés aux horaires</a></li>
                <li><a href="manage_assignments.php" class="text-indigo-600 hover:underline">Gérer les assignations</a></li>
                <li><a href="manage_request_leave.php" class="text-indigo-600 hover:underline">Demandes de congés</a></li>
                <li><a href="schedule.php" class="text-indigo-600 hover:underline">Voir planning complet</a></li>
            </ul>
        </section>
    <?php else: ?>
        <section class="bg-white p-4 sm:p-6 rounded shadow mb-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">Votre planning</h2>
            <p class="mb-4 text-sm sm:text-base">Consultez votre planning et faites vos demandes de congés.</p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="schedule_employee.php" class="bg-indigo-600 text-white text-center px-5 py-2 rounded hover:bg-indigo-700 transition">
                    Voir mon planning
                </a>
                <a href="request_leave.php" class="bg-gray-200 text-gray-700 text-center px-5 py-2 rounded hover:bg-gray-300 transition">
                    Mes congés
                </a>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>