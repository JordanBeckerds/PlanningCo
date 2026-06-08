<?php
session_start();

$page_title = 'Connexion - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';

$error   = $_SESSION['error']   ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<div class="min-h-[80vh] w-[100vw] flex items-center justify-center">

    <div class="min-w-[20vw] max-w-md mx-auto mt-16 bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-bold mb-6 text-indigo-700 text-center">Connexion</h1>

        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="../actions/login_action.php" method="POST" class="space-y-6">
            <label class="block">
                <span>Email</span>
                <input type="email" name="email" required autofocus
                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </label>

            <label class="block">
                <span>Mot de passe</span>
                <input type="password" name="password" required
                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </label>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition">
                Se connecter
            </button>
        </form>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
