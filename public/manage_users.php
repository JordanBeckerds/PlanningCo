<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$page_title = 'Gestion des utilisateurs - PlanningCo';

try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.role, u.hpw,
               d.name AS department_name, d.color AS department_color
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        ORDER BY d.name,
                 CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
                 u.name
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('manage_users: ' . $e->getMessage());
    $users = [];
}

$departments = [];
foreach ($users as $user) {
    $dept = $user['department_name'] ?? 'Aucun département';
    if (!isset($departments[$dept])) {
        $departments[$dept] = [
            'color' => $user['department_color'] ?? '#cccccc',
            'users' => []
        ];
    }
    $departments[$dept]['users'][] = $user;
}

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="max-w-6xl mx-auto mt-10 px-4 min-h-[75vh]">
    <h1 class="text-3xl font-bold mb-6 text-indigo-700">Gestion des utilisateurs</h1>

    <a href="create_user.php" class="inline-block mb-6 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
        + Ajouter un utilisateur
    </a>

    <?php if (empty($users)): ?>
        <div class="text-center py-6 text-gray-500">Aucun utilisateur trouv&eacute;.</div>
    <?php else: ?>
        <?php foreach ($departments as $deptName => $deptData): ?>
            <h2 class="text-xl font-semibold mb-3" style="color:<?= h($deptData['color']) ?>">
                <?= h($deptName) ?>
            </h2>

            <!-- Desktop -->
            <div class="hidden sm:block w-full bg-white rounded shadow overflow-hidden mb-6">
                <table class="min-w-full">
                    <thead class="bg-indigo-600 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Nom</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">R&ocirc;le</th>
                            <th class="text-left py-3 px-4">HPW</th>
                            <th class="text-center py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptData['users'] as $user): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?= h($user['name']) ?></td>
                            <td class="py-3 px-4"><?= h($user['email']) ?></td>
                            <td class="py-3 px-4 capitalize"><?= h($user['role']) ?></td>
                            <td class="py-3 px-4"><?= h((string)($user['hpw'] ?? '-')) ?></td>
                            <td class="py-3 px-4 text-center space-x-2">
                                <a href="edit_user.php?id=<?= (int)$user['id'] ?>" class="text-indigo-600 hover:underline">Modifier</a>
                                <a href="delete_user.php?id=<?= (int)$user['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');" class="text-red-600 hover:underline">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile -->
            <div class="sm:hidden space-y-4 mb-6">
                <?php foreach ($deptData['users'] as $user): ?>
                    <div class="bg-white p-4 rounded shadow border-l-4" style="border-color:<?= h($deptData['color']) ?>">
                        <p class="font-semibold text-indigo-700 mb-2"><?= h($user['name']) ?></p>
                        <p><span class="font-medium">Email :</span> <?= h($user['email']) ?></p>
                        <p><span class="font-medium">R&ocirc;le :</span> <?= h($user['role']) ?></p>
                        <p><span class="font-medium">HPW :</span> <?= h((string)($user['hpw'] ?? '-')) ?></p>
                        <div class="mt-3 flex justify-end space-x-4 text-sm">
                            <a href="edit_user.php?id=<?= (int)$user['id'] ?>" class="text-indigo-600 hover:underline">Modifier</a>
                            <a href="delete_user.php?id=<?= (int)$user['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');" class="text-red-600 hover:underline">Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
