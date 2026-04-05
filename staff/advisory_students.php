<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit();
}

include('processes/server/conn.php');

$class_name = $_GET['class_id'] ?? '';            // e.g. "BSIT-1A"
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


if (!preg_match('/^(?<course>[A-Z]+)-(?<year>[1-4])/', $class_name, $m)) {
    throw new RuntimeException("Bad class code: $class_name");
}
$course   = $m['course'];      // "BSIT"
$year_num = $m['year'];        // "1"

$yearMap = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
$year_level = $yearMap[$year_num];       // adjust if DB stores digits


$students_stmt = $pdo->prepare("
    SELECT s.*
    FROM students s
    JOIN students_advising sa
      ON sa.student_id = s.id
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

$referrer = $_SERVER['HTTP_REFERER'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AdNU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
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
                <img src="external/img/ADNU_Logo.png" class="logo-small">
                <span class="text-white"><b>AdNU</b> - Student Management System
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
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                    <i class="bi bi-plus-circle"></i> Add Student
                                </button>
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
                                                            <img src="external/img/ADNU_Logo.png"
                                                                class="student-photo"
                                                                alt="Default student photo">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                    <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars($student['middle_name']) ?></td>
                                                    <td><?= htmlspecialchars($student['email']) ?></td>
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

     
    <script src="js/app.js"></script>

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
            function getTime() {
                const now = new Date();
                const newTime = now.toLocaleString();

                document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
            }
            setInterval(getTime, 100);
        </script>
</body>

</html>