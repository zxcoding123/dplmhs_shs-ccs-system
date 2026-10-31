<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/academic.php';

// ---------- Page data ----------
$teacher_name = $_SESSION['teacher_name'] ?? $_SESSION['full_name'] ?? '';
$advisory_classes = [];
$schedules = [];
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

try {
    $advisory_stmt = $pdo->prepare("
        SELECT sa.id, sa.class_advising, COUNT(stu.id) AS student_count
        FROM staff_advising sa
        LEFT JOIN students_advising sad ON sad.staff_advising_id = sa.id
        LEFT JOIN students stu ON stu.id = sad.student_id
        WHERE sa.fullName = :teacher_name
        GROUP BY sa.id, sa.class_advising
        ORDER BY sa.class_advising
    ");
    $advisory_stmt->execute(['teacher_name' => $teacher_name]);
    $advisory_classes = $advisory_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error fetching advisory classes: ' . $e->getMessage());
}

// Only the teacher's own advisory sections can be viewed
$advisory_codes = array_column($advisory_classes, 'class_advising');
$current_class_id = $_GET['class_id'] ?? '';
if (!in_array($current_class_id, $advisory_codes, true)) {
    $current_class_id = $advisory_codes[0] ?? '';
}

$current_class = null;
foreach ($advisory_classes as $class) {
    if ($class['class_advising'] === $current_class_id) {
        $current_class = $class;
    }
}

if ($current_class_id !== '') {
    try {
        $sched_stmt = $pdo->prepare("
            SELECT sch.meeting_days, sch.start_time, sch.end_time,
                sub.name AS subject_name, c.teacher AS teacher_name
            FROM classes c
            JOIN subjects sub ON sub.id = c.subject_id
            JOIN subjects_schedules sch ON sch.subject_id = sub.id
            WHERE c.name = :class_name AND c.is_archived = 0
            ORDER BY sch.start_time
        ");
        $sched_stmt->execute(['class_name' => $current_class_id]);
        $schedules = $sched_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching class schedule: ' . $e->getMessage());
    }
}

// meeting_days can list several days ("Monday, Wednesday"), so file each row under every day it names
$schedule_by_day = array_fill_keys($week_days, []);
foreach ($schedules as $sch) {
    foreach ($week_days as $day) {
        if (stripos($sch['meeting_days'], $day) !== false) {
            $schedule_by_day[$day][] = $sch;
        }
    }
}

// Weekend columns only show when something is scheduled on them
$shown_days = array_filter($week_days, function ($day) use ($schedule_by_day) {
    return !in_array($day, ['Saturday', 'Sunday'], true) || !empty($schedule_by_day[$day]);
});

$today = date('l');
$now = date('H:i:s');
$today_classes = $schedule_by_day[$today] ?? [];
$weekly_meetings = array_sum(array_map('count', $schedule_by_day));
$subject_count = count(array_unique(array_column($schedules, 'subject_name')));
$parsed = $current_class_id !== '' ? shs_parse_section($current_class_id) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DPLMHS-SHS | Student Management System</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?php echo csms_asset('../admin/css/admin-ui.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?php echo csms_asset('css/staff-ui.css', __DIR__); ?>" rel="stylesheet">
    <script src="<?php echo csms_asset('js/staff-ui.js', __DIR__); ?>" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?php echo csms_asset('css/dashboard.css', __DIR__); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Section switcher */
        .cs-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
        }

        .cs-tabs .fd-btn-ghost.is-current {
            background-color: var(--au-navy);
            border-color: var(--au-navy);
            color: #fff !important;
        }

        .cs-tabs .fd-count {
            margin-left: 0.15rem;
        }

        .cs-tabs .is-current .fd-count {
            background-color: rgba(255, 255, 255, 0.18);
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php') ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white"><b>DPLMHS</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="fd-hero">
                        <div>
                            <div class="fd-eyebrow">Advisory</div>
                            <h1 class="fd-title">Class Schedules</h1>
                            <p class="fd-subtitle">The weekly timetable of each of your advisory sections.</p>
                        </div>
                        <div class="fd-quick">
                            <a class="fd-btn-ghost" href="class_management.php"><i class="bi bi-arrow-left"></i> Back to Class Management</a>
                        </div>
                    </div>

                    <?php if (empty($advisory_classes)): ?>
                        <div class="card fd-panel mb-4">
                            <div class="card-body">
                                <div class="fd-empty">
                                    <i class="bi bi-person-badge"></i>
                                    <p>You are not currently assigned as an adviser to any section.</p>
                                    <p class="small mt-1">Once the administrator assigns you a section, its schedule will show up here.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Section switcher -->
                        <?php if (count($advisory_classes) > 1): ?>
                            <nav class="cs-tabs" aria-label="Advisory sections">
                                <?php foreach ($advisory_classes as $class):
                                    $is_current = $class['class_advising'] === $current_class_id;
                                ?>
                                    <a href="class_schedule.php?class_id=<?= urlencode($class['class_advising']) ?>"
                                        class="fd-btn-ghost <?= $is_current ? 'is-current' : '' ?>"
                                        <?= $is_current ? 'aria-current="page"' : '' ?>>
                                        <?= htmlspecialchars($class['class_advising']) ?>
                                        <span class="fd-count"><?= (int) $class['student_count'] ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>

                        <!-- Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-diagram-3"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= htmlspecialchars($current_class_id) ?></div>
                                        <div class="fd-stat-label">
                                            <?= $parsed ? htmlspecialchars($parsed['grade_level'] . ' · ' . shs_strand_label($parsed['strand'])) : 'Advisory section' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-people"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= (int) ($current_class['student_count'] ?? 0) ?></div>
                                        <div class="fd-stat-label">Students</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-book"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $subject_count ?></div>
                                        <div class="fd-stat-label">Subject<?= $subject_count === 1 ? '' : 's' ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-calendar-week"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $weekly_meetings ?></div>
                                        <div class="fd-stat-label">Meetings per week</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today -->
                        <div class="card fd-panel mb-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title"><i class="bi bi-clock"></i> Today &middot; <?= $today ?> <span class="fd-count"><?= count($today_classes) ?></span></h5>
                                    <p class="fd-panel-sub">Classes <?= htmlspecialchars($current_class_id) ?> has today.</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($today_classes)): ?>
                                    <div class="fd-empty">
                                        <i class="bi bi-cup-hot"></i>
                                        <p>No classes scheduled for today.</p>
                                    </div>
                                <?php else: ?>
                                    <ul class="fd-schedule">
                                        <?php foreach ($today_classes as $sch):
                                            if ($now >= $sch['end_time']) {
                                                $status = ['is-done', 'Done'];
                                            } elseif ($now >= $sch['start_time']) {
                                                $status = ['is-now', 'In progress'];
                                            } else {
                                                $status = ['is-upcoming', 'Upcoming'];
                                            }
                                        ?>
                                            <li>
                                                <div class="fd-time">
                                                    <strong><?= date('g:i A', strtotime($sch['start_time'])) ?></strong>
                                                    <span><?= date('g:i A', strtotime($sch['end_time'])) ?></span>
                                                </div>
                                                <div>
                                                    <div class="fd-class-name"><?= htmlspecialchars($sch['subject_name']) ?></div>
                                                    <div class="fd-class-meta"><i class="bi bi-person"></i> <?= htmlspecialchars($sch['teacher_name']) ?></div>
                                                </div>
                                                <span class="fd-status <?= $status[0] ?>"><?= $status[1] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Weekly timetable -->
                        <div class="card fd-panel mb-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title"><i class="bi bi-calendar-week"></i> Weekly Schedule</h5>
                                    <p class="fd-panel-sub">Every subject <?= htmlspecialchars($current_class_id) ?> takes, by day.</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($schedules)): ?>
                                    <div class="fd-empty">
                                        <i class="bi bi-calendar-x"></i>
                                        <p>No subject schedules have been set for this section yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="cs-week">
                                        <?php foreach ($shown_days as $day): ?>
                                            <section class="cs-day <?= $day === $today ? 'is-today' : '' ?>">
                                                <div class="cs-day-head">
                                                    <span><?= $day ?></span>
                                                    <?php if ($day === $today): ?><span>Today</span><?php endif; ?>
                                                </div>
                                                <div class="cs-day-body">
                                                    <?php if (empty($schedule_by_day[$day])): ?>
                                                        <div class="cs-day-empty">No classes</div>
                                                    <?php else: ?>
                                                        <?php foreach ($schedule_by_day[$day] as $sch): ?>
                                                            <div class="cs-slot">
                                                                <div class="cs-slot-time">
                                                                    <?= date('g:i A', strtotime($sch['start_time'])) ?> &ndash; <?= date('g:i A', strtotime($sch['end_time'])) ?>
                                                                </div>
                                                                <div class="cs-slot-subject"><?= htmlspecialchars($sch['subject_name']) ?></div>
                                                                <div class="cs-slot-teacher"><?= htmlspecialchars($sch['teacher_name']) ?></div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <script src="<?php echo csms_asset('js/app.js', __DIR__); ?>"></script>

    <?php include('processes/server/modals.php'); ?>

    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();

            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
    </script>

</body>

</html>
<?php include('processes/server/alerts.php'); ?>
