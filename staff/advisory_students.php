<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit();
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/academic.php';

$teacher = $_SESSION['teacher_name'] ?? $_SESSION['full_name'] ?? ''; // matches staff_advising.fullName

// The teacher's advisory sections, for the section switcher
$advisory_stmt = $pdo->prepare("
    SELECT sa.id, sa.class_advising, COUNT(stu.id) AS student_count
    FROM staff_advising sa
    LEFT JOIN students_advising sad ON sad.staff_advising_id = sa.id
    LEFT JOIN students stu ON stu.id = sad.student_id
    WHERE sa.fullName = :teacher
    GROUP BY sa.id, sa.class_advising
    ORDER BY sa.class_advising
");
$advisory_stmt->execute(['teacher' => $teacher]);
$advisory_classes = $advisory_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($advisory_classes)) {
    $_SESSION['error'] = "You are not assigned as an adviser to any section yet.";
    header("Location: class_management.php");
    exit();
}

// No section in the URL: open the first one. A section that isn't the teacher's: refuse.
$class_name = $_GET['class_id'] ?? '';            // e.g. "STEM-11A"
if ($class_name === '') {
    $class_name = $advisory_classes[0]['class_advising'];
}

$staff_advising = null;
foreach ($advisory_classes as $class) {
    if ($class['class_advising'] === $class_name) {
        $staff_advising = $class;
    }
}

if (!$staff_advising) {                  // Security guard‑rail
    $_SESSION['error'] = "You are not authorized for this class.";
    header("Location: class_management.php");
    exit();
}
$staff_advising_id = $staff_advising['id'];

$section = shs_parse_section($class_name);
if (!$section) {
    $_SESSION['error'] = "The section code \"$class_name\" isn't a valid strand and grade. Ask the administrator to fix it.";
    header("Location: class_management.php");
    exit();
}
$course     = $section['strand'];       // "STEM"
$year_level = $section['grade_level'];  // "Grade 11"


$students_stmt = $pdo->prepare("
    SELECT s.*, u.email
    FROM students s
    JOIN students_advising sa
      ON sa.student_id = s.id
    LEFT JOIN users u
      ON u.id = s.user_id
    WHERE sa.staff_advising_id = :sa_id
    ORDER BY s.last_name, s.first_name
");
$students_stmt->execute(['sa_id' => $staff_advising_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);


// Students of the same strand and grade who aren't in this section yet, with any section they're already in
$avail_stmt = $pdo->prepare("
    SELECT s.*, GROUP_CONCAT(st.class_advising ORDER BY st.class_advising SEPARATOR ', ') AS current_sections
    FROM students s
    LEFT JOIN students_advising sad ON sad.student_id = s.id
    LEFT JOIN staff_advising st ON st.id = sad.staff_advising_id
    WHERE s.course     = :course
      AND s.year_level = :year_level
      AND s.id NOT IN (
          SELECT student_id
          FROM students_advising
          WHERE staff_advising_id = :sa_id
      )
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
");
$avail_stmt->execute([
    'course'     => $course,
    'year_level' => $year_level,
    'sa_id'      => $staff_advising_id
]);
$available_students = $avail_stmt->fetchAll(PDO::FETCH_ASSOC);

// One-time data from processes/teachers/advisory/create_student.php
$newStudentForm = $_SESSION['new_student_form'] ?? null;          // refill after a failed attempt
$newStudentCredentials = $_SESSION['new_student_credentials'] ?? null; // login details after success
unset($_SESSION['new_student_form'], $_SESSION['new_student_credentials']);
$old = function (string $key) use ($newStudentForm) {
    return htmlspecialchars((string) ($newStudentForm[$key] ?? ''));
};

$flash = null;
if (!empty($_SESSION['success']) || !empty($_SESSION['error'])) {
    $flash = !empty($_SESSION['success'])
        ? ['icon' => 'success', 'title' => 'Success', 'text' => $_SESSION['success']]
        : ['icon' => 'error', 'title' => 'Error', 'text' => $_SESSION['error']];
    unset($_SESSION['success'], $_SESSION['error']);
}
$jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link href="<?php echo csms_asset('css/dashboard.css', __DIR__); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <style>
        /* Section switcher */
        .as-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
        }

        .as-tabs .fd-btn-ghost.is-current {
            background-color: var(--au-navy);
            border-color: var(--au-navy);
            color: #fff !important;
        }

        .as-tabs .is-current .fd-count {
            background-color: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .student-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid var(--au-line);
        }

        .as-student {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
                            <h1 class="fd-title"><?= htmlspecialchars($class_name) ?></h1>
                            <p class="fd-subtitle"><?= htmlspecialchars(shs_strand_label($course) . ' · ' . $year_level . ' · Section ' . $section['letter']) ?></p>
                        </div>
                        <div class="fd-quick">
                            <a class="fd-btn-ghost" href="class_management.php"><i class="bi bi-arrow-left"></i> Back to Class Management</a>
                            <a class="fd-btn-ghost" href="class_schedule.php?class_id=<?= urlencode($class_name) ?>"><i class="bi bi-calendar-week"></i> Schedule</a>
                        </div>
                    </div>

                    <?php if (count($advisory_classes) > 1): ?>
                        <nav class="as-tabs" aria-label="Advisory sections">
                            <?php foreach ($advisory_classes as $class):
                                $is_current = $class['class_advising'] === $class_name;
                            ?>
                                <a href="advisory_students.php?class_id=<?= urlencode($class['class_advising']) ?>"
                                    class="fd-btn-ghost <?= $is_current ? 'is-current' : '' ?>"
                                    <?= $is_current ? 'aria-current="page"' : '' ?>>
                                    <?= htmlspecialchars($class['class_advising']) ?>
                                    <span class="fd-count"><?= (int) $class['student_count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>

                    <div class="card fd-panel mb-4">
                        <div class="card-header">
                            <div>
                                <h5 class="card-title"><i class="bi bi-people"></i> Class Students <span class="fd-count"><?= count($students) ?></span></h5>
                                <p class="fd-panel-sub">Students in your advisory section.</p>
                            </div>
                            <div class="fd-quick">
                                <button type="button" class="fd-btn fd-btn-sm" data-bs-toggle="modal" data-bs-target="#registerStudentModal">
                                    <i class="bi bi-person-plus"></i> Register New Student
                                </button>
                                <button type="button" class="fd-btn-ghost fd-btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <i class="bi bi-plus-circle"></i> Add Existing Student
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($students)): ?>
                                <div class="fd-empty">
                                    <i class="bi bi-person-plus"></i>
                                    <p>No students in this section yet.</p>
                                    <p class="small mt-1">Register a new student or add one who is already enrolled.</p>
                                </div>
                            <?php else: ?>
                                <table class="table fd-table" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Student ID</th>
                                            <th>Email</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student):
                                            $full_name = trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . ($student['middle_name'] ?? ''));
                                            $photo = !empty($student['photo'])
                                                ? '../uploads/students/' . $student['photo']
                                                : '../external/img/dp_logo.avif';
                                        ?>
                                            <tr>
                                                <td data-order="<?= htmlspecialchars($full_name) ?>">
                                                    <div class="as-student">
                                                        <img src="<?= htmlspecialchars($photo) ?>" class="student-photo" alt="">
                                                        <span class="fd-strong"><?= htmlspecialchars($full_name) ?></span>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars((string) $student['student_id']) ?></td>
                                                <td><?= htmlspecialchars($student['email'] ?? '') ?: '<span class="fd-muted">No account</span>' ?></td>
                                                <td>
                                                    <div class="fd-actions">
                                                        <button type="button" class="fd-action view-student-profile"
                                                            data-id="<?= (int) $student['id'] ?>">
                                                            <i class="bi bi-person"></i> Profile
                                                        </button>
                                                        <button type="button" class="fd-action is-danger remove-student"
                                                            data-id="<?= (int) $student['id'] ?>"
                                                            data-name="<?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?>">
                                                            <i class="bi bi-person-dash"></i> Remove
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Students to <?= htmlspecialchars($class_name) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addStudentForm" action="processes/teachers/advisory/add_student_to_class.php" method="POST">
                    <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">
                    <input type="hidden" name="staff_advising_id" value="<?= (int) $staff_advising_id ?>">

                    <div class="modal-body">
                        <?php if (empty($available_students)): ?>
                            <div class="fd-empty">
                                <i class="bi bi-search"></i>
                                <p>No other <?= htmlspecialchars($course . ' ' . $year_level) ?> students to add.</p>
                            </div>
                        <?php else: ?>
                            <p class="fd-panel-sub mb-3"><?= htmlspecialchars($course . ' ' . $year_level) ?> students who aren't in this section yet.</p>
                            <table class="table fd-table" id="availableStudentsTable">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Name</th>
                                        <th>Student ID</th>
                                        <th>Current Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available_students as $student): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input" name="student_ids[]"
                                                    value="<?= (int) $student['id'] ?>"
                                                    aria-label="Select <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>">
                                            </td>
                                            <td class="fd-strong"><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></td>
                                            <td><?= htmlspecialchars((string) $student['student_id']) ?></td>
                                            <td>
                                                <?php if (!empty($student['current_sections'])): ?>
                                                    <span class="fd-pill is-type"><?= htmlspecialchars($student['current_sections']) ?></span>
                                                <?php else: ?>
                                                    <span class="fd-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if (!empty($available_students)): ?>
                            <button type="submit" class="btn btn-primary">Add Selected Students</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Register New Student Modal -->
    <div class="modal fade" id="registerStudentModal" tabindex="-1" aria-labelledby="registerStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <form class="modal-content au-profile" id="registerStudentForm" action="processes/teachers/advisory/create_student.php" method="POST" autocomplete="off">
                <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">

                <div class="au-profile-banner">
                    <button type="button" class="btn-close btn-close-white au-profile-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <span class="au-profile-avatar au-profile-initials" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                    <h5 class="au-profile-name" id="registerStudentModalLabel">Register New Student</h5>
                    <div class="au-profile-tags">
                        <span class="au-portal-role"><?= htmlspecialchars($class_name) ?></span>
                        <span class="au-profile-status is-active"><?= htmlspecialchars($course . ' · ' . $year_level) ?></span>
                    </div>
                    <p class="au-profile-hint mt-2">The student is added to your advisory class and can log in right away.</p>
                </div>

                <div class="modal-body au-profile-body">
                    <fieldset class="au-profile-section">
                        <legend><i class="bi bi-person" aria-hidden="true"></i> Student Information</legend>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="newStudentFirstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="newStudentFirstName" name="first_name" value="<?= $old('first_name') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="newStudentMiddleName" class="form-label">Middle Name <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" class="form-control" id="newStudentMiddleName" name="middle_name" value="<?= $old('middle_name') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="newStudentLastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="newStudentLastName" name="last_name" value="<?= $old('last_name') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="newStudentId" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="newStudentId" value="Assigned automatically" disabled>
                                <small class="text-muted">The system gives each student a unique number.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="newStudentGender" class="form-label">Gender</label>
                                <select class="form-select" id="newStudentGender" name="gender" required>
                                    <option value="" disabled <?= empty($newStudentForm['gender']) ? 'selected' : '' ?>>Select gender</option>
                                    <?php foreach (['Male', 'Female', 'Others'] as $option): ?>
                                        <option value="<?= $option ?>" <?= ($newStudentForm['gender'] ?? '') === $option ? 'selected' : '' ?>><?= $option ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Strand</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($course) ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade Level</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($year_level) ?>" disabled>
                            </div>
                        </div>
                        <div class="form-text">Strand and grade level come from your section (<?= htmlspecialchars($class_name) ?>).</div>
                    </fieldset>

                    <fieldset class="au-profile-section">
                        <legend><i class="bi bi-key" aria-hidden="true"></i> Login Details</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="newStudentEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="newStudentEmail" name="email" value="<?= $old('email') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="newStudentPassword" class="form-label">Starting Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newStudentPassword" name="password" minlength="8" autocomplete="new-password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="newStudentPasswordToggle" aria-label="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="newStudentPasswordGenerate">
                                        <i class="bi bi-shuffle" aria-hidden="true"></i> Generate
                                    </button>
                                </div>
                                <div class="form-text">At least 8 characters. You'll see it once after saving so you can give it to the student.</div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="modal-footer au-profile-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-csms"><i class="bi bi-person-check"></i> Register Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Profile Modal -->
    <div class="modal fade" id="studentProfileModal" tabindex="-1" aria-labelledby="studentProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentProfileModalLabel">Student Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentProfileContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo csms_asset('js/app.js', __DIR__); ?>"></script>

    <?php include('processes/server/modals.php'); ?>

    <script>
        $(function() {
            $('#studentsTable').DataTable({
                responsive: true,
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    }, // Name
                    {
                        responsivePriority: 2,
                        targets: 3
                    }, // Actions
                    {
                        orderable: false,
                        targets: 3
                    }
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search students...'
                }
            });

            $('#availableStudentsTable').DataTable({
                pageLength: 10,
                order: [
                    [1, 'asc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search students...'
                }
            });

            <?php if ($flash): ?>
                Swal.fire({
                    icon: <?= json_encode($flash['icon']) ?>,
                    title: <?= json_encode($flash['title']) ?>,
                    text: <?= json_encode($flash['text'], $jsFlags) ?>,
                    confirmButtonColor: '#061E4E'
                });
            <?php endif; ?>

            /* ------------------ Remove a student ------------------ */
            $(document).on('click', '.remove-student', function() {
                const studentId = $(this).data('id');
                const studentName = $('<div>').text($(this).data('name')).html();
                const staffAdvisingId = <?= (int) $staff_advising_id ?>;

                Swal.fire({
                    title: 'Confirm Removal',
                    html: `Are you sure you want to remove <strong>${studentName}</strong> from your advisory class?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#a3191b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    /* ---- build a throw‑away form and submit it ---- */
                    const $form = $('<form>', {
                            method: 'POST',
                            action: 'processes/teachers/advisory/remove_student_from_class.php'
                        })
                        .append($('<input>', {
                            type: 'hidden',
                            name: 'student_id',
                            value: studentId
                        }))
                        .append($('<input>', {
                            type: 'hidden',
                            name: 'staff_advising_id',
                            value: staffAdvisingId
                        }));

                    $('body').append($form);
                    $form.submit(); // Page navigates; PHP sets flash + redirects
                });
            });

            /* ------------------ Add student(s)  ------------------ */
            $('#addStudentForm').on('submit', function(e) {
                // DataTables only keeps the current page in the DOM; carry checked boxes from other pages along
                const table = $('#availableStudentsTable').DataTable();
                const $form = $(this);
                $form.find('input.carried').remove();
                table.$('input[name="student_ids[]"]:checked').each(function() {
                    if (!$.contains(document, this)) {
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: 'student_ids[]',
                            value: this.value,
                            class: 'carried'
                        }));
                    }
                });

                if (!table.$('input[name="student_ids[]"]:checked').length) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select at least one student'
                    });
                }
            });

            /* ------------------ View profile ------------------ */
            const loadingHtml = `
                <div class="fd-empty">
                    <div class="spinner-border text-primary mb-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading student profile...</p>
                </div>`;
            $('#studentProfileContent').html(loadingHtml);

            $(document).on('click', '.view-student-profile', function() {
                const studentId = $(this).data('id');
                $('#studentProfileContent').html(loadingHtml);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('studentProfileModal')).show();

                $.ajax({
                    url: 'processes/teachers/advisory/view_profile.php',
                    method: 'GET',
                    data: {
                        id: studentId
                    },
                    success: function(response) {
                        $('#studentProfileContent').html(response);
                    },
                    error: function() {
                        $('#studentProfileContent').html('<div class="alert alert-danger">Failed to load student profile. Please try again.</div>');
                    }
                });
            });

            $('#studentProfileModal').on('hidden.bs.modal', function() {
                $('#studentProfileContent').html(loadingHtml);
            });
        });
    </script>

    <script>
        (function () {
            var password = document.getElementById('newStudentPassword');
            var toggle = document.getElementById('newStudentPasswordToggle');

            function showPassword(show) {
                password.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            }

            toggle.addEventListener('click', function () {
                showPassword(password.type === 'password');
            });

            // Readable random password (no look-alike characters such as 0/O or 1/l)
            document.getElementById('newStudentPasswordGenerate').addEventListener('click', function () {
                var chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
                var values = new Uint32Array(10);
                crypto.getRandomValues(values);
                password.value = Array.from(values, function (v) { return chars[v % chars.length]; }).join('');
                showPassword(true);
            });

            document.getElementById('registerStudentModal').addEventListener('hidden.bs.modal', function () {
                showPassword(false);
            });

            <?php if ($newStudentForm): ?>
            // A previous attempt failed: reopen the form with what was typed
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('registerStudentModal')).show();
            });
            <?php endif; ?>

            <?php if ($newStudentCredentials): ?>
            document.addEventListener('DOMContentLoaded', function () {
                var c = {
                    name: <?= json_encode($newStudentCredentials['name'], $jsFlags) ?>,
                    studentId: <?= json_encode((string) ($newStudentCredentials['student_id'] ?? ''), $jsFlags) ?>,
                    email: <?= json_encode($newStudentCredentials['email'], $jsFlags) ?>,
                    password: <?= json_encode($newStudentCredentials['password'], $jsFlags) ?>
                };
                var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
                Swal.fire({
                    icon: 'success',
                    title: 'Student Registered',
                    html: '<p><strong>' + esc(c.name) + '</strong> was added to your advisory class. Give them these login details:</p>' +
                        '<table class="table table-sm text-start mb-2">' + (c.studentId ? '<tr><th>Student ID</th><td>' + esc(c.studentId) + '</td></tr>' : '') + '<tr><th>Email</th><td>' + esc(c.email) + '</td></tr>' +
                        '<tr><th>Password</th><td><code>' + esc(c.password) + '</code></td></tr></table>' +
                        '<small class="text-muted">The password won\'t be shown again.</small>',
                    showDenyButton: true,
                    denyButtonText: '<i class="bi bi-clipboard"></i> Copy details',
                    denyButtonColor: '#6c757d',
                    confirmButtonText: 'Done',
                    confirmButtonColor: '#061E4E',
                    preDeny: function () {
                        var text = (c.studentId ? 'Student ID: ' + c.studentId + '\n' : '') + 'Email: ' + c.email + '\nPassword: ' + c.password;
                        return navigator.clipboard.writeText(text).then(function () {
                            Swal.showValidationMessage('Copied to clipboard');
                            return false; // keep the dialog open
                        }, function () {
                            Swal.showValidationMessage('Copy failed, please copy them manually');
                            return false;
                        });
                    }
                });
            });
            <?php endif; ?>
        })();
    </script>

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
