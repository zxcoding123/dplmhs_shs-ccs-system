<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/../includes/academic.php';

// Advisory sections grouped by strand (STRAND-GRADE+SECTION, e.g. STEM-11A)
$sections = shs_sections();

$classes = $pdo->query("SELECT * FROM classes WHERE status IS NOT NULL
    ORDER BY is_archived, FIELD(status, 'pending', 'accepted', 'disapproved', 'rejected'), name")->fetchAll(PDO::FETCH_ASSOC);

$advisers = [];
foreach ($pdo->query("SELECT class_advising, fullName FROM staff_advising") as $row) {
    $advisers[$row['class_advising']] = $row['fullName'];
}

$teachers = $pdo->query("SELECT fullName FROM staff_accounts WHERE fullName IS NOT NULL ORDER BY fullName")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT id, name, code, type, course, year_level FROM subjects WHERE is_archived = 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$activeSemester = admin_count($pdo, "SELECT name FROM semester WHERE status = 'active' LIMIT 1", [], null);

// Class rosters in one query
$rosters = [];
foreach ($pdo->query("SELECT se.class_id, s.first_name, s.middle_name, s.last_name, s.gender, s.course, s.year_level
    FROM students_enrollments se JOIN students s ON se.student_id = s.student_id
    ORDER BY s.last_name, s.first_name") as $row) {
    $rosters[$row['class_id']][] = $row;
}

$schedules = [];
foreach ($pdo->query("SELECT subject_id, meeting_days, start_time, end_time FROM subjects_schedules ORDER BY FIELD(meeting_days, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time") as $row) {
    $schedules[$row['subject_id']][] = substr($row['meeting_days'], 0, 3) . ' ' . date('g:i A', strtotime($row['start_time'])) . '–' . date('g:i A', strtotime($row['end_time']));
}

/**
 * Weekly schedule with days that share a time merged, same rules as groupSchedule() in the page script:
 * ["Mon 7:00 AM–4:00 PM", "Tue 7:00 AM–4:00 PM"] → <div class="cls-times"> "Mon–Tue | 7:00 AM–4:00 PM" </div>
 */
function class_schedule_html(array $slots): string
{
    if (!$slots) {
        return '<span class="text-muted fst-italic">Not scheduled yet</span>';
    }
    $week = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $byTime = [];
    foreach ($slots as $slot) {
        $byTime[substr($slot, 4)][] = array_search(substr($slot, 0, 3), $week, true);
    }
    $html = '<div class="cls-times">';
    foreach ($byTime as $time => $days) {
        sort($days);
        $runs = [];
        foreach ($days as $d) {
            $last = count($runs) - 1;
            if ($last >= 0 && $d === $runs[$last][1] + 1) $runs[$last][1] = $d; else $runs[] = [$d, $d];
        }
        $label = implode(', ', array_map(fn($r) => $r[1] - $r[0] >= 2 ? $week[$r[0]] . '–' . $week[$r[1]]
            : ($r[0] === $r[1] ? $week[$r[0]] : $week[$r[0]] . ', ' . $week[$r[1]]), $runs));
        $html .= '<span class="days">' . admin_e($label) . '</span><span class="time">' . admin_e($time) . '</span>';
    }
    return $html . '</div>';
}

function class_state(array $c): array
{
    if ((int) $c['is_archived'] === 1) return ['Archived', ''];
    switch ($c['status']) {
        case 'pending': return ['Pending approval', 'is-warn'];
        case 'accepted': return ['Active', 'is-ok'];
        case 'disapproved':
        case 'rejected': return ['Disapproved', 'is-alert'];
        default: return [ucfirst((string) $c['status']), ''];
    }
}

$counts = ['Pending approval' => 0, 'Active' => 0, 'Archived' => 0, 'Disapproved' => 0];
foreach ($classes as $c) {
    $state = class_state($c)[0];
    $counts[$state] = ($counts[$state] ?? 0) + 1;
}
// Count only enrollments in classes listed here (orphaned rows for deleted classes are ignored)
$enrolled = array_sum(array_map(fn($c) => count($rosters[$c['id']] ?? []), $classes));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Classes | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .cls-section { font-weight: 700; color: var(--ui-navy); }
        .cls-sub { display: block; font-size: 0.8rem; color: var(--ui-muted); }
        .cls-roster-btn { border: 0; background: none; padding: 0; font-weight: 700; color: var(--ui-blue); text-decoration: underline dotted; }
        .cls-note { display: block; max-width: 260px; font-size: 0.78rem; color: var(--ui-muted); white-space: normal; }
        .code-box { display: flex; gap: 0.5rem; align-items: center; }
        .cls-summary { height: 100%; padding: 1rem 1.1rem; border: 1px solid var(--ui-line); border-radius: var(--ui-radius); background: var(--ui-soft); }
        .cls-summary-title { display: flex; align-items: center; gap: 0.4rem; margin: 0 0 0.75rem; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ui-muted); }
        .cls-summary dl { margin: 0; }
        .cls-summary dt { font-size: 0.75rem; font-weight: 600; color: var(--ui-muted); }
        .cls-summary dd { margin: 0.1rem 0 0.85rem; font-weight: 600; color: var(--ui-ink); overflow-wrap: anywhere; }
        .cls-summary dd.is-empty { font-weight: 400; font-style: italic; color: var(--ui-muted); }
        .cls-summary dd.is-warn { color: var(--ui-amber); }
        .cls-times { display: grid; grid-template-columns: auto 1fr; gap: 0.3rem 0.75rem; margin-top: 0.15rem; padding: 0.5rem 0.65rem; border: 1px solid var(--ui-line); border-radius: 8px; background: var(--ui-surface); font-size: 0.85rem; }
        .cls-times .days { font-weight: 700; color: var(--ui-navy); white-space: nowrap; }
        .cls-times .time { font-weight: 500; color: var(--ui-ink); }
        .cls-summary-foot { margin: 0; padding-top: 0.6rem; border-top: 1px solid var(--ui-line); font-size: 0.75rem; color: var(--ui-muted); }
        .code-box code { padding: 0.3rem 0.6rem; border-radius: 6px; background: var(--ui-soft); font-size: 1rem; font-weight: 700; letter-spacing: 0.12em; color: var(--ui-navy); }
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
                    <?php admin_page_header('Classes', 'Subject classes per section — approve teacher requests and manage rosters', 'bi-door-open',
                        '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createClassModal"' . ($activeSemester ? '' : ' disabled title="Activate a semester first"') . '><i class="bi bi-plus-lg"></i> New class</button>'); ?>

                    <?php if (!$activeSemester): ?>
                        <div class="ui-callout is-alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div><b>No active semester.</b> New classes are created in the active semester — <a href="semester_management.php">activate one first</a>.</div>
                        </div>
                    <?php elseif ($counts['Pending approval'] > 0): ?>
                        <div class="ui-callout">
                            <i class="bi bi-hourglass-split"></i>
                            <div><b><?= $counts['Pending approval'] ?> class request<?= $counts['Pending approval'] === 1 ? '' : 's' ?></b> from teachers waiting for your approval. They're listed first below.</div>
                        </div>
                    <?php endif; ?>

                    <?php admin_stat_tiles([
                        ['label' => 'Active classes', 'value' => $counts['Active'], 'icon' => 'bi-door-open', 'tone' => 'ok', 'filter' => 'Active', 'filter_col' => 'Status'],
                        ['label' => 'Pending approval', 'value' => $counts['Pending approval'], 'icon' => 'bi-hourglass-split', 'tone' => $counts['Pending approval'] ? 'alert' : 'muted', 'filter' => 'Pending approval', 'filter_col' => 'Status'],
                        ['label' => 'Learners enrolled', 'value' => $enrolled, 'icon' => 'bi-people'],
                        ['label' => 'Archived', 'value' => $counts['Archived'], 'icon' => 'bi-archive', 'tone' => 'muted', 'filter' => 'Archived', 'filter_col' => 'Status', 'hint' => $counts['Disapproved'] . ' disapproved request' . ($counts['Disapproved'] === 1 ? '' : 's')],
                    ]); ?>

                    <section class="ui-panel">
                        <?php if ($classes): ?>
                            <div class="ui-panel-head">
                                <div>
                                    <h2>All classes</h2>
                                    <p>Click a learner count to see the class roster.</p>
                                </div>
                                <div class="ui-filters">
                                    <select class="form-select form-select-sm" id="semesterFilter" aria-label="Filter by semester">
                                        <option value="">All semesters</option>
                                        <?php foreach (array_unique(array_column($classes, 'semester')) as $sem): ?><option value="<?= admin_e($sem) ?>"><?= admin_e($sem) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <table id="classes" class="display">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Semester</th>
                                        <th>Learners</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $c):
                                        $id = (int) $c['id'];
                                        [$stateLabel, $stateTone] = class_state($c);
                                        $learners = count($rosters[$id] ?? []); ?>
                                        <tr>
                                            <td>
                                                <span class="cls-section"><?= admin_e($c['name']) ?></span>
                                                <span class="cls-sub">Adviser: <?= admin_e($advisers[$c['name']] ?? 'None') ?></span>
                                            </td>
                                            <td>
                                                <?= admin_e($c['subject']) ?> <span class="ui-tag"><?= admin_e($c['type']) ?></span>
                                                <span class="cls-sub"><?= admin_e($c['code']) ?></span>
                                            </td>
                                            <td><?= admin_e($c['teacher'] ?: '—') ?></td>
                                            <td><?= admin_e($c['semester']) ?></td>
                                            <td data-order="<?= $learners ?>">
                                                <button type="button" class="cls-roster-btn" data-bs-toggle="modal" data-bs-target="#studentModal<?= $id ?>" aria-label="View roster for <?= admin_e($c['name']) ?>"><?= $learners ?></button>
                                            </td>
                                            <td>
                                                <span class="ui-pill <?= $stateTone ?>"><?= $stateLabel ?></span>
                                                <?php if ($c['status'] === 'pending' && !(int) $c['is_archived']): ?>
                                                    <span class="cls-note">Requested by <?= admin_e($c['requestor']) ?></span>
                                                <?php elseif ($stateLabel === 'Disapproved' && $c['reason']): ?>
                                                    <span class="cls-note">Reason: <?= admin_e($c['reason']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="ui-acts">
                                                    <?php if ($c['status'] === 'pending' && !(int) $c['is_archived']): ?>
                                                        <button type="button" class="ui-act is-ok" onclick="approveClass(<?= admin_e(json_encode(['id' => $id, 'subject' => $c['subject'], 'requestor' => $c['requestor'], 'class' => $c['name']])) ?>)" title="Approve" aria-label="Approve"><i class="bi bi-check2-square"></i> Approve</button>
                                                        <button type="button" class="ui-act is-danger" onclick="openDisapprove(<?= admin_e(json_encode(['id' => $id, 'subject' => $c['subject'], 'requestor' => $c['requestor'], 'class' => $c['name']])) ?>)" title="Disapprove" aria-label="Disapprove"><i class="bi bi-x-circle"></i></button>
                                                    <?php else: ?>
                                                        <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#viewModal<?= $id ?>" title="View" aria-label="View"><i class="bi bi-eye"></i></button>
                                                        <?php if ($stateLabel === 'Active'): ?>
                                                            <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                                        <?php endif; ?>
                                                        <?php if ($stateLabel !== 'Archived'): ?>
                                                            <button type="button" class="ui-act is-danger" onclick="confirmDelete(<?= $id ?>, <?= admin_e(json_encode($c['name'] . ' · ' . $c['subject'])) ?>, <?= $learners ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="ui-empty">
                                <i class="bi bi-door-open"></i>
                                <h3>No classes yet</h3>
                                <p>Create a class by pairing a section with a subject and a teacher, or wait for teachers to request one.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- Create class -->
    <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" action="processes/admin/classes/add.php" method="POST" id="createClassForm">
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-door-open"></i></span>
                    <h1 class="modal-title" id="createClassLabel">New class</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="semester" value="<?= admin_e($activeSemester ?? '') ?>">
                    <input type="hidden" name="subjectTypeClass" id="newSubjectType">
                    <div class="row g-4">
                        <!-- What the admin chooses -->
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="newSection" class="form-label">Section</label>
                                <select class="form-select" name="selectedClassAdd" id="newSection" required>
                                    <option value="" selected disabled>Select a section</option>
                                    <?php foreach ($sections as $dept => $list): ?>
                                        <optgroup label="<?= admin_e(shs_strand_label($dept)) ?>">
                                            <?php foreach ($list as $sec): ?><option value="<?= $sec ?>"><?= str_replace('-', ' – ', $sec) ?></option><?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="newSubject" class="form-label">Subject</label>
                                <select class="form-select" name="subjectNameClass" id="newSubject" required disabled>
                                    <option value="">Select a section first</option>
                                </select>
                                <div class="form-text" id="newSubjectHint">Subjects for the chosen section's strand and grade appear here.</div>
                            </div>
                            <div class="mb-3">
                                <label for="newTeacher" class="form-label">Subject teacher</label>
                                <select class="form-select" name="teacher" id="newTeacher" required>
                                    <option value="" selected disabled><?= $teachers ? 'Select a teacher' : 'No teachers added yet' ?></option>
                                    <?php foreach ($teachers as $t): ?><option value="<?= admin_e($t) ?>"><?= admin_e(preg_replace('/\s+/', ' ', $t)) ?></option><?php endforeach; ?>
                                </select>
                                <div class="form-text">They're notified once the class is created.</div>
                            </div>
                            <div>
                                <label for="newDesc" class="form-label">Description</label>
                                <textarea class="form-control" id="newDesc" name="classDesc" rows="3" required maxlength="500" placeholder="e.g. Bring a scientific calculator. Quizzes every Friday."></textarea>
                                <div class="form-text">Shown to learners when they join the class.</div>
                            </div>
                        </div>

                        <!-- Filled in automatically -->
                        <div class="col-md-5">
                            <aside class="cls-summary" aria-live="polite">
                                <h2 class="cls-summary-title"><i class="bi bi-info-circle"></i> Class details</h2>
                                <dl>
                                    <dt>Semester</dt>
                                    <dd><?= $activeSemester ? admin_e($activeSemester) : '<span class="text-danger">No active semester</span>' ?></dd>
                                    <dt>Class adviser</dt>
                                    <dd id="newAdviser" class="is-empty">Pick a section</dd>
                                    <dt>Type</dt>
                                    <dd id="newSubjectTypeView" class="is-empty">Pick a subject</dd>
                                    <dt>Weekly schedule</dt>
                                    <dd id="newSchedule" class="is-empty">Pick a subject</dd>
                                </dl>
                                <p class="cls-summary-foot">These come from the section and subject setup. Change them under Sections or Subjects.</p>
                            </aside>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?= $activeSemester ? '' : 'disabled' ?>><i class="bi bi-check2"></i> Create class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Disapprove request (shared) -->
    <div class="modal fade" id="disapproveModal" tabindex="-1" aria-labelledby="disapproveLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" id="disapproveForm">
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-x-circle"></i></span>
                    <h1 class="modal-title" id="disapproveLabel">Disapprove class request</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="disapproveSummary"></p>
                    <label for="disapproveReason" class="form-label">Reason</label>
                    <select class="form-select mb-3" id="disapproveReason" name="reason" required>
                        <option value="" disabled selected>Select a reason</option>
                        <option value="Incomplete information">Incomplete information</option>
                        <option value="Class does not meet criteria">Class does not meet criteria</option>
                        <option value="Duplicate class">Duplicate class</option>
                        <option value="Other">Other…</option>
                    </select>
                    <div class="d-none" id="otherReasonWrap">
                        <label for="otherReason" class="form-label">Describe the reason</label>
                        <textarea class="form-control" id="otherReason" name="other_reason" rows="3"></textarea>
                    </div>
                    <small class="text-muted">The teacher is notified with this reason.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-x-circle"></i> Disapprove</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($classes as $c):
        $id = (int) $c['id'];
        [$stateLabel, $stateTone] = class_state($c);
        $roster = $rosters[$id] ?? [];
        $archived = (int) $c['is_archived'] === 1;
        $gradesPage = $c['type'] === 'Laboratory' ? 'lab_grades.php' : ($c['type'] === 'Lecture' ? 'lecture_grades.php' : null);
        if ($gradesPage && $archived) $gradesPage = 'archived_' . $gradesPage;
        $gradesReady = $c['grade_checker'] === 'available'; ?>

        <!-- Roster -->
        <div class="modal fade" id="studentModal<?= $id ?>" tabindex="-1" aria-labelledby="rosterLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-people"></i></span>
                        <h1 class="modal-title" id="rosterLabel<?= $id ?>"><?= admin_e($c['name']) ?> · <?= admin_e($c['subject']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($roster):
                            $byGender = [];
                            foreach ($roster as $r) $byGender[$r['gender'] ?: 'Not specified'][] = $r;
                            ksort($byGender); ?>
                            <p class="text-muted small mb-2"><?= count($roster) ?> learner<?= count($roster) === 1 ? '' : 's' ?> enrolled</p>
                            <?php foreach ($byGender as $gender => $list): ?>
                                <p class="ui-section-label"><?= admin_e($gender) ?> (<?= count($list) ?>)</p>
                                <ul class="ui-list">
                                    <?php foreach ($list as $r): ?>
                                        <li><span><?= admin_e(trim(preg_replace('/\s+/', ' ', $r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']))) ?></span><span class="text-muted small"><?= admin_e($r['course'] . ' ' . $r['year_level']) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ui-list-empty">No learners have joined this class yet. Share the class code <b><?= admin_e($c['classCode']) ?></b> with them.</div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View class -->
        <div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewClassLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-door-open"></i></span>
                        <h1 class="modal-title" id="viewClassLabel<?= $id ?>"><?= admin_e($c['name']) ?> · <?= admin_e($c['subject']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="ui-dl">
                            <dt>Status</dt>
                            <dd><span class="ui-pill <?= $stateTone ?>"><?= $stateLabel ?></span></dd>
                            <dt>Class code</dt>
                            <dd>
                                <div class="code-box">
                                    <code><?= admin_e($c['classCode']) ?></code>
                                    <button type="button" class="ui-act" onclick="copyCode(<?= admin_e(json_encode($c['classCode'])) ?>)" title="Copy class code" aria-label="Copy class code"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </dd>
                            <dt>Subject</dt>
                            <dd><?= admin_e($c['subject']) ?> <span class="ui-tag"><?= admin_e($c['type']) ?></span> <span class="text-muted"><?= admin_e($c['code']) ?></span></dd>
                            <dt>Schedule</dt>
                            <dd><?= class_schedule_html($schedules[$c['subject_id']] ?? []) ?></dd>
                            <dt>Teacher</dt>
                            <dd><?= admin_e($c['teacher'] ?: '—') ?></dd>
                            <dt>Adviser</dt>
                            <dd><?= admin_e($advisers[$c['name']] ?? 'None') ?></dd>
                            <dt>Semester</dt>
                            <dd><?= admin_e($c['semester']) ?></dd>
                            <dt>Learners</dt>
                            <dd><?= count($roster) ?></dd>
                            <dt>Description</dt>
                            <dd><?= admin_e($c['description'] ?: '—') ?></dd>
                            <dt>Created</dt>
                            <dd><?= $c['datetime_added'] ? date('M j, Y g:i A', strtotime($c['datetime_added'])) : '—' ?></dd>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <?php if ($gradesPage): ?>
                            <?php if ($gradesReady): ?>
                                <a class="btn btn-ui-gold me-auto" href="<?= $gradesPage ?>?id=<?= $id ?>&amp;subject_id=<?= (int) $c['subject_id'] ?>"><i class="bi bi-clipboard-data"></i> View grades</a>
                            <?php else: ?>
                                <span class="text-muted small me-auto"><i class="bi bi-lock"></i> Grades not submitted yet</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <?php if ($stateLabel === 'Active'): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($stateLabel === 'Active'): ?>
            <!-- Edit class -->
            <div class="modal fade" id="editModal<?= $id ?>" tabindex="-1" aria-labelledby="editClassLabel<?= $id ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <form class="modal-content" action="processes/admin/classes/update.php?id=<?= $id ?>" method="POST">
                        <div class="modal-header">
                            <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                            <h1 class="modal-title" id="editClassLabel<?= $id ?>">Edit <?= admin_e($c['name']) ?> · <?= admin_e($c['subject']) ?></h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="semester" value="<?= admin_e($c['semester']) ?>">
                            <input type="hidden" name="subjectNameClass" value="<?= admin_e($c['subject']) ?>">
                            <div class="row g-4">
                                <!-- What the admin can change -->
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label for="editSection<?= $id ?>" class="form-label">Section</label>
                                        <select class="form-select" name="selectedClassAdd" id="editSection<?= $id ?>" required data-adviser-target="#editAdviser<?= $id ?>">
                                            <?php $known = in_array($c['name'], array_merge(...array_values($sections)), true); ?>
                                            <?php if (!$known): ?><option value="<?= admin_e($c['name']) ?>" selected><?= admin_e($c['name']) ?></option><?php endif; ?>
                                            <?php foreach ($sections as $dept => $list): ?>
                                                <optgroup label="<?= admin_e(shs_strand_label($dept)) ?>">
                                                    <?php foreach ($list as $sec): ?><option value="<?= $sec ?>" <?= $c['name'] === $sec ? 'selected' : '' ?>><?= str_replace('-', ' – ', $sec) ?></option><?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editTeacher<?= $id ?>" class="form-label">Subject teacher</label>
                                        <select class="form-select" id="editTeacher<?= $id ?>" name="teacher" required>
                                            <?php if ($c['teacher'] && !in_array($c['teacher'], $teachers, true)): ?><option value="<?= admin_e($c['teacher']) ?>" selected><?= admin_e($c['teacher']) ?> (no longer on staff)</option><?php endif; ?>
                                            <?php if (!$c['teacher']): ?><option value="" selected disabled>Select a teacher</option><?php endif; ?>
                                            <?php foreach ($teachers as $t): ?><option value="<?= admin_e($t) ?>" <?= $c['teacher'] === $t ? 'selected' : '' ?>><?= admin_e(preg_replace('/\s+/', ' ', $t)) ?></option><?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Changing the teacher notifies them and the enrolled learners.</div>
                                    </div>
                                    <div>
                                        <label for="editDesc<?= $id ?>" class="form-label">Description</label>
                                        <textarea class="form-control" id="editDesc<?= $id ?>" name="classDesc" rows="3" required maxlength="500"><?= admin_e($c['description']) ?></textarea>
                                        <div class="form-text">Shown to learners in the class.</div>
                                    </div>
                                </div>

                                <!-- Fixed for this class, or follows the section -->
                                <div class="col-md-5">
                                    <aside class="cls-summary" aria-live="polite">
                                        <h2 class="cls-summary-title"><i class="bi bi-info-circle"></i> Class details</h2>
                                        <dl>
                                            <dt>Subject</dt>
                                            <dd><?= admin_e($c['subject']) ?> <span class="text-muted fw-normal"><?= admin_e($c['code']) ?></span></dd>
                                            <dt>Type</dt>
                                            <dd><?= admin_e($c['type']) ?></dd>
                                            <dt>Semester</dt>
                                            <dd><?= admin_e($c['semester']) ?></dd>
                                            <dt>Class adviser</dt>
                                            <dd id="editAdviser<?= $id ?>" class="<?= isset($advisers[$c['name']]) ? '' : 'is-warn' ?>"><?= admin_e($advisers[$c['name']] ?? 'No adviser assigned yet') ?></dd>
                                            <dt>Weekly schedule</dt>
                                            <dd><?= class_schedule_html($schedules[$c['subject_id']] ?? []) ?></dd>
                                        </dl>
                                        <p class="cls-summary-foot">To change the subject, create a new class. The adviser follows the section you pick.</p>
                                    </aside>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        const advisers = <?= json_encode($advisers) ?>;
        const subjects = <?= json_encode($subjects) ?>;
        const subjectSchedules = <?= json_encode($schedules) ?>;

        $(function () {
            const table = $('#classes').DataTable({
                order: [],
                columnDefs: [{ targets: 6, orderable: false, searchable: false }]
            });
            $('#semesterFilter').on('change', function () {
                table.column(3).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
            });
        });

        // ---- Create class: section → adviser + matching subjects → type + schedule ----
        const sectionSelect = document.getElementById('newSection');
        const subjectSelect = document.getElementById('newSubject');

        // Summary panel value: text, or a muted placeholder; tone 'warn' highlights a gap to fix
        function setDetail(id, text, placeholder, tone) {
            const el = document.getElementById(id);
            el.classList.toggle('is-empty', !text);
            el.classList.toggle('is-warn', tone === 'warn');
            el.textContent = text || placeholder;
        }

        sectionSelect.addEventListener('change', function () {
            const [course, year] = sectionSelect.value.split('-');
            const adviser = advisers[sectionSelect.value];
            setDetail('newAdviser', adviser || 'No adviser assigned yet', '', adviser ? '' : 'warn');

            const matching = subjects.filter(s => s.course === course && s.year_level === year);
            const list = matching.length ? matching : subjects;
            document.getElementById('newSubjectHint').textContent = matching.length
                ? 'Showing subjects for ' + course + ' ' + year + '.'
                : (subjects.length ? 'No subjects are set up for ' + course + ' ' + year + ' — showing all subjects.' : 'No subjects exist yet. Add them under Subjects first.');

            subjectSelect.innerHTML = '<option value="" selected disabled>Select a subject</option>';
            list.forEach(function (s) {
                const option = new Option(s.name + ' (' + s.code + ') · ' + s.type, s.name);
                option.dataset.type = s.type;
                option.dataset.id = s.id;
                subjectSelect.add(option);
            });
            subjectSelect.disabled = list.length === 0;
            subjectSelect.dispatchEvent(new Event('change'));
        });

        subjectSelect.addEventListener('change', function () {
            const option = subjectSelect.selectedOptions[0];
            const type = option && option.dataset.type ? option.dataset.type : '';
            document.getElementById('newSubjectType').value = type;
            setDetail('newSubjectTypeView', type, 'Pick a subject');

            const schedule = document.getElementById('newSchedule');
            const slots = option && option.dataset.id ? (subjectSchedules[option.dataset.id] || []) : null;
            if (!slots) {
                setDetail('newSchedule', '', 'Pick a subject');
            } else if (!slots.length) {
                setDetail('newSchedule', 'Not scheduled yet', '', 'warn');
            } else {
                schedule.className = '';
                const grid = Object.assign(document.createElement('div'), { className: 'cls-times' });
                groupSchedule(slots).forEach(function ([days, time]) {
                    grid.append(
                        Object.assign(document.createElement('span'), { className: 'days', textContent: days }),
                        Object.assign(document.createElement('span'), { className: 'time', textContent: time })
                    );
                });
                schedule.replaceChildren(grid);
            }
        });

        // ["Mon 7:00 AM–4:00 PM", "Tue 7:00 AM–4:00 PM", "Fri 1:00 PM–3:00 PM"] → [["Mon–Tue", "7:00 AM–4:00 PM"], ["Fri", "1:00 PM–3:00 PM"]]
        // Days sharing a time are merged; a consecutive run becomes a range, otherwise a list.
        function groupSchedule(slots) {
            const week = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const byTime = new Map();
            slots.forEach(function (slot) {
                const day = slot.slice(0, 3);
                const time = slot.slice(4);
                if (!byTime.has(time)) byTime.set(time, []);
                byTime.get(time).push(day);
            });
            return Array.from(byTime, function ([time, days]) {
                const idx = days.map(d => week.indexOf(d)).sort((a, b) => a - b);
                const runs = [];
                idx.forEach(function (i) {
                    const last = runs[runs.length - 1];
                    if (last && i === last[1] + 1) last[1] = i; else runs.push([i, i]);
                });
                const label = runs.map(([a, b]) => b - a >= 2 ? week[a] + '–' + week[b] : (a === b ? week[a] : week[a] + ', ' + week[b])).join(', ');
                return [label, time];
            });
        }

        // Opening the modal again starts from a clean form
        document.getElementById('createClassModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('createClassForm').reset();
            subjectSelect.innerHTML = '<option value="">Select a section first</option>';
            subjectSelect.disabled = true;
            document.getElementById('newSubjectHint').textContent = "Subjects for the chosen section's strand and grade appear here.";
            setDetail('newAdviser', '', 'Pick a section');
            setDetail('newSubjectTypeView', '', 'Pick a subject');
            setDetail('newSchedule', '', 'Pick a subject');
        });

        // Edit modal: adviser follows the chosen section
        document.querySelectorAll('[data-adviser-target]').forEach(function (select) {
            select.addEventListener('change', function () {
                const target = document.querySelector(select.dataset.adviserTarget);
                target.textContent = advisers[select.value] || 'No adviser assigned yet';
                target.classList.toggle('is-warn', !advisers[select.value]);
            });
        });

        // ---- Approve / disapprove teacher requests ----
        function requestQuery(req) {
            return new URLSearchParams({ id: req.id, subject: req.subject, requestor: req.requestor, class: req.class }).toString();
        }

        function approveClass(req) {
            Swal.fire({
                title: 'Approve this class?',
                html: '<b>' + $('<div>').text(req.class + ' · ' + req.subject).html() + '</b><br>requested by ' + $('<div>').text(req.requestor).html(),
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Approve'
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = 'processes/admin/classes/accept.php?' + requestQuery(req);
            });
        }

        const reasonSelect = document.getElementById('disapproveReason');
        const otherWrap = document.getElementById('otherReasonWrap');
        const otherReason = document.getElementById('otherReason');

        function openDisapprove(req) {
            const form = document.getElementById('disapproveForm');
            form.action = 'processes/admin/classes/reject.php?' + requestQuery(req);
            document.getElementById('disapproveSummary').innerHTML = 'Request for <b>' + $('<div>').text(req.class + ' · ' + req.subject).html() + '</b> from ' + $('<div>').text(req.requestor).html() + '.';
            reasonSelect.value = '';
            otherReason.value = '';
            reasonSelect.dispatchEvent(new Event('change'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('disapproveModal')).show();
        }

        reasonSelect.addEventListener('change', function () {
            const other = reasonSelect.value === 'Other';
            otherWrap.classList.toggle('d-none', !other);
            otherReason.required = other;
        });

        // ---- Misc ----
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(function () {
                Swal.fire({ icon: 'success', title: 'Copied', text: 'Class code ' + code + ' copied to the clipboard.', timer: 1400, showConfirmButton: false });
            }).catch(function () {
                Swal.fire('Copy failed', 'Select the code and copy it manually.', 'error');
            });
        }

        function confirmDelete(id, label, learners) {
            Swal.fire({
                title: 'Delete ' + label + '?',
                html: learners ? '<b>' + learners + ' enrolled learner' + (learners === 1 ? '' : 's') + '</b> will lose access to this class. This cannot be undone.' : 'This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete class',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (!result.isConfirmed) return;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'processes/admin/classes/delete.php';
                form.innerHTML = '<input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>

    <?php
    $classAlerts = [
        'ADDED_NEW_CLASS_SUCCESS' => ['Class created', 'The class has been created and the teacher notified.', 'success'],
        'ADDED_NEW_CLASS_FAILED' => ['Error', 'The class could not be created. Please try again.', 'error'],
        'NEW_CLASS_EXISTS' => ['Class already exists', 'A class for this subject and type already exists.', 'error'],
        'NO_ACTIVE_SEMESTER' => ['No active semester', 'Activate a semester before creating classes.', 'warning'],
        'EDIT_CLASS_SUCCESS' => ['Saved', 'The class has been updated.', 'success'],
        'EDIT_CLASS_ERROR' => ['Error', 'The class could not be updated. Please try again.', 'error'],
        'EDIT_CLASS_FIELDS_REQUIRED' => ['Missing details', 'Section, teacher and semester are required.', 'warning'],
        'EDIT_CLASS_TEACHER_NOT_FOUND' => ['Teacher not found', 'The selected teacher no longer has a staff account.', 'error'],
        'CLASS_STATUS_ACCEPTED' => ['Approved', 'The class request has been approved and the teacher notified.', 'success'],
        'CLASS_STATUS_ERROR' => ['Error', 'The class request could not be approved.', 'error'],
        'CLASS_STATUS_DISAPPROVED' => ['Disapproved', 'The teacher has been notified with your reason.', 'success'],
        'CLASS_STATUS_DISAPPROVE_ERROR' => ['Error', 'The class request could not be disapproved.', 'error'],
        'DELETE_CLASS_SUCCESS' => ['Deleted', 'The class has been deleted.', 'success'],
        'DELETE_CLASS_FAIL' => ['Error', 'The class could not be deleted.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $classAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $classAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
