<?php
session_start();

require_once '../includes/head.php';
require_once '../includes/header.php';

// Vérification d’authentification
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = "Gestion des utilisateurs - PlanningCo";

require_once '../includes/db.php';

// Récupérer tous les utilisateurs avec leur département et la couleur
try {
    $stmt = $pdo->query("
        SELECT users.id, users.name, users.email, users.role, users.hpw,
               departments.name AS department_name, departments.color AS department_color
        FROM users
        LEFT JOIN departments ON users.department_id = departments.id
        ORDER BY departments.name, 
                 CASE WHEN users.role = 'manager' THEN 0 ELSE 1 END, 
                 users.name
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des utilisateurs.");
}

// Organiser les utilisateurs par département
$departments = [];
foreach ($users as $user) {
    $dept = $user['department_name'] ?? 'Aucun';
    if (!isset($departments[$dept])) {
        $departments[$dept] = [
            'color' => $user['department_color'] ?? 'CCCCCC', // fallback gray
            'users' => []
        ];
    }
    $departments[$dept]['users'][] = $user;
}
?>

<div class="max-w-6xl mx-auto mt-10 px-4 min-h-[75vh]">
    <h1 class="text-3xl font-bold mb-6 text-indigo-700">Gestion des utilisateurs</h1>

    <a href="create_user.php" class="inline-block mb-6 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
        + Ajouter un utilisateur
    </a>

    <?php if (empty($users)): ?>
        <div class="text-center py-6 text-gray-500">Aucun utilisateur trouvé.</div>
    <?php else: ?>
        <?php foreach ($departments as $deptName => $deptData): ?>
            <h2 class="text-xl font-semibold mb-3" style="color:#<?= htmlspecialchars($deptData['color']) ?>">
                <?= htmlspecialchars($deptName) ?>
            </h2>

            <!-- Version Desktop -->
            <div class="hidden sm:table w-full bg-white rounded shadow overflow-hidden mb-6">
                <table class="min-w-full">
                    <thead class="bg-indigo-600 text-white">
                        <tr>
                            <th class="text-left py-3 px-4">Nom</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">Rôle</th>
                            <th class="text-left py-3 px-4">HPW</th>
                            <th class="text-center py-3 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptData['users'] as $user): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?= htmlspecialchars($user['name']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="py-3 px-4 capitalize"><?= htmlspecialchars($user['role']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($user['hpw'] ?? '-') ?></td>
                            <td class="py-3 px-4 text-center space-x-2">
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="text-indigo-600 hover:underline">Modifier</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');" class="text-red-600 hover:underline">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Version Mobile -->
            <div class="sm:hidden space-y-4 mb-6">
                <?php foreach ($deptData['users'] as $user): ?>
                    <div class="bg-white p-4 rounded shadow border-l-4" style="border-color:#<?= htmlspecialchars($deptData['color']) ?>">
                        <p class="font-semibold text-indigo-700 mb-2"><?= htmlspecialchars($user['name']) ?></p>
                        <p><span class="font-medium">Email :</span> <?= htmlspecialchars($user['email']) ?></p>
                        <p><span class="font-medium">Rôle :</span> <?= htmlspecialchars($user['role']) ?></p>
                        <p><span class="font-medium">HPW :</span> <?= htmlspecialchars($user['hpw'] ?? '-') ?></p>
                        <div class="mt-3 flex justify-end space-x-4 text-sm">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="text-indigo-600 hover:underline">Modifier</a>
                            <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');" class="text-red-600 hover:underline">Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
