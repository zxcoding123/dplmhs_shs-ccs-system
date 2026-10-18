<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit();
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/academic.php';

$class_name = $_GET['class_id'] ?? '';            // e.g. "STEM-11A"
$teacher    = $_SESSION['full_name'];             // matches staff_advising.fullName


$sa_stmt = $pdo->prepare("
    SELECT id, class_advising
    FROM staff_advising
    WHERE fullName      = :teacher
      AND class_advising = :class_name
    LIMIT 1
");
$sa_stmt->execute([
    'teacher'    => $teacher,
    'class_name' => $class_name,
]);

$staff_advising = $sa_stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff_advising) {                  // Security guard‑rail
    $_SESSION['error'] = "You are not authorized for this class.";
    header("Location: dashboard.php");
    exit();
}
$staff_advising_id = $staff_advising['id'];


$section = shs_parse_section($class_name);
if (!$section) {
    throw new RuntimeException("Bad class code: $class_name");
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


$avail_stmt = $pdo->prepare("
    SELECT *
    FROM students
    WHERE course      = :course
      AND year_level  = :year_level
      AND id NOT IN (
          SELECT student_id
          FROM students_advising
          WHERE staff_advising_id = :sa_id
      )
    ORDER BY last_name, first_name
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DPLMHS-SHS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
</head>

<style>
    table.dataTable {
        font-size: 12px;
    }

    td {
        text-align: center;
        vertical-align: middle;
        border-bottom: 1px solid black;
    }

    .btn-csms {
        background-color: #10177a;
        color: white;
        border: 1px solid white;
    }

    .btn-csms:hover {
        border: 1px solid #10177a;
        background-color: white;
        color: #10177a;
    }

    .announcement-card {
        border-left: 4px solid #709775;
        margin-bottom: 15px;
    }

    .class-card {
        transition: transform 0.2s;
    }

    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .student-photo {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 50%;
    }

    .table-responsive {
        overflow-x: auto;
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
                <span class="text-white"><b>DPLMHS</b> - Student Management System
                </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>

            <main class="content">
                <div id="page-content-wrapper">
                    <div class="container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h1 class="h3 mb-0"><b>Advisory Class:</b> <?= htmlspecialchars($class_name) ?>
                                        <br>
                                       
                                    </h1>

                                    <a href="class_management.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                    
                                </div>
                                 <p class="text-secondary fs-6 mt-2">You are now viewing your advisory class.</p>
                            </div>
                        </div>

                        <?php if (!empty($_SESSION['success']) || !empty($_SESSION['error'])): ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    Swal.fire({
                                        icon: '<?= !empty($_SESSION['success']) ? 'success' : 'error' ?>',
                                        title: '<?= !empty($_SESSION['success']) ? 'Success' : 'Error' ?>',
                                        text: '<?= addslashes(!empty($_SESSION['success']) ? $_SESSION['success'] : $_SESSION['error']) ?>',
                                        confirmButtonColor: '#3085d6'
                                    });
                                });
                            </script>
                            <?php unset($_SESSION['success'], $_SESSION['error']); ?>
                        <?php endif; ?>



                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center"
                                style="    background-color: #10177a;
        color: white;">
                                <h6 class="m-0 font-weight-bold text-white">Class Students</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#registerStudentModal">
                                        <i class="bi bi-person-plus"></i> Register New Student
                                    </button>
                                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                        <i class="bi bi-plus-circle"></i> Add Existing Student
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <?php if (!empty($student['photo'])): ?>
                                                            <img src="../uploads/students/<?= htmlspecialchars($student['photo']) ?>"
                                                                class="student-photo"
                                                                alt="<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>">
                                                        <?php else: ?>
                                                            <img src="../external/img/dp_logo.avif"
                                                                class="student-photo"
                                                                alt="Default student photo">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                    <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars($student['middle_name']) ?></td>
                                                    <td><?= htmlspecialchars($student['email'] ?? '') ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm view-student-profile"
                                                            data-id="<?= $student['id'] ?>"
                                                            title="View Profile">
                                                            <i class="bi bi-person"></i> View Student Profile
                                                        </button>
                                                        <button class="btn btn-danger btn-sm remove-student"
                                                            data-id="<?= $student['id'] ?>"
                                                            data-name="<?= htmlspecialchars($student['last_name'] . ', ' . $student['first_name']) ?>"
                                                            title="Remove from Class"> 
                                                            <i class="bi bi-trash"></i> Remove Student from Class
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </main>
        </div>

        <!-- Add Student Modal -->
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Students to <?= htmlspecialchars($class_name) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addStudentForm" action="processes/teachers/advisory/add_student_to_class.php" method="POST">
                        <input type="hidden" name="class_name" value="<?= htmlspecialchars($class_name) ?>">
                        <input type="hidden" name="staff_advising_id" value="<?= $staff_advising_id ?>">

                        <div class="modal-body">
                            <?php if (empty($available_students)): ?>
                                <div class="alert alert-info">No available students to add to this class.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="availableStudentsTable" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Select</th>
                                                <th>Student ID</th>
                                                <th>Name</th>
                                                <th>Current Class</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($available_students as $student): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="student_ids[]"
                                                            value="<?= $student['id'] ?>">
                                                    </td>
                                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                    <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></td>
                                                    <td><?= htmlspecialchars($student['class_id'] ?? 'Not assigned') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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
                                    <input type="text" class="form-control" id="newStudentId" name="student_id" value="<?= $old('student_id') ?>"
                                        inputmode="numeric" pattern="\d{1,9}" maxlength="9" placeholder="e.g. 202312345" required>
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
                                    <label class="form-label">Course</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($course) ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Year Level</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($year_level) ?>" disabled>
                                </div>
                            </div>
                            <div class="form-text">Course and year level come from your section (<?= htmlspecialchars($class_name) ?>).</div>
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
        <div class="modal fade" id="studentProfileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Student Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="studentProfileContent">
                        <!-- Content will be loaded here via AJAX -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Loading student profile...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

     
    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>

    <?php include('processes/server/modals.php'); ?>
        <script>
            $(document).ready(function() {
                // Initialize DataTables
                $('#studentsTable').DataTable({
                    responsive: true
                });

                $('#availableStudentsTable').DataTable({
                    responsive: true,
                    columnDefs: [{
                        orderable: false,
                        targets: 0
                    }]
                });
            });
        </script>

        <script>
            $(function() {
                /* DataTables setup … (unchanged) */

                /* ------------------ Remove a student ------------------ */
                $(document).on('click', '.remove-student', function() {
                    const studentId = $(this).data('id');
                    const studentName = $(this).data('name');
                    const staffAdvisingId = <?= (int)$staff_advising_id ?>;

                    Swal.fire({
                        title: 'Confirm Removal',
                        html: `Are you sure you want to remove <strong>${studentName}</strong> from your advisory class?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
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
                    if (!$(this).find('input[name="student_ids[]"]:checked').length) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Please select at least one student'
                        });
                    }

                });
            });
        </script>

        <script>
            $(document).ready(function() {
                // Handle view profile button click
                $(document).on('click', '.view-student-profile', function() {
                    const studentId = $(this).data('id');
                    const modal = new bootstrap.Modal(document.getElementById('studentProfileModal'));

                    // Show loading state
                    $('#studentProfileContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading student profile...</p>
            </div>
        `);

                    // Show modal immediately
                    modal.show();

                    // Load content via AJAX
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
                            $('#studentProfileContent').html(`
                    <div class="alert alert-danger">
                        Failed to load student profile. Please try again.
                    </div>
                `);
                        }
                    });
                });

                // Close modal when clicking outside or pressing ESC
                $('#studentProfileModal').on('hidden.bs.modal', function() {
                    $('#studentProfileContent').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading student profile...</p>
            </div>
        `);
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
                <?php $jsFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT; ?>
                document.addEventListener('DOMContentLoaded', function () {
                    var c = {
                        name: <?= json_encode($newStudentCredentials['name'], $jsFlags) ?>,
                        email: <?= json_encode($newStudentCredentials['email'], $jsFlags) ?>,
                        password: <?= json_encode($newStudentCredentials['password'], $jsFlags) ?>
                    };
                    var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
                    Swal.fire({
                        icon: 'success',
                        title: 'Student Registered',
                        html: '<p><strong>' + esc(c.name) + '</strong> was added to your advisory class. Give them these login details:</p>' +
                            '<table class="table table-sm text-start mb-2"><tr><th>Email</th><td>' + esc(c.email) + '</td></tr>' +
                            '<tr><th>Password</th><td><code>' + esc(c.password) + '</code></td></tr></table>' +
                            '<small class="text-muted">The password won\'t be shown again.</small>',
                        showDenyButton: true,
                        denyButtonText: '<i class="bi bi-clipboard"></i> Copy details',
                        denyButtonColor: '#6c757d',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#061E4E',
                        preDeny: function () {
                            var text = 'Email: ' + c.email + '\nPassword: ' + c.password;
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