<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$stmtUsers = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'employee' ORDER BY name");
$stmtUsers->execute();
$employees = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

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
        $message = '<p class="text-red-600 font-semibold">Veuillez s&eacute;lectionner au moins un employ&eacute;, une date et des shifts par date.</p>';
    } else {
        try {
            $pdo->beginTransaction();
            $weeksCount = $recurring ? 10 : 1;

            foreach ($dates as $index => $date) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new Exception("Date invalide : " . h($date));
                $shift_ids_for_date = $shifts_per_date[$index] ?? [];
                if (empty($shift_ids_for_date) || !is_array($shift_ids_for_date)) throw new Exception("Aucun shift s&eacute;lectionn&eacute; pour la date $date");

                foreach ($user_ids as $user_id) {
                    for ($i = 0; $i < $weeksCount; $i++) {
                        $work_date = date('Y-m-d', strtotime("+$i week", strtotime($date)));
                        foreach ($shift_ids_for_date as $shift_id) {
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE user_id = :user_id AND shift_id = :shift_id AND work_date = :work_date");
                            $checkStmt->execute(['user_id' => $user_id, 'shift_id' => $shift_id, 'work_date' => $work_date]);
                            if (!$checkStmt->fetchColumn()) {
                                $insertStmt = $pdo->prepare("INSERT INTO schedules (user_id, shift_id, work_date) VALUES (:user_id, :shift_id, :work_date)");
                                $insertStmt->execute(['user_id' => $user_id, 'shift_id' => $shift_id, 'work_date' => $work_date]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $message = '<p class="text-green-600 font-semibold">Shifts assign&eacute;s avec succ&egrave;s.</p>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<p class="text-red-600 font-semibold">Erreur : ' . h($e->getMessage()) . '</p>';
        }
    }
}

$page_title = 'Assigner des Shifts - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="max-w-6xl mx-auto mt-12 p-6 bg-white rounded-2xl shadow-lg">
    <h1 class="text-2xl md:text-3xl font-bold mb-8 text-center md:text-left text-indigo-700">Assigner des shifts</h1>

    <?= $message ?>

    <form method="POST" class="space-y-8" id="assignShiftForm">
        <!-- Employee Search -->
        <div class="space-y-2">
            <label for="employeeSearch" class="block font-semibold text-gray-700">Chercher un employ&eacute; :</label>
            <input type="text" id="employeeSearch" placeholder="Nom ou email..." 
                class="w-full border border-gray-300 rounded-lg p-3 focus:ring-indigo-500 focus:border-indigo-500" />
        </div>

        <!-- Employees Select -->
        <div class="space-y-2">
            <label for="user_ids" class="block font-semibold text-gray-700">Employ&eacute;s :</label>
            <select name="user_ids[]" id="user_ids" multiple required 
                class="w-full border border-gray-300 rounded-lg p-3 h-36 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= h($employee['id']) ?>">
                        <?= h($employee['name']) ?> (<?= h($employee['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-500">Ctrl / Cmd pour multi-s&eacute;lection</p>
        </div>

        <!-- Dates & Shifts -->
        <div>
            <label class="block font-semibold text-gray-700 mb-3">Dates &amp; Shifts :</label>
            <div id="datesShiftsContainer" class="space-y-6">
                <div class="date-shift-block border rounded-xl p-5 bg-indigo-50 shadow-sm" data-index="0">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
                        <input type="date" name="dates[0]" required class="border border-gray-300 rounded-lg p-3 w-full sm:w-48" />
                        <button type="button" class="removeDateBtn text-red-600 text-2xl font-bold hover:text-red-800">&times;</button>
                    </div>

                    <input type="text" placeholder="Rechercher un shift..." class="shiftSearchInput mb-3 w-full border border-gray-300 rounded-lg p-3" />

                    <select name="shifts_per_date[0][]" multiple required 
                        class="shift-select w-full border border-gray-300 rounded-lg p-3 h-44 overflow-auto text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?= h($shift['id']) ?>">
                                <?= h($shift['name']) ?> (<?= h(substr($shift['start_time'], 0, 5)) ?> - <?= h(substr($shift['end_time'], 0, 5)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class="text-xs text-gray-500 mt-2">Ctrl / Cmd pour multi-s&eacute;lection</p>
                </div>
            </div>

            <button type="button" id="addDateShiftBtn" class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm">
                + Ajouter une date
            </button>
        </div>

        <!-- Recurring -->
        <div class="flex items-center gap-3">
            <input type="checkbox" name="recurring" id="recurring" value="1" class="h-4 w-4 accent-indigo-600">
            <label for="recurring" class="font-semibold text-gray-700">R&eacute;current chaque semaine (10 semaines)</label>
        </div>

        <button type="submit" class="mt-6 w-full md:w-auto bg-indigo-600 text-white px-8 py-3 rounded-xl hover:bg-indigo-700 transition-colors font-semibold">
            Assigner les shifts
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let dateShiftIndex = 1;
    const container = document.getElementById('datesShiftsContainer');
    const addBtn = document.getElementById('addDateShiftBtn');

    addBtn.addEventListener('click', () => {
        const template = container.querySelector('.date-shift-block');
        const clone = template.cloneNode(true);
        clone.dataset.index = dateShiftIndex;

        const dateInput = clone.querySelector('input[type="date"]');
        dateInput.name = `dates[${dateShiftIndex}]`;
        dateInput.value = '';

        const shiftSelect = clone.querySelector('select.shift-select');
        shiftSelect.name = `shifts_per_date[${dateShiftIndex}][]`;
        shiftSelect.selectedIndex = -1;

        clone.querySelector('.shiftSearchInput').value = '';
        container.appendChild(clone);
        dateShiftIndex++;
    });

    container.addEventListener('click', e => {
        if (e.target.classList.contains('removeDateBtn')) {
            if (container.children.length > 1) e.target.closest('.date-shift-block').remove();
            else alert('Vous devez garder au moins une date.');
        }
    });

    container.addEventListener('input', e => {
        if (e.target.classList.contains('shiftSearchInput')) {
            const filter = e.target.value.toLowerCase();
            const select = e.target.nextElementSibling;
            Array.from(select.options).forEach(opt => opt.style.display = opt.text.toLowerCase().includes(filter) ? '' : 'none');
        }
    });

    const empSearch = document.getElementById('employeeSearch');
    const userSelect = document.getElementById('user_ids');
    empSearch.addEventListener('input', () => {
        const filter = empSearch.value.toLowerCase();
        Array.from(userSelect.options).forEach(opt => opt.style.display = opt.text.toLowerCase().includes(filter) ? '' : 'none');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
