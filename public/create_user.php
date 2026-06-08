<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$errors = [];
$name = '';
$email = '';
$role = 'employee';
$department_id = '';
$hpw = '';
$success = '';

try {
    $departments_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $departments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    $department_id = $_POST['department_id'] ?? null;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $hpw = trim($_POST['hpw'] ?? '');

    if ($name === '') $errors[] = 'Le nom est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email n'est pas valide.";
    if (!in_array($role, ['admin', 'employee', 'manager'])) $errors[] = 'R&ocirc;le invalide.';
    if ($department_id !== null && $department_id !== '' && !is_numeric($department_id)) $errors[] = 'D&eacute;partement invalide.';
    if ($password === '') $errors[] = 'Le mot de passe est obligatoire.';
    if ($password !== $password_confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
    if ($hpw === '' || !is_numeric($hpw) || (int)$hpw <= 0) $errors[] = 'Les heures par semaine doivent &ecirc;tre un nombre positif.';

    if (empty($errors)) {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check->execute(['email' => $email]);
        if ($stmt_check->fetch()) {
            $errors[] = 'Cette adresse email est d&eacute;j&agrave; utilis&eacute;e.';
        }
    }

    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, department_id, hpw)
                VALUES (:name, :email, :password_hash, :role, :department, :hpw)
            ");
            $stmt_insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $password_hash,
                'role' => $role,
                'department' => $department_id !== '' ? $department_id : null,
                'hpw' => (int)$hpw
            ]);
            $success = 'Utilisateur cr&eacute;&eacute; avec succ&egrave;s.';
            $name = $email = $hpw = '';
            $role = 'employee';
            $department_id = '';
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la cr&eacute;ation de l'utilisateur.";
        }
    }
}

$page_title = 'Créer un Utilisateur - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="w-full px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto mt-6 min-h-[75vh]">
    <div class="bg-white p-4 sm:p-6 rounded shadow">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 text-indigo-700">Cr&eacute;er un nouvel utilisateur</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm sm:text-base">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 text-sm sm:text-base" novalidate>
            <div>
                <label for="name" class="block font-medium mb-1">Nom complet</label>
                <input type="text" id="name" name="name" value="<?= h($name) ?>" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div>
                <label for="email" class="block font-medium mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?= h($email) ?>" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block font-medium mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="password_confirm" class="block font-medium mb-1">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
            </div>

            <div>
                <label for="role" class="block font-medium mb-1">R&ocirc;le</label>
                <select id="role" name="role" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="employee" <?= $role === 'employee' ? 'selected' : '' ?>>Employ&eacute;</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                    <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>Manager</option>
                </select>
            </div>

            <div>
                <label for="department_id" class="block font-medium mb-1">D&eacute;partement</label>
                <select id="department_id" name="department_id"
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= h($dep['id']) ?>" <?= $department_id == $dep['id'] ? 'selected' : '' ?>>
                            <?= h($dep['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="hpw" class="block font-medium mb-1">Heures par semaine (HPW)</label>
                <input type="number" id="hpw" name="hpw" min="1" value="<?= h($hpw) ?>" required
                    class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-3 sm:space-y-0">
                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition text-sm sm:text-base">
                    Cr&eacute;er l'utilisateur
                </button>
                <a href="manage_users.php" class="text-gray-600 hover:underline text-sm sm:text-base">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
