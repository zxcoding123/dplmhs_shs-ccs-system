<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
}
include('processes/server/conn.php');
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
        background-color: #709775;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #709775;
    }
</style>

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
                  <img src="external/img/ADNU_Logo.png" class="logo-small">
                <span class="text-white"><b>AdNU</b> - Student Management System
                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">
                        <li class="nav-item dropdown">
                            <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
                                <div class="position-relative">
                                    <i class="align-middle" data-feather="bell"></i>
                                    <span class="indicator">4</span>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0"
                                aria-labelledby="alertsDropdown">
                                <div class="dropdown-menu-header">
                                    4 New Notifications
                                </div>
                                <div class="list-group">
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <i class="text-danger" data-feather="alert-circle"></i>
                                            </div>
                                            <div class="col-10">
                                                <div class="text-dark">Update completed</div>
                                                <div class="text-muted small mt-1">Restart server 12 to complete the
                                                    update.</div>
                                                <div class="text-muted small mt-1">30m ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <i class="text-warning" data-feather="bell"></i>
                                            </div>
                                            <div class="col-10">
                                                <div class="text-dark">Lorem ipsum</div>
                                                <div class="text-muted small mt-1">Aliquam ex eros, imperdiet vulputate
                                                    hendrerit et.</div>
                                                <div class="text-muted small mt-1">2h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <i class="text-primary" data-feather="home"></i>
                                            </div>
                                            <div class="col-10">
                                                <div class="text-dark">Login from 192.186.1.8</div>
                                                <div class="text-muted small mt-1">5h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <i class="text-success" data-feather="user-plus"></i>
                                            </div>
                                            <div class="col-10">
                                                <div class="text-dark">New connection</div>
                                                <div class="text-muted small mt-1">Christina accepted your request.
                                                </div>
                                                <div class="text-muted small mt-1">14h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="dropdown-menu-footer">
                                    <a href="#" class="text-muted">Show all notifications</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-icon dropdown-toggle" href="#" id="messagesDropdown"
                                data-bs-toggle="dropdown">
                                <div class="position-relative">
                                    <i class="align-middle" data-feather="message-square"></i>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0"
                                aria-labelledby="messagesDropdown">
                                <div class="dropdown-menu-header">
                                    <div class="position-relative">
                                        4 New Messages
                                    </div>
                                </div>
                                <div class="list-group">
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <img src="img/avatars/avatar-5.jpg"
                                                    class="avatar img-fluid rounded-circle" alt="Vanessa Tucker">
                                            </div>
                                            <div class="col-10 ps-2">
                                                <div class="text-dark">Vanessa Tucker</div>
                                                <div class="text-muted small mt-1">Nam pretium turpis et arcu. Duis arcu
                                                    tortor.</div>
                                                <div class="text-muted small mt-1">15m ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <img src="img/avatars/avatar-2.jpg"
                                                    class="avatar img-fluid rounded-circle" alt="William Harris">
                                            </div>
                                            <div class="col-10 ps-2">
                                                <div class="text-dark">William Harris</div>
                                                <div class="text-muted small mt-1">Curabitur ligula sapien euismod
                                                    vitae.</div>
                                                <div class="text-muted small mt-1">2h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <img src="img/avatars/avatar-4.jpg"
                                                    class="avatar img-fluid rounded-circle" alt="Christina Mason">
                                            </div>
                                            <div class="col-10 ps-2">
                                                <div class="text-dark">Christina Mason</div>
                                                <div class="text-muted small mt-1">Pellentesque auctor neque nec urna.
                                                </div>
                                                <div class="text-muted small mt-1">4h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="list-group-item">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-2">
                                                <img src="img/avatars/avatar-3.jpg"
                                                    class="avatar img-fluid rounded-circle" alt="Sharon Lessman">
                                            </div>
                                            <div class="col-10 ps-2">
                                                <div class="text-dark">Sharon Lessman</div>
                                                <div class="text-muted small mt-1">Aenean tellus metus, bibendum sed,
                                                    posuere ac, mattis non.</div>
                                                <div class="text-muted small mt-1">5h ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="dropdown-menu-footer">
                                    <a href="#" class="text-muted">Show all messages</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#"
                                data-bs-toggle="dropdown">
                                <i class="align-middle" data-feather="settings"></i>
                            </a>

                            <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#"
                                data-bs-toggle="dropdown">
                                <span class="text-light">Admin</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="pages-profile.html"><i class="align-middle me-1"
                                        data-feather="user"></i> Profile</a>
                                <a class="dropdown-item" href="#"><i class="align-middle me-1"
                                        data-feather="pie-chart"></i> Analytics</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="index.html"><i class="align-middle me-1"
                                        data-feather="settings"></i> Settings & Privacy</a>
                                <a class="dropdown-item" href="#"><i class="align-middle me-1"
                                        data-feather="help-circle"></i> Help Center</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">Log out</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <?php
            // Check if a class ID is passed
            if (isset($_GET['class_id'])) {
                $class_id = $_GET['class_id'];

                // Fetch class details
                $classQuery = "SELECT id, name AS class_name, subject, teacher, semester FROM classes WHERE id = :class_id";
                $classStmt = $pdo->prepare($classQuery);
                $classStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                $classStmt->execute();
                $classDetails = $classStmt->fetch(PDO::FETCH_ASSOC);

                $class_Subject = $classDetails['subject'];

                // 2. Fetch Teacher Details (only if teacher ID exists in class)
                if (isset($classDetails['teacher'])) {
                    $teacher_id = $classDetails['teacher']; // Get the teacher's ID
            

                    $teacherQuery = "SELECT fullName AS teacher_name FROM staff_accounts WHERE id = :teacher_id";
                    $teacherStmt = $pdo->prepare($teacherQuery);
                    $teacherStmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
                    $teacherStmt->execute();
                    $teacherDetails = $teacherStmt->fetch(PDO::FETCH_ASSOC);

                    // Add teacher name to class details if available
                    if ($teacherDetails) {
                        $classDetails['teacher_name'] = $teacherDetails['teacher_name'];
                    } else {
                        $classDetails['teacher_name'] = 'No Teacher Assigned';
                    }
                } else {
                    // Handle case where no teacher is assigned
                    $classDetails['teacher_name'] = 'No Teacher Assigned';
                }
                // Fetch students enrolled in the class
                $studentsStmt = $pdo->prepare("SELECT sa.fullName, sa.email FROM students sa JOIN class_students cs ON sa.id = cs.student_id WHERE cs.class_id = :class_id");
                $studentsStmt->bindParam(':class_id', $class_id);
                $studentsStmt->execute();
                $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                // Fetch subject details (name, type, code, semester, course, and year level)
                $subjectQuery = "SELECT name, type, code, semester, course, year_level FROM subjects WHERE name = :name";
                $subjectStmt = $pdo->prepare($subjectQuery);
                $subjectStmt->bindParam(':name', $class_Subject);
                $subjectStmt->execute();
                $subjectDetails = $subjectStmt->fetch(PDO::FETCH_ASSOC);

                // Get the class details or set defaults
                $class_name = $classDetails['class_name'] ?? 'Class not found';
                $subject = $classDetails['subject'] ?? 'N/A';
                $teacher = $classDetails['teacher_name'] ?? 'No Teacher Assigned';
                $semester = $classDetails['semester'] ?? 'Not Available';

                // Subject details (optional based on the best choice)
                $subject_name = $subjectDetails['name'] ?? 'N/A';
                $subject_type = $subjectDetails['type'] ?? 'N/A';
                $subject_code = $subjectDetails['code'] ?? 'N/A';
                $subject_semester = $subjectDetails['semester'] ?? 'N/A';
                $course = $subjectDetails['course'] ?? 'N/A';
                $year_level = $subjectDetails['year_level'] ?? 'N/A';
            } else {
                echo "<p>No class selected.</p>";
                exit;
            }
            ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="container">
                        <!-- Class Overview Section -->
                        <div class="card shadow-sm border-light">
                            <div class="card-body">
                                <h2>Class Overview</h2>
                                <div class="row">
                                    <div class="col">
                                        <p><strong>Class:</strong> <?php echo htmlspecialchars($class_name); ?></p>
                                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?></p>
                                        <p><strong>Subject Type:</strong> <?php echo htmlspecialchars($subject_type); ?>
                                        </p>
                                        <p><strong>Subject Code:</strong> <?php echo htmlspecialchars($subject_code); ?>
                                        </p>
                                    </div>
                                    <div class="col">
                                        <p><strong>Semester:</strong> <?php echo htmlspecialchars($subject_semester); ?>
                                        </p>
                                        <p><strong>Course:</strong> <?php echo htmlspecialchars($course); ?></p>
                                        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($year_level); ?></p>
                                    </div>
                                </div>


                            </div>
                        </div>

                        <!-- Enrolled Students Section -->
                        <div class="card mt-4 shadow-sm border-light">
                            <div class="card-body">
                                <h3 class="card-title text-secondary">Enrolled Students</h3>
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($students) {
                                            foreach ($students as $student) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($student['fullName']) . '</td>';
                                                echo '<td>' . htmlspecialchars($student['email']) . '</td>';

                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="3" class="text-center">No students enrolled in this class.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Action Section (Manage Attendance, Grades, etc.) -->
                        <div class="card mt-4 shadow-sm border-light">
                            <div class="card-body">
                                <h3 class="card-title text-secondary">Manage Class</h3>
                                <div class="btn-group" role="group">
                                    <a href="manage_attendance.php?class_id=<?php echo $class_id; ?>"
                                        class="btn btn-primary btn-lg">Manage Attendance</a>
                                    <a href="view_grades.php?class_id=<?php echo $class_id; ?>"
                                        class="btn btn-warning btn-lg">View Grades</a>
                                    <a href="assign_students.php?class_id=<?php echo $class_id; ?>"
                                        class="btn btn-info btn-lg">Assign Students</a>
                                </div>
                            </div>
                        </div>

                        <!-- Report Generation Section -->
                        <div class="card mt-4 shadow-sm border-light">
                            <div class="card-body">
                                <h3 class="card-title text-secondary">Generate Reports</h3>
                                <div class="btn-group" role="group">
                                    <a href="generate_report.php?class_id=<?php echo $class_id; ?>"
                                        class="btn btn-success btn-lg">Generate Performance Report</a>
                                    <a href="generate_attendance_report.php?class_id=<?php echo $class_id; ?>"
                                        class="btn btn-secondary btn-lg">Generate Attendance Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Add Bootstrap's JS and necessary resources -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>



        </div>
    </div>


    <script src="js/app.js"></script>
    <?php
    include('processes/server/modals.php');
    ?>




    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();
            console.log(newTime);
            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);

    </script>

</html>

<?php
include('processes/server/alerts.php');
?>