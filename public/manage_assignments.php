<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$employeesStmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignments'])) {
    $assignment_ids = $_POST['assignment_ids'] ?? [];
    if (!empty($assignment_ids)) {
        $placeholders = implode(',', array_fill(0, count($assignment_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id IN ($placeholders)");
        $stmt->execute($assignment_ids);
        $message = "Assignations supprim&eacute;es avec succ&egrave;s.";
    }
}

$selected_employee_ids = $_GET['employees'] ?? [];
if (!is_array($selected_employee_ids)) {
    $selected_employee_ids = [$selected_employee_ids];
}
$selected_employee_ids = array_filter($selected_employee_ids, 'is_numeric');

$common_shifts = [];
$common_assignments = [];

if (count($selected_employee_ids) > 0) {
    $in_placeholders = implode(',', array_fill(0, count($selected_employee_ids), '?'));
    $sql_common_shifts = "
        SELECT shift_id, COUNT(DISTINCT user_id) as cnt
        FROM schedules
        WHERE user_id IN ($in_placeholders)
        GROUP BY shift_id
        HAVING cnt = ?
    ";
    $stmt = $pdo->prepare($sql_common_shifts);
    $params = array_merge($selected_employee_ids, [count($selected_employee_ids)]);
    $stmt->execute($params);
    $common_shift_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (count($common_shift_ids) > 0) {
        $in_shifts = implode(',', array_fill(0, count($common_shift_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id IN ($in_shifts)");
        $stmt->execute($common_shift_ids);
        $common_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql_assignments = "
            SELECT s.id, s.user_id, s.shift_id, s.work_date, u.name as user_name, sh.name as shift_name
            FROM schedules s
            JOIN users u ON s.user_id = u.id
            JOIN shifts sh ON s.shift_id = sh.id
            WHERE s.user_id IN ($in_placeholders)
            AND s.shift_id IN ($in_shifts)
            ORDER BY s.work_date, u.name
        ";
        $stmt = $pdo->prepare($sql_assignments);
        $stmt->execute(array_merge($selected_employee_ids, $common_shift_ids));
        $common_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$page_title = 'G&eacute;rer les Assignations - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="min-h-[80vh] p-6 bg-gray-50 flex flex-col items-center">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">G&eacute;rer les assignations</h1>

    <div class="flex flex-col lg:flex-row w-full max-w-7xl gap-6">
        <!-- Employee Selection -->
        <div class="bg-white p-6 rounded-lg shadow-md w-full lg:w-1/3">
            <h2 class="text-xl font-semibold mb-4">S&eacute;lectionnez des employ&eacute;s</h2>
            <form method="get" id="selectEmployeesForm">
                <select name="employees[]" id="employees" multiple size="10"
                    class="border rounded p-3 h-[10vh] md:h-auto w-full overflow-auto focus:ring-2 focus:ring-blue-500"
                    onchange="document.getElementById('selectEmployeesForm').submit()">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= h($emp['id']) ?>" <?= in_array($emp['id'], $selected_employee_ids) ? 'selected' : '' ?>>
                            <?= h($emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Assignments Display -->
        <div class="bg-white p-6 rounded-lg shadow-md w-full lg:w-2/3">
            <?php if (!empty($common_shifts)): ?>
                <h2 class="text-xl font-semibold mb-4">Shifts communs</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <?php foreach ($common_shifts as $shift): ?>
                        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                            <h3 class="font-semibold text-blue-700"><?= h($shift['name']) ?></h3>
                            <p class="text-gray-600"><?= h($shift['start_time']) ?> - <?= h($shift['end_time']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer les assignations s&eacute;lectionn&eacute;es ?');">
                    <input type="hidden" name="delete_assignments" value="1" />
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-center"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"/></th>
                                    <th class="p-3 text-left">Employ&eacute;</th>
                                    <th class="p-3 text-left">Shift</th>
                                    <th class="p-3 text-left">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($common_assignments as $assign): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-2 text-center">
                                            <input type="checkbox" name="assignment_ids[]" value="<?= h($assign['id']) ?>" class="h-4 w-4 text-blue-600"/>
                                        </td>
                                        <td class="p-2"><?= h($assign['user_name']) ?></td>
                                        <td class="p-2"><?= h($assign['shift_name']) ?></td>
                                        <td class="p-2"><?= h($assign['work_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="mt-4 px-6 py-2 bg-red-600 text-white font-semibold rounded hover:bg-red-700 transition">
                        Supprimer les assignations s&eacute;lectionn&eacute;es
                    </button>
                </form>
            <?php else: ?>
                <p class="text-gray-500">S&eacute;lectionnez au moins un employ&eacute; pour voir les shifts communs.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('input[name="assignment_ids[]"]').forEach(cb => cb.checked = source.checked);
        }
    </script>
</div>

<?php require_once '../includes/footer.php'; ?>
