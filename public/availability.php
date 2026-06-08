<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();
$page_title = 'Disponibilités - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>
<div class="max-w-2xl mx-auto mt-10 px-4 min-h-[75vh]">
  <h1 class="text-2xl font-bold text-indigo-700">Disponibilit&eacute;s</h1>
  <p class="mt-4 text-gray-500">Cette fonctionnalit&eacute; sera disponible prochainement.</p>
</div>
<?php require_once '../includes/footer.php'; ?>
