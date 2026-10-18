<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$courses = ['BSIT' => 'Bachelor of Science in Information Technology', 'BSCS' => 'Bachelor of Science in Computer Science'];
$yearLevels = ['1A', '1B', '2A', '2B', '3A', '3B', '4A', '4B'];

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY is_archived, name")->fetchAll(PDO::FETCH_ASSOC);

$schedules = [];
foreach ($pdo->query("SELECT subject_id, meeting_days, start_time, end_time FROM subjects_schedules ORDER BY FIELD(meeting_days, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time") as $row) {
    $schedules[$row['subject_id']][] = $row;
}

$classCounts = [];
foreach ($pdo->query("SELECT subject_id, COUNT(*) AS total FROM classes WHERE is_archived = 0 GROUP BY subject_id") as $row) {
    $classCounts[$row['subject_id']] = (int) $row['total'];
}

$activeSemesters = $pdo->query("SELECT name FROM semester WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$activeSemester = $activeSemesters[0] ?? null;

$activeSubjects = array_filter($subjects, fn($s) => (int) $s['is_archived'] === 0);
$lectureCount = count(array_filter($activeSubjects, fn($s) => $s['type'] === 'Lecture'));
$unscheduled = count(array_filter($activeSubjects, fn($s) => empty($schedules[$s['id']])));

// Weekly timetable for active subjects
$timetable = array_fill_keys($days, []);
foreach ($activeSubjects as $s) {
    foreach ($schedules[$s['id']] ?? [] as $slot) {
        if (isset($timetable[$slot['meeting_days']])) {
            $timetable[$slot['meeting_days']][] = $slot + ['subject' => $s];
        }
    }
}
foreach ($timetable as &$slots) {
    usort($slots, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
}
unset($slots);

// Slots for the client-side clash check (same rule as processes/admin/subjects/add.php)
$clashData = [];
foreach ($activeSubjects as $s) {
    foreach ($schedules[$s['id']] ?? [] as $slot) {
        $clashData[] = ['id' => (int) $s['id'], 'name' => $s['code'] . ' ' . $s['name'], 'day' => $slot['meeting_days'], 'start' => substr($slot['start_time'], 0, 5), 'end' => substr($slot['end_time'], 0, 5)];
    }
}

$usedCourses = array_values(array_unique(array_filter(array_column($subjects, 'course'))));
$usedYears = array_values(array_unique(array_filter(array_column($subjects, 'year_level'))));
sort($usedYears);

function time_label(string $time): string
{
    return date('g:i A', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Subjects | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .subj-name { font-weight: 700; color: var(--ui-navy); }
        .subj-code { display: block; font-size: 0.8rem; color: var(--ui-muted); }
        .slot { display: block; white-space: nowrap; font-size: 0.82rem; }
        .slot b { display: inline-block; min-width: 2.4rem; color: var(--ui-navy); }
        .week { display: grid; grid-template-columns: repeat(6, minmax(150px, 1fr)); gap: 0.6rem; overflow-x: auto; }
        .week-day h3 { margin: 0 0 0.5rem; padding-bottom: 0.4rem; border-bottom: 2px solid var(--ui-gold); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ui-navy); }
        .week-slot { margin-bottom: 0.45rem; padding: 0.5rem 0.6rem; border-radius: 8px; border-left: 3px solid var(--ui-blue); background: #f3f6fc; font-size: 0.8rem; }
        .week-slot.is-lab { border-left-color: var(--ui-gold); background: #fbf7ea; }
        .week-slot strong { display: block; color: var(--ui-navy); }
        .week-slot span { color: var(--ui-muted); }
        .week-empty { font-size: 0.8rem; color: var(--ui-muted); }
        .nav-tabs .nav-link { font-weight: 600; color: var(--ui-muted); }
        .nav-tabs .nav-link.active { color: var(--ui-navy); border-bottom-color: #fff; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php'); ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <?php admin_page_header('Subjects', 'Subjects offered this semester and their weekly meeting times', 'bi-journal-bookmark',
                        '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createSubjectModal"' . ($activeSemester ? '' : ' disabled title="Activate a semester first"') . '><i class="bi bi-plus-lg"></i> New subject</button>'); ?>

                    <?php if (!$activeSemester): ?>
                        <div class="ui-callout is-alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div><b>No active semester.</b> Subjects are added to the active semester, so <a href="semester_management.php">activate a semester</a> before creating or editing subjects.</div>
                        </div>
                    <?php endif; ?>

                    <?php admin_stat_tiles([
                        ['label' => 'Active subjects', 'value' => count($activeSubjects), 'icon' => 'bi-journal-bookmark', 'filter' => 'Active', 'filter_col' => 'Status'],
                        ['label' => 'Lecture / Lab', 'value' => $lectureCount . ' / ' . (count($activeSubjects) - $lectureCount), 'icon' => 'bi-easel'],
                        ['label' => 'Without schedule', 'value' => $unscheduled, 'icon' => 'bi-calendar-x', 'tone' => $unscheduled ? 'alert' : 'ok', 'filter' => 'No schedule'],
                        ['label' => 'Archived', 'value' => count($subjects) - count($activeSubjects), 'icon' => 'bi-archive', 'tone' => 'muted', 'filter' => 'Archived', 'filter_col' => 'Status'],
                    ]); ?>

                    <section class="ui-panel">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabList" type="button" role="tab"><i class="bi bi-list-ul"></i> Subject list</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabWeek" type="button" role="tab"><i class="bi bi-calendar-week"></i> Weekly timetable</button></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tabList" role="tabpanel">
                                <?php if ($subjects): ?>
                                    <div class="ui-filters mb-3">
                                        <select class="form-select form-select-sm" data-col-filter="3" aria-label="Filter by course">
                                            <option value="">All courses</option>
                                            <?php foreach ($usedCourses as $c): ?><option value="<?= admin_e($c) ?>"><?= admin_e($c) ?></option><?php endforeach; ?>
                                        </select>
                                        <select class="form-select form-select-sm" data-col-filter="4" aria-label="Filter by year level">
                                            <option value="">All year levels</option>
                                            <?php foreach ($usedYears as $y): ?><option value="<?= admin_e($y) ?>"><?= admin_e($y) ?></option><?php endforeach; ?>
                                        </select>
                                        <select class="form-select form-select-sm" data-col-filter="1" aria-label="Filter by type">
                                            <option value="">Lecture &amp; lab</option>
                                            <option value="Lecture">Lecture</option>
                                            <option value="Laboratory">Laboratory</option>
                                        </select>
                                    </div>
                                    <table id="subjects" class="display">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Type</th>
                                                <th>Schedule</th>
                                                <th>Course</th>
                                                <th>Year level</th>
                                                <th>Classes</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjects as $s):
                                                $id = (int) $s['id'];
                                                $archived = (int) $s['is_archived'] === 1; ?>
                                                <tr>
                                                    <td>
                                                        <span class="subj-name"><?= admin_e($s['name']) ?></span>
                                                        <span class="subj-code"><?= admin_e($s['code']) ?> · <?= admin_e($s['semester']) ?></span>
                                                    </td>
                                                    <td><span class="ui-tag"><?= admin_e($s['type']) ?></span></td>
                                                    <td>
                                                        <?php if (!empty($schedules[$id])): ?>
                                                            <?php foreach ($schedules[$id] as $slot): ?>
                                                                <span class="slot"><b><?= substr($slot['meeting_days'], 0, 3) ?></b> <?= time_label($slot['start_time']) ?> – <?= time_label($slot['end_time']) ?></span>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="ui-pill is-alert">No schedule</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= admin_e($s['course']) ?></td>
                                                    <td><?= admin_e($s['year_level']) ?></td>
                                                    <td><?= $classCounts[$id] ?? 0 ?></td>
                                                    <td><span class="ui-pill <?= $archived ? '' : 'is-ok' ?>"><?= $archived ? 'Archived' : 'Active' ?></span></td>
                                                    <td>
                                                        <div class="ui-acts">
                                                            <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#viewModal<?= $id ?>" title="View" aria-label="View"><i class="bi bi-eye"></i></button>
                                                            <?php if (!$archived): ?>
                                                                <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>" title="Edit" aria-label="Edit" <?= $activeSemester ? '' : 'disabled' ?>><i class="bi bi-pencil"></i></button>
                                                                <button type="button" class="ui-act is-danger" onclick="deleteSubject(<?= $id ?>, <?= admin_e(json_encode($s['name'])) ?>, <?= $classCounts[$id] ?? 0 ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="ui-empty">
                                        <i class="bi bi-journal-bookmark"></i>
                                        <h3>No subjects yet</h3>
                                        <p>Add the subjects offered this semester, with their meeting days and times.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="tab-pane fade" id="tabWeek" role="tabpanel">
                                <div class="week">
                                    <?php foreach ($timetable as $day => $slots): ?>
                                        <div class="week-day">
                                            <h3><?= $day ?></h3>
                                            <?php if ($slots): ?>
                                                <?php foreach ($slots as $slot): ?>
                                                    <div class="week-slot <?= $slot['subject']['type'] === 'Laboratory' ? 'is-lab' : '' ?>">
                                                        <strong><?= admin_e($slot['subject']['code']) ?></strong>
                                                        <?= admin_e($slot['subject']['name']) ?><br>
                                                        <span><?= time_label($slot['start_time']) ?> – <?= time_label($slot['end_time']) ?> · <?= admin_e($slot['subject']['course'] . ' ' . $slot['subject']['year_level']) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="week-empty">No meetings</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-muted small mt-2 mb-0"><span class="ui-tag">Blue</span> lecture · <span class="ui-tag">Gold</span> laboratory. Archived subjects are not shown.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <?php
    // Shared subject form fields (create + edit). $s is the subject being edited, or null.
    function subject_fields(?array $s, array $slots, array $days, array $courses, array $yearLevels, ?string $activeSemester, string $prefix): void
    {
        $selectedDays = array_column($slots, 'meeting_days');
        $start = $slots ? substr($slots[0]['start_time'], 0, 5) : '';
        $end = $slots ? substr($slots[0]['end_time'], 0, 5) : '';
        $nameField = $s ? 'name' : 'subjectName';
        $codeField = $s ? 'code' : 'subjectCode';
        $course = $s['course'] ?? '';
        $year = $s['year_level'] ?? '';
        ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label for="<?= $prefix ?>Name" class="form-label">Subject name</label>
                <input type="text" class="form-control" id="<?= $prefix ?>Name" name="<?= $nameField ?>" value="<?= admin_e($s['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="<?= $prefix ?>Code" class="form-label">Subject code</label>
                <input type="text" class="form-control" id="<?= $prefix ?>Code" name="<?= $codeField ?>" value="<?= admin_e($s['code'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="<?= $prefix ?>Type" class="form-label">Type</label>
                <select class="form-select" id="<?= $prefix ?>Type" name="type" required>
                    <option value="" disabled <?= $s ? '' : 'selected' ?>>Select a type</option>
                    <?php foreach (['Lecture', 'Laboratory'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($s['type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="<?= $prefix ?>Course" class="form-label">Course</label>
                <select class="form-select" id="<?= $prefix ?>Course" name="course" required>
                    <option value="" disabled <?= $course ? '' : 'selected' ?>>Select a course</option>
                    <?php if ($course && !isset($courses[$course])): ?><option value="<?= admin_e($course) ?>" selected><?= admin_e($course) ?></option><?php endif; ?>
                    <?php foreach ($courses as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $course === $value ? 'selected' : '' ?>><?= $value ?> — <?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="<?= $prefix ?>Year" class="form-label">Year level</label>
                <select class="form-select" id="<?= $prefix ?>Year" name="year_level" required>
                    <option value="" disabled <?= $year ? '' : 'selected' ?>>Select a year level</option>
                    <?php foreach ($yearLevels as $y): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <p class="ui-section-label">Weekly schedule</p>
        <div class="ui-day-picker mb-3" role="group" aria-label="Meeting days">
            <?php foreach ($days as $day): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="meeting_days[]" value="<?= $day ?>" id="<?= $prefix . $day ?>" <?= in_array($day, $selectedDays, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= $prefix . $day ?>"><?= substr($day, 0, 3) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <label for="<?= $prefix ?>Start" class="form-label">Start time</label>
                <input type="time" class="form-control" id="<?= $prefix ?>Start" name="start_time" value="<?= admin_e($start) ?>" required>
            </div>
            <div class="col-6">
                <label for="<?= $prefix ?>End" class="form-label">End time</label>
                <input type="time" class="form-control" id="<?= $prefix ?>End" name="end_time" value="<?= admin_e($end) ?>" required>
            </div>
        </div>
        <input type="hidden" name="semester" value="<?= admin_e($activeSemester ?? '') ?>">
        <?php
    }
    ?>

    <!-- Create subject -->
    <div class="modal fade" id="createSubjectModal" tabindex="-1" aria-labelledby="createSubjectLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="processes/admin/subjects/add.php" data-subject-form data-subject-id="0">
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-journal-plus"></i></span>
                    <h1 class="modal-title" id="createSubjectLabel">New subject</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($activeSemester): ?><p class="text-muted small">This subject will be added to <b><?= admin_e($activeSemester) ?></b>.</p><?php endif; ?>
                    <?php subject_fields(null, [], $days, $courses, $yearLevels, $activeSemester, 'new'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?= $activeSemester ? '' : 'disabled' ?>><i class="bi bi-check2"></i> Create subject</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($subjects as $s):
        $id = (int) $s['id'];
        $slots = $schedules[$id] ?? []; ?>

        <!-- View subject -->
        <div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewSubjectLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-journal-bookmark"></i></span>
                        <h1 class="modal-title" id="viewSubjectLabel<?= $id ?>"><?= admin_e($s['name']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="ui-dl">
                            <dt>Code</dt>
                            <dd><?= admin_e($s['code']) ?></dd>
                            <dt>Type</dt>
                            <dd><span class="ui-tag"><?= admin_e($s['type']) ?></span></dd>
                            <dt>Course &amp; year</dt>
                            <dd><?= admin_e($s['course'] . ' · ' . $s['year_level']) ?></dd>
                            <dt>Semester</dt>
                            <dd><?= admin_e($s['semester']) ?></dd>
                            <dt>Active classes</dt>
                            <dd><?= $classCounts[$id] ?? 0 ?></dd>
                            <dt>Status</dt>
                            <dd><span class="ui-pill <?= (int) $s['is_archived'] ? '' : 'is-ok' ?>"><?= (int) $s['is_archived'] ? 'Archived' : 'Active' ?></span></dd>
                            <dt>Added</dt>
                            <dd><?= $s['datetime_added'] ? date('M j, Y', strtotime($s['datetime_added'])) : '—' ?></dd>
                        </dl>
                        <p class="ui-section-label">Meeting times</p>
                        <?php if ($slots): ?>
                            <ul class="ui-list">
                                <?php foreach ($slots as $slot): ?>
                                    <li><b><?= admin_e($slot['meeting_days']) ?></b><span><?= time_label($slot['start_time']) ?> – <?= time_label($slot['end_time']) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="ui-list-empty">No meeting times set.</div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <?php if (!(int) $s['is_archived'] && $activeSemester): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!(int) $s['is_archived']): ?>
            <!-- Edit subject -->
            <div class="modal fade" id="editModal<?= $id ?>" tabindex="-1" aria-labelledby="editSubjectLabel<?= $id ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <form class="modal-content" method="POST" action="processes/admin/subjects/edit.php?id=<?= $id ?>" data-subject-form data-subject-id="<?= $id ?>">
                        <div class="modal-header">
                            <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                            <h1 class="modal-title" id="editSubjectLabel<?= $id ?>">Edit <?= admin_e($s['name']) ?></h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <?php subject_fields($s, $slots, $days, $courses, $yearLevels, $activeSemester, 'edit' . $id); ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" <?= $activeSemester ? '' : 'disabled' ?>><i class="bi bi-check2"></i> Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        const scheduleSlots = <?= json_encode($clashData) ?>;

        $(function () {
            const table = $('#subjects').DataTable({
                order: [[6, 'asc'], [0, 'asc']],
                columnDefs: [{ targets: 7, orderable: false, searchable: false }]
            });
            document.querySelectorAll('[data-col-filter]').forEach(function (select) {
                select.addEventListener('change', function () {
                    const value = select.value ? '^' + $.fn.dataTable.util.escapeRegex(select.value) + '$' : '';
                    table.column(+select.dataset.colFilter).search(value, true, false).draw();
                });
            });
        });

        // Validate days/times and warn about clashes before the server rejects the save
        document.querySelectorAll('form[data-subject-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const subjectId = +form.dataset.subjectId;
                const days = Array.from(form.querySelectorAll('input[name="meeting_days[]"]:checked')).map(i => i.value);
                const start = form.querySelector('input[name=start_time]').value;
                const end = form.querySelector('input[name=end_time]').value;

                let problem = '';
                if (!days.length) problem = 'Pick at least one meeting day.';
                else if (end <= start) problem = 'The end time must be after the start time.';
                if (problem) {
                    e.preventDefault();
                    Swal.fire('Check the schedule', problem, 'warning');
                    return;
                }

                // Same overlap rule as the server (boundaries count as overlapping)
                const clashes = scheduleSlots.filter(s => s.id !== subjectId && days.includes(s.day) && start <= s.end && end >= s.start);
                if (clashes.length) {
                    e.preventDefault();
                    const list = clashes.map(c => '<li><b>' + $('<div>').text(c.name).html() + '</b> — ' + c.day + ' ' + c.start + '–' + c.end + '</li>').join('');
                    Swal.fire({
                        title: 'Schedule clash',
                        html: 'These subjects already meet at that time:<ul class="text-start mt-2">' + list + '</ul>Pick different days or times.',
                        icon: 'warning'
                    });
                }
            });
        });

        function deleteSubject(id, name, classCount) {
            Swal.fire({
                title: 'Delete ' + name + '?',
                html: classCount
                    ? 'It is used by <b>' + classCount + ' active class' + (classCount === 1 ? '' : 'es') + '</b>. Deleting the subject removes its schedule and may leave those classes without a subject.'
                    : 'Its weekly schedule will be removed too.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete subject',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = 'processes/admin/subjects/delete.php?id=' + id;
            });
        }
    </script>

    <?php
    $subjectAlerts = [
        'ADMIN_SUBJECT_ADD_SUCCESS' => ['Subject added', 'The subject and its schedule have been saved.', 'success'],
        'ADMIN_SUBJECT_ADD_FAIL' => ['Error', 'The subject could not be added. Please try again.', 'error'],
        'ADMIN_SUBJECT_EXISTS' => ['Subject already exists', 'A subject with this code already exists.', 'error'],
        'ADMIN_SUBJECT_UPDATE_SUCCESS' => ['Saved', 'The subject has been updated.', 'success'],
        'ADMIN_SUBJECT_UPDATE_ERROR' => ['Error', 'The subject could not be updated. Please try again.', 'error'],
        'ADMIN_SUBJECT_DELETE_SUCCESS' => ['Deleted', 'The subject has been deleted.', 'success'],
        'ADMIN_SUBJECT_DELETE_ERROR' => ['Error', 'The subject could not be deleted. Please try again.', 'error'],
        'SCHEDULE_CONFLICT' => ['Schedule clash', 'Another subject already meets at that day and time.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $subjectAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $subjectAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
