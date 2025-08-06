<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('Location: ../public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$today = date('Y-m-d');

// --- Suppression d'une demande ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    // Vérifier que la demande appartient bien à l'utilisateur et est pending
    $stmt = $pdo->prepare("SELECT status FROM leave_requests WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $delete_id, 'user_id' => $user_id]);
    $request = $stmt->fetch();

    if ($request && $request['status'] === 'pending') {
        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $success = "Demande supprimée avec succès.";
    } else {
        $error = "Impossible de supprimer cette demande.";
    }
}

// --- Ajout d'une nouvelle demande ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date'], $_POST['end_date'], $_POST['reason']) && !isset($_POST['delete_id'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if (!$start_date || !$end_date) {
        $error = 'Veuillez renseigner les deux dates.';
    } elseif ($start_date > $end_date) {
        $error = 'La date de début doit être antérieure ou égale à la date de fin.';
    } else {
        // Vérifier qu'il n'y a pas de chevauchement avec une demande pending existante
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM leave_requests 
            WHERE user_id = :user_id 
              AND status = 'pending' 
              AND NOT (end_date < :start_date OR start_date > :end_date)
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "Vous avez déjà une demande de congé en attente ou chevauchante sur cette période.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO leave_requests (user_id, start_date, end_date, reason, status) 
                VALUES (:user_id, :start_date, :end_date, :reason, 'pending')
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
            ]);
            $success = "Demande envoyée avec succès.";
            // Evite la double soumission
            header('Location: request_leave.php');
            exit;
        }
    }
}

// --- Récupérer toutes les demandes de l'utilisateur ---
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = :user_id ORDER BY start_date DESC");
$stmt->execute(['user_id' => $user_id]);
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction utilitaire pour couleur de ligne selon status
function statusColorClass($status) {
    return match ($status) {
        'pending' => 'bg-yellow-100',
        'approved' => 'bg-green-100',
        'denied' => 'bg-red-100',
        default => '',
    };
}
?>

<?php require_once '../includes/head.php'; ?>
<?php require_once '../includes/header.php'; ?>

<div class="max-w-2xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-6">Demande de congé</h1>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-200 text-red-800 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-200 text-green-800 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="start_date" class="block mb-2 font-semibold">Date de début *</label>
        <input type="date" id="start_date" name="start_date" required class="w-full p-2 border border-gray-300 rounded mb-4" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">

        <label for="end_date" class="block mb-2 font-semibold">Date de fin *</label>
        <input type="date" id="end_date" name="end_date" required class="w-full p-2 border border-gray-300 rounded mb-4" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">

        <label for="reason" class="block mb-2 font-semibold">Motif</label>
        <textarea id="reason" name="reason" rows="4" class="w-full p-2 border border-gray-300 rounded mb-4" placeholder="(facultatif)"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>

        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">Envoyer la demande</button>
    </form>
</div>

<!-- Version desktop tableau -->
<div class="max-w-4xl mx-auto mt-12 p-6 bg-white rounded shadow hidden sm:block">
    <h2 class="text-xl font-bold mb-4">Historique et demandes en cours</h2>

    <table class="min-w-full border-collapse border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Date début</th>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Date fin</th>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Motif</th>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Statut</th>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Date de demande</th>
                <th class="px-4 py-2 border-b border-gray-300 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leave_requests)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-gray-500 italic">Aucune demande de congé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($leave_requests as $request): ?>
                    <tr class="<?= statusColorClass($request['status']) ?>">
                        <td class="px-4 py-2 border-b border-gray-300"><?= htmlspecialchars($request['start_date']) ?></td>
                        <td class="px-4 py-2 border-b border-gray-300"><?= htmlspecialchars($request['end_date']) ?></td>
                        <td class="px-4 py-2 border-b border-gray-300 whitespace-pre-line"><?= htmlspecialchars($request['reason'] ?: '-') ?></td>
                        <td class="px-4 py-2 border-b border-gray-300 font-semibold capitalize"><?= htmlspecialchars($request['status']) ?></td>
                        <td class="px-4 py-2 border-b border-gray-300"><?= htmlspecialchars($request['created_at']) ?></td>
                        <td class="px-4 py-2 border-b border-gray-300">
                            <?php if ($request['status'] === 'pending' && $request['end_date'] >= $today): ?>
                                <a href="edit_leave_request.php?id=<?= $request['id'] ?>" class="text-indigo-600 hover:underline mr-3">Modifier</a>
                                <form method="POST" action="" class="inline-block" onsubmit="return confirm('Confirmez-vous la suppression de cette demande ?');">
                                    <input type="hidden" name="delete_id" value="<?= $request['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400 italic">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Version mobile cartes -->
<div class="max-w-4xl mx-auto mt-12 p-4 bg-white rounded shadow sm:hidden">
    <h2 class="text-xl font-bold mb-4">Historique et demandes en cours</h2>

    <?php if (empty($leave_requests)): ?>
        <div class="text-center text-gray-500 italic">Aucune demande de congé.</div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($leave_requests as $request): ?>
                <div class="p-4 rounded border <?= statusColorClass($request['status']) ?>">
                    <div class="mb-2">
                        <span class="font-semibold">Date début :</span> <?= htmlspecialchars($request['start_date']) ?>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold">Date fin :</span> <?= htmlspecialchars($request['end_date']) ?>
                    </div>
                    <div class="mb-2 whitespace-pre-line">
                        <span class="font-semibold">Motif :</span> <?= htmlspecialchars($request['reason'] ?: '-') ?>
                    </div>
                    <div class="mb-2 font-semibold capitalize">
                        Statut : <?= htmlspecialchars($request['status']) ?>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold">Date de demande :</span> <?= htmlspecialchars($request['created_at']) ?>
                    </div>
                    <div class="flex justify-end space-x-4 text-sm">
                        <?php if ($request['status'] === 'pending' && $request['end_date'] >= $today): ?>
                            <a href="edit_leave_request.php?id=<?= $request['id'] ?>" class="text-indigo-600 hover:underline">Modifier</a>
                            <form method="POST" action="" class="inline-block" onsubmit="return confirm('Confirmez-vous la suppression de cette demande ?');">
                                <input type="hidden" name="delete_id" value="<?= $request['id'] ?>">
                                <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                            </form>
                        <?php else: ?>
                            <span class="text-gray-400 italic">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>