<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get current year, month, and optional selected day from URL
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));
$selected_day = $_GET['day'] ?? null;

if ($selected_day) {
    $dayDt = DateTime::createFromFormat('Y-m-d', $selected_day);
    if (!$dayDt) {
        // Invalid day format fallback
        $selected_day = null;
        $dayDt = null;
    } else {
        $year = (int)$dayDt->format('Y');
        $month = (int)$dayDt->format('m');
    }
} else {
    $dayDt = null;
}

// Month info
$firstOfMonth = new DateTime("$year-$month-01");
$daysInMonth = (int)$firstOfMonth->format('t');
$firstDayWeekday = (int)$firstOfMonth->format('N'); // 1=Mon .. 7=Sun

// Calculate weeks for month calendar
$weeks = [];
$start = clone $firstOfMonth;
$start->modify('-' . ($firstDayWeekday - 1) . ' days');

do {
    $week = [];
    for ($i = 0; $i < 7; $i++) {
        $week[] = clone $start;
        $start->modify('+1 day');
    }
    $weeks[] = $week;
} while ($start->format('m') == $month || $start->format('N') != 1);

// Week view start calculation if a day is selected
if ($dayDt) {
    $weekStart = clone $dayDt;
    $weekStart->modify('-' . ($dayDt->format('N') - 1) . ' days'); // Monday
} else {
    $weekStart = null;
}

// Prepare Previous/Next URLs for Month View
$prevMonth = (clone $firstOfMonth)->modify('-1 month');
$nextMonth = (clone $firstOfMonth)->modify('+1 month');

function buildUrlMonth($year, $month) {
    return "schedule.php?year=$year&month=$month";
}

// Prepare Previous/Next URLs for Week View
if ($weekStart) {
    $prevWeekStart = (clone $weekStart)->modify('-7 days');
    $nextWeekStart = (clone $weekStart)->modify('+7 days');

    function buildUrlWeek(DateTime $dt) {
        return "schedule.php?day=" . $dt->format('Y-m-d');
    }
}

// Fetch users
$stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch shifts
$stmt = $pdo->query("SELECT id, name, start_time, end_time FROM shifts ORDER BY start_time");
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch schedules for month view
$sqlMonth = "
    SELECT work_date, u.id AS user_id, u.name AS user_name
    FROM schedules s
    JOIN users u ON s.user_id = u.id
    WHERE MONTH(work_date) = ? AND YEAR(work_date) = ? AND s.status = 'assigned'
    ORDER BY u.name
";
$stmt = $pdo->prepare($sqlMonth);
$stmt->execute([$month, $year]);
$monthSchedulesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthSchedules = [];
foreach ($monthSchedulesRaw as $ms) {
    $d = $ms['work_date'];
    $monthSchedules[$d][$ms['user_id']] = $ms['user_name'];
}

// Fetch schedules for week view
$schedulesByUserDate = [];
if ($weekStart) {
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $d = clone $weekStart;
        $d->modify("+$i day");
        $weekDays[] = $d->format('Y-m-d');
    }

    $inQuery = implode(',', array_fill(0, count($weekDays), '?'));

    $sql = "
        SELECT s.user_id, s.work_date, sh.id AS shift_id, sh.name AS shift_name, sh.start_time, sh.end_time, u.name AS user_name
        FROM schedules s
        JOIN shifts sh ON s.shift_id = sh.id
        JOIN users u ON s.user_id = u.id
        WHERE s.work_date IN ($inQuery)
        AND s.status = 'assigned'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($weekDays);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($schedules as $sched) {
        $uid = $sched['user_id'];
        $date = $sched['work_date'];
        if (!isset($schedulesByUserDate[$uid])) $schedulesByUserDate[$uid] = [];
        if (!isset($schedulesByUserDate[$uid][$date])) $schedulesByUserDate[$uid][$date] = [];
        $schedulesByUserDate[$uid][$date][] = $sched;
    }
}
?>

<?php require_once '../includes/head.php'; ?>
<?php require_once '../includes/header.php'; ?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-semibold mb-6 text-center">Planning des Shifts</h1>

    <!-- View Toggle + Navigation Buttons -->
    <div class="flex flex-wrap justify-center space-x-6 space-y-2 mb-6 max-w-lg mx-auto">
        <!-- Toggle Button -->
        <?php if ($weekStart): ?>
            <a href="schedule.php" 
               class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition">
                Voir le Mois
            </a>
        <?php else: ?>
            <?php 
                // For toggle to week view, default to first Monday of current month
                $firstMonday = clone $firstOfMonth;
                $firstMonday->modify('Monday this week');
                if ($firstMonday < $firstOfMonth) {
                    $firstMonday->modify('+7 days');
                }
            ?>
            <a href="schedule.php?day=<?= $firstMonday->format('Y-m-d') ?>" 
               class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition">
                Voir la Semaine
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$weekStart): ?>
        <div class="my-6 w-full h-[10vh] flex justify-center items-center overflow-x-auto">
            <div class="flex gap-6 min-w-max">
                <!-- Prev Button -->
                <a href="<?= buildUrlMonth($prevMonth->format('Y'), $prevMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    &larr; Mois Précédent
                </a>

                <!-- Next Button -->
                <a href="<?= buildUrlMonth($nextMonth->format('Y'), $nextMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    Mois Suivant &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Month View -->
    <section class="<?= $weekStart ? 'opacity-50 pointer-events-none' : '' ?>">
        <div class="grid grid-cols-7 gap-2 text-center rounded-lg max-w-5xl mx-auto
                    sm:grid-cols-7 
                    xs:grid-cols-4 xs:gap-1
                    overflow-x-auto">
            <?php 
            $daysOfWeek = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            foreach ($daysOfWeek as $dayName): ?>
                <div class="font-bold py-2 border-b border-gray-300 bg-gray-100 text-xs sm:text-sm"><?= $dayName ?></div>
            <?php endforeach; ?>

            <?php foreach ($weeks as $week): ?>
                <?php foreach ($week as $day): 
                    $dateStr = $day->format('Y-m-d');
                    $isCurrentMonth = $day->format('m') === sprintf('%02d', $month);
                    $isToday = $dateStr === date('Y-m-d');
                    ?>
                    <div 
                        class="min-h-[100px] sm:min-h-[120px] border border-gray-300 p-1 sm:p-2 cursor-pointer rounded-lg
                        <?= $isCurrentMonth ? 'hover:bg-indigo-50' : 'bg-gray-100 text-gray-400' ?>
                        <?= $isToday ? 'bg-indigo-100 border-indigo-500' : '' ?>"
                        onclick="location.href='schedule.php?day=<?= $dateStr ?>'">
                        <div class="text-right font-semibold text-sm sm:text-base"><?= $day->format('j') ?></div>
                        <div class="mt-1 sm:mt-2 space-y-1 text-left text-xs sm:text-sm max-h-20 sm:max-h-24 overflow-auto">
                            <?php if (isset($monthSchedules[$dateStr])): ?>
                                <?php foreach ($monthSchedules[$dateStr] as $userName): ?>
                                    <div><?= htmlspecialchars($userName) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-gray-400 italic">Aucun employé</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="my-12 w-full h-[10vh] flex justify-center items-center overflow-x-auto">
        <div class="flex gap-6 min-w-max flex-wrap justify-center">
            <!-- Prev Button -->
            <?php if (!$weekStart): ?>
                <a href="<?= buildUrlMonth($prevMonth->format('Y'), $prevMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    &larr; Mois Précédent
                </a>
            <?php else: ?>
                <a href="<?= buildUrlWeek($prevWeekStart) ?>"
                   class="px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    &larr; Semaine Précédente
                </a>
            <?php endif; ?>

            <!-- Next Button -->
            <?php if (!$weekStart): ?>
                <a href="<?= buildUrlMonth($nextMonth->format('Y'), $nextMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    Mois Suivant &rarr;
                </a>
            <?php else: ?>
                <a href="<?= buildUrlWeek($nextWeekStart) ?>"
                   class="px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs">
                    Semaine Suivante &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Week View -->
    <?php if ($weekStart): ?>
        <section class="max-w-7xl mx-auto">
            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 sticky left-0 bg-gray-100 z-20 text-xs sm:text-sm whitespace-nowrap">Employé</th>
                            <?php
                            $weekDays = [];
                            for ($i = 0; $i < 7; $i++) {
                                $d = clone $weekStart;
                                $d->modify("+$i day");
                                $weekDays[] = $d;
                            }
                            foreach ($weekDays as $d): 
                                $dayName = $d->format('D'); // Mon, Tue...
                                $dayNumber = $d->format('j');
                                $isToday = $d->format('Y-m-d') === date('Y-m-d');
                            ?>
                                <th class="border border-gray-300 px-3 py-2 text-center text-xs sm:text-sm whitespace-nowrap
                                <?= $isToday ? 'bg-indigo-200' : '' ?>">
                                    <div><?= $dayName ?></div>
                                    <div class="font-semibold"><?= $dayNumber ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="even:bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 sticky left-0 bg-white font-medium text-xs sm:text-sm whitespace-nowrap max-w-[140px] truncate"><?= htmlspecialchars($user['name']) ?></td>
                                <?php foreach ($weekDays as $d): 
                                    $dateKey = $d->format('Y-m-d');
                                    $shiftsForDay = $schedulesByUserDate[$user['id']][$dateKey] ?? [];
                                ?>
                                    <td class="border border-gray-300 px-2 py-1 text-center align-top text-xs sm:text-sm max-w-[100px] min-w-[100px]">
                                        <?php if (count($shiftsForDay) > 0): ?>
                                            <?php foreach ($shiftsForDay as $shift): ?>
                                                <div class="mb-1 p-1 bg-indigo-100 rounded text-indigo-800 truncate">
                                                    <?= htmlspecialchars($shift['shift_name']) ?><br>
                                                    <small class="text-gray-600"><?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-gray-400 italic">Aucun</div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>

<style>
/* Custom breakpoint for very small screens */
@media (max-width: 480px) {
    /* Month view: fewer columns for days of week */
    .grid-cols-7 {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }
    /* Allow horizontal scroll on month view */
    section > .grid {
        overflow-x: auto;
        min-width: 280px;
    }
    /* Week view table: smaller font size */
    table {
        font-size: 0.75rem;
    }
}

/* Sticky header and first column */
thead th {
    position: sticky;
    top: 0;
    background: inherit;
    z-index: 10;
}

tbody td:first-child {
    position: sticky;
    left: 0;
    background: white;
    z-index: 5;
}

/* Horizontal scroll on small screens for week table */
@media (max-width: 640px) {
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}
</style>