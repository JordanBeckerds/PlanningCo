<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = (int)$_POST['request_id'];

    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = :id AND status = 'pending'");
    $stmt->execute(['id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $error = 'Demande introuvable ou d&eacute;j&agrave; trait&eacute;e.';
    } else {
        if ($action === 'approve') {
            $new_status = 'approved';
        } elseif ($action === 'deny') {
            $new_status = 'denied';
        } else {
            $error = 'Action invalide.';
        }

        if (!$error) {
            $update = $pdo->prepare("UPDATE leave_requests SET status = :status WHERE id = :id");
            $update->execute(['status' => $new_status, 'id' => $request_id]);
            $message = "Demande #" . h($request_id) . " mise &agrave; jour avec succ&egrave;s.";
        }
    }
}

$searchName = trim($_GET['search'] ?? '');

$perPage = 20;
$pagePending = isset($_GET['pagePending']) && is_numeric($_GET['pagePending']) ? (int)$_GET['pagePending'] : 1;
$pageTreated = isset($_GET['pageTreated']) && is_numeric($_GET['pageTreated']) ? (int)$_GET['pageTreated'] : 1;
$offsetPending = ($pagePending - 1) * $perPage;
$offsetTreated = ($pageTreated - 1) * $perPage;

$searchSql = '';
$paramsSearch = [];
if ($searchName !== '') {
    $searchSql = "AND u.name LIKE :searchName";
    $paramsSearch['searchName'] = '%' . $searchName . '%';
}

$countPendingStmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.status = 'pending' $searchSql");
$countPendingStmt->execute($paramsSearch);
$totalPending = $countPendingStmt->fetchColumn();
$totalPagesPending = ceil($totalPending / $perPage);

$sqlPending = "
    SELECT lr.*, u.name AS user_name, u.email 
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE lr.status = 'pending' $searchSql
    ORDER BY lr.created_at DESC
    LIMIT :offset, :perPage
";
$stmtPending = $pdo->prepare($sqlPending);
foreach ($paramsSearch as $key => $val) {
    $stmtPending->bindValue(":$key", $val);
}
$stmtPending->bindValue(':offset', $offsetPending, PDO::PARAM_INT);
$stmtPending->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmtPending->execute();
$requestsPending = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

$countTreatedStmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.status IN ('approved', 'denied') $searchSql");
$countTreatedStmt->execute($paramsSearch);
$totalTreated = $countTreatedStmt->fetchColumn();
$totalPagesTreated = ceil($totalTreated / $perPage);

$sqlTreated = "
    SELECT lr.*, u.name AS user_name, u.email 
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE lr.status IN ('approved', 'denied') $searchSql
    ORDER BY lr.created_at DESC
    LIMIT :offset, :perPage
";
$stmtTreated = $pdo->prepare($sqlTreated);
foreach ($paramsSearch as $key => $val) {
    $stmtTreated->bindValue(":$key", $val);
}
$stmtTreated->bindValue(':offset', $offsetTreated, PDO::PARAM_INT);
$stmtTreated->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmtTreated->execute();
$requestsTreated = $stmtTreated->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Gestion des Congés - PlanningCo';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="max-w-7xl min-h-[74vh] mx-auto px-4 sm:px-6 lg:px-8 mt-10">

    <h1 class="text-3xl font-bold mb-6">Gestion des demandes de cong&eacute;</h1>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-200 text-red-800 rounded"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="mb-4 p-3 bg-green-200 text-green-800 rounded"><?= $message ?></div>
    <?php endif; ?>

    <form method="GET" class="mb-6 flex items-center space-x-3">
        <input type="text" name="search" placeholder="Rechercher par nom employ&eacute;..." 
               value="<?= h($searchName) ?>" 
               class="border border-gray-300 rounded p-2 w-64" />
        <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Rechercher</button>
    </form>

    <section class="mb-10">
        <h2 class="text-2xl font-semibold mb-4">Demandes en attente (<?= h($totalPending) ?>)</h2>

        <?php if (count($requestsPending) === 0): ?>
            <p class="text-gray-600">Aucune demande en attente.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Employ&eacute;</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Email</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">D&eacute;but</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Fin</th>
                            <th class="border border-gray-300 px-4 py-2 text-left max-w-xs">Motif</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Statut</th>
                            <th class="border border-gray-300 px-4 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requestsPending as $req): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['id']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['user_name']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['email']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['start_date']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['end_date']) ?></td>
                            <td class="border border-gray-300 px-4 py-2 max-w-xs break-words"><?= nl2br(h($req['reason'])) ?></td>
                            <td class="border border-gray-300 px-4 py-2 capitalize"><?= h($req['status']) ?></td>
                            <td class="border border-gray-300 px-4 py-2 text-center">
                                <form method="POST" class="inline-block mr-1">
                                    <input type="hidden" name="request_id" value="<?= h($req['id']) ?>">
                                    <button name="action" value="approve" type="submit" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition" title="Approuver">&#10004;</button>
                                </form>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="request_id" value="<?= h($req['id']) ?>">
                                    <button name="action" value="deny" type="submit" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition" title="Refuser">&#10006;</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-center space-x-2">
                <?php for ($p = 1; $p <= $totalPagesPending; $p++): ?>
                    <a href="?search=<?= urlencode($searchName) ?>&pagePending=<?= $p ?>&pageTreated=<?= $pageTreated ?>" 
                       class="px-3 py-1 border rounded <?= $p === $pagePending ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600' ?>">
                        <?= h($p) ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-4">Demandes trait&eacute;es (<?= h($totalTreated) ?>)</h2>

        <?php if (count($requestsTreated) === 0): ?>
            <p class="text-gray-600">Aucune demande trait&eacute;e.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Employ&eacute;</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Email</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">D&eacute;but</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Fin</th>
                            <th class="border border-gray-300 px-4 py-2 text-left max-w-xs">Motif</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Statut</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Trait&eacute;e le</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requestsTreated as $req): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['id']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['user_name']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['email']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['start_date']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['end_date']) ?></td>
                            <td class="border border-gray-300 px-4 py-2 max-w-xs break-words"><?= nl2br(h($req['reason'])) ?></td>
                            <td class="border border-gray-300 px-4 py-2 capitalize"><?= h($req['status']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= h($req['updated_at'] ?? $req['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-center space-x-2">
                <?php for ($p = 1; $p <= $totalPagesTreated; $p++): ?>
                    <a href="?search=<?= urlencode($searchName) ?>&pagePending=<?= $pagePending ?>&pageTreated=<?= $p ?>" 
                       class="px-3 py-1 border rounded <?= $p === $pageTreated ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600' ?>">
                        <?= h($p) ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once '../includes/footer.php'; ?>
