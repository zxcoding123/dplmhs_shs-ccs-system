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
            // Placeholder data for students and their grades (including scores and max points)
            $students = [
                [
                    'id' => 1,
                    'fullName' => 'John Doe',
                    'grades' => [
                        'Quiz' => [
                            ['score' => 85, 'max' => 100],
                            ['score' => 90, 'max' => 100],
                            ['score' => 88, 'max' => 100]
                        ],
                        'Activity' => [
                            ['score' => 78, 'max' => 80],
                            ['score' => 82, 'max' => 80],
                            ['score' => 79, 'max' => 80]
                        ],
                        'Project' => [
                            ['score' => 92, 'max' => 100]
                        ],
                        'Examination' => [
                            ['score' => 88, 'max' => 100]
                        ],
                    ]
                ],
                [
                    'id' => 2,
                    'fullName' => 'Jane Smith',
                    'grades' => [
                        'Quiz' => [
                            ['score' => 75, 'max' => 100],
                            ['score' => 80, 'max' => 100],
                            ['score' => 78, 'max' => 100]
                        ],
                        'Activity' => [
                            ['score' => 80, 'max' => 80],
                            ['score' => 85, 'max' => 80],
                            ['score' => 83, 'max' => 80]
                        ],
                        'Project' => [
                            ['score' => 91, 'max' => 100]
                        ],
                        'Examination' => [
                            ['score' => 90, 'max' => 100]
                        ],
                    ]
                ]
            ];

            // Function to calculate GPA based on grades
            function calculateGPA($grades)
            {
                $totalScore = 0;
                $totalMax = 0;
                foreach ($grades as $category => $categoryGrades) {
                    foreach ($categoryGrades as $grade) {
                        $totalScore += $grade['score'];
                        $totalMax += $grade['max'];
                    }
                }
                return $totalScore / $totalMax * 4.0;  // Simplified GPA scale (out of 4.0)
            }

            ?>

<main class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-light">
            <div class="card-body">
                <h2>Grades</h2>

                <!-- Accordion for each student -->
                <div class="accordion" id="studentsAccordion">
                    <?php foreach ($students as $index => $student): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false"
                                    aria-controls="collapse<?php echo $index; ?>">
                                    <?php echo htmlspecialchars($student['fullName']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>"
                                class="accordion-collapse collapse"
                                aria-labelledby="heading<?php echo $index; ?>"
                                data-bs-parent="#studentsAccordion">
                                <div class="accordion-body">
                                    <!-- Nested Accordion for Grades Categories -->
                                    <div class="accordion" id="gradeCategories<?php echo $index; ?>">

                                        <!-- Quizzes Accordion -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="quizHeading<?php echo $index; ?>">
                                                <button class="accordion-button" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#quizCollapse<?php echo $index; ?>"
                                                    aria-expanded="false"
                                                    aria-controls="quizCollapse<?php echo $index; ?>">
                                                    Quizzes
                                                </button>
                                            </h2>
                                            <div id="quizCollapse<?php echo $index; ?>"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="quizHeading<?php echo $index; ?>"
                                                data-bs-parent="#gradeCategories<?php echo $index; ?>">
                                                <div class="accordion-body">
                                                    <?php foreach ($student['grades']['Quiz'] as $key => $quiz): ?>
                                                        <p>Q<?php echo ($key + 1); ?> - Score:
                                                            <?php echo $quiz['score']; ?> /
                                                            <?php echo $quiz['max']; ?> </p>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Activities Accordion -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header"
                                                id="activityHeading<?php echo $index; ?>">
                                                <button class="accordion-button" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#activityCollapse<?php echo $index; ?>"
                                                    aria-expanded="false"
                                                    aria-controls="activityCollapse<?php echo $index; ?>">
                                                    Activities
                                                </button>
                                            </h2>
                                            <div id="activityCollapse<?php echo $index; ?>"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="activityHeading<?php echo $index; ?>"
                                                data-bs-parent="#gradeCategories<?php echo $index; ?>">
                                                <div class="accordion-body">
                                                    <?php foreach ($student['grades']['Activity'] as $key => $activity): ?>
                                                        <p>Activity <?php echo ($key + 1); ?> - Score:
                                                            <?php echo $activity['score']; ?> /
                                                            <?php echo $activity['max']; ?></p>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Project Accordion -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header"
                                                id="projectHeading<?php echo $index; ?>">
                                                <button class="accordion-button" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#projectCollapse<?php echo $index; ?>"
                                                    aria-expanded="false"
                                                    aria-controls="projectCollapse<?php echo $index; ?>">
                                                    Projects
                                                </button>
                                            </h2>
                                            <div id="projectCollapse<?php echo $index; ?>"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="projectHeading<?php echo $index; ?>"
                                                data-bs-parent="#gradeCategories<?php echo $index; ?>">
                                                <div class="accordion-body">
                                                    <?php foreach ($student['grades']['Project'] as $key => $project): ?>
                                                        <p>Project - Score: <?php echo $project['score']; ?> /
                                                            <?php echo $project['max']; ?></p>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Examination Accordion -->
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="examHeading<?php echo $index; ?>">
                                                <button class="accordion-button" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#examCollapse<?php echo $index; ?>"
                                                    aria-expanded="false"
                                                    aria-controls="examCollapse<?php echo $index; ?>">
                                                    Examinations
                                                </button>
                                            </h2>
                                            <div id="examCollapse<?php echo $index; ?>"
                                                class="accordion-collapse collapse"
                                                aria-labelledby="examHeading<?php echo $index; ?>"
                                                data-bs-parent="#gradeCategories<?php echo $index; ?>">
                                                <div class="accordion-body">
                                                    <?php foreach ($student['grades']['Examination'] as $key => $exam): ?>
                                                        <p>Examination - Score: <?php echo $exam['score']; ?> /
                                                            <?php echo $exam['max']; ?></p>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- GPA Calculation -->
                                    <hr>
                                    <h5>GPA: <?php echo number_format(calculateGPA($student['grades']), 2); ?>
                                    </h5>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>






        </div>
        </main>


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