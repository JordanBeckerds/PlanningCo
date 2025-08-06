<?php
session_start();

require_once '../includes/head.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

$page_title = "Modifier un utilisateur - PlanningCo";

// Vérifie que l’id est présent et numérique
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-red-600 p-4'>ID utilisateur invalide.</p>";
    require_once '../includes/footer.php';
    exit;
}

$user_id = (int)$_GET['id'];

// Récupérer données utilisateur
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role, department_id FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<p class='text-red-600 p-4'>Utilisateur non trouvé.</p>";
        require_once '../includes/footer.php';
        exit;
    }

    // Récupérer les départements pour le select
    $departments_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $departments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='text-red-600 p-4'>Erreur serveur.</p>";
    require_once '../includes/footer.php';
    exit;
}

// Gestion du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'employee';
    $department_id = $_POST['department_id'] ?? null;

    $errors = [];

    if ($name === '') {
        $errors[] = "Le nom est obligatoire.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    if (!in_array($role, ['admin', 'employee'])) {
        $errors[] = "Rôle invalide.";
    }
    if ($department_id !== null && $department_id !== '' && !is_numeric($department_id)) {
        $errors[] = "Département invalide.";
    }

    // Vérifier unicité email sauf pour l'utilisateur en cours
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $stmt_check->execute(['email' => $email, 'id' => $user_id]);
    if ($stmt_check->fetch()) {
        $errors[] = "Cette adresse email est déjà utilisée.";
    }

    if (empty($errors)) {
        try {
            $stmt_update = $pdo->prepare("UPDATE users SET name = :name, email = :email, role = :role, department_id = :department WHERE id = :id");
            $stmt_update->execute([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'department' => $department_id !== '' ? $department_id : null,
                'id' => $user_id
            ]);

            echo "<p class='bg-green-100 text-green-800 p-3 rounded mb-4'>Utilisateur mis à jour avec succès.</p>";
            // Met à jour $user pour afficher les nouvelles valeurs dans le formulaire
            $user['name'] = $name;
            $user['email'] = $email;
            $user['role'] = $role;
            $user['department_id'] = $department_id !== '' ? $department_id : null;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour.";
        }
    }
}
?>

<div class="w-full max-w-3xl mx-auto mt-8 px-4 sm:px-6">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-xl sm:text-2xl font-bold mb-6 text-indigo-700 text-center">Modifier l'utilisateur</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4 text-sm">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="name" class="block font-medium mb-1 text-sm">Nom complet</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div>
                <label for="email" class="block font-medium mb-1 text-sm">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div>
                <label for="role" class="block font-medium mb-1 text-sm">Rôle</label>
                <select id="role" name="role" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employé</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                </select>
            </div>

            <div>
                <label for="department_id" class="block font-medium mb-1 text-sm">Département</label>
                <select id="department_id" name="department_id"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= $dep['id'] ?>" <?= $user['department_id'] == $dep['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dep['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <button type="submit"
                    class="w-full sm:w-auto bg-indigo-600 text-white px-5 py-2 rounded hover:bg-indigo-700 transition text-sm">
                    Enregistrer les modifications
                </button>
                <a href="manage_users.php"
                    class="text-center text-sm text-gray-600 hover:underline">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>