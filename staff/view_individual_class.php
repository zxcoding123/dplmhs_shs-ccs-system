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

                // Fetch all classes matching the class name (not using class_id as filter)
                $classQuery = "SELECT id, name AS class_name, teacher, semester, subject_id AS subject_ids FROM classes WHERE name = :class_name";
                $classStmt = $pdo->prepare($classQuery);
                $classStmt->bindParam(':class_name', $class_id, PDO::PARAM_STR);  // Use PDO::PARAM_STR for class name
                $classStmt->execute();
                $classDetails = $classStmt->fetchAll(PDO::FETCH_ASSOC);  // Use fetchAll() to get multiple records
            
                // Prepare data
                $class_name = '';
                $teacher = 'No Teacher Assigned';
                $semester = '';
                $subjects = [];
                $students = [];

                // Check if any class records were found
                if (!empty($classDetails)) {
                    // Iterate over each class
                    foreach ($classDetails as $classDetail) {
                        // Get subject IDs and fetch related subject details
                        $subjectIds = isset($classDetail['subject_ids']) ? explode(',', $classDetail['subject_ids']) : [];

                        if (!empty($subjectIds)) {
                            // Fetch subjects for the current class
                            $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
                            $subjectsQuery = "SELECT id, name, type, code, course, year_level 
                                  FROM subjects 
                                  WHERE id IN ($placeholders)";
                            $subjectsStmt = $pdo->prepare($subjectsQuery);
                            $subjectsStmt->execute($subjectIds);
                            $fetchedSubjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

                            // Merge fetched subjects with the already existing ones
                            $subjects = array_merge($subjects, $fetchedSubjects);
                        }

                        // Fetch teacher details
                        if (!empty($classDetail['teacher'])) {
                            $teacherQuery = "SELECT fullName AS teacher_name FROM staff_accounts WHERE id = :teacher_id";
                            $teacherStmt = $pdo->prepare($teacherQuery);
                            $teacherStmt->bindParam(':teacher_id', $classDetail['teacher'], PDO::PARAM_INT);
                            $teacherStmt->execute();
                            $teacherDetails = $teacherStmt->fetch(PDO::FETCH_ASSOC);
                            $teacher = $teacherDetails['teacher_name'] ?? 'No Teacher Assigned';
                        }

                        // Fetch students enrolled in the class
                        $studentsStmt = $pdo->prepare("SELECT sa.fullName, sa.email 
                                          FROM students sa 
                                          JOIN class_students cs ON sa.id = cs.student_id 
                                          WHERE cs.class_id = :class_id");
                        $studentsStmt->bindParam(':class_id', $classDetail['id'], PDO::PARAM_INT); // Use current class ID
                        $studentsStmt->execute();
                        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

                        // Get class details or set defaults
                        $class_name = $classDetail['class_name'] ?? 'Class not found';
                        $semester = $classDetail['semester'] ?? 'Not Available';
                    }
                } else {
                    $class_name = 'Class not found';
                    $semester = 'Not Available';
                }
            } else {
                $class_name = 'Class not selected';
                $semester = 'Not Available';
            }
            ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Main Card Wrapper -->
                    <div class="card shadow-sm border-light">
                        <div class="card-body">
                            <!-- Class Overview Section -->
                            <div class="mb-4">
                                <h2>Class Overview</h2>
                                <div class="row">
                                    <div class="col">
                                        <p><strong>Class:</strong> <?php echo htmlspecialchars($class_name); ?></p>
                                        <p><strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Subjects Assigned Section -->
                            <div class="mb-4">
                                <h2>Subjects Assigned to this Class</h2>
                                <div class="row">
                                    <?php if (!empty($subjects)): ?>
                                        <?php foreach ($subjects as $subject): ?>
                                            <div class="col-md-4 mb-4"> <!-- 3 columns per row -->
                                                <div class="card shadow-sm">
                                                    <div class="card-body">
                                                        <strong><?php echo htmlspecialchars($subject['name']); ?></strong><br>
                                                        <small>Code:
                                                            <?php echo htmlspecialchars($subject['code']); ?></small><br>
                                                        <small>Type:
                                                            <?php echo htmlspecialchars($subject['type']); ?></small><br>
                                                        <small>Course:
                                                            <?php echo htmlspecialchars($subject['course']); ?></small><br>
                                                        <small>Year Level:
                                                            <?php echo htmlspecialchars($subject['year_level']); ?></small><br>

                                                        <!-- Action Buttons -->
                                                        <div class="mt-3">
                                                            <a href="view_attendance.php?subject_id=<?php echo urlencode($subject['id']); ?>"
                                                                class="btn btn-primary btn-sm">View Attendance</a>
                                                            <a href="view_grades.php?subject_id=<?php echo urlencode($subject['id']); ?>"
                                                                class="btn btn-secondary btn-sm">View Grades</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p>No subjects assigned to this class.</p>
                                        </div>
                                    <?php endif; ?>
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