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

<div class="max-w-6xl mx-auto mt-10 px-4 sm:px-6 min-h-[75vh]">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl p-6 sm:p-10 shadow-lg mb-10 text-center">
        <h1 class="text-2xl sm:text-4xl font-bold">👋 Bienvenue, <?= $user_name ?> !</h1>
        <p class="mt-2 text-sm sm:text-lg opacity-90">Vous êtes connecté en tant que <span class="font-semibold"><?= ucfirst($user_role) ?></span></p>
    </div>

    <?php if ($user_role === 'admin'): ?>
        <!-- Admin Dashboard -->
        <h2 class="text-xl sm:text-2xl font-semibold mb-6 text-gray-800 text-center">Espace Administration</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Users -->
            <a href="manage_users.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="users" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Gestion des utilisateurs</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Ajouter, modifier ou supprimer des comptes.</p>
            </a>

            <!-- Départements -->
            <a href="manage_departements.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="layers" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Gestion des départements</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Ajouter, modifier ou supprimer des départements.</p>
            </a>

            <!-- Shifts -->
            <a href="manage_shifts.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="clock" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Gestion des horaires</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Définir et organiser les shifts.</p>
            </a>

            <!-- Assign Shift -->
            <a href="assign_shift.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="link" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Lier employés aux horaires</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Assigner les shifts aux employés.</p>
            </a>

            <!-- Manage Assignments -->
            <a href="manage_assignments.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="calendar-check" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Gérer les assignations</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Modifier ou vérifier les plannings.</p>
            </a>

            <!-- Leave Requests -->
            <a href="manage_request_leave.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="plane" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Demandes de congés</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Valider ou refuser les congés.</p>
            </a>

            <!-- Full Schedule -->
            <a href="schedule.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="calendar" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Voir planning complet</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Consulter le calendrier global.</p>
            </a>
        </div>

    <?php else: ?>
        <!-- Employee Dashboard -->
        <h2 class="text-xl sm:text-2xl font-semibold mb-6 text-gray-800 text-center">Espace Employé</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- My Schedule -->
            <a href="schedule_employee.php" class="group bg-indigo-600 text-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="calendar-days" class="w-10 h-10 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg">Voir mon planning</h3>
                <p class="text-sm mt-1 opacity-90 text-center">Consultez vos prochains shifts.</p>
            </a>

            <!-- My Leave -->
            <a href="request_leave.php" class="group bg-white p-6 rounded-2xl shadow hover:shadow-xl transition flex flex-col items-center">
                <i data-lucide="umbrella" class="w-10 h-10 text-indigo-600 mb-4 group-hover:scale-110 transition"></i>
                <h3 class="font-semibold text-lg text-gray-800">Mes congés</h3>
                <p class="text-gray-500 text-sm mt-1 text-center">Faire une demande ou consulter l’historique.</p>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Lucide Icons Script -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script>
  lucide.createIcons();
</script>

<?php require_once '../includes/footer.php'; ?>