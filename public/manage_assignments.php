<?php
session_start();
require_once '../includes/db.php';

// Sécurité : accès admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// Récupération de la liste des employés
$employeesStmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement formulaire suppression assignations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignments'])) {
    $assignment_ids = $_POST['assignment_ids'] ?? [];
    if (!empty($assignment_ids)) {
        $placeholders = implode(',', array_fill(0, count($assignment_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id IN ($placeholders)");
        $stmt->execute($assignment_ids);
        $message = "Assignations supprimées avec succès.";
    }
}

// Récupération employés sélectionnés
$selected_employee_ids = $_GET['employees'] ?? [];
if (!is_array($selected_employee_ids)) {
    $selected_employee_ids = [$selected_employee_ids];
}
$selected_employee_ids = array_filter($selected_employee_ids, 'is_numeric');

$common_shifts = [];
$common_assignments = [];

if (count($selected_employee_ids) > 0) {
    // 1. Trouver les shifts communs aux employés sélectionnés
    // Requête pour trouver shifts communs à tous ces employés
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
        // Récupérer détails des shifts communs
        $in_shifts = implode(',', array_fill(0, count($common_shift_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id IN ($in_shifts)");
        $stmt->execute($common_shift_ids);
        $common_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Récupérer assignations communes (employés sélectionnés + shifts communs)
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
?>

<?php
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Gérer les assignations</title>
    <link href="../assets/tailwind.css" rel="stylesheet" />
</head>
<body class="p-6 bg-gray-50">

<h1 class="text-xl sm:text-2xl font-bold mb-4">Gérer les assignations</h1>

<form method="get" id="selectEmployeesForm" class="mb-6">
    <label for="employees" class="font-semibold block mb-2">Sélectionnez un ou plusieurs employés :</label>
    <select name="employees[]" id="employees"
        multiple size="10"
        class="border rounded p-2 w-full max-w-md sm:max-w-xs md:max-w-sm overflow-auto"
        onchange="document.getElementById('selectEmployeesForm').submit()">
        <?php foreach ($employees as $emp): ?>
            <option value="<?= htmlspecialchars($emp['id']) ?>" <?= in_array($emp['id'], $selected_employee_ids) ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if (count($selected_employee_ids) > 0 && !empty($common_shifts)): ?>
    <h2 class="text-lg sm:text-xl mt-6 mb-2 font-semibold">Shifts communs aux employés sélectionnés :</h2>
    <ul class="mb-4 list-disc list-inside">
        <?php foreach ($common_shifts as $shift): ?>
            <li><?= htmlspecialchars($shift['name']) ?> (<?= htmlspecialchars($shift['start_time']) ?> - <?= htmlspecialchars($shift['end_time']) ?>)</li>
        <?php endforeach; ?>
    </ul>

    <form method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer les assignations sélectionnées ?');">
        <input type="hidden" name="delete_assignments" value="1" />
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300 rounded text-sm">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="p-2 border-b"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"/></th>
                        <th class="p-2 border-b">Employé</th>
                        <th class="p-2 border-b">Shift</th>
                        <th class="p-2 border-b">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($common_assignments as $assign): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="p-2 border-b text-center">
                                <input type="checkbox" name="assignment_ids[]" value="<?= $assign['id'] ?>" />
                            </td>
                            <td class="p-2 border-b"><?= htmlspecialchars($assign['user_name']) ?></td>
                            <td class="p-2 border-b"><?= htmlspecialchars($assign['shift_name']) ?></td>
                            <td class="p-2 border-b"><?= htmlspecialchars($assign['work_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit"
            class="mt-3 w-full sm:w-auto px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Supprimer les assignations sélectionnées
        </button>
    </form>
<?php endif; ?>

<script>
    function toggleAll(source) {
        checkboxes = document.querySelectorAll('input[name="assignment_ids[]"]');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
</script>

<?php
require_once '../includes/footer.php';
?>

</body>
</html>