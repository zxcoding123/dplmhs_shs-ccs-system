<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/academic.php';
// include('processes/server/automatic_grader_cron.php');

function getAbsenceBadge($absences)
{
    if ($absences == 2) {
        return '<span class="badge bg-warning text-dark">2 Absences</span>';
    }
    if ($absences == 3) {
        return '<span class="badge bg-orange text-white">3 Absences</span>';
    }
    if ($absences > 3) {
        return '<span class="badge bg-danger text-white">3+ Absences</span>';
    }
    return ''; // No badge
}

// ---------- Page data ----------
$teacher_id = $_SESSION['teacher_id'];
$today = date('l');

$classes = [];
$active_class_count = 0;
$todays_classes = [];
$average_attendance_rate = null;
$excessive_absences = [];
$pending_activities = 0;
$currentSemester = null;
$subjects = [];
$semesters = [];

try {
    $teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
    $teacher_stmt->execute(['teacher_id' => $teacher_id]);
    $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo "Teacher not found.";
        exit;
    }

    $teacher_name = $teacher['fullName'];

    // Every class the teacher owns, with the semester id the class pages expect
    $class_stmt = $pdo->prepare("
        SELECT
            c.id AS id,
            c.classCode AS class_code,
            c.name AS class_section,
            c.subject AS subject_name,
            c.code AS subject_code,
            c.subject_id AS subject_id,
            c.semester AS semester,
            c.status AS class_status,
            c.type AS class_type,
            c.is_archived AS class_archived,
            s.is_archived AS subject_archived,
            sem.id AS semester_id
        FROM classes c
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN semester sem ON sem.name = c.semester
        WHERE c.teacher = :teacherName
        ORDER BY c.is_archived, c.subject
    ");
    $class_stmt->execute(['teacherName' => $teacher_name]);
    $classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Classes that count for today's schedule and attendance (not pending)
    $active_classes = array_values(array_filter($classes, function ($c) {
        return $c['class_status'] !== 'pending';
    }));
    $active_class_count = count(array_filter($active_classes, function ($c) {
        return $c['class_archived'] != 1 && $c['class_status'] !== 'disapproved';
    }));

    $subject_ids = array_values(array_filter(array_column($active_classes, 'subject_id')));
    if (!empty($subject_ids)) {
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $schedules_stmt = $pdo->prepare("
            SELECT ss.meeting_days, ss.start_time, ss.end_time, s.name AS subject_name
            FROM subjects_schedules ss
            JOIN subjects s ON ss.subject_id = s.id
            WHERE ss.subject_id IN ($placeholders)
        ");
        $schedules_stmt->execute($subject_ids);
        $todays_classes = array_filter($schedules_stmt->fetchAll(PDO::FETCH_ASSOC), function ($schedule) use ($today) {
            return stripos($schedule['meeting_days'], $today) !== false;
        });
    }

    // Attendance rate and students with excessive absences
    $total_records = 0;
    $total_present = 0;

    foreach ($active_classes as $class) {
        $class_id = $class['id'];
        $class_label = $class['class_section'] . ' - ' . $class['subject_name'];

        $totals_stmt = $pdo->prepare("
            SELECT COUNT(*) AS total, SUM(a.status = 'present') AS present
            FROM attendance a
            JOIN classes_meetings cm ON a.meeting_id = cm.id
            WHERE cm.class_id = :class_id
        ");
        $totals_stmt->execute(['class_id' => $class_id]);
        $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);
        $total_records += (int) $totals['total'];
        $total_present += (int) $totals['present'];

        $meetings_stmt = $pdo->prepare("SELECT COUNT(*) FROM classes_meetings WHERE class_id = :class_id");
        $meetings_stmt->execute(['class_id' => $class_id]);
        $total_meetings = (int) $meetings_stmt->fetchColumn();

        if ($total_meetings === 0) continue;

        $students_stmt = $pdo->prepare("
            SELECT s.id AS student_id, s.fullName,
                (SELECT COUNT(*)
                 FROM attendance a
                 JOIN classes_meetings cm ON a.meeting_id = cm.id
                 WHERE cm.class_id = se.class_id AND a.student_id = s.id AND a.status = 'present') AS present_count
            FROM students_enrollments se
            INNER JOIN students s ON se.student_id = s.id
            WHERE se.class_id = :class_id
        ");
        $students_stmt->execute(['class_id' => $class_id]);

        foreach ($students_stmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $present_count = (int) $student['present_count'];
            $absence_count = $total_meetings - $present_count;
            $attendance_rate = ($present_count / $total_meetings) * 100;

            // Flag if below 75% OR has 3+ absences
            if ($attendance_rate < 75 || $absence_count >= 3) {
                $excessive_absences[] = [
                    'student_name' => $student['fullName'],
                    'class_name' => $class_label,
                    'attendance_rate' => number_format($attendance_rate, 2),
                    'present_count' => $present_count,
                    'absence_count' => $absence_count,
                    'badge' => getAbsenceBadge($absence_count)
                ];
            }
        }
    }

    if ($total_records > 0) {
        $average_attendance_rate = ($total_present / $total_records) * 100;
    }

    // Activities with no graded submissions yet
    $pending_stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM activities act
        JOIN classes c ON act.class_id = c.id
        WHERE c.teacher = :teacher_name
          AND NOT EXISTS (
              SELECT 1 FROM activity_submissions sub
              WHERE sub.activity_id = act.id AND sub.status = 'graded'
          )
    ");
    $pending_stmt->execute(['teacher_name' => $teacher_name]);
    $pending_activities = (int) $pending_stmt->fetchColumn();

    $currentSemester = $pdo->query("
        SELECT s.name, s.start_date, s.end_date
        FROM current_semester cs
        JOIN semester s ON cs.semester = s.name
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    // Subjects nobody teaches yet, for the "add a class" form
    $subjects = $pdo->query("
        SELECT s.id, s.name, s.semester, s.type
        FROM subjects s
        LEFT JOIN classes c ON s.id = c.subject_id
        WHERE c.subject_id IS NULL
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $semesters = $pdo->query("SELECT name FROM semester ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading subject management: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link href="<?php echo csms_asset('css/dashboard.css', __DIR__); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php
        include('sidebar.php')
        ?>

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
                            <div class="fd-eyebrow">Teaching<?= $currentSemester ? ' &middot; ' . htmlspecialchars($currentSemester['name']) : '' ?></div>
                            <h1 class="fd-title">Subject Management</h1>
                            <p class="fd-subtitle">Open a subject to take attendance, record grades or manage activities.</p>
                        </div>
                        <?php if ($currentSemester): ?>
                            <div class="fd-quick">
                                <button type="button" class="fd-btn" data-bs-toggle="modal" data-bs-target="#createClassModal">
                                    <i class="bi bi-plus-lg"></i> Add a Class to Teach
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$currentSemester): ?>
                        <div class="fd-notice mb-4">
                            <i class="bi bi-exclamation-circle"></i>
                            No current semester is set, so you can't add classes yet. Please contact the administrator.
                        </div>
                    <?php endif; ?>

                    <!-- Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="card fd-stat">
                                <span class="fd-stat-icon"><i class="bi bi-journal-bookmark"></i></span>
                                <div>
                                    <div class="fd-stat-value"><?= $active_class_count ?></div>
                                    <div class="fd-stat-label">Active subjects</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card fd-stat">
                                <span class="fd-stat-icon"><i class="bi bi-calendar-event"></i></span>
                                <div>
                                    <div class="fd-stat-value"><?= count($todays_classes) ?></div>
                                    <div class="fd-stat-label">Classes today (<?= htmlspecialchars($today) ?>)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card fd-stat">
                                <span class="fd-stat-icon is-gold"><i class="bi bi-pencil-square"></i></span>
                                <div>
                                    <div class="fd-stat-value"><?= $pending_activities ?></div>
                                    <div class="fd-stat-label">Activities to grade</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <a href="#" class="card fd-stat fd-stat-link" data-bs-toggle="modal" data-bs-target="#excessiveAbsencesModal">
                                <span class="fd-stat-icon <?= count($excessive_absences) > 0 ? 'is-danger' : 'is-success' ?>">
                                    <i class="bi <?= count($excessive_absences) > 0 ? 'bi-exclamation-triangle' : 'bi-check2-circle' ?>"></i>
                                </span>
                                <div>
                                    <div class="fd-stat-value"><?= count($excessive_absences) ?></div>
                                    <div class="fd-stat-label">
                                        Students flagged
                                        <?php if ($average_attendance_rate !== null): ?>
                                            &middot; <?= number_format($average_attendance_rate, 1) ?>% avg
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Subjects -->
                    <div class="card fd-panel mb-4">
                        <div class="card-header">
                            <div>
                                <h5 class="card-title"><i class="bi bi-journal-bookmark"></i> My Subjects <span class="fd-count"><?= count($classes) ?></span></h5>
                                <p class="fd-panel-sub">Every class you teach, including archived ones from past semesters.</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($classes)): ?>
                                <div class="fd-empty">
                                    <i class="bi bi-journal-plus"></i>
                                    <p>You aren't teaching any subjects yet.</p>
                                    <?php if ($currentSemester): ?>
                                        <p class="small mt-1">Use <b>Add a Class to Teach</b> to request one.</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <table id="classesTable" class="table fd-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Type</th>
                                            <th>Semester</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class):
                                            $is_archived = $class['class_archived'] == 1;
                                            if ($is_archived) {
                                                $status = ['is-archived', 'bi-archive', 'Archived'];
                                            } elseif ($class['class_status'] == 'pending') {
                                                $status = ['is-pending', 'bi-hourglass-split', 'Pending approval'];
                                            } elseif ($class['class_status'] == 'disapproved') {
                                                $status = ['is-rejected', 'bi-x-circle', 'Disapproved'];
                                            } else {
                                                $status = ['is-active', 'bi-check-circle', 'Active'];
                                            }
                                            $ids = 'class_id=' . urlencode($class['id']);
                                            $semester_q = '&semester_id=' . urlencode($class['semester_id'] ?? '');
                                            $subject_q = '&subject_id=' . urlencode($class['subject_id']);
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="fd-strong"><?= htmlspecialchars($class['subject_name']) ?></div>
                                                    <div class="fd-muted"><?= htmlspecialchars($class['subject_code']) ?></div>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($class['class_section']) ?></div>
                                                    <div class="fd-muted"><?= htmlspecialchars($class['class_code']) ?></div>
                                                </td>
                                                <td><span class="fd-pill is-type"><?= htmlspecialchars($class['class_type']) ?></span></td>
                                                <td><?= htmlspecialchars($class['semester']) ?></td>
                                                <td><span class="fd-pill <?= $status[0] ?>"><i class="bi <?= $status[1] ?>"></i> <?= $status[2] ?></span></td>
                                                <td>
                                                    <?php if ($is_archived): ?>
                                                        <div class="fd-actions">
                                                            <a class="fd-action" href="class_attendance_archived.php?<?= $ids . $semester_q ?>"><i class="bi bi-calendar-check"></i> Classes</a>
                                                            <a class="fd-action" href="class_grades.php?<?= $ids . $semester_q . $subject_q ?>"><i class="bi bi-bar-chart-line"></i> Grades</a>
                                                            <a class="fd-action" href="subject_activities_archived.php?url=people&<?= $ids . $subject_q ?>"><i class="bi bi-pencil-square"></i> Manage</a>
                                                        </div>
                                                    <?php elseif ($class['class_status'] == 'pending'): ?>
                                                        <span class="fd-muted">Waiting for admin approval</span>
                                                    <?php elseif ($class['class_status'] == 'disapproved'): ?>
                                                        <span class="fd-muted">Request was not approved</span>
                                                    <?php else: ?>
                                                        <div class="fd-actions">
                                                            <a class="fd-action" href="class_attendance.php?<?= $ids . $semester_q ?>"><i class="bi bi-calendar-check"></i> Classes</a>
                                                            <a class="fd-action" href="class_grades.php?<?= $ids . $semester_q . $subject_q ?>"><i class="bi bi-bar-chart-line"></i> Grades</a>
                                                            <a class="fd-action" href="subject_activities.php?url=people&<?= $ids . $subject_q ?>"><i class="bi bi-pencil-square"></i> Manage</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Modal: students with excessive absences -->
                <div class="modal fade" id="excessiveAbsencesModal" tabindex="-1" aria-labelledby="excessiveAbsencesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header text-white">
                                <h5 class="modal-title" id="excessiveAbsencesModalLabel">Students with Excessive Absences</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (count($excessive_absences) === 0): ?>
                                    <p class="mb-0">No students with excessive absences.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th class="text-center">Present</th>
                                                    <th class="text-center">Absences</th>
                                                    <th class="text-center">Attendance Rate (%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($excessive_absences as $student): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($student['student_name']) ?></td>
                                                        <td><?= htmlspecialchars($student['class_name']) ?></td>
                                                        <td class="text-center"><?= $student['present_count'] ?></td>
                                                        <td class="text-center <?= $student['absence_count'] >= 3 ? 'text-danger fw-bold' : '' ?>">
                                                            <?= $student['absence_count'] ?>
                                                            <?= $student['badge'] ?>
                                                        </td>
                                                        <td class="text-center"><?= $student['attendance_rate'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="createClassModalLabel"><b>Add a Class to Teach</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addClassForm" action="processes/teachers/classes/add.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="classSelect" class="form-label bold">Section</label>
                            <select class="form-select" name="class" id="classSelect" required>
                                <option value="" selected disabled>Select a section</option>
                                <?php foreach (shs_sections() as $strand => $list): ?>
                                    <optgroup label="<?= htmlspecialchars(shs_strand_label($strand)) ?>">
                                        <?php foreach ($list as $sec): ?><option value="<?= $sec ?>"><?= str_replace('-', ' - ', $sec) ?></option><?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="assignedAdviser" class="form-label bold">Assigned Adviser</label>
                            <input type="text" class="form-control" id="assignedAdviser" name="assignedAdviser"
                                placeholder="Select a section first" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="subjectId" class="form-label bold">Subject</label>
                            <select class="form-select" name="subjectId" id="subjectId" required>
                                <?php if (!empty($subjects)): ?>
                                    <option value="" selected disabled>Select a subject</option>
                                    <?php foreach ($subjects as $row): ?>
                                        <option value="<?= htmlspecialchars($row['id']) ?>">
                                            <?= htmlspecialchars($row['name']) . ' [' . htmlspecialchars($row['type']) . '] (' . htmlspecialchars($row['semester']) . ')' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" selected>No available subjects</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="semesterSelect" class="form-label bold">Semester</label>
                            <select class="form-select" name="semester" id="semesterSelect" required>
                                <?php if (!empty($semesters)): ?>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?= htmlspecialchars($semester['name']) ?>"
                                            <?= $currentSemester && $currentSemester['name'] === $semester['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($semester['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No semesters available</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="classDesc" class="form-label bold">Class Description</label>
                            <textarea class="form-control" id="classDesc" name="classDesc" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" <?= empty($subjects) ? 'disabled' : '' ?>>Submit for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?php echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <?php
    include('processes/server/modals.php');
    ?>

    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();
            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
    </script>

    <script>
        $(document).ready(function() {
            $('#classesTable').DataTable({
                responsive: true,
                order: [],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    }, // Subject
                    {
                        responsivePriority: 2,
                        targets: 5
                    }, // Actions
                    {
                        orderable: false,
                        targets: 5
                    }
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search subjects...'
                }
            });
        });
    </script>

    <script>
        document.getElementById('classSelect').addEventListener('change', function() {
            const selectedClass = this.value;
            fetch('getAdviser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'class=' + encodeURIComponent(selectedClass)
                })
                .then(response => response.json())
                .then(data => {
                    const adviserField = document.getElementById('assignedAdviser');
                    if (data.fullName) {
                        adviserField.value = data.fullName;
                    } else {
                        adviserField.value = 'No adviser assigned';
                    }
                })
                .catch(error => console.error('Error fetching adviser:', error));
        });
    </script>

<!-- Floating Chat Widget -->

</body>

</html>

<?php
include('processes/server/alerts.php');
?>
