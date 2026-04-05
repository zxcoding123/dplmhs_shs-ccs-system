<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set the timezone for the script
require('../vendor/phpqrcode/qrlib.php');
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}

$class_id = $_GET['class_id'];
$classAttendanceId = $_GET['classAttendanceId'];
$semester_id = $_GET['semesterId'];
include('processes/server/conn.php');
$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ADDU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>


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

        .meeting-day {
            background-color: rgba(40, 167, 69, 0.5) !important;
            /* Light green background */
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
                <span class="text-white"><b>AdNU</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>



            <main class="content">
                <div id="page-content-wrapper">
                    <div class="container-fluid">

                        <div class="card mb-4">
                            <div class="card-header">
                                <!-- Optional card header content -->
                            </div>
                            <div class="card-body mb-4">

                                <a href="class_attendance.php?class_id=<?php echo $class_id ?>&semester_id=<?php echo $semester_id ?>"
                                    class="d-flex align-items-center mb-3">
                                    <i class="bi bi-arrow-left-circle"
                                        style="font-size: 1.5rem; margin-right: 5px;"></i>
                                    <p class="m-0">Back</p>
                                </a>

                                <?php
                                $classAttendanceId = $_GET['classAttendanceId'] ?? null; // Get the meeting ID from the URL
                                $class_id = $_GET['class_id'] ?? null; // Get the class ID from the URL
                                $semester = $_GET['semester'] ?? null; // Optional semester parameter

                                // Ensure `classAttendanceId` is provided before proceeding
                                if (!$classAttendanceId) {
                                    die("Error: Missing 'classAttendanceId' parameter.");
                                }

                                try {
                                    // Fetch meeting data
                                    $stmt = $pdo->prepare("
                                       SELECT id, date, class_id, status, start_time, end_time, type, addu_radius
                                       FROM classes_meetings 
                                       WHERE id = :id
                                   ");
                                    $stmt->bindParam(':id', $classAttendanceId, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);

                                    // Validate meeting data
                                    if (!$meetingData) {
                                        die("Error: Meeting data not found.");
                                    }

                                    // Extract data for further processing
                                    $date = new DateTime($meetingData['date']);
                                    $meetingId = $meetingData['id'];
                                    $classId = $meetingData['class_id'];
                                    $startTime = str_replace(':', '_', $meetingData['start_time']); // Sanitize time
                                    $endTime = str_replace(':', '_', $meetingData['end_time']);
                                    $type = $meetingData['type'];

                                    // QR Code generation
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                                    $host = $_SERVER['HTTP_HOST'];

                                    // EDIT THIS ONE


                                    $baseUrl = 'https://ccs-sms.com/attendance/attendance_student_scanning.php';

                                    // Construct the QR data
                                    $qrData = "$baseUrl?id={$meetingId}&class_id={$classId}&date={$date->format('Y-m-d')}&start_time=$startTime&end_time=$endTime&meetingId=$classAttendanceId";

                                    // Create folder if it does not exist
                                    $qrFolder = __DIR__ . '/class_attendance/';
                                    if (!is_dir($qrFolder)) {
                                        mkdir($qrFolder, 0755, true);
                                    }

                                    // Define QR code file path
                                    $qrImageName = "{$startTime}_{$endTime}_{$classId}_{$meetingId}_{$date->format('Y-m-d')}_qr_image.png";
                                    $qrImagePath = $qrFolder . $qrImageName;

                                    // Generate the QR code
                                    QRcode::png($qrData, $qrImagePath);

                                    // Optional: Fetch additional class data if needed
                                    $classData = null;
                                    if ($class_id) {
                                        $stmt = $pdo->prepare("
                                           SELECT name, subject, teacher, semester 
                                           FROM classes 
                                           WHERE id = :class_id 
                                           LIMIT 1
                                       ");
                                        $stmt->execute(['class_id' => $class_id]);
                                        $classData = $stmt->fetch(PDO::FETCH_ASSOC);
                                    }
                                } catch (PDOException $e) {
                                    die("Error: " . $e->getMessage());
                                }
                                ?>

                                <div class="row">
                                    <h2 class="bold" style="margin-bottom: 20px; text-align:center;">Class Attendance
                                        for <?php echo $date->format('F j, Y') ?></h2>
                                    <h2>Class Details</h2>
                                    <div class="col">
                                        <h3><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                Teacher:</b>
                                            <span><?php echo htmlspecialchars($classData['teacher'] ?? 'Not Assigned'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                            <span><?php echo htmlspecialchars($classData['subject'] ?? 'No Subject'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-building" style="margin-right: 5px;"></i> Year and
                                                Section:</b>
                                            <span><?php echo htmlspecialchars($classData['name'] ?? 'Not Available'); ?></span>
                                        </h3>
                                    </div>
                                    <div class="col">
                                        <h3><b><i class="bi bi-calendar3" style="margin-right: 5px;"></i> Semester:</b>
                                            <span><?php echo htmlspecialchars($classData['semester'] ?? 'No Semester'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-range" style="margin-right: 5px;"></i> School
                                                Year:</b>
                                            <span>2023-2024</span> <!-- Replace with actual school year if dynamic -->
                                        </h3>

                                    </div>

                                    <?php
                                    // Assume $date is a DateTime object representing the meeting date
                                    // And $meetingData is an array with class information including 'start_time' and 'end_time'

                                    $currentDateTime = new DateTime(); // Current date and time

                                    // Combine the meeting date and end time for comparison
                                    $classStartDateTime = new DateTime($date->format('Y-m-d') . ' ' . $meetingData['start_time']);
                                    $classEndDateTime = new DateTime($date->format('Y-m-d') . ' ' . $meetingData['end_time']);

                                    // Check if the meeting has ended or not
                                    $isClassFinished = $currentDateTime > $classEndDateTime;

                                    // Check if the current time is within the attendance timeframe
                                    $isInAttendanceWindow = $currentDateTime >= $classStartDateTime && $currentDateTime <= $classEndDateTime;

                                    // Check if the class date is today
                                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                                    // Check if the QR code path is set and the file exists
                                    $qrCodeExists = isset($qrImagePath) && file_exists($qrImagePath);

                                    // Display logic based on conditions
                                    if ($isClassFinished) {
                                        // If the class time has passed (after end_time)
                                    ?>
                                        <div class="col text-end">
                                            <div class="text-center mb-3">
                                                <h4>Attendance Closed</h4>
                                                <p>The attendance period for this class has ended.</p>
                                            </div>
                                        </div>
                                    <?php
                                    } elseif ($isToday && $qrCodeExists && $isInAttendanceWindow) {
                                        // If it's today and the QR code exists (class is happening today)
                                    ?>
                                        <div class="col text-end">
                                            <!-- QR Code Display -->
                                            <div class="text-center mb-3">
                                                <h4>Scan the QR Code for Attendance</h4>
                                                <img src="class_attendance/<?php echo htmlspecialchars($qrImageName); ?>"
                                                    alt="Class Attendance QR Code" style="width: 200px; height: auto;">
                                            </div>
                                        </div>
                                    <?php
                                    } elseif ($isInAttendanceWindow) {
                                        // If it's within the allowed attendance window (between start_time and end_time)
                                    ?>
                                        <div class="col text-end">
                                            <div class="text-center mb-3">
                                                <h4>You can mark attendance now</h4>
                                                <p>Please scan the QR code for attendance.</p>
                                                <img src="class_attendance/<?php echo htmlspecialchars($qrImageName); ?>"
                                                    alt="Class Attendance QR Code" style="width: 200px; height: auto;">
                                            </div>
                                        </div>
                                    <?php
                                    } else {
                                        // If it's not the right time for attendance (outside the start and end time)
                                    ?>
                                        <div class="col text-end">
                                            <!-- QR Code Notice -->
                                            <div class="text-center mb-3">
                                                <h4>QR Code for Attendance</h4>
                                                <p>The QR code will be generated on the day and time of the class.</p>
                                                <p>Class Date: <?php echo $date->format('F j, Y'); ?></p>
                                                <p>Attendance Time:
                                                    <?php echo $meetingData['start_time'] . ' - ' . $meetingData['end_time']; ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>

                                </div>

                                <br>
                                <div class="row">
                                    <h2>Meeting Details</h2>
                                    <div class="col">
                                        <!-- Display meeting details -->
                                        <h3><b><i class="bi bi-clock" style="margin-right: 5px;"></i> Start Time:</b>
                                            <span><?php echo htmlspecialchars($meetingData['start_time'] ?? 'N/A'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-clock-history" style="margin-right: 5px;"></i> End
                                                Time:</b>
                                            <span><?php echo htmlspecialchars($meetingData['end_time'] ?? 'N/A'); ?></span>
                                        </h3>
                                    </div>
                                    <Div class="col">
                                        <h3><b><i class="bi bi-bar-chart-line" style="margin-right: 5px;"></i>
                                                Status:</b>
                                            <span><?php echo htmlspecialchars($meetingData['status'] ?? 'N/A'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-list-task" style="margin-right: 5px;"></i> Type:</b>
                                            <span><?php echo htmlspecialchars($meetingData['type'] ?? 'N/A'); ?></span>
                                        </h3>
                                    </Div>

                                </div>
                                <hr>



                                <!-- Attendance List -->

                                <?php

                                $class_id = $_GET['class_id'] ?? null; // Get the class ID from the URL
                                $meeting_id = $_GET['classAttendanceId'];




                                $students = [];
                                if ($class_id) {
                                    $stmt = $pdo->prepare("SELECT se.student_id FROM students_enrollments se WHERE se.class_id = :class_id");
                                    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                    $stmt->execute();

                                    $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Fetch student details based on student_id
                                    foreach ($enrolledStudents as $enrollment) {


                                        $studentId = $enrollment['student_id'];


                                        $stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE student_id = :student_id");
                                        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
                                        $stmt->execute();

                                        $studentData = $stmt->fetch(PDO::FETCH_ASSOC);


                                        if ($studentData) {
                                            // Fetch attendance status from the attendance table
                                            $attendanceStatus = getAttendanceStatus($studentId, $class_id,    $meeting_id); // Function to determine attendance status

                                            $students[] = [
                                                'id' => $studentData['id'],
                                                'fullName' => $studentData['fullName'],
                                                'status' => $attendanceStatus,
                                            ];
                                        }
                                    }
                                }

                                // Function to determine attendance status based on your application logic
                                function getAttendanceStatus($studentId, $classId, $meeting_id)
                                {
                                    global $pdo; // Use the global PDO connection
                                    $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = :student_id AND meeting_id = :meeting_id");
                                    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);

                                    $stmt->bindParam(':meeting_id', $meeting_id, PDO::PARAM_INT);
                                    $stmt->execute();



                                    $attendanceRecord = $stmt->fetch(PDO::FETCH_ASSOC);



                                    return $attendanceRecord ? $attendanceRecord['status'] : 'none'; // Return 'none' if no record found
                                }

                                // Function to determine attendance status based on your application logic
                                ?>

                                <div id="printableArea">
                                    <h2 class="bold" style="margin-bottom: 20px; text-align:center;">Class Attendance
                                        for <?php echo $date->format('F j, Y') ?></h2>
                                    <div class="row text-center">
                                        <div class="col">

                                            <h5><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                                <span><?php echo htmlspecialchars($classData['subject'] ?? 'No Subject'); ?></span>
                                            </h5>
                                        </div>
                                        <div class="col">
                                            <h5><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                    Teacher:</b>
                                                <span><?php echo htmlspecialchars($classData['teacher'] ?? 'Not Assigned'); ?></span>
                                            </h5>

                                        </div>
                                        <!-- Button Section -->
                                        <div class="col" id="noPrint">


                                            <!-- Set all to Present button with an icon -->
                                            <a href="javascript:void(0)"
                                                onclick="setAllPresent('<?php echo $_GET['class_id']; ?>', '<?php echo $_GET['classAttendanceId']; ?>')">
                                                <button class="btn btn-primary" id="setAllPresent">
                                                    <i class="bi bi-check-circle" style="margin-right: 5px;"></i> Set
                                                    all to Present
                                                </button>
                                            </a>

                                            <!-- Set all to Absent button with an icon -->
                                            <a href="javascript:void(0)"
                                                onclick="setAllAbsent('<?php echo $_GET['class_id']; ?>', '<?php echo $_GET['classAttendanceId']; ?>')">
                                                <button class="btn btn-danger" id="setAllAbsent">
                                                    <i class="bi bi-x-circle" style="margin-right: 5px;"></i> Set all to
                                                    Absent
                                                </button>
                                            </a>
                                            <?php if ($meetingData['addu_radius'] == 'on') { ?>
                                                <!-- Turn off ADDU Radius button with an icon -->
                                                <button class="btn btn-warning" id="turnOffAdduRadius"
                                                    onclick="turnAdduRadius('off', <?php echo $_GET['class_id']; ?>, '<?php echo $_GET['classAttendanceId']; ?>')">
                                                    <i class="bi bi-x-circle" style="margin-right: 5px;"></i> Turn off ADDUU
                                                    Radius
                                                </button>
                                            <?php } else { ?>
                                                <!-- Turn on ADDU Radius button with an icon -->
                                                <button class="btn btn-success" id="turnOnAdduRadius"
                                                    onclick="turnAdduRadius('on', <?php echo $_GET['class_id']; ?>, '<?php echo $_GET['classAttendanceId']; ?>')">
                                                    <i class="bi bi-circle" style="margin-right: 5px;"></i> Turn on ADDU
                                                    Radius
                                                </button>
                                            <?php } ?>


                                        </div>

                                        <script>
                                            function setAllPresent(classId, meetingId) {
                                                // Display SweetAlert confirmation
                                                Swal.fire({
                                                    title: 'Are you sure?',
                                                    text: "This action will set all students to Present.",
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Yes, set all to Present',
                                                    cancelButtonText: 'No, cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        // Proceed to set all to Present
                                                        window.location.href = `setAllPresent.php?class_id=${classId}&meeting_id=${meetingId}`;
                                                    }
                                                });
                                            }

                                            function setAllAbsent(classId, meetingId) {
                                                // Display SweetAlert confirmation
                                                Swal.fire({
                                                    title: 'Are you sure?',
                                                    text: "This action will set all students to Absent.",
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Yes, set all to Absent',
                                                    cancelButtonText: 'No, cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        // Proceed to set all to Absent
                                                        window.location.href = `setAllAbsent.php?class_id=${classId}&meeting_id=${meetingId}`;
                                                    }
                                                });
                                            }

                                            function turnAdduRadius(action, classId, meetingId) {
                                                // Display SweetAlert confirmation
                                                let message = (action === 'on') ?
                                                    'Are you sure you want to turn on ADDU Radius?' :
                                                    'Are you sure you want to turn off ADDU Radius?';

                                                Swal.fire({
                                                    title: 'Are you sure?',
                                                    text: message,
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonText: `Yes, turn ${action} ADDU Radius`,
                                                    cancelButtonText: 'No, cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        // Redirect to the respective action based on on/off status
                                                        window.location.href = `turnOffAdduRadius.php?action=${action}&class_id=${classId}&meeting_id=${meetingId}`;
                                                    }
                                                });
                                            }
                                        </script>


                                    </div>
                                    <hr>
                                    <h3 class="text-center">Attendance List</h3>

                                    <table id="attendanceTable" class="display">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="attendanceBody">
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['fullName']); ?></td>
                                                    <td>
                                                        <?php if ($student['status'] === 'present'): ?>
                                                            <span class="text-success">✔ Present</span>
                                                        <?php elseif ($student['status'] === 'late'): ?>
                                                            <span class="text-warning">✔ Late</span>
                                                        <?php elseif ($student['status'] === 'none'): ?>
                                                            <span class="text-warning">✔ No attendance yet</span>
                                                        <?php else: ?>
                                                            <span class="text-danger">✖ Absent</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                </div>

                                <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        function fetchAttendance() {
                                            let classId = "<?php echo $_GET['class_id']; ?>";
                                            let meetingId = "<?php echo $_GET['classAttendanceId']; ?>";


                                            fetch(`fetch_attendance.php?class_id=${classId}&meeting_id=${meetingId}`)
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.error) {
                                                        console.error(data.error);
                                                        return;
                                                    }

                                                    let tbody = document.getElementById("attendanceBody");
                                                    tbody.innerHTML = ""; // Clear old data

                                                    data.forEach(student => {
                                                        let row = `<tr>
                        <td>${student.fullName}</td>
                        <td>${getStatusLabel(student.status)}</td>
                    </tr>`;
                                                        tbody.innerHTML += row;
                                                    });
                                                })
                                                .catch(error => console.error("Error fetching attendance:", error));
                                        }

                                        function getStatusLabel(status) {
                                            if (status === "present") {
                                                return '<span class="text-success">✔ Present</span>';
                                            } else if (status === "late") {
                                                return '<span class="text-warning">✔ Late</span>';
                                            } else if (status === "none") {
                                                return '<span class="text-warning">✔ No attendance yet</span>';
                                            } else {
                                                return '<span class="text-danger">✖ Absent</span>';
                                            }
                                        }

                                        // Auto-refresh every 10 seconds
                                        setInterval(fetchAttendance, 1000);

                                    });

                                    setInterval(fetchAttendance, 1000);
                                </script>

                                <button onclick="printDiv('printableArea')" class="btn btn-primary mt-3">Print
                                    Attendance</button>

                                <!-- JavaScript to print a specific div -->
                                <script>
                                    function printDiv(divId) {
                                        // Get the HTML content to print
                                        var printContents = document.getElementById(divId).innerHTML;

                                        // Create a new window for printing
                                        var printWindow = window.open('', '_blank');

                                        // Add styles and contents
                                        printWindow.document.write(`
            <html>
            <head>
                <title>Print</title>
                <!-- Include Bootstrap CSS -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
                <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
                <style>
                    /* Set print styles */
                    * {
                        font-family: 'Calibri', sans-serif;
                    }
                    body {
                     
                        margin: 0;
                        padding: 0;
                       
                    }
                    .content-container {
                        padding: 20px;
                        border: 1px solid #ccc;
                        border-radius: 10px;
                        background-color: #ffffff;
                    }
                      @media print {
    #noPrint {
        display: none;
    }
}

                </style>
            </head>
            <body>
                <div class="container content-container">
                    ${printContents}
                </div>
            </body>
            </html>
        `);

                                        // Close the document stream
                                        printWindow.document.close();

                                        // Wait for the content to load before printing
                                        printWindow.onload = function() {
                                            printWindow.focus();
                                            printWindow.print();
                                            printWindow.close();
                                        };
                                    }

                                    // Initialize DataTable with your required settings
                                    $(document).ready(function() {
                                        $('#attendanceTable').DataTable({
                                            paging: false, // Disables pagination
                                            searching: false, // Disables the search box
                                            info: false, // Hides "Showing entries" information
                                            lengthChange: false // Hides the "Number of entries" dropdown
                                        });
                                    });
                                </script>


                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>




        <script src="js/app.js"></script>
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



        <!-- Add this modal to your HTML for creating a new class meeting -->



        <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createClassModalLabel">Create Class Meeting on <span
                                id="createModalDate"></span></h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createClassForm">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" value="<?php echo $subjectName ?>"
                                    readonly required>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-control" id="type" required>
                                    <option value="" disabled selected>Select type</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Late">Ongoing</option>
                                    <option value="Make-up">Ended</option>
                                </select>
                            </div>


                            <div class="mb-3">
                                <label for="startTime" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="startTime" value="<?php echo $startTime ?>"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="endTime" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="endTime" value="<?php echo $endTime ?>"
                                    required>
                            </div>


                            <button type="submit" class="btn btn-primary">Create Meeting</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>



        <div class="modal modal-lg fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="classModalLabel">Classes on <span id="modalDate"></span></h5>
                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="classDetails"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editClassModalLabel">Edit Class Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editClassForm">
                            <input type="hidden" id="editClassId" name="classId">
                            <div class="mb-3">
                                <label for="status" class="form-label">Class Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                $('#attendanceTable').DataTable(); // Initialize DataTable
            });
        </script>


</html>



<?php
include('processes/server/alerts.php');
?>