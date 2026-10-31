<?php
require_once __DIR__ . '/../includes/grading.php';
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

// ---------- Class ----------
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$class = null;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT id, name, subject, subject_id, teacher, semester, type, is_archived FROM classes WHERE id = :class_id LIMIT 1");
    $stmt->execute(['class_id' => $class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($class) {
    // Recomputes every student's quarter grades for this class (expects $pdo and $_GET['class_id'])
    include('processes/server/automatic_grader_cron.php');
}

// ---------- Page data ----------
$students = [];
$activities = [];
$weights = [];
$pair = null;
$schoolYear = null;
$semester_id = isset($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;
$subject_id = $class['subject_id'] ?? ($_GET['subject_id'] ?? '');
$state = 'none';

if ($class) {
    $class_id = (int) $class['id'];
    $type = $class['type'];
    $is_archived = (int) $class['is_archived'] === 1;
    $is_lab = strcasecmp($type, 'Laboratory') === 0;

    $stmt = $pdo->prepare("SELECT id, school_year FROM semester WHERE name = :name LIMIT 1");
    $stmt->execute(['name' => $class['semester']]);
    if ($sem = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schoolYear = (int) $sem['school_year'];
        $semester_id = $semester_id ?: (int) $sem['id'];
    }

    $state = grades_class_state($pdo, $class_id);

    // The other half of a lecture + laboratory subject: same subject, teacher and section
    $stmt = $pdo->prepare("
        SELECT id, type, subject_id FROM classes
        WHERE subject = :subject AND teacher = :teacher AND name = :name AND id <> :id
          AND type IN ('Lecture', 'Laboratory') AND type <> :type
        ORDER BY is_archived, id DESC
        LIMIT 1
    ");
    $stmt->execute([
        'subject' => $class['subject'],
        'teacher' => $class['teacher'],
        'name' => $class['name'],
        'id' => $class_id,
        'type' => $type,
    ]);
    $pair = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $lecture_id = $pair ? ($is_lab ? (int) $pair['id'] : $class_id) : null;
    $lab_id = $pair ? ($is_lab ? $class_id : (int) $pair['id']) : null;

    // Students enrolled in this class (or either half of the subject)
    $class_ids = $pair ? [$class_id, (int) $pair['id']] : [$class_id];
    $in = implode(',', array_fill(0, count($class_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.student_id, s.fullName
        FROM students_enrollments e
        JOIN students s ON s.student_id = e.student_id
        WHERE e.class_id IN ($in)
        ORDER BY s.fullName
    ");
    $stmt->execute($class_ids);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Saved grades, keyed by class then student
    $stmt = $pdo->prepare("SELECT class_id, student_id, midterm_grade, final_grade, overall_grade FROM student_grades WHERE class_id IN ($in)");
    $stmt->execute($class_ids);
    $grades = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
        $grades[$g['class_id']][$g['student_id']] = $g;
    }

    // Component weights and the activities under them (attendance is graded separately)
    $stmt = $pdo->prepare("SELECT title, percentile FROM rubrics WHERE class_id = :class_id AND title <> 'Attendance' GROUP BY title ORDER BY title");
    $stmt->execute(['class_id' => $class_id]);
    $weights = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if ($weights) {
        $titles = array_keys($weights);
        $tin = implode(',', array_fill(0, count($titles), '?'));
        $stmt = $pdo->prepare("
            SELECT id, title, type, term, max_points FROM activities
            WHERE class_id = ? AND type IN ($tin)
            ORDER BY FIELD(term, 'midterm', 'final', 'other'), type, due_date, id
        ");
        $stmt->execute(array_merge([$class_id], $titles));
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $scores = [];
    if ($activities) {
        $ain = implode(',', array_fill(0, count($activities), '?'));
        $stmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($ain)");
        $stmt->execute(array_column($activities, 'id'));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $scores[$s['student_id']][$s['activity_id']] = $s['score'];
        }
    }

    $show = function ($g) {
        return deped_display($g);
    };

    foreach ($students as &$st) {
        $sid = $st['student_id'];
        $own = $grades[$class_id][$sid] ?? [];
        $st['q1'] = $show($own['midterm_grade'] ?? null);
        $st['q2'] = $show($own['final_grade'] ?? null);
        $st['final'] = $show($own['overall_grade'] ?? null);

        if ($pair) {
            $lec = $show($grades[$lecture_id][$sid]['overall_grade'] ?? null);
            $lab = $show($grades[$lab_id][$sid]['overall_grade'] ?? null);
            $st['other'] = $is_lab ? $lec : $lab;
            $st['result'] = deped_combined_grade($lec, $lab);
        } else {
            $st['result'] = $st['final'];
        }

        $done = 0;
        $st['scores'] = [];
        foreach ($activities as $a) {
            $score = $scores[$sid][$a['id']] ?? null;
            if ($score !== null && $score !== '') {
                $done++;
            }
            $st['scores'][$a['id']] = $score;
        }
        $st['done'] = $done;
        $st['remarks'] = deped_remarks($st['result']);
        $st['bucket'] = deped_is_number($st['result'])
            ? ((float) $st['result'] >= DEPED_PASSING_GRADE ? 'passing' : 'failing')
            : 'pending';
    }
    unset($st);
}

$buckets = array_count_values(array_column($students, 'bucket')) + ['passing' => 0, 'failing' => 0, 'pending' => 0];
$numeric = array_filter(array_column($students, 'result'), 'deped_is_number');
$average = $numeric ? round(array_sum(array_map('floatval', $numeric)) / count($numeric), 1) : null;

$state_pill = [
    'none' => ['Not started', 'is-archived'],
    'draft' => ['Draft · not submitted', 'is-type'],
    'submitted' => ['Waiting for review', 'is-pending'],
    'locked' => ['Accepted · locked', 'is-active'],
][$state];

$tabular_url = $class
    ? ($is_lab ? 'lab' : 'lecture') . '_class_grades_tabular' . ($is_archived ? '_archived' : '') . '.php?' .
        http_build_query(['id' => $class_id, 'semester_id' => $semester_id, 'subject_id' => $subject_id])
    : '';
$pair_url = $pair
    ? 'class_grades.php?' . http_build_query(['class_id' => $pair['id'], 'semester_id' => $semester_id, 'subject_id' => $pair['subject_id']])
    : '';
$component = $class ? ($is_lab ? 'Laboratory' : 'Lecture') : '';
$other_component = $class ? ($is_lab ? 'Lecture' : 'Laboratory') : '';

function cg_grade($grade)
{
    $flag = deped_flagged($grade) ? (deped_is_number($grade) ? ' is-fail' : ' is-none') : '';
    return '<span class="cg-grade' . $flag . '">' . htmlspecialchars($grade) . '</span>';
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        .cg-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.6rem;
        }

        .cg-meta .fd-chip i {
            color: var(--au-gold);
            margin-right: 0.2rem;
        }

        /* Filter bar */
        .cg-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .cg-filters .fd-btn-ghost {
            cursor: pointer;
        }

        .cg-filters .fd-btn-ghost.is-current {
            background-color: var(--au-navy);
            border-color: var(--au-navy);
            color: #fff !important;
        }

        .cg-filters .is-current .fd-count {
            background-color: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        /* Grade cells */
        .cg-table td,
        .cg-table th {
            text-align: center;
        }

        .cg-table td:nth-child(2),
        .cg-table th:nth-child(2) {
            text-align: left;
        }

        .cg-grade {
            display: inline-block;
            min-width: 2.4rem;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            font-weight: 700;
            color: var(--au-ink);
        }

        .cg-grade.is-fail {
            background-color: rgba(163, 25, 27, 0.08);
            color: var(--au-crimson);
        }

        .cg-grade.is-none {
            color: var(--au-muted);
            font-weight: 600;
        }

        .cg-result .cg-grade {
            font-size: 0.95rem;
            background-color: rgba(6, 30, 78, 0.06);
            color: var(--au-navy);
        }

        .cg-result .cg-grade.is-fail {
            background-color: rgba(163, 25, 27, 0.1);
            color: var(--au-crimson);
        }

        .cg-result .cg-grade.is-none {
            background-color: transparent;
            color: var(--au-muted);
        }

        .cg-progress {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            min-width: 6.5rem;
            font-size: 0.78rem;
            color: var(--au-muted);
        }

        .cg-progress .fd-progress {
            flex: 1;
            height: 6px;
            margin: 0;
        }

        .cg-name {
            font-weight: 700;
            color: var(--au-ink);
        }

        .cg-id {
            display: block;
            font-size: 0.75rem;
            color: var(--au-muted);
        }

        .cg-legend {
            font-size: 0.78rem;
            color: var(--au-muted);
        }

        /* Breakdown modal */
        .cg-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .cg-summary div {
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--au-line);
            border-radius: 8px;
            background-color: #faf9f5;
            text-align: center;
        }

        .cg-summary span {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--au-muted);
        }

        .cg-summary strong {
            font-family: var(--au-serif);
            font-size: 1.35rem;
            color: var(--au-navy);
        }

        .cg-summary strong.is-fail {
            color: var(--au-crimson);
        }

        .cg-term + .cg-term {
            margin-top: 1.25rem;
        }

        .cg-term h6 {
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--au-muted);
        }

        .cg-act {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--au-line);
            font-size: 0.88rem;
        }

        .cg-act:last-child {
            border-bottom: 0;
        }

        .cg-act-title {
            flex: 1;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .cg-act-score {
            flex-shrink: 0;
            font-weight: 700;
            color: var(--au-navy);
        }

        .cg-act-score.is-missing {
            color: var(--au-crimson);
            font-weight: 600;
        }

        @media (max-width: 575.98px) {
            .cg-filters {
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
                <div class="container-fluid p-0">

                    <?php if (!$class): ?>
                        <div class="fd-hero">
                            <div>
                                <div class="fd-eyebrow">Class Grades</div>
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
                                    <p class="small mt-1">Open a class from Class Management to see its grades.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <div class="fd-hero">
                            <div>
                                <div class="fd-eyebrow">Class Grades<?= $pair ? ' · ' . $component . ' component' : '' ?></div>
                                <h1 class="fd-title"><?= htmlspecialchars($class['subject'] ?: 'No Subject') ?></h1>
                                <div class="cg-meta">
                                    <span class="fd-chip"><i class="bi bi-diagram-3"></i><?= htmlspecialchars($class['name'] ?: 'No section') ?></span>
                                    <span class="fd-chip"><i class="bi bi-easel"></i><?= htmlspecialchars($type ?: 'Class') ?></span>
                                    <span class="fd-chip"><i class="bi bi-calendar3"></i><?= htmlspecialchars($class['semester'] ?: 'No semester') ?><?= $schoolYear ? ' · S.Y. ' . $schoolYear . '–' . ($schoolYear + 1) : '' ?></span>
                                    <span class="fd-chip"><i class="bi bi-person"></i><?= htmlspecialchars($class['teacher'] ?: 'Not assigned') ?></span>
                                    <span class="fd-pill <?= $state_pill[1] ?>"><?= $state === 'locked' ? '<i class="bi bi-lock"></i> ' : '' ?><?= $state_pill[0] ?></span>
                                    <?php if ($is_archived): ?><span class="fd-pill is-archived"><i class="bi bi-archive"></i> Archived</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="fd-quick">
                                <a class="fd-btn-ghost" href="class_management.php"><i class="bi bi-arrow-left"></i> Back</a>
                                <?php if ($pair): ?>
                                    <a class="fd-btn-ghost" href="<?= htmlspecialchars($pair_url) ?>"><i class="bi bi-arrow-left-right"></i> <?= $other_component ?> grades</a>
                                <?php endif; ?>
                                <a class="fd-btn" href="<?= htmlspecialchars($tabular_url) ?>"><i class="bi bi-table"></i> <?= $is_archived || $state === 'locked' ? 'Open gradebook' : 'Edit in gradebook' ?></a>
                            </div>
                        </div>

                        <?php if ($state === 'submitted'): ?>
                            <div class="fd-notice mb-4"><i class="bi bi-hourglass-split"></i><span>These grades were submitted and are waiting for the registrar's review.</span></div>
                        <?php elseif ($state === 'locked'): ?>
                            <div class="fd-notice mb-4"><i class="bi bi-lock"></i><span>These grades have been accepted and are locked. Ask the registrar to unlock them if a correction is needed.</span></div>
                        <?php endif; ?>

                        <!-- Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-people"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= count($students) ?></div>
                                        <div class="fd-stat-label">Students</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-graph-up"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $average === null ? '—' : $average ?></div>
                                        <div class="fd-stat-label">Class average<?= $pair ? ' (combined)' : '' ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-success"><i class="bi bi-check2-circle"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $buckets['passing'] ?></div>
                                        <div class="fd-stat-label">Passing (<?= DEPED_PASSING_GRADE ?>+)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon <?= $buckets['failing'] ? 'is-danger' : '' ?>"><i class="bi bi-exclamation-triangle"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $buckets['failing'] ?></div>
                                        <div class="fd-stat-label">Failing<?= $buckets['pending'] ? ' · ' . $buckets['pending'] . ' not graded' : '' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grades -->
                        <div class="card fd-panel mb-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title"><i class="bi bi-bar-chart-line"></i> Student Grades</h5>
                                    <p class="fd-panel-sub">
                                        <?= $pair
                                            ? 'Final grades combine the lecture (60%) and laboratory (40%) components.'
                                            : 'Final grade is the average of the two quarters.' ?>
                                        Select a student to see their activity scores.
                                    </p>
                                </div>
                                <?php if ($students): ?>
                                    <div class="cg-filters" role="group" aria-label="Filter students">
                                        <button type="button" class="fd-btn-ghost fd-btn-sm is-current" data-filter="">All <span class="fd-count"><?= count($students) ?></span></button>
                                        <button type="button" class="fd-btn-ghost fd-btn-sm" data-filter="passing">Passing <span class="fd-count"><?= $buckets['passing'] ?></span></button>
                                        <button type="button" class="fd-btn-ghost fd-btn-sm" data-filter="failing">Failing <span class="fd-count"><?= $buckets['failing'] ?></span></button>
                                        <button type="button" class="fd-btn-ghost fd-btn-sm" data-filter="pending">Not graded <span class="fd-count"><?= $buckets['pending'] ?></span></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!$students): ?>
                                    <div class="fd-empty">
                                        <i class="bi bi-person-plus"></i>
                                        <p>No students are enrolled in this class yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table fd-table cg-table" id="gradesTable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student</th>
                                                    <th>1st Qtr</th>
                                                    <th>2nd Qtr</th>
                                                    <th><?= $pair ? $component : 'Final' ?></th>
                                                    <?php if ($pair): ?>
                                                        <th><?= $other_component ?></th>
                                                        <th>Final</th>
                                                    <?php endif; ?>
                                                    <th>Remarks</th>
                                                    <th>Activities</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $i => $st):
                                                    $pct = $activities ? round($st['done'] / count($activities) * 100) : 0;
                                                ?>
                                                    <tr data-bucket="<?= $st['bucket'] ?>">
                                                        <td><?= $i + 1 ?></td>
                                                        <td data-order="<?= htmlspecialchars($st['fullName']) ?>">
                                                            <span class="cg-name"><?= htmlspecialchars($st['fullName']) ?></span>
                                                            <span class="cg-id"><?= htmlspecialchars($st['student_id']) ?></span>
                                                        </td>
                                                        <td data-order="<?= deped_is_number($st['q1']) ? $st['q1'] : -1 ?>"><?= cg_grade($st['q1']) ?></td>
                                                        <td data-order="<?= deped_is_number($st['q2']) ? $st['q2'] : -1 ?>"><?= cg_grade($st['q2']) ?></td>
                                                        <td data-order="<?= deped_is_number($st['final']) ? $st['final'] : -1 ?>" class="<?= $pair ? '' : 'cg-result' ?>"><?= cg_grade($st['final']) ?></td>
                                                        <?php if ($pair): ?>
                                                            <td data-order="<?= deped_is_number($st['other']) ? $st['other'] : -1 ?>"><?= cg_grade($st['other']) ?></td>
                                                            <td data-order="<?= deped_is_number($st['result']) ? $st['result'] : -1 ?>" class="cg-result"><?= cg_grade($st['result']) ?></td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <?php if ($st['remarks'] === 'Passed'): ?>
                                                                <span class="fd-pill is-active">Passed</span>
                                                            <?php elseif ($st['remarks'] === 'Failed'): ?>
                                                                <span class="fd-pill is-rejected">Failed</span>
                                                            <?php elseif ($st['remarks']): ?>
                                                                <span class="fd-pill is-archived"><?= htmlspecialchars($st['remarks']) ?></span>
                                                            <?php else: ?>
                                                                <span class="fd-pill is-archived">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td data-order="<?= $pct ?>">
                                                            <?php if ($activities): ?>
                                                                <div class="cg-progress" title="<?= $st['done'] ?> of <?= count($activities) ?> activities scored">
                                                                    <div class="fd-progress"><span class="<?= $pct < 75 ? 'is-low' : '' ?>" style="width: <?= $pct ?>%"></span></div>
                                                                    <?= $st['done'] ?>/<?= count($activities) ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="cg-legend">None yet</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="fd-action" onclick="showStudent(<?= $i ?>)"><i class="bi bi-eye"></i> Details</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="cg-legend mt-3 mb-0">
                                        <span class="cg-grade is-fail">74</span> below passing &nbsp;·&nbsp;
                                        <span class="cg-grade is-none">N/A</span> not graded yet
                                    </p>
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

    <?php if ($class && $students): ?>
        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="studentModalLabel"></h5>
                            <small class="text-muted" id="studentModalId"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="studentModalBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const STUDENTS = <?= json_encode(array_map(function ($s) {
                return array_intersect_key($s, array_flip(['student_id', 'fullName', 'q1', 'q2', 'final', 'other', 'result', 'remarks', 'scores', 'done']));
            }, $students), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const ACTIVITIES = <?= json_encode($activities, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const WEIGHTS = <?= json_encode((object) $weights, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const PAIRED = <?= $pair ? 'true' : 'false' ?>;
            const COMPONENT = <?= json_encode($component) ?>;
            const OTHER_COMPONENT = <?= json_encode($other_component) ?>;
            const PASSING = <?= DEPED_PASSING_GRADE ?>;
            const TERMS = { midterm: '1st Quarter', final: '2nd Quarter', other: 'Other' };

            function esc(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            const isFail = g => !isNaN(parseFloat(g)) && parseFloat(g) < PASSING;

            function tile(label, grade) {
                return `<div><span>${esc(label)}</span><strong class="${isFail(grade) ? 'is-fail' : ''}">${esc(grade)}</strong></div>`;
            }

            function showStudent(index) {
                const s = STUDENTS[index];
                document.getElementById('studentModalLabel').textContent = s.fullName;
                document.getElementById('studentModalId').textContent = s.student_id;

                let html = '<div class="cg-summary">' +
                    tile('1st Quarter', s.q1) +
                    tile('2nd Quarter', s.q2) +
                    tile(PAIRED ? COMPONENT : 'Final', s.final) +
                    (PAIRED ? tile(OTHER_COMPONENT, s.other) + tile('Final', s.result) : '') +
                    '</div>';

                if (!ACTIVITIES.length) {
                    html += '<div class="fd-empty"><i class="bi bi-journal"></i><p>No activities have been given in this class yet.</p></div>';
                } else {
                    const byTerm = {};
                    ACTIVITIES.forEach(a => {
                        const term = TERMS[a.term] ? a.term : 'other';
                        (byTerm[term] = byTerm[term] || {});
                        (byTerm[term][a.type] = byTerm[term][a.type] || []).push(a);
                    });

                    Object.keys(TERMS).filter(t => byTerm[t]).forEach(term => {
                        html += `<div class="cg-term"><h6>${TERMS[term]}</h6>`;
                        Object.keys(byTerm[term]).forEach(type => {
                            const list = byTerm[term][type];
                            let got = 0, max = 0;
                            const rows = list.map(a => {
                                const score = s.scores[a.id];
                                const has = score !== null && score !== undefined && score !== '';
                                if (has) got += parseFloat(score);
                                max += parseFloat(a.max_points) || 0;
                                return `<div class="cg-act">
                                    <span class="cg-act-title">${esc(a.title)}</span>
                                    <span class="cg-act-score ${has ? '' : 'is-missing'}">${has ? esc(score) : 'No score'} / ${esc(a.max_points)}</span>
                                </div>`;
                            }).join('');
                            const weight = WEIGHTS[type] ? ` · ${esc(WEIGHTS[type])}% of grade` : '';
                            html += `<div class="card fd-panel mb-2"><div class="card-header py-2">
                                    <span class="fw-bold">${esc(type)}<span class="fd-panel-sub d-inline">${weight}</span></span>
                                    <span class="fd-count">${got} / ${max}</span>
                                </div><div class="card-body py-1">${rows}</div></div>`;
                        });
                        html += '</div>';
                    });
                }

                document.getElementById('studentModalBody').innerHTML = html;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('studentModal')).show();
            }

            $(function() {
                let bucket = '';
                $.fn.dataTable.ext.search.push((settings, data, index) => {
                    if (settings.nTable.id !== 'gradesTable' || !bucket) return true;
                    return settings.aoData[index].nTr.dataset.bucket === bucket;
                });

                const table = $('#gradesTable').DataTable({
                    order: [[1, 'asc']],
                    pageLength: 25,
                    columnDefs: [{ targets: -1, orderable: false, searchable: false }],
                    language: { search: '', searchPlaceholder: 'Search students', emptyTable: 'No students', zeroRecords: 'No students match' }
                });

                $('.cg-filters [data-filter]').on('click', function() {
                    bucket = this.dataset.filter;
                    $('.cg-filters [data-filter]').removeClass('is-current');
                    $(this).addClass('is-current');
                    table.draw();
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>
