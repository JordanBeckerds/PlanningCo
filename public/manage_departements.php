<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

// Handle delete BEFORE any HTML output
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_departements.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM departments ORDER BY id ASC");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestion des Départements - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="min-h-[80vh]">
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Gestion des d&eacute;partements</h1>

        <a href="create_departements.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 mb-4 inline-block">
            Ajouter un d&eacute;partement
        </a>

        <table class="w-full border border-gray-300 rounded text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">ID</th>
                    <th class="border px-4 py-2">Nom</th>
                    <th class="border px-4 py-2">Couleur</th>
                    <th class="border px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td class="border px-4 py-2"><?= h($dept['id']) ?></td>
                        <td class="border px-4 py-2"><?= h($dept['name']) ?></td>
                        <td class="border px-4 py-2">
                            <span class="inline-block w-6 h-6 rounded" style="background-color:#<?= h($dept['color']) ?>"></span>
                            #<?= h($dept['color']) ?>
                        </td>
                        <td class="border px-4 py-2">
                            <a href="edit_departements.php?id=<?= h($dept['id']) ?>" class="text-blue-600 hover:underline mr-2">Modifier</a>
                            <a href="manage_departements.php?delete=<?= h($dept['id']) ?>" class="text-red-600 hover:underline" onclick="return confirm('Supprimer ce d&eacute;partement ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
