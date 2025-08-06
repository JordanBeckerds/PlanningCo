<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$success_msg = $_GET['success'] ?? '';
$error_msg = $_GET['error'] ?? '';

$stmt = $pdo->query("SELECT * FROM shifts ORDER BY start_time ASC");
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Gestion des Plages Horaires (Shifts)</h1>

    <?php if ($success_msg): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <a href="create_shift.php" class="mb-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        Ajouter un shift
    </a>

    <?php if (count($shifts) === 0): ?>
        <p>Aucun shift défini pour le moment.</p>
    <?php else: ?>

        <!-- Desktop Table -->
        <div class="overflow-x-auto hidden sm:block">
            <table class="min-w-full bg-white border rounded shadow">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="py-2 px-4 border-b">Nom</th>
                        <th class="py-2 px-4 border-b">Heure de début</th>
                        <th class="py-2 px-4 border-b">Heure de fin</th>
                        <th class="py-2 px-4 border-b">Nuit</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $shift): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($shift['name']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars(substr($shift['start_time'], 0, 5)); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars(substr($shift['end_time'], 0, 5)); ?></td>
                            <td class="py-2 px-4 border-b text-center"><?php echo $shift['is_night'] ? 'Oui' : 'Non'; ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="edit_shift.php?id=<?php echo $shift['id']; ?>" class="text-blue-600 hover:underline mr-3">Modifier</a>
                                <a href="delete_shift.php?id=<?php echo $shift['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce shift ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="sm:hidden space-y-4">
            <?php foreach ($shifts as $shift): ?>
                <div class="bg-white rounded shadow p-4 border">
                    <p class="font-semibold">Nom : <span class="font-normal"><?php echo htmlspecialchars($shift['name']); ?></span></p>
                    <p class="font-semibold">Début : <span class="font-normal"><?php echo htmlspecialchars(substr($shift['start_time'], 0, 5)); ?></span></p>
                    <p class="font-semibold">Fin : <span class="font-normal"><?php echo htmlspecialchars(substr($shift['end_time'], 0, 5)); ?></span></p>
                    <p class="font-semibold">Nuit : <span class="font-normal"><?php echo $shift['is_night'] ? 'Oui' : 'Non'; ?></span></p>
                    <div class="mt-2 flex gap-3">
                        <a href="edit_shift.php?id=<?php echo $shift['id']; ?>" class="text-blue-600 hover:underline">Modifier</a>
                        <a href="delete_shift.php?id=<?php echo $shift['id']; ?>" class="text-red-600 hover:underline"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce shift ?');">Supprimer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';