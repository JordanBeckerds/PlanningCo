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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: request_leave.php');
    exit;
}

$request_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = :id AND user_id = :user_id AND status = 'pending'");
$stmt->execute(['id' => $request_id, 'user_id' => $user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: request_leave.php');
    exit;
}

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date'], $_POST['end_date'], $_POST['reason'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if (!$start_date || !$end_date) {
        $error = 'Veuillez renseigner les deux dates.';
    } elseif ($start_date > $end_date) {
        $error = 'La date de début doit être antérieure ou égale à la date de fin.';
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM leave_requests 
            WHERE user_id = :user_id 
              AND status = 'pending' 
              AND id != :current_id
              AND NOT (end_date < :start_date OR start_date > :end_date)
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'current_id' => $request_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "Vous avez déjà une demande de congé en attente ou chevauchante sur cette période.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE leave_requests 
                SET start_date = :start_date, end_date = :end_date, reason = :reason 
                WHERE id = :id AND user_id = :user_id AND status = 'pending'
            ");
            $stmt->execute([
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
                'id' => $request_id,
                'user_id' => $user_id,
            ]);

            $success = "Demande mise à jour avec succès.";
            header('Location: request_leave.php');
            exit;
        }
    }
}
?>

<?php require_once '../includes/head.php'; ?>
<?php require_once '../includes/header.php'; ?>

<div class="max-w-xl mx-auto mt-10 px-4 sm:px-6 py-6 bg-white rounded shadow">
    <h1 class="text-xl sm:text-2xl font-bold mb-6">Modifier ma demande de congé</h1>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-200 text-red-800 rounded text-sm"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-200 text-green-800 rounded text-sm"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate class="space-y-4">
        <div>
            <label for="start_date" class="block mb-1 font-semibold text-sm">Date de début *</label>
            <input
                type="date"
                id="start_date"
                name="start_date"
                required
                class="w-full p-2 border border-gray-300 rounded"
                value="<?= htmlspecialchars($_POST['start_date'] ?? $request['start_date']) ?>"
                min="<?= $today ?>"
            >
        </div>

        <div>
            <label for="end_date" class="block mb-1 font-semibold text-sm">Date de fin *</label>
            <input
                type="date"
                id="end_date"
                name="end_date"
                required
                class="w-full p-2 border border-gray-300 rounded"
                value="<?= htmlspecialchars($_POST['end_date'] ?? $request['end_date']) ?>"
                min="<?= $today ?>"
            >
        </div>

        <div>
            <label for="reason" class="block mb-1 font-semibold text-sm">Motif</label>
            <textarea
                id="reason"
                name="reason"
                rows="4"
                class="w-full p-2 border border-gray-300 rounded"
                placeholder="(facultatif)"
            ><?= htmlspecialchars($_POST['reason'] ?? $request['reason']) ?></textarea>
        </div>

        <div class="flex flex-col sm:flex-row justify-between gap-4 mt-6">
            <a href="request_leave.php" class="w-full sm:w-auto text-center px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition">Annuler</a>
            <button type="submit" class="w-full sm:w-auto text-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Enregistrer</button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>