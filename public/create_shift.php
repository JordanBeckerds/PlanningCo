<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$page_title = 'Ajouter un Shift - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-6 flex flex-col items-center gap-12">
    <h1 class="text-2xl font-bold mb-6 text-center sm:text-left">Ajouter un nouveau shift</h1>

    <form action="../actions/admin_actions.php?action=create_shift" method="POST"
        class="bg-white shadow-md rounded-lg p-6 w-full max-w-lg mx-auto">
        
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium mb-2">Nom du shift :</label>
            <input type="text" name="name" id="name" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ex: Matin, Soir">
        </div>

        <div class="mb-4">
            <label for="start_time" class="block text-sm font-medium mb-2">Heure de d&eacute;but :</label>
            <input type="time" name="start_time" id="start_time" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="end_time" class="block text-sm font-medium mb-2">Heure de fin :</label>
            <input type="time" name="end_time" id="end_time" required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="is_night" class="inline-flex items-center">
                <input type="checkbox" name="is_night" id="is_night" class="mr-2">
                <span class="text-sm">Shift de nuit</span>
            </label>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4">
            <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition w-full sm:w-auto mb-3 sm:mb-0">
                Ajouter le shift
            </button>
            <a href="manage_shifts.php"
                class="text-center sm:text-left text-blue-600 hover:underline text-sm sm:text-base">
                Annuler
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
