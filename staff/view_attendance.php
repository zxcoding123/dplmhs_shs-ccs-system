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
            // Assuming you have already established a PDO connection as $pdo
            
            // Check if the subject_id is passed in the URL
            if (isset($_GET['subject_id'])) {
                $subject_id = $_GET['subject_id'];

                // Query to get the subject name for the given subject_id
                $subjectQuery = "SELECT name FROM subjects WHERE id = :subject_id";
                $subjectStmt = $pdo->prepare($subjectQuery);
                $subjectStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                $subjectStmt->execute();
                $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

                // If subject is found, assign the name to the $subject_name variable
                $subject_name = $subject ? $subject['name'] : 'Subject Not Found';

                // Query to get all meetings for the subject and order them by date
                $meetingsQuery = "SELECT id, date, start_time, end_time, type FROM subjects_meetings WHERE subject_id = :subject_id ORDER BY date ASC";
                $meetingsStmt = $pdo->prepare($meetingsQuery);
                $meetingsStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                $meetingsStmt->execute();
                $meetings = $meetingsStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Handle case where subject_id is not provided
                echo "Subject ID is missing!";
                exit;
            }
            ?>

            <main class="content">
                <div class="container-fluid mt-5">
                    <div class="card mt-4 shadow-sm border-light">
                        <div class="card-body">
                            <h2>Attendance for Subject: <?php echo htmlspecialchars($subject_name); ?></h2>

                            <!-- Meeting Date Filter Dropdown -->
                            <label for="meetingDateFilter">Filter by Date:</label>
                            <select id="meetingDateFilter" class="form-select mb-3">
                                <option value="">Select a Date</option>
                                <?php foreach ($meetings as $meeting): ?>
                                    <option value="<?php echo $meeting['date']; ?>">
                                        <?php echo $meeting['date'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- DataTable for Attendance Records -->
                            <table id="attendanceTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Meeting Date</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Loop through each meeting and fetch attendance data
                                    foreach ($meetings as $meeting) {
                                        $meeting_date = $meeting['date'];

                                        // Query to get attendance for the specific meeting
                                        $attendanceQuery = "SELECT sa.fullName, sa.email, a.status, a.date FROM attendance a
                                JOIN students sa ON a.student_id = sa.id
                                WHERE a.subject_id = :subject_id 
                                AND DATE(a.date) = :meeting_date";
                                        $attendanceStmt = $pdo->prepare($attendanceQuery);
                                        $attendanceStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                                        $attendanceStmt->bindParam(':meeting_date', $meeting_date, PDO::PARAM_STR);
                                        $attendanceStmt->execute();
                                        $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

                                        // Display attendance data if records exist
                                        if (!empty($attendanceRecords)) {
                                            foreach ($attendanceRecords as $attendance): ?>
                                                <tr>
                                                    <td><?php echo ($attendance['date']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($attendance['fullName']); ?></td>
                                                    <td>
                                                        <span style="text-transform:capitalize"
                                                            class="badge 
                                                  <?php echo ($attendance['status'] == 'Present') ? 'bg-success text-white' :
                                                      (($attendance['status'] == 'Absent') ? 'bg-danger text-white' : 'bg-warning text-dark'); ?>">
                                                            <?php echo htmlspecialchars($attendance['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>



            <script>
                $(document).ready(function () {
                    // Initialize DataTable
                    var table = $('#attendanceTable').DataTable();

                    // Filter table by selected meeting date
                    $('#meetingDateFilter').change(function () {
                        var selectedDate = $(this).val();
                        if (selectedDate) {
                            table.column(0).search(selectedDate).draw(); // Assuming column 0 holds the meeting date
                        } else {
                            table.column(0).search('').draw(); // Reset filter when no date is selected
                        }
                    });
                });
            </script>






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