<?php
session_start();

if (!isset($_GET['view']) && !isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['week'])) {
    $currentYear = date('Y');
    $currentMonth = date('m');
    header("Location: ?view=month&year=$currentYear&month=$currentMonth");
    exit;
}

require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'month';

$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));
$week = (int)($_GET['week'] ?? date('W'));

function prevMonth($year, $month) {
    $date = DateTime::createFromFormat('Y-m-d', "$year-$month-01");
    $date->modify('-1 month');
    return [$date->format('Y'), $date->format('m')];
}
function nextMonth($year, $month) {
    $date = DateTime::createFromFormat('Y-m-d', "$year-$month-01");
    $date->modify('+1 month');
    return [$date->format('Y'), $date->format('m')];
}
function prevWeek($year, $week) {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $dto->modify('-1 week');
    return [(int)$dto->format('Y'), (int)$dto->format('W')];
}
function nextWeek($year, $week) {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $dto->modify('+1 week');
    return [(int)$dto->format('Y'), (int)$dto->format('W')];
}

function getUserShifts($pdo, $user_id, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT s.work_date, sh.name, sh.start_time, sh.end_time
        FROM schedules s
        JOIN shifts sh ON s.shift_id = sh.id
        WHERE s.user_id = :user_id 
          AND s.work_date BETWEEN :start AND :end
        ORDER BY s.work_date, sh.start_time
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'start' => $startDate,
        'end' => $endDate,
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $shiftsByDate = [];
    foreach ($results as $row) {
        if (!isset($shiftsByDate[$row['work_date']])) {
            $shiftsByDate[$row['work_date']] = [];
        }
        $shiftsByDate[$row['work_date']][] = $row;
    }
    return $shiftsByDate;
}

function getWeekFromDate($dateStr) {
    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    return (int)$date->format('W');
}
?>

<?php require_once '../includes/head.php'; ?>
<?php require_once '../includes/header.php'; ?>

<style>
/* myRHIS style dots */
.shift-dots {
    display: flex;
    gap: 3px;
    margin-top: 4px;
    justify-content: center;
    align-items: center;
    height: 50%;
}
.shift-dot {
    width: 12px;
    height: 12px;
    background-color: #4F46E5; /* Indigo-600 */
    border-radius: 50%;
}

/* Month view */
.month-table td {
    height: 100px; /* taller day cells */
    vertical-align: top;
}

/* Prev/Next buttons container */
.nav-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.nav-buttons a {
    flex: 1 1 120px;
    text-align: center;
    padding: 0.4rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.375rem;
    background-color: #fff;
    color: #374151; /* gray-700 */
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.2s;
}

.nav-buttons a:hover {
    background-color: #e0e7ff; /* indigo-100 */
}

/* Week view - mobile vertical stacked days */
@media (max-width: 640px) {
    .week-table {
        display: block;
    }
    .week-table thead {
        display: none;
    }
    .week-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        padding: 0.75rem;
        background-color: #f9fafb;
    }
    .week-table tbody tr td {
        display: block;
        padding: 0.25rem 0;
        border: none;
    }
    .week-table tbody tr td:first-child {
        font-weight: 700;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
        color: #4F46E5; /* indigo-600 */
    }
}

/* Ensure month day link fills whole cell */
.month-table td a {
    display: block;
    width: 100%;
    height: 100%;
    text-decoration: none;
    color: inherit;
}

/* Hover for month day */
.month-table td a:hover {
    background-color: #e0e7ff;
    border-radius: 0.25rem;
}
</style>

<div class="min-h-[80vh]">
    <div class="max-w-[95vw] md:max-w-[75vw] mx-auto mt-8 p-4 sm:p-6 bg-white rounded shadow">

        <h1 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6">Mon planning</h1>

        <!-- View toggle & navigation -->
        <div class="mb-4 flex flex-wrap sm:flex-nowrap items-center space-x-0 sm:space-x-4 space-y-2 sm:space-y-0 flex-col">

            <div class="flex gap-12 mb-4">
                <a href="?view=month&year=<?= $year ?>&month=<?= $month ?>" 
                    class="px-3 py-1 rounded text-sm sm:text-base <?= $view === 'month' ? 'bg-indigo-600 text-white' : 'bg-gray-200' ?> min-w-[15vw] flex items-center justify-center md:min-w-[5vw]">
                    Mois
                </a>
                <a href="?view=week&year=<?= $year ?>&week=<?= $week ?>" 
                    class="px-3 py-1 rounded text-sm sm:text-base <?= $view === 'week' ? 'bg-indigo-600 text-white' : 'bg-gray-200' ?> min-w-[15vw] flex items-center justify-center md:min-w-[5vw]">
                    Semaine
                </a>
            </div>

            <div>
                <div class="ml-auto nav-buttons">

                <?php if ($view === 'month'):
                    list($prevYear, $prevMonth) = prevMonth($year, $month);
                    list($nextYear, $nextMonth) = nextMonth($year, $month);
                ?>
                    <div class="flex items-center justify-center gap-2">
                        <a href="?view=month&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" 
                            aria-label="Mois précédent" class="text-xs md:text-base min-w-[35%] md:min-w-[12vw] flex items-center justify-center">
                            &laquo; Précédent
                        </a>
                        <span class="font-semibold text-lg whitespace-nowrap px-3 py-1"><?= sprintf("%04d-%02d", $year, $month) ?></span>
                        <a href="?view=month&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" 
                            aria-label="Mois suivant" class="text-xs md:text-base min-w-[35%] md:min-w-[12vw] flex items-center justify-center">
                            Suivant &raquo;
                        </a>
                    </div>

                <?php elseif ($view === 'week'):
                    list($prevYear, $prevWeek) = prevWeek($year, $week);
                    list($nextYear, $nextWeek) = nextWeek($year, $week);
                ?>
                    <div class="flex items-center justify-center gap-2">
                        <a href="?view=week&year=<?= $prevYear ?>&week=<?= $prevWeek ?>" 
                            aria-label="Semaine précédente" class="text-xs md:text-base min-w-[35%] md:min-w-[6vw] flex items-center justify-center">
                            &laquo; Précédent
                        </a>
                        <div class="px-3 py-1 flex flex-col items-center justify-center">
                            <span class="font-semibold text-lg whitespace-nowrap text-sm"><?= $year ?></span>
                            <span class="font-semibold text-lg whitespace-nowrap text-sm">Semaine <?= $week ?></span>
                        </div>
                        <a href="?view=week&year=<?= $nextYear ?>&week=<?= $nextWeek ?>" 
                            aria-label="Semaine suivante" class="text-xs md:text-base min-w-[35%] md:min-w-[6vw] flex items-center justify-center">
                            Suivant &raquo;
                        </a>
                    </div>
                <?php endif; ?>

                </div>
            </div>
        </div>

    <?php if ($view === 'month'):

        $firstDayOfMonth = new DateTime("$year-$month-01");
        $daysInMonth = (int)$firstDayOfMonth->format('t');
        $startDayOfWeek = (int)$firstDayOfMonth->format('N');

        $startDisplay = clone $firstDayOfMonth;
        $startDisplay->modify('-' . ($startDayOfWeek - 1) . ' days');

        $endDisplay = new DateTime("$year-$month-$daysInMonth");
        $endDayOfWeek = (int)$endDisplay->format('N');
        if ($endDayOfWeek < 7) {
            $endDisplay->modify('+' . (7 - $endDayOfWeek) . ' days');
        }

        $shiftsByDate = getUserShifts($pdo, $user_id, $startDisplay->format('Y-m-d'), $endDisplay->format('Y-m-d'));

        $dayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        ?>

        <div class="overflow-x-auto">
            <table class="month-table w-full border-collapse border border-gray-300 text-xs sm:text-sm">
                <thead class="bg-indigo-100">
                    <tr>
                        <?php foreach ($dayLabels as $label): ?>
                            <th class="border border-gray-300 p-2 text-center"><?= $label ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $currentDate = clone $startDisplay;
                    while ($currentDate <= $endDisplay) {
                        if ($currentDate->format('N') == 1) {
                            echo '<tr>';
                        }

                        $dayNum = $currentDate->format('j');
                        $isCurrentMonth = $currentDate->format('m') === sprintf('%02d', $month);
                        $dateStr = $currentDate->format('Y-m-d');
                        $weekNum = getWeekFromDate($dateStr);

                        $shiftCount = isset($shiftsByDate[$dateStr]) ? count($shiftsByDate[$dateStr]) : 0;
                        $dotsToShow = min($shiftCount, 3);

                        $link = "?view=week&year=" . $currentDate->format('Y') . "&week=$weekNum";

                        // Tooltip text for shift count (only if shifts)
                        $tooltip = $shiftCount > 0 ? "$shiftCount shift(s) worked" : '';

                        echo '<td class="border border-gray-300 p-1 align-top ' . ($isCurrentMonth ? '' : 'text-gray-400') . '">';
                        echo '<a href="' . $link . '" title="' . htmlspecialchars($tooltip) . '" class="flex flex-col h-full justify-between">';
                        echo '<span class="font-semibold">' . $dayNum . '</span>';

                        if ($shiftCount > 0) {
                            echo '<div class="shift-dots">';
                            for ($i = 0; $i < $dotsToShow; $i++) {
                                echo '<span class="shift-dot"></span>';
                            }
                            echo '</div>';
                        }
                        echo '</a>';
                        echo '</td>';

                        if ($currentDate->format('N') == 7) {
                            echo '</tr>';
                        }
                        $currentDate->modify('+1 day');
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($view === 'week'):

    // Get first day of the ISO week
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $startOfWeek = clone $dto;
    $endOfWeek = clone $dto;
    $endOfWeek->modify('+6 days');

    $shiftsByDate = getUserShifts($pdo, $user_id, $startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d'));
    $dayLabels = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    // Define expected weekly hours (adjust this as needed)
    $expectedWeeklyHours = 35;
?>

<div class="overflow-x-auto hidden md:block">
    <table class="week-table w-full border-collapse border border-gray-300 text-xs sm:text-sm min-w-[320px]">
        <thead>
            <tr class="bg-indigo-100">
                <?php foreach ($dayLabels as $label): ?>
                    <th class="border border-gray-300 p-2 text-center"><?= $label ?></th>
                <?php endforeach; ?>
                <th class="border border-gray-300 p-2 text-center">Total H</th> <!-- New header -->
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                $dateIter = clone $startOfWeek;
                $totalHours = 0;

                for ($i = 0; $i < 7; $i++) {
                    $dateStr = $dateIter->format('Y-m-d');
                    echo '<td class="border border-gray-300 p-2 align-top">';
                    echo '<div class="font-semibold mb-1">' . $dateIter->format('d/m') . '</div>';

                    if (isset($shiftsByDate[$dateStr])) {
                        foreach ($shiftsByDate[$dateStr] as $shift) {
                            echo '<div class="text-xs text-indigo-700 border border-indigo-300 rounded p-1 mb-1">';
                            echo htmlspecialchars($shift['name']) . '<br>';
                            echo htmlspecialchars($shift['start_time']) . ' - ' . htmlspecialchars($shift['end_time']);
                            echo '</div>';

                            // Calculate total hours for the week
                            $start = new DateTime($shift['start_time']);
                            $end = new DateTime($shift['end_time']);
                            $interval = $start->diff($end);
                            $totalHours += $interval->h + $interval->i / 60;
                        }
                    }
                    echo '</td>';
                    $dateIter->modify('+1 day');
                }

                // Determine color for total hours
                if ($totalHours < $expectedWeeklyHours) {
                    $colorClass = 'bg-orange-300';
                } elseif ($totalHours == $expectedWeeklyHours) {
                    $colorClass = 'bg-green-300';
                } else {
                    $colorClass = 'bg-red-300';
                }

                // Add total hours cell at the end
                echo '<td class="border border-gray-300 p-2 text-center ' . $colorClass . ' font-semibold">';
                echo round($totalHours, 2) . 'h';
                echo '</td>';
                ?>
            </tr>
        </tbody>
    </table>
</div>

<!-- Mobile vertical stacked week -->
<div class="sm:hidden mt-4">
    <?php 
    $dateIter = clone $startOfWeek;
    $totalHours = 0;

    for ($i = 0; $i < 7; $i++):
        $dateStr = $dateIter->format('Y-m-d');
    ?>
        <div class="border border-gray-300 rounded p-4 mb-4 bg-gray-50">
            <div class="font-semibold text-indigo-600 mb-2"><?= $dayLabels[$i] . ' ' . $dateIter->format('d/m') ?></div>
            <?php if (isset($shiftsByDate[$dateStr])): ?>
                <?php foreach ($shiftsByDate[$dateStr] as $shift): ?>
                    <div class="text-sm text-indigo-700 border border-indigo-300 rounded p-2 mb-2">
                        <?= htmlspecialchars($shift['name']) ?><br>
                        <?= htmlspecialchars($shift['start_time']) ?> - <?= htmlspecialchars($shift['end_time']) ?>
                    </div>
                    <?php
                        $start = new DateTime($shift['start_time']);
                        $end = new DateTime($shift['end_time']);
                        $interval = $start->diff($end);
                        $totalHours += $interval->h + $interval->i / 60;
                    ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-gray-500 italic">Aucun shift</div>
            <?php endif; ?>
        </div>
    <?php
        $dateIter->modify('+1 day');
    endfor;

    // Mobile total hours display
    if ($totalHours < $expectedWeeklyHours) {
        $colorClass = 'bg-orange-300';
    } elseif ($totalHours == $expectedWeeklyHours) {
        $colorClass = 'bg-green-300';
    } else {
        $colorClass = 'bg-red-300';
    }
    ?>
    <div class="border border-gray-300 rounded p-4 mb-4 text-center font-semibold <?= $colorClass ?>">
        Total semaine: <?= round($totalHours, 2) ?>h
    </div>
</div>

<?php endif; ?> 

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>