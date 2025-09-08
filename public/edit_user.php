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
    $stmt = $pdo->prepare("SELECT id, name, email, role, department_id, hpw FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<p class='text-red-600 p-4'>Utilisateur non trouvé.</p>";
        require_once '../includes/footer.php';
        exit;
    }

    $departments_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $departments_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='text-red-600 p-4'>Erreur serveur.</p>";
    require_once '../includes/footer.php';
    exit;
}

// Gestion du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $hpw = isset($_POST['hpw']) && is_numeric($_POST['hpw']) ? (int)$_POST['hpw'] : null;
    $new_password = trim($_POST['password'] ?? '');

    // Vérification des champs remplis
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    if ($role !== '' && !in_array($role, ['admin', 'employee', 'manager'])) {
        $errors[] = "Rôle invalide.";
    }
    if ($department_id !== '' && !is_numeric($department_id)) {
        $errors[] = "Département invalide.";
    }

    // Vérifier unicité email sauf pour l'utilisateur en cours
    if ($email !== '') {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt_check->execute(['email' => $email, 'id' => $user_id]);
        if ($stmt_check->fetch()) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
    }

    if (empty($errors)) {
        try {
            $updates = [];
            $params = ['id' => $user_id];

            if ($name !== '') { $updates[] = "name = :name"; $params['name'] = $name; }
            if ($email !== '') { $updates[] = "email = :email"; $params['email'] = $email; }
            if ($role !== '') { $updates[] = "role = :role"; $params['role'] = $role; }
            if ($department_id !== '') { $updates[] = "department_id = :department"; $params['department'] = $department_id; }
            if ($hpw !== null) { $updates[] = "hpw = :hpw"; $params['hpw'] = $hpw; }
            if ($new_password !== '') { $updates[] = "password = :password"; $params['password'] = password_hash($new_password, PASSWORD_DEFAULT); }

            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmt_update = $pdo->prepare($sql);
                $stmt_update->execute($params);

                echo "<p class='bg-green-100 text-green-800 p-3 rounded mb-4'>Utilisateur mis à jour avec succès.</p>";

                // Mise à jour locale
                foreach ($params as $key => $value) {
                    if ($key !== 'id') $user[$key] = $value;
                }
            } else {
                echo "<p class='bg-yellow-100 text-yellow-800 p-3 rounded mb-4'>Aucune modification effectuée.</p>";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour.";
        }
    }
}
?>

<!-- Formulaire reste le même -->
<div class="min-h-[78vh] flex items-center justify-center">
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
                <!-- Les champs du formulaire restent inchangés -->
                <div>
                    <label for="name" class="block font-medium mb-1 text-sm">Nom complet</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="email" class="block font-medium mb-1 text-sm">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="role" class="block font-medium mb-1 text-sm">Rôle</label>
                    <select id="role" name="role"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Aucun --</option>
                        <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employé</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
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

                <div>
                    <label for="hpw" class="block font-medium mb-1 text-sm">Heures par semaine (HPW)</label>
                    <input type="number" id="hpw" name="hpw" value="<?= htmlspecialchars($user['hpw'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="password" class="block font-medium mb-1 text-sm">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Laisser vide pour ne pas changer"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
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
</div>

<?php
require_once '../includes/footer.php';
?>