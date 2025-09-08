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
$stmt = $pdo->query("SELECT id, name, hpw FROM users ORDER BY name");
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

    <!-- Month View -->
    <?php if (!$weekStart): ?>
        <div class="my-6 w-full h-[10vh] flex justify-center items-center overflow-x-auto">
            <div class="flex gap-6 min-w-max">
                <a href="<?= buildUrlMonth($prevMonth->format('Y'), $prevMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs min-w-[7.5vw] text-center">
                    &larr; Mois Précédent
                </a>
                <a href="<?= buildUrlMonth($nextMonth->format('Y'), $nextMonth->format('m')) ?>"
                   class="md:px-6 md:py-3 px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs min-w-[7.5vw] text-center">
                    Mois Suivant &rarr;
                </a>
            </div>
        </div>

        <section>
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
                            <div class="mt-1 sm:mt-2 space-y-1 text-left text-xs sm:text-sm max-h-20 sm:max-h-24">
                                <?php if (isset($monthSchedules[$dateStr])): ?>
                                    <?php
                                        $usersForDay = array_values($monthSchedules[$dateStr]);
                                        $displayCount = 2;
                                        $totalCount = count($usersForDay);
                                        $extraCount = $totalCount - $displayCount;
                                    ?>
                                    <?php for ($i = 0; $i < min($totalCount, $displayCount); $i++): ?>
                                        <div><?= htmlspecialchars($usersForDay[$i]) ?></div>
                                    <?php endfor; ?>
                                    <?php if ($extraCount > 0): ?>
                                        <div class="text-indigo-600 font-semibold">+<?= $extraCount ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-gray-400 italic">Aucun employé</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Week View -->
<?php if ($weekStart): ?>
<div class="min-h-[61vh]">
    <div class="my-6 w-full h-[10vh] flex justify-center items-center overflow-x-auto">
        <div class="flex gap-6 min-w-max">
            <a href="<?= buildUrlWeek($prevWeekStart) ?>"
               class="px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs min-w-[8vw] text-center">
                &larr; Semaine Précédente
            </a>
            <a href="<?= buildUrlWeek($nextWeekStart) ?>"
               class="px-3 py-3 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition font-semibold whitespace-nowrap text-xs min-w-[8vw] text-center">
                Semaine Suivante &rarr;
            </a>
        </div>
    </div>

    <section class="max-w-7xl mx-auto">
        <div class="overflow-x-auto border border-gray-300 rounded-lg shadow">
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
                            echo '<th class="border border-gray-300 px-3 py-2 text-xs sm:text-sm">' . $d->format('D j') . '</th>';
                        }
                        ?>
                        <th class="border border-gray-300 px-3 py-2 text-xs sm:text-sm">Total Heures</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $uid = $user['id'];
                        $weeklyTotalHours = 0;
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="border border-gray-300 px-3 py-2 sticky left-0 bg-gray-50 z-10 text-xs sm:text-sm font-medium"><?= htmlspecialchars($user['name']) ?></td>
                        <?php foreach ($weekDays as $day):
                            $dateStr = $day->format('Y-m-d');
                            $shiftsForDay = $schedulesByUserDate[$uid][$dateStr] ?? [];
                            $dailyHours = 0;
                        ?>
                        <td class="border border-gray-300 px-3 py-2 align-top text-xs sm:text-sm">
                            <?php foreach ($shiftsForDay as $shift):
                                $start = new DateTime($shift['start_time']);
                                $end = new DateTime($shift['end_time']);
                                $hours = $start->diff($end)->h + $start->diff($end)->i / 60;
                                $dailyHours += $hours;
                            ?>
                            <div class="bg-indigo-100 text-indigo-900 rounded-lg px-2 py-1 mb-1 shadow-sm hover:shadow-md transition relative group cursor-pointer">
                                <?= $start->format('H:i') ?>-<?= $end->format('H:i') ?>
                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 bg-gray-700 text-white text-xs rounded px-1 py-0.5 opacity-0 group-hover:opacity-100 transition">
                                    <?= number_format($hours, 2) ?>h
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </td>
                        <?php 
                            $weeklyTotalHours += $dailyHours;
                        endforeach; 
                        
                        $expectedHours = htmlspecialchars($user['hpw']);
                        $bgColor = $weeklyTotalHours == $expectedHours ? 'from-green-300 to-green-400' :
                                   ($weeklyTotalHours < $expectedHours ? 'from-orange-300 to-orange-400' : 'from-red-300 to-red-400');
                        ?>
                        <td class="border border-gray-300 px-3 py-2 text-xs sm:text-sm font-semibold text-center bg-gradient-to-r <?= $bgColor ?>">
                            <?= number_format($weeklyTotalHours, 2) ?>h
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php endif; ?>


</div>

<?php require_once '../includes/footer.php'; ?>

<style>
@media (max-width: 480px) {
    .grid-cols-7 {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }
    section > .grid {
        overflow-x: auto;
        min-width: 280px;
    }
    table {
        font-size: 0.75rem;
    }
}

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

@media (max-width: 640px) {
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}
</style>