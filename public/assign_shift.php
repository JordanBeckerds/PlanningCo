<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// Récupérer employés
$stmtUsers = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'employee' ORDER BY name");
$stmtUsers->execute();
$employees = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Récupérer shifts
$stmtShifts = $pdo->prepare("SELECT id, name, start_time, end_time FROM shifts ORDER BY start_time");
$stmtShifts->execute();
$shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_ids = $_POST['user_ids'] ?? [];
    $dates = $_POST['dates'] ?? [];
    $shifts_per_date = $_POST['shifts_per_date'] ?? [];
    $recurring = isset($_POST['recurring']) ? true : false;

    if (empty($user_ids) || empty($dates) || empty($shifts_per_date)) {
        $message = '<p class="text-red-600">Veuillez sélectionner au moins un employé, une date et des shifts par date.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            foreach ($dates as $date) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    throw new Exception("Date invalide : $date");
                }
                if (empty($shifts_per_date[$date]) || !is_array($shifts_per_date[$date])) {
                    throw new Exception("Aucun shift sélectionné pour la date $date");
                }
            }

            $weeksCount = $recurring ? 10 : 1;

            foreach ($user_ids as $user_id) {
                foreach ($dates as $date) {
                    $shift_ids_for_date = $shifts_per_date[$date];
                    for ($i = 0; $i < $weeksCount; $i++) {
                        $work_date = date('Y-m-d', strtotime("+$i week", strtotime($date)));

                        foreach ($shift_ids_for_date as $shift_id) {
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE user_id = :user_id AND shift_id = :shift_id AND work_date = :work_date");
                            $checkStmt->execute([
                                'user_id' => $user_id,
                                'shift_id' => $shift_id,
                                'work_date' => $work_date
                            ]);
                            $exists = $checkStmt->fetchColumn();

                            if (!$exists) {
                                $insertStmt = $pdo->prepare("INSERT INTO schedules (user_id, shift_id, work_date) VALUES (:user_id, :shift_id, :work_date)");
                                $insertStmt->execute([
                                    'user_id' => $user_id,
                                    'shift_id' => $shift_id,
                                    'work_date' => $work_date
                                ]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $message = '<p class="text-green-600">Shifts assignés avec succès.</p>';

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<p class="text-red-600">Erreur lors de l\'assignation : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}
?>

<?php require_once '../includes/head.php'; ?>
<?php require_once '../includes/header.php'; ?>

<div class="max-w-5xl mx-auto mt-10 p-4 md:p-6 bg-white rounded shadow">
    <h1 class="text-xl md:text-2xl font-bold mb-6 text-center md:text-left">Assigner shifts à plusieurs employés sur plusieurs dates</h1>

    <?= $message ?>

    <form method="POST" action="assign_shift.php" class="space-y-6" id="assignShiftForm">

        <div>
            <label for="employeeSearch" class="block font-semibold mb-1">Chercher un employé :</label>
            <input type="text" id="employeeSearch" placeholder="Rechercher par nom ou email..." 
                class="w-full border border-gray-300 rounded p-2 mb-2" />
        </div>

        <div>
            <label for="user_ids" class="block font-semibold mb-1">Employés :</label>
            <select name="user_ids[]" id="user_ids" multiple required class="w-full border border-gray-300 rounded p-2 h-32 text-sm">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= htmlspecialchars($employee['id']) ?>">
                        <?= htmlspecialchars($employee['name']) ?> (<?= htmlspecialchars($employee['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Ctrl / Cmd pour multi-sélection</p>
        </div>

        <div>
            <label class="block font-semibold mb-1">Dates & Shifts :</label>
            <div id="datesShiftsContainer" class="space-y-6">
                <div class="date-shift-block border rounded-lg p-4 relative bg-gray-50 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <input type="date" name="dates[]" required class="border border-gray-300 rounded-md p-2 w-full sm:w-44" />
                        <button type="button" class="removeDateBtn text-red-600 hover:text-red-800 text-xl font-bold">&times;</button>
                    </div>

                    <input type="text" placeholder="Rechercher un shift..." class="shiftSearchInput mb-3 w-full border border-gray-300 rounded-md p-2" />

                    <select name="shifts_per_date[__INDEX__][]" multiple required class="shift-select w-full border border-gray-300 rounded-md p-2 h-44 overflow-auto text-sm">
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?= htmlspecialchars($shift['id']) ?>">
                                <?= htmlspecialchars($shift['name']) ?> (<?= substr($shift['start_time'], 0, 5) ?> - <?= substr($shift['end_time'], 0, 5) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="text-xs text-gray-500 mt-2">Ctrl / Cmd pour sélection multiple</p>
                </div>
            </div>
            <button type="button" id="addDateShiftBtn" class="mt-2 px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                + Ajouter une date avec shifts
            </button>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <input type="checkbox" name="recurring" id="recurring" value="1">
            <label for="recurring" class="font-semibold">Récurrent chaque semaine (10 semaines)</label>
        </div>

        <button type="submit" class="mt-6 w-full md:w-auto bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
            Assigner les shifts
        </button>
    </form>
</div>

<script>
// Reste du JS (inchangé)
</script>

<?php require_once '../includes/footer.php'; ?>
