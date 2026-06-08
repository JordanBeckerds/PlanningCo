<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $color = ltrim($_POST['color'] ?? '', '#');

    if ($name !== '' && preg_match('/^[A-Fa-f0-9]{3,6}$/', $color)) {
        $stmt = $pdo->prepare("INSERT INTO departments (name, color) VALUES (?, ?)");
        $stmt->execute([$name, strtoupper($color)]);
        header('Location: manage_departements.php');
        exit;
    } else {
        $error = 'Veuillez entrer un nom et une couleur valide (hex).';
    }
}

$page_title = 'Créer un Département - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="min-h-[78vh]">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Cr&eacute;er un d&eacute;partement</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 max-w-md">
            <div>
                <label class="block mb-1">Nom</label>
                <input type="text" name="name" class="w-full border px-3 py-2 rounded" required>
            </div>
            <div class="mb-4">
                <label class="block mb-2 font-medium text-gray-700">Couleur</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="colorPicker" name="color" value="#FFA500"
                        class="w-12 h-12 p-0 border-0 rounded-full cursor-pointer transition shadow-md hover:scale-105">
                    <span class="text-gray-600 text-sm">S&eacute;lectionnez la couleur du d&eacute;partement</span>
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Cr&eacute;er</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
