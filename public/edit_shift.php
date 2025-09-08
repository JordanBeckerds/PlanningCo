<?php
session_start();
require_once '../includes/head.php';
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header('Location: manage_shifts.php?error=invalid_id');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = :id");
$stmt->execute(['id' => $id]);
$shift = $stmt->fetch();

if (!$shift) {
    header('Location: manage_shifts.php?error=not_found');
    exit;
}
?>

<div class="min-h-[78vh]">
    <div class="max-w-md sm:max-w-xl mx-auto mt-6 sm:mt-10 p-4 sm:p-6 bg-white rounded shadow ">
        <h1 class="text-xl sm:text-2xl font-bold mb-6 text-center">Modifier le créneau</h1>

        <form action="../actions/admin_actions.php?action=edit_shift&id=<?= $shift['id'] ?>" method="POST" class="space-y-4">
            <div class="flex flex-col">
                <label class="text-gray-700 font-medium mb-1">Nom du créneau</label>
                <input 
                    type="text" 
                    name="name" 
                    value="<?= htmlspecialchars($shift['name']) ?>" 
                    required
                    class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"
                >
            </div>

            <div class="flex flex-col">
                <label class="text-gray-700 font-medium mb-1">Heure de début</label>
                <input 
                    type="time" 
                    name="start_time" 
                    value="<?= $shift['start_time'] ?>" 
                    required
                    class="border border-gray-300 rounded px-3 py-2"
                >
            </div>

            <div class="flex flex-col">
                <label class="text-gray-700 font-medium mb-1">Heure de fin</label>
                <input 
                    type="time" 
                    name="end_time" 
                    value="<?= $shift['end_time'] ?>" 
                    required
                    class="border border-gray-300 rounded px-3 py-2"
                >
            </div>

            <div class="flex items-center space-x-2">
                <input 
                    type="checkbox" 
                    name="is_night" 
                    id="is_night" 
                    <?= $shift['is_night'] ? 'checked' : '' ?> 
                    class="text-blue-600"
                >
                <label for="is_night" class="text-gray-700">Créneau de nuit</label>
            </div>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-3 sm:gap-6 mt-4">
                <button 
                    type="submit"
                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded"
                >
                    Enregistrer
                </button>
                <a 
                    href="manage_shifts.php"
                    class="w-full sm:w-auto text-center text-gray-600 hover:underline"
                >
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>