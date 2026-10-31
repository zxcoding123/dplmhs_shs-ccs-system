<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include __DIR__ . '/../database/connection.php';

// Close out meetings whose end time has passed (on their own date, not just today's clock)
$pdo->exec("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE status NOT IN ('Finished', 'Cancelled')
      AND STR_TO_DATE(CONCAT(date, ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");

// ---------- Page data ----------
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$semester_id = isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;

$classData = null;
$schoolYear = null;
$meetings = [];
$schedules = [];
$semester_start = null;
$semester_end = null;

if ($class_id) {
    $stmt = $pdo->prepare("SELECT id, name, subject, subject_id, teacher, semester, is_archived, type FROM classes WHERE id = :class_id LIMIT 1");
    $stmt->execute(['class_id' => $class_id]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($classData) {
    $stmt = $pdo->prepare("SELECT id, school_year, start_date, end_date FROM semester WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $classData['semester']]);
    $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($semester_found) {
        $schoolYear = (int) $semester_found['school_year'];
        $semester_start = $semester_found['start_date'];
        $semester_end = $semester_found['end_date'];
        if (!$semester_id) {
            $semester_id = (int) $semester_found['id'];
        }
    }

    try {
        $stmt = $pdo->prepare("
            SELECT m.id, m.date, m.start_time, m.end_time, m.type, m.status,
                COUNT(a.id) AS roster,
                SUM(a.status = 'present') AS present,
                SUM(a.status = 'late') AS late,
                SUM(a.status = 'absent') AS absent
            FROM classes_meetings m
            LEFT JOIN attendance a ON a.meeting_id = m.id
            WHERE m.class_id = :class_id
            GROUP BY m.id
            ORDER BY m.date, STR_TO_DATE(m.start_time, '%h:%i %p')
        ");
        $stmt->execute(['class_id' => $class_id]);
        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching class meetings: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->prepare("
            SELECT meeting_days, start_time, end_time
            FROM subjects_schedules
            WHERE subject_id = :subject_id
            ORDER BY FIELD(meeting_days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time
        ");
        $stmt->execute(['subject_id' => $classData['subject_id']]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching subject schedule: ' . $e->getMessage());
    }
}

// Schedule times are stored as "07:00"; show them like meeting times
foreach ($schedules as &$sch) {
    $sch['start_label'] = date('g:i A', strtotime($sch['start_time']));
    $sch['end_label'] = date('g:i A', strtotime($sch['end_time']));
}
unset($sch);

$is_archived = $classData && (int) $classData['is_archived'] === 1;
$today = date('Y-m-d');

// Times are stored as "1:30 PM"; the forms and calendar need 24-hour values
foreach ($meetings as &$m) {
    $start = DateTime::createFromFormat('g:i A', $m['start_time']);
    $end = DateTime::createFromFormat('g:i A', $m['end_time']);
    $m['start_24'] = $start ? $start->format('H:i') : '';
    $m['end_24'] = $end ? $end->format('H:i') : '';
    $m['roster'] = (int) $m['roster'];
    $m['attended'] = (int) $m['present'] + (int) $m['late'];
}
unset($m);

$counts = ['total' => count($meetings), 'finished' => 0, 'upcoming' => 0, 'cancelled' => 0];
$upcoming = [];
$recent = [];
$attended_total = 0;
$roster_total = 0;

foreach ($meetings as $m) {
    if ($m['status'] === 'Cancelled') {
        $counts['cancelled']++;
    } elseif ($m['status'] === 'Finished' || $m['status'] === 'Ended') {
        $counts['finished']++;
        $recent[] = $m;
        $attended_total += $m['attended'];
        $roster_total += $m['roster'];
    } elseif ($m['date'] >= $today) {
        $counts['upcoming']++;
        $upcoming[] = $m;
    }
}

$upcoming = array_slice($upcoming, 0, 5);
$recent = array_slice(array_reverse($recent), 0, 5);
$attendance_rate = $roster_total > 0 ? round($attended_total / $roster_total * 100) : null;

$status_class = [
    'Scheduled' => 'is-upcoming',
    'Ongoing' => 'is-now',
    'Rescheduled' => 'is-pending',
    'Finished' => 'is-done',
    'Ended' => 'is-done',
    'Cancelled' => 'is-rejected',
];

$general_url = 'class_attendance_general.php?class_id=' . $class_id . '&semester_id=' . $semester_id;

// Default range for generating from the schedule: today (or the semester start) to the semester end
$generate_from = $semester_start && $semester_start > $today ? $semester_start : $today;
$generate_to = $semester_end && $semester_end >= $generate_from ? $semester_end : date('Y-m-d', strtotime($generate_from . ' +4 weeks'));

function ca_meeting_date($date)
{
    return date('D, M j', strtotime($date));
}
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        /* Meeting status colours, shared by the calendar, legend and lists */
        .ca-page {
            --ca-scheduled: var(--au-navy);
            --ca-ongoing: #1c8a5f;
            --ca-rescheduled: var(--au-gold);
            --ca-finished: #8b909c;
            --ca-cancelled: var(--au-crimson);
        }

        .ca-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.6rem;
        }

        .ca-meta .fd-chip i {
            color: var(--au-gold);
            margin-right: 0.2rem;
        }

        .ca-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
            font-size: 0.78rem;
            color: var(--au-muted);
        }

        .ca-legend span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .ca-dot {
            display: inline-block;
            width: 0.6rem;
            height: 0.6rem;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .ca-dot.is-scheduled { background-color: var(--ca-scheduled); }
        .ca-dot.is-ongoing { background-color: var(--ca-ongoing); }
        .ca-dot.is-rescheduled { background-color: var(--ca-rescheduled); }
        .ca-dot.is-finished { background-color: var(--ca-finished); }
        .ca-dot.is-cancelled { background-color: var(--ca-cancelled); }

        /* Calendar */
        .ca-page .fc {
            --fc-today-bg-color: rgba(194, 167, 77, 0.1);
            --fc-event-text-color: #fff;
        }

        .ca-page .fc .fc-toolbar {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .ca-page .fc .fc-daygrid-day:not(.fc-day-other) {
            cursor: <?= $is_archived ? 'default' : 'pointer' ?>;
        }

        .ca-page .fc .fc-daygrid-day:not(.fc-day-other):hover {
            background-color: #fbfaf6;
        }

        .ca-page .fc .fc-event {
            border-radius: 4px;
            padding: 0.05rem 0.3rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }

        .ca-page .fc .fc-list-event:hover td {
            background-color: #fbfaf6;
        }

        /* Side lists */
        .ca-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .ca-list li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--au-line);
        }

        .ca-list li:first-child {
            padding-top: 0;
        }

        .ca-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .ca-date {
            flex-shrink: 0;
            width: 3rem;
            padding: 0.3rem 0;
            border-radius: 6px;
            background-color: rgba(6, 30, 78, 0.06);
            text-align: center;
            line-height: 1.1;
        }

        .ca-date strong {
            display: block;
            font-family: var(--au-serif);
            font-size: 1.15rem;
            color: var(--au-navy);
        }

        .ca-date span {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--au-muted);
        }

        .ca-date.is-today {
            background-color: rgba(194, 167, 77, 0.2);
        }

        .ca-list-body {
            flex: 1;
            min-width: 0;
        }

        .ca-list-body .fd-class-name {
            font-size: 0.9rem;
        }

        .ca-list-body .fd-class-meta {
            font-size: 0.78rem;
        }

        .ca-rate {
            flex-shrink: 0;
            text-align: right;
            font-size: 0.78rem;
            color: var(--au-muted);
            line-height: 1.2;
        }

        .ca-rate strong {
            display: block;
            font-size: 0.95rem;
            color: var(--au-navy);
        }

        .ca-rate.is-low strong {
            color: var(--au-crimson);
        }

        /* Day modal meeting cards */
        .ca-meeting {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
            padding: 0.9rem 1rem;
            border: 1px solid var(--au-line);
            border-left: 4px solid var(--ca-scheduled);
            border-radius: 8px;
            background-color: #fff;
        }

        .ca-meeting + .ca-meeting {
            margin-top: 0.6rem;
        }

        .ca-meeting.is-ongoing { border-left-color: var(--ca-ongoing); }
        .ca-meeting.is-rescheduled { border-left-color: var(--ca-rescheduled); }
        .ca-meeting.is-finished { border-left-color: var(--ca-finished); }
        .ca-meeting.is-cancelled { border-left-color: var(--ca-cancelled); }

        .ca-meeting-main {
            flex: 1 1 14rem;
            min-width: 0;
        }

        .ca-meeting-time {
            font-weight: 700;
            color: var(--au-navy);
        }

        .ca-meeting-sub {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.3rem;
            font-size: 0.8rem;
            color: var(--au-muted);
        }

        .ca-meeting .fd-actions {
            flex-shrink: 0;
        }

        .ca-meeting .fd-action {
            border: 1px solid var(--au-line);
            cursor: pointer;
        }

        .ca-day-empty {
            padding: 1.25rem 0.5rem;
            text-align: center;
            color: var(--au-muted);
        }

        .ca-day-empty i {
            display: block;
            font-size: 1.5rem;
            color: var(--au-gold);
            margin-bottom: 0.3rem;
        }

        .ca-modal .modal-footer {
            justify-content: space-between;
        }

        @media (max-width: 575.98px) {
            .ca-page .fc .fc-toolbar-title {
                font-size: 1.1rem !important;
            }

            .ca-page .fc .fc-toolbar {
                justify-content: center;
            }

            .ca-meeting .fd-actions {
                width: 100%;
            }
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
                <div class="container-fluid p-0 ca-page">

                    <?php if (!$classData): ?>
                        <div class="fd-hero">
                            <div>
                                <div class="fd-eyebrow">Class Meetings</div>
                                <h1 class="fd-title">Class not found</h1>
                            </div>
                            <div class="fd-quick">
                                <a class="fd-btn-ghost" href="class_management.php"><i class="bi bi-arrow-left"></i> Back to Class Management</a>
                            </div>
                        </div>
                        <div class="card fd-panel mb-4">
                            <div class="card-body">
                                <div class="fd-empty">
                                    <i class="bi bi-journal-x"></i>
                                    <p>This class doesn't exist or the link is missing its class.</p>
                                    <p class="small mt-1">Open a class from Class Management to see its meetings.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <div class="fd-hero">
                            <div>
                                <div class="fd-eyebrow">Class Meetings</div>
                                <h1 class="fd-title"><?= htmlspecialchars($classData['subject'] ?: 'No Subject') ?></h1>
                                <div class="ca-meta">
                                    <span class="fd-chip"><i class="bi bi-diagram-3"></i><?= htmlspecialchars($classData['name'] ?: 'No section') ?></span>
                                    <span class="fd-chip"><i class="bi bi-easel"></i><?= htmlspecialchars($classData['type'] ?: 'Class') ?></span>
                                    <span class="fd-chip"><i class="bi bi-calendar3"></i><?= htmlspecialchars($classData['semester'] ?: 'No semester') ?><?= $schoolYear ? ' · S.Y. ' . $schoolYear . '–' . ($schoolYear + 1) : '' ?></span>
                                    <span class="fd-chip"><i class="bi bi-person"></i><?= htmlspecialchars($classData['teacher'] ?: 'Not assigned') ?></span>
                                    <?php if ($is_archived): ?><span class="fd-pill is-archived"><i class="bi bi-archive"></i> Archived</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="fd-quick">
                                <a class="fd-btn-ghost" href="class_management.php"><i class="bi bi-arrow-left"></i> Back</a>
                                <a class="fd-btn-ghost" href="<?= htmlspecialchars($general_url) ?>"><i class="bi bi-table"></i> Attendance Records</a>
                                <?php if (!$is_archived): ?>
                                    <button type="button" class="fd-btn-ghost" onclick="openGenerate()"><i class="bi bi-calendar-week"></i> From Schedule</button>
                                    <button type="button" class="fd-btn" onclick="openCreateMeeting('<?= $today ?>')"><i class="bi bi-plus-lg"></i> New Meeting</button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($is_archived): ?>
                            <div class="fd-notice mb-4">
                                <i class="bi bi-archive"></i>
                                <span>This class is archived. Meetings can be viewed but not added or changed.</span>
                            </div>
                        <?php endif; ?>

                        <!-- Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-calendar-event"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $counts['total'] ?></div>
                                        <div class="fd-stat-label">Total meetings</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-hourglass-split"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $counts['upcoming'] ?></div>
                                        <div class="fd-stat-label">Upcoming</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-success"><i class="bi bi-check2-circle"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $counts['finished'] ?></div>
                                        <div class="fd-stat-label">Held<?= $counts['cancelled'] ? ' · ' . $counts['cancelled'] . ' cancelled' : '' ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon <?= $attendance_rate !== null && $attendance_rate < 75 ? 'is-danger' : '' ?>"><i class="bi bi-people"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $attendance_rate === null ? '—' : $attendance_rate . '%' ?></div>
                                        <div class="fd-stat-label">Attendance rate</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <!-- Calendar -->
                            <div class="col-xl-8">
                                <div class="card fd-panel">
                                    <div class="card-header">
                                        <div>
                                            <h5 class="card-title"><i class="bi bi-calendar3"></i> Meeting Calendar</h5>
                                            <p class="fd-panel-sub">
                                                <?= $is_archived ? 'Select a meeting to see its details.' : 'Select a day to add a meeting, or a meeting to manage it.' ?>
                                            </p>
                                        </div>
                                        <div class="ca-legend" aria-label="Status colours">
                                            <span><i class="ca-dot is-scheduled"></i> Scheduled</span>
                                            <span><i class="ca-dot is-ongoing"></i> Ongoing</span>
                                            <span><i class="ca-dot is-rescheduled"></i> Rescheduled</span>
                                            <span><i class="ca-dot is-finished"></i> Finished</span>
                                            <span><i class="ca-dot is-cancelled"></i> Cancelled</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="calendar"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-4 d-flex flex-column gap-4">
                                <!-- Upcoming -->
                                <div class="card fd-panel">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="bi bi-clock"></i> Upcoming <span class="fd-count"><?= $counts['upcoming'] ?></span></h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($upcoming)): ?>
                                            <div class="fd-empty">
                                                <i class="bi bi-calendar-plus"></i>
                                                <p>No upcoming meetings.</p>
                                                <?php if (!$is_archived): ?><p class="small mt-1">Pick a day on the calendar to add one.</p><?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <ul class="ca-list">
                                                <?php foreach ($upcoming as $m): ?>
                                                    <li>
                                                        <div class="ca-date <?= $m['date'] === $today ? 'is-today' : '' ?>">
                                                            <strong><?= date('j', strtotime($m['date'])) ?></strong>
                                                            <span><?= $m['date'] === $today ? 'Today' : date('M', strtotime($m['date'])) ?></span>
                                                        </div>
                                                        <div class="ca-list-body">
                                                            <div class="fd-class-name"><?= htmlspecialchars($m['start_time'] . ' – ' . $m['end_time']) ?></div>
                                                            <div class="fd-class-meta"><?= htmlspecialchars($m['type']) ?> &middot; <span class="fd-pill <?= $status_class[$m['status']] ?? 'is-type' ?>"><?= htmlspecialchars($m['status']) ?></span></div>
                                                        </div>
                                                        <a class="fd-action" href="<?= htmlspecialchars('class_attendance_qr.php?class_id=' . $class_id . '&classAttendanceId=' . $m['id'] . '&semesterId=' . $semester_id) ?>" title="Take attendance">
                                                            <i class="bi bi-qr-code"></i><span class="d-none d-sm-inline">Attendance</span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Recent -->
                                <div class="card fd-panel">
                                    <div class="card-header">
                                        <h5 class="card-title"><i class="bi bi-clock-history"></i> Recently held</h5>
                                        <?php if (!empty($recent)): ?><a class="fd-btn-ghost fd-btn-sm" href="<?= htmlspecialchars($general_url) ?>">All records</a><?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($recent)): ?>
                                            <div class="fd-empty">
                                                <i class="bi bi-journal-check"></i>
                                                <p>No meetings have been held yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <ul class="ca-list">
                                                <?php foreach ($recent as $m):
                                                    $rate = $m['roster'] > 0 ? round($m['attended'] / $m['roster'] * 100) : null;
                                                ?>
                                                    <li>
                                                        <div class="ca-date">
                                                            <strong><?= date('j', strtotime($m['date'])) ?></strong>
                                                            <span><?= date('M', strtotime($m['date'])) ?></span>
                                                        </div>
                                                        <div class="ca-list-body">
                                                            <div class="fd-class-name">
                                                                <a href="<?= htmlspecialchars('class_attendance_qr.php?class_id=' . $class_id . '&classAttendanceId=' . $m['id'] . '&semesterId=' . $semester_id) ?>" class="text-reset text-decoration-none"><?= htmlspecialchars($m['type']) ?> meeting</a>
                                                            </div>
                                                            <div class="fd-class-meta"><?= htmlspecialchars($m['start_time'] . ' – ' . $m['end_time']) ?></div>
                                                        </div>
                                                        <div class="ca-rate <?= $rate !== null && $rate < 75 ? 'is-low' : '' ?>">
                                                            <strong><?= $rate === null ? '—' : $rate . '%' ?></strong>
                                                            <?= $m['attended'] ?>/<?= $m['roster'] ?> in
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
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

    <?php if ($classData): ?>
        <!-- Meetings on a day -->
        <div class="modal fade ca-modal" id="dayModal" tabindex="-1" aria-labelledby="dayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dayModalLabel">Meetings on <span id="dayModalDate"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="dayModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <?php if (!$is_archived): ?>
                            <button type="button" class="btn btn-primary" id="dayModalAdd"><i class="bi bi-plus-lg"></i> Add meeting</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$is_archived): ?>
            <!-- Create / edit meeting -->
            <div class="modal fade ca-modal" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="meetingForm" novalidate>
                            <div class="modal-header">
                                <h5 class="modal-title" id="meetingModalLabel">New Meeting</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="meetingId">
                                <div class="mb-3">
                                    <label for="meetingDate" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="meetingDate" required>
                                </div>
                                <div class="mb-3" id="meetingTypeGroup">
                                    <label for="meetingType" class="form-label">Type</label>
                                    <select class="form-select" id="meetingType" required>
                                        <option value="Regular">Regular</option>
                                        <option value="Late">Late</option>
                                        <option value="Make-up">Make-up</option>
                                    </select>
                                </div>
                                <div class="mb-3 d-none" id="meetingStatusGroup">
                                    <label for="meetingStatus" class="form-label">Status</label>
                                    <select class="form-select" id="meetingStatus">
                                        <option value="Scheduled">Scheduled</option>
                                        <option value="Ongoing">Ongoing</option>
                                        <option value="Rescheduled">Rescheduled</option>
                                        <option value="Finished">Finished</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label for="meetingStart" class="form-label">Start time</label>
                                        <input type="time" class="form-control" id="meetingStart" required>
                                    </div>
                                    <div class="col-6">
                                        <label for="meetingEnd" class="form-label">End time</label>
                                        <input type="time" class="form-control" id="meetingEnd" required>
                                    </div>
                                </div>
                                <p class="small text-muted mt-3 mb-0" id="meetingHint">Every enrolled student starts as absent and is notified of the new meeting.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="meetingSubmit">Create meeting</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Generate meetings from the subject schedule -->
            <div class="modal fade ca-modal" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="generateForm" novalidate>
                            <div class="modal-header">
                                <h5 class="modal-title" id="generateModalLabel">Add Meetings from Schedule</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (empty($schedules)): ?>
                                    <div class="ca-day-empty">
                                        <i class="bi bi-calendar-x"></i>
                                        No weekly schedule has been set for <?= htmlspecialchars($classData['subject'] ?: 'this subject') ?> yet.
                                    </div>
                                <?php else: ?>
                                    <label class="form-label">Weekly schedule</label>
                                    <ul class="ca-list mb-3">
                                        <?php foreach ($schedules as $sch): ?>
                                            <li>
                                                <div class="ca-list-body">
                                                    <div class="fd-class-name"><?= htmlspecialchars($sch['meeting_days']) ?></div>
                                                </div>
                                                <div class="fd-class-meta"><?= htmlspecialchars($sch['start_label'] . ' – ' . $sch['end_label']) ?></div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label for="generateFrom" class="form-label">From</label>
                                            <input type="date" class="form-control" id="generateFrom" value="<?= $generate_from ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label for="generateTo" class="form-label">To</label>
                                            <input type="date" class="form-control" id="generateTo" value="<?= $generate_to ?>" required>
                                        </div>
                                    </div>
                                    <p class="small text-muted mt-3 mb-0" id="generateHint"></p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <?php if (!empty($schedules)): ?>
                                    <button type="submit" class="btn btn-primary" id="generateSubmit">Add meetings</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <script>
            const CLASS_ID = <?= $class_id ?>;
            const SEMESTER_ID = <?= $semester_id ?>;
            const SUBJECT = <?= json_encode($classData['subject'], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const IS_ARCHIVED = <?= $is_archived ? 'true' : 'false' ?>;
            const TODAY = '<?= $today ?>';
            const MEETINGS = <?= json_encode($meetings, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

            const STATUS_KEY = {
                Scheduled: 'scheduled',
                Ongoing: 'ongoing',
                Rescheduled: 'rescheduled',
                Finished: 'finished',
                Ended: 'finished',
                Cancelled: 'cancelled'
            };
            const PILL_CLASS = <?= json_encode($status_class) ?>;

            const rootStyle = getComputedStyle(document.querySelector('.ca-page'));
            const statusColor = status => rootStyle.getPropertyValue('--ca-' + (STATUS_KEY[status] || 'scheduled')).trim();

            function esc(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function formatDate(dateStr) {
                return new Date(dateStr + 'T00:00:00').toLocaleDateString(undefined, {
                    weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
                });
            }

            function attendanceUrl(meetingId) {
                return `class_attendance_qr.php?class_id=${CLASS_ID}&classAttendanceId=${meetingId}&semesterId=${SEMESTER_ID}`;
            }

            let calendar;

            document.addEventListener('DOMContentLoaded', function() {
                const narrow = window.matchMedia('(max-width: 767.98px)').matches;

                calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                    initialView: narrow ? 'listMonth' : 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: narrow ? 'dayGridMonth,listMonth' : 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
                    height: 'auto',
                    dayMaxEvents: 3,
                    nowIndicator: true,
                    slotMinTime: '06:00:00',
                    slotMaxTime: '20:00:00',
                    noEventsContent: 'No meetings this month',
                    events: MEETINGS.map(m => ({
                        id: String(m.id),
                        title: m.type + (m.status !== 'Scheduled' ? ' · ' + m.status : ''),
                        start: m.start_24 ? `${m.date}T${m.start_24}` : m.date,
                        end: m.end_24 ? `${m.date}T${m.end_24}` : undefined,
                        allDay: !m.start_24,
                        backgroundColor: statusColor(m.status),
                        borderColor: statusColor(m.status),
                        extendedProps: { date: m.date }
                    })),
                    eventDidMount: info => {
                        const m = MEETINGS.find(x => String(x.id) === info.event.id);
                        if (m) info.el.title = `${m.type} · ${m.status}\n${m.start_time} – ${m.end_time}`;
                    },
                    dateClick: info => openDay(info.dateStr),
                    eventClick: info => {
                        info.jsEvent.preventDefault();
                        openDay(info.event.extendedProps.date);
                    }
                });
                calendar.render();
            });

            // ---------- Day view ----------
            let dayModal;

            function openDay(dateStr) {
                const list = MEETINGS.filter(m => m.date === dateStr);
                if (IS_ARCHIVED && list.length === 0) return;
                if (!IS_ARCHIVED && list.length === 0) {
                    openCreateMeeting(dateStr);
                    return;
                }

                document.getElementById('dayModalDate').textContent = formatDate(dateStr);
                document.getElementById('dayModalBody').innerHTML = list.map(m => {
                    const key = STATUS_KEY[m.status] || 'scheduled';
                    const rate = m.roster > 0 ? Math.round(m.attended / m.roster * 100) + '% present' : 'No students enrolled';
                    return `
                        <div class="ca-meeting is-${key}">
                            <div class="ca-meeting-main">
                                <div class="ca-meeting-time">${esc(m.start_time)} – ${esc(m.end_time)}</div>
                                <div class="ca-meeting-sub">
                                    <span class="fd-pill ${PILL_CLASS[m.status] || 'is-type'}">${esc(m.status)}</span>
                                    <span class="fd-pill is-type">${esc(m.type)}</span>
                                    <span>${m.attended}/${m.roster} in · ${rate}</span>
                                </div>
                            </div>
                            <div class="fd-actions">
                                <a class="fd-action" href="${attendanceUrl(m.id)}"><i class="bi bi-qr-code"></i> Attendance</a>
                                ${IS_ARCHIVED ? '' : `
                                <button type="button" class="fd-action" onclick="openEditMeeting(${m.id})"><i class="bi bi-pencil"></i> Edit</button>
                                <button type="button" class="fd-action is-danger" onclick="deleteMeeting(${m.id})"><i class="bi bi-trash"></i> Delete</button>`}
                            </div>
                        </div>`;
                }).join('');

                const addBtn = document.getElementById('dayModalAdd');
                if (addBtn) {
                    addBtn.onclick = () => {
                        dayModal.hide();
                        openCreateMeeting(dateStr);
                    };
                }

                dayModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('dayModal'));
                dayModal.show();
            }

            // ---------- Create / edit ----------
            function meetingModal() {
                return bootstrap.Modal.getOrCreateInstance(document.getElementById('meetingModal'));
            }

            function openCreateMeeting(dateStr) {
                if (IS_ARCHIVED) return;
                const form = document.getElementById('meetingForm');
                form.reset();
                document.getElementById('meetingId').value = '';
                document.getElementById('meetingDate').value = dateStr;
                document.getElementById('meetingDate').disabled = false;
                document.getElementById('meetingTypeGroup').classList.remove('d-none');
                document.getElementById('meetingStatusGroup').classList.add('d-none');
                document.getElementById('meetingHint').classList.remove('d-none');
                document.getElementById('meetingModalLabel').textContent = 'New Meeting';
                document.getElementById('meetingSubmit').textContent = 'Create meeting';
                meetingModal().show();
            }

            function openEditMeeting(id) {
                const m = MEETINGS.find(x => x.id == id);
                if (!m) return;
                if (dayModal) dayModal.hide();

                document.getElementById('meetingId').value = m.id;
                document.getElementById('meetingDate').value = m.date;
                document.getElementById('meetingDate').disabled = true;
                document.getElementById('meetingStart').value = m.start_24;
                document.getElementById('meetingEnd').value = m.end_24;
                document.getElementById('meetingStatus').value = m.status === 'Ended' ? 'Finished' : m.status;
                document.getElementById('meetingTypeGroup').classList.add('d-none');
                document.getElementById('meetingStatusGroup').classList.remove('d-none');
                document.getElementById('meetingHint').classList.add('d-none');
                document.getElementById('meetingModalLabel').textContent = 'Edit Meeting';
                document.getElementById('meetingSubmit').textContent = 'Save changes';
                meetingModal().show();
            }

            document.getElementById('meetingForm')?.addEventListener('submit', function(event) {
                event.preventDefault();

                const id = document.getElementById('meetingId').value;
                const date = document.getElementById('meetingDate').value;
                const start = document.getElementById('meetingStart').value;
                const end = document.getElementById('meetingEnd').value;

                if (!date || !start || !end) {
                    Swal.fire('Missing details', 'Fill in the date, start time and end time.', 'warning');
                    return;
                }
                if (end <= start) {
                    Swal.fire('Check the times', 'The end time must be later than the start time.', 'warning');
                    return;
                }

                const submit = document.getElementById('meetingSubmit');
                submit.disabled = true;

                const request = id ?
                    fetch('update_class_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            meetingId: id,
                            status: document.getElementById('meetingStatus').value,
                            startTime: start,
                            endTime: end
                        })
                    }) :
                    fetch('create_class_meeting.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            date: date,
                            subject: SUBJECT,
                            start_time: start,
                            end_time: end,
                            class_id: CLASS_ID,
                            type: document.getElementById('meetingType').value
                        })
                    });

                request
                    .then(response => {
                        if (!response.ok) throw new Error('The server returned ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) throw new Error(data.message || 'Unknown error');
                        meetingModal().hide();
                        return Swal.fire({
                            icon: 'success',
                            title: id ? 'Meeting updated' : 'Meeting created',
                            timer: 1400,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire('Something went wrong', error.message, 'error');
                    })
                    .finally(() => {
                        submit.disabled = false;
                    });
            });

            // ---------- Delete ----------
            function deleteMeeting(id) {
                const m = MEETINGS.find(x => x.id == id);
                Swal.fire({
                    title: 'Delete this meeting?',
                    html: m ? `${esc(formatDate(m.date))}, ${esc(m.start_time)} – ${esc(m.end_time)}<br><small>Its attendance records will be deleted too. This can't be undone.</small>` : '',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete meeting',
                    confirmButtonColor: '#a3191b',
                    cancelButtonText: 'Keep it'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    fetch(`delete_meeting.php?meeting_id=${encodeURIComponent(id)}`, { method: 'DELETE' })
                        .then(response => {
                            if (!response.ok) throw new Error('The server returned ' + response.status);
                            return response.json();
                        })
                        .then(data => {
                            if (!data.success) throw new Error(data.message || 'Unknown error');
                            return Swal.fire({
                                icon: 'success',
                                title: 'Meeting deleted',
                                timer: 1400,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        })
                        .catch(error => {
                            Swal.fire('Could not delete', error.message, 'error');
                        });
                });
            }

            // ---------- Generate from schedule ----------
            const SCHEDULES = <?= json_encode($schedules, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            function generateModal() {
                return bootstrap.Modal.getOrCreateInstance(document.getElementById('generateModal'));
            }

            function openGenerate() {
                if (IS_ARCHIVED) return;
                updateGenerateHint();
                generateModal().show();
            }

            // Mirrors the server: one meeting per schedule slot per matching day, skipping slots that already have one
            function countScheduledMeetings(from, to) {
                const existing = new Set(MEETINGS.map(m => m.date + '|' + m.start_time));
                let created = 0;
                let skipped = 0;
                for (let d = new Date(from + 'T00:00:00'); d <= new Date(to + 'T00:00:00'); d.setDate(d.getDate() + 1)) {
                    const date = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    SCHEDULES.filter(s => s.meeting_days === WEEKDAYS[d.getDay()]).forEach(s => {
                        existing.has(date + '|' + s.start_label) ? skipped++ : created++;
                    });
                }
                return { created, skipped };
            }

            function updateGenerateHint() {
                const hint = document.getElementById('generateHint');
                if (!hint) return;
                const from = document.getElementById('generateFrom').value;
                const to = document.getElementById('generateTo').value;
                const submit = document.getElementById('generateSubmit');

                if (!from || !to || to < from) {
                    hint.textContent = 'Pick a start date and an end date on or after it.';
                    submit.disabled = true;
                    return;
                }

                const { created, skipped } = countScheduledMeetings(from, to);
                hint.textContent = created === 0 ?
                    'No new meetings fall in this range' + (skipped ? ` (${skipped} already exist).` : '.') :
                    `${created} meeting${created === 1 ? '' : 's'} will be added` +
                    (skipped ? `, ${skipped} already exist and will be skipped` : '') +
                    '. Every enrolled student starts as absent and is notified once.';
                submit.disabled = created === 0;
            }

            ['generateFrom', 'generateTo'].forEach(id => {
                document.getElementById(id)?.addEventListener('input', updateGenerateHint);
            });

            document.getElementById('generateForm')?.addEventListener('submit', function(event) {
                event.preventDefault();

                const submit = document.getElementById('generateSubmit');
                if (!submit || submit.disabled) return;
                submit.disabled = true;

                fetch('generate_scheduled_meetings.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            class_id: CLASS_ID,
                            from: document.getElementById('generateFrom').value,
                            to: document.getElementById('generateTo').value
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('The server returned ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) throw new Error(data.message || 'Unknown error');
                        generateModal().hide();
                        return Swal.fire({
                            icon: 'success',
                            title: `${data.created} meeting${data.created === 1 ? '' : 's'} added`,
                            text: data.skipped ? `${data.skipped} already existed and were skipped.` : '',
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    })
                    .catch(error => {
                        Swal.fire('Something went wrong', error.message, 'error');
                        submit.disabled = false;
                    });
            });
        </script>
    <?php endif; ?>

</body>

</html>
<?php include('processes/server/alerts.php'); ?>
