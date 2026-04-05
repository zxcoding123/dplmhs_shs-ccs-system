<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set the timezone for the script
require('../vendor/phpqrcode/qrlib.php');
if (!isset($_SESSION['student_id'])) {
    $_SESSION['STATUS'] = "STUDENT_NOT_LOGGED_IN";
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>


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

    .meeting-day {
        background-color: rgba(40, 167, 69, 0.5) !important;
        /* Light green background */
    }

    .bordered {
        border: 1px solid black;
        margin: 10px;
        padding: 10px;
    }

    .container-bordered {
        border: 1px solid black;
        margin: 10px;
        padding: 10px;
    }
</style>

<body>
    <div class="wrapper">
        <?php
        include('sidebar.php')
        ?>

        <div class="main">
            <?php include('topbar.php') ?>
            <main class="content">
                <div id="page-content-wrapper">
                    <div class="container-fluid">

                        <div class="card mb-4">
                            <div class="card-header">
                                <!-- Optional card header content -->
                            </div>
                            <div class="card-body mb-4">
                                <a href="student_dashboard.php" class="d-flex align-items-center mb-3">
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
                                       SELECT id, date, class_id, status, start_time, end_time, type 
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
                                    // Dynamically get the server's IP address
                                    $ipAddress = $_SERVER['SERVER_ADDR'];
                                    $baseUrl = "https://ccs-sms.com/capstone/attendance/attendance_student_scanning.php";

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
                                        for <?php echo $date->format('F j, Y'); ?>
                                    </h2>
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
                                    $student_id = $_SESSION['student_id'];
                                    $meeting_id = $_GET['classAttendanceId'];
                                    $class_id = $_GET['class_id'];  // Assuming $class_id comes from a GET parameter


                                    // SQL query to get attendance status
                                    $sql = "SELECT status FROM attendance WHERE student_id = :student_id AND class_id = :class_id AND meeting_id = :meeting_id";

                                    try {
                                        // Prepare and bind the parameters
                                        $stmt = $pdo->prepare($sql);
                                        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                        $stmt->bindParam(':meeting_id', $meeting_id, PDO::PARAM_INT);

                                        // Execute the statement
                                        $stmt->execute();

                                        // Fetch the result
                                        $attendanceStatus = $stmt->fetch(PDO::FETCH_ASSOC)['status'] ?? 'Absent';
                                    } catch (PDOException $e) {
                                        echo "Error: " . $e->getMessage();
                                    }



                                    // Current time and class start and end time calculation
                                    $currentDateTime = new DateTime(); // Current date and time

                                    // Define class meeting date, start and end times
                                    $classStartDateTime = new DateTime($date->format('Y-m-d') . ' ' . $meetingData['start_time']);
                                    $classEndDateTime = new DateTime($date->format('Y-m-d') . ' ' . $meetingData['end_time']);

                                    // Conditions for checking if the class has finished or is ongoing
                                    $isClassFinished = $currentDateTime > $classEndDateTime;
                                    $isInAttendanceWindow = $currentDateTime >= $classStartDateTime && $currentDateTime <= $classEndDateTime;
                                    $isToday = $date->format('Y-m-d') === date('Y-m-d');

                                    // Check if the QR code file exists
                                    $qrCodeExists = isset($qrImagePath) && file_exists($qrImagePath);

                                    // Attendance Logic
                                    if ($isClassFinished) {
                                        // If the class time has passed (after end_time)
                                        echo '
        <div class="col text-end">
            <div class="text-center mb-3">
                <h4>Attendance Closed</h4>
                <p>The attendance period for this class has ended.</p>
                 <br>
         <p>Your attendance status: <span class="alert alert-warning">' . ucfirst($attendanceStatus) . '</span></p>
                <br>
            </div>
        </div>';
                                    } elseif ($isToday && $qrCodeExists && $isInAttendanceWindow && $attendanceStatus == 'late') {
                                        // If within the allowed attendance window (between start_time and end_time)
                                        echo '
<div class="col text-end">
    <div class="text-center mb-3">
        <h4>You can mark attendance now</h4>
        <p>Please scan the QR code for attendance.</p>
        <br>
         <p>Your attendance status: <span class="alert alert-warning">Late</span></p>
                <br>
        <img src="class_attendance/' . htmlspecialchars($qrImageName) . '"
             alt="Class Attendance QR Code" style="width: 200px; height: auto;">
    </div>
</div>';
                                    } elseif ($isToday && $qrCodeExists && $isInAttendanceWindow && $attendanceStatus == 'absent') {
                                        // If within the allowed attendance window (between start_time and end_time)
                                        echo '
<div class="col text-end">
<div class="text-center mb-3">
<h4>You can mark attendance now</h4>
<p>Please scan the QR code for attendance.</p>
<br>
<p>Your attendance status: <span class="alert alert-danger">Absent</span></p>
<br>
<img src="class_attendance/' . htmlspecialchars($qrImageName) . '"
alt="Class Attendance QR Code" style="width: 200px; height: auto;">
</div>
</div>';
                                    } elseif ($isToday && $qrCodeExists && $isInAttendanceWindow && $attendanceStatus == 'present') {
                                        // If within the allowed attendance window (between start_time and end_time)
                                        echo '
<div class="col text-end">
<div class="text-center mb-3">
<h4>You have already marked for attendance!</h4><br>
      <p>Your attendance status: <span class="alert alert-success">Present</span></p>
<br>
</div>';
                                    } else if ($isToday && $qrCodeExists && $isInAttendanceWindow) {


                                        echo '
<div class="col text-end">
    <div class="text-center mb-3">
        <h4>You can mark attendance now</h4>
        <p>Please scan the QR code for attendance.</p>
        <br>
      
        <img src="class_attendance/' . htmlspecialchars($qrImageName) . '"
             alt="Class Attendance QR Code" style="width: 200px; height: auto;">
    </div>
</div>';
                                    } else {
                                        // If the time is outside the attendance window
                                        echo '
        <div class="col text-end">
            <div class="text-center mb-3">
                <h4>QR Code for Attendance</h4>
                <p>The QR code will be generated on the day and time of the class.</p>
                <p>Class Date: ' . $date->format('F j, Y') . '</p>
                <p>Attendance Time: ' . $meetingData['start_time'] . ' - ' . $meetingData['end_time'] . '</p>
            </div>
        </div>';
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
                                    <div class="col">
                                        <h3><b><i class="bi bi-bar-chart-line" style="margin-right: 5px;"></i>
                                                Status:</b>
                                            <span><?php echo htmlspecialchars($meetingData['status'] ?? 'N/A'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-list-task" style="margin-right: 5px;"></i> Type:</b>
                                            <span><?php echo htmlspecialchars($meetingData['type'] ?? 'N/A'); ?></span>
                                        </h3>
                                    </div>
                                </div>
                                <hr>
                            </div>

                            <div class="row text-center d-flex justify-content-center">
                                <h3 class="bold">Shortcut Links</h3>
                                <div class="col-sm-2 container-bordered cb-hover  " data-bs-toggle="collapse"
                                    data-bs-target="#studentAllInfo">
                                    Info
                                </div>
                                <div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
                                    data-bs-target="#studentAllSubjects">
                                    Subjects
                                </div>
                                <div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
                                    data-bs-target="#studentAllActivities">
                                    Activities
                                </div>

                                <div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
                                    data-bs-target="#studentAllAttendance">
                                    Attendance
                                </div>

                                <div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
                                    data-bs-target="#studentAllGrades">
                                    Grades
                                </div>
                            </div>

                            <?php
                            if (isset($_SESSION['student_id'])) {

                                $studentId = $_SESSION['student_id'];

                                // Fetch student data from the database
                                $sql = "SELECT * FROM student_info WHERE student_id = :studentId";
                                $stmt = $pdo->prepare($sql);
                                $stmt->bindParam(':studentId', $studentId);
                                $stmt->execute();

                                // Assuming there is one row of data
                                $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                                // If no data is found, redirect or handle the error
                                if (!$studentInfo) {
                                }
                            }
                            ?>


                            <div class="accordion" id="shortcutLinks">
                                <div class="container-fluid accordion-collapse collapse bordered" id="studentAllInfo"
                                    data-bs-parent="#shortcutLinks">
                                    <div class="accordion-body">
                                        <h1 class="text-center bold">Personal Information</h1>
                                        <br>
                                        <p><strong>Full Name:</strong>
                                            <?php echo htmlspecialchars($studentInfo['full_name'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Email:</strong>
                                            <?php echo htmlspecialchars($studentInfo['email'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Course & Year:</strong>
                                            <?php echo htmlspecialchars($studentInfo['course_year'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Address:</strong>
                                            <?php echo htmlspecialchars($studentInfo['address'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Phone Number:</strong>
                                            <?php echo htmlspecialchars($studentInfo['phone_number'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Emergency Contact:</strong>
                                            <?php echo htmlspecialchars($studentInfo['emergency_contact'] ?? 'No information added yet.'); ?>
                                        </p>
                                        <p><strong>Gender:</strong>
                                            <?php echo htmlspecialchars($studentInfo['gender'] ?? 'No information added yet.'); ?>
                                        </p>
                                    </div>

                                </div>

                                <div class="container-fluid accordion-collapse collapse bordered"
                                    id="studentAllSubjects" data-bs-parent="#shortcutLinks">
                                    <div class="accordion-body">
                                        <?php

                                        $studentId = $_SESSION['student_id'];

                                        $stmt = $pdo->prepare("SELECT class_id FROM students_enrollments WHERE student_id = ?");
                                        $stmt->execute([$studentId]);
                                        $enrolledClasses = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                        $classes = [];

                                        if (!empty($enrolledClasses)) {
                                            // Fetch class details from the 'classes' table using class_id
                                            $inQuery = implode(',', array_fill(0, count($enrolledClasses), '?')); // For use in WHERE IN clause
                                            $stmt = $pdo->prepare("SELECT * FROM classes WHERE id IN ($inQuery)");
                                            $stmt->execute($enrolledClasses);
                                            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        }
                                        ?>
                                        <div class="container-fluid text-center">
                                            <h1 class="bold">Subjects</h1>

                                            <div class="d-flex align-items-center">

                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#enterClassModal">
                                                    <i class="bi bi-door-open-fill"></i> Enter Class
                                                </button>
                                                <div class=" ms-auto" aria-hidden="true">
                                                    <form>
                                                        <input type="text" class="form-control" id="searchClasses"
                                                            placeholder="Search classes by name, subject, or teacher"
                                                            oninput="filterClasses()">
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="row align-items-center mb-4">



                                            </div>

                                            <div class="row" id="classesContainer">
                                                <!-- Display the enrolled classes -->
                                                <?php if (!empty($classes)): ?>
                                                    <?php foreach ($classes as $class): ?>
                                                        <div class="col mb-4 class-item">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <h5 class="card-text class-name">
                                                                        <span class="bold">
                                                                            Course Year and Section:
                                                                        </span>
                                                                        <em>
                                                                            <?php echo htmlspecialchars($class['name']); ?>
                                                                        </em>
                                                                    </h5>
                                                                    <h5 class="card-text class-subject">
                                                                        <span class="bold">Subject:
                                                                        </span><em><?php echo htmlspecialchars($class['subject']); ?></em>
                                                                    </h5>
                                                                    <h5 class="card-text class-teacher">
                                                                        <span class="bold">Teacher:</span>
                                                                        <em><?php echo htmlspecialchars($class['teacher']); ?></em>
                                                                    </h5>
                                                                    <h5 class="card-text class-code">
                                                                        <span class="bold">Class Code:</span>
                                                                        <em><?php echo htmlspecialchars($class['classCode']); ?></em>
                                                                    </h5>
                                                                    <br>
                                                                    <a href="student_classes.php?class_id=<?php echo $class['id']; ?>"
                                                                        class="btn btn-primary">
                                                                        <i class="bi bi-door-open-fill"></i> Go to Class
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p>No classes enrolled.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <script>
                                            // JavaScript function to filter classes
                                            function filterClasses() {
                                                const searchValue = document.getElementById('searchClasses').value.toLowerCase();
                                                const classItems = document.querySelectorAll('#classesContainer .class-item');

                                                classItems.forEach(item => {
                                                    const className = item.querySelector('.class-name').textContent.toLowerCase();
                                                    const classSubject = item.querySelector('.class-subject').textContent.toLowerCase();
                                                    const classTeacher = item.querySelector('.class-teacher').textContent.toLowerCase();
                                                    const classCode = item.querySelector('.class-code').textContent.toLowerCase();

                                                    // Check if the search value matches any relevant text in the card
                                                    if (
                                                        className.includes(searchValue) ||
                                                        classSubject.includes(searchValue) ||
                                                        classTeacher.includes(searchValue) ||
                                                        classCode.includes(searchValue)
                                                    ) {
                                                        item.style.display = ''; // Show the class
                                                    } else {
                                                        item.style.display = 'none'; // Hide the class
                                                    }
                                                });
                                            }
                                        </script>

                                    </div>
                                </div>

                                <div class="modal fade" id="enterClassModal" tabindex="-1"
                                    aria-labelledby="enterClassModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="enterClassModalLabel">Enter
                                                    Class Code</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="processes/students/class/enter.php">
                                                    <div class="mb-3">
                                                        <label for="classCode" class="form-label">Class
                                                            Code</label>
                                                        <input type="text" class="form-control" id="classCode"
                                                            name="classCode" placeholder="Enter Class Code">
                                                    </div>

                                            </div>
                                            <div class="modal-footer">

                                                <input type="submit" class="btn btn-csms" value="Join">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="container-fluid accordion-collapse collapse bordered"
                                    id="studentAllActivities" data-bs-parent="#shortcutLinks">
                                    <div class="accordion-body">
                                        <h1 class="bold text-center mb-4">Activities List</h1>
                                        <div>
                                            <?php
                                            try {
                                                // Fetch the student's ID from the session
                                                $student_id = $_SESSION['student_id'];

                                                // Fetch all class IDs the student is enrolled in
                                                $stmt = $pdo->prepare("SELECT e.class_id, c.subject 
                FROM students_enrollments e
                INNER JOIN classes c ON e.class_id = c.id
                WHERE e.student_id = :student_id");
                                                $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                                                $stmt->execute();
                                                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                if ($classes) {
                                                    foreach ($classes as $class) {
                                                        $class_id = $class['class_id'];
                                                        $subject = htmlspecialchars($class['subject']);
                                            ?>
                                                        <!-- Subject Title -->
                                                        <div class="subject-section mb-4 text-center">
                                                            <h3 class="subject-title mb-4"><span class="bold">Subject:</span>
                                                                <?php echo $subject; ?>
                                                            </h3>

                                                            <!-- Activity Table -->
                                                            <table class="table table-striped table-hover table-bordered">
                                                                <thead class="table-secondary">
                                                                    <tr>
                                                                        <th scope="col">
                                                                            <i class="bi bi-card-text"></i> Title
                                                                        </th>
                                                                        <th scope="col">
                                                                            <i class="bi bi-info-circle"></i> Description
                                                                        </th>
                                                                        <th scope="col">
                                                                            <i class="bi bi-calendar-date"></i> Due Date
                                                                        </th>
                                                                        <th scope="col">
                                                                            <i class="bi bi-tools"></i> Manage
                                                                        </th>
                                                                    </tr>

                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    // Fetch all activities for this class
                                                                    $activityStmt = $pdo->prepare("SELECT title, message, due_date 
                                    FROM activities 
                                    WHERE class_id = :class_id 
                                    ORDER BY due_date ASC");
                                                                    $activityStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                                    $activityStmt->execute();
                                                                    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

                                                                    if ($activities) {
                                                                        foreach ($activities as $activity) {
                                                                            $dueDate = new DateTime($activity['due_date']);
                                                                            $formattedDueDate = $dueDate->format('F j, Y');
                                                                    ?>
                                                                            <tr>
                                                                                <td><strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                                                                </td>
                                                                                <td><?php echo htmlspecialchars($activity['message']); ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php echo $formattedDueDate; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <a href="student_classes.php?class_id=<?php echo $class_id; ?>"
                                                                                        <button class="btn btn-primary"><i
                                                                                            class="bi bi-door-open-fill"></i>Open
                                                                                        Class</button>
                                                                                    </a>
                                                                                    <button class="btn btn-success"><i
                                                                                            class="bi bi-door-open-fill"></i>View (Shortcut)
                                                                                    </button>
                                                                                </td>
                                                                            </tr>
                                                                    <?php
                                                                        }
                                                                    } else {
                                                                        echo '<tr><td colspan="3" class="text-center text-muted">No activities found for this subject.</td></tr>';
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>


                                                        </div>
                                            <?php
                                                    }
                                                } else {
                                                    echo "<p class='text-muted text-center'>This student is not enrolled in any classes.</p>";
                                                }
                                            } catch (PDOException $e) {
                                                echo "<p class='text-danger text-center'>Error: " . $e->getMessage() . "</p>";
                                            }
                                            ?>
                                        </div>
                                    </div>


                                </div>
                                <div class="container-fluid accordion-collapse collapse bordered"
                                    id="studentAllAttendance" data-bs-parent="#shortcutLinks">
                                    <div class="accordion-body">
                                        <h1 class="bold text-center mb-4">Attendance</h1>
                                        <?php
                                        // Include the database connection
                                        require_once 'processes/server/conn.php';

                                        // Get the logged-in student's ID
                                        $studentId = $_SESSION['student_id'] ?? null;

                                        if ($studentId) {
                                            // Query to get meetings for classes the student is enrolled in
                                            $stmtMeetings = $pdo->prepare("
            SELECT cm.id AS meeting_id, cm.date, cm.class_id, cm.status, cm.start_time, cm.end_time, cm.type,
                   c.name AS class_name, c.subject AS subject_name, c.teacher AS teacher_name,
                   s.id AS semesterId
            FROM students_enrollments se
            JOIN classes_meetings cm ON se.class_id = cm.class_id
            JOIN classes c ON cm.class_id = c.id
            JOIN semester s ON c.semester = s.name
            WHERE se.student_id = :student_id
              AND cm.date = CURDATE()
              AND cm.status = 'Ongoing'
              AND s.status = 'active'
        ");
                                            $stmtMeetings->execute([':student_id' => $studentId]);

                                            // Check if there are results
                                            if ($stmtMeetings->rowCount() > 0) {
                                                echo '<div class="list-group">';
                                                while ($row = $stmtMeetings->fetch(PDO::FETCH_ASSOC)) {
                                                    // Create the URL for the attendance page
                                                    $attendanceUrl = 'class_attendance_qr.php?class_id=' . urlencode($row['class_id']) .
                                                        '&classAttendanceId=' . urlencode($row['meeting_id']) .
                                                        '&semesterId=' . urlencode($row['semesterId']);

                                                    echo '<div class="list-group-item border-0 mb-3 shadow-sm rounded">';
                                                    echo '<div class="d-flex justify-content-between align-items-center">';
                                                    echo '<div class="pe-3">';
                                                    echo '<h5 class="mb-1 text-primary"><strong>' . htmlspecialchars($row['class_name']) . '</strong></h5>';
                                                    echo '<p class="mb-1"><strong>Subject:</strong> ' . htmlspecialchars($row['subject_name']) . '</p>';
                                                    echo '<p class="mb-1"><strong>Teacher:</strong> ' . htmlspecialchars($row['teacher_name']) . '</p>';
                                                    echo '<p class="mb-1"><strong>Date:</strong> ' . htmlspecialchars($row['date']) . '</p>';
                                                    echo '<p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">' . htmlspecialchars($row['status']) . '</span></p>';
                                                    echo '<p class="mb-1"><strong>Start Time:</strong> ' . htmlspecialchars($row['start_time']) . '</p>';
                                                    echo '<p><strong>End Time:</strong> ' . htmlspecialchars($row['end_time']) . '</p>';
                                                    echo '</div>';
                                                    echo '<div>';
                                                    echo '<a href="' . htmlspecialchars($attendanceUrl) . '" class="btn btn-outline-primary btn-lg d-flex align-items-center">';
                                                    echo '<i class="bi bi-arrow-right-circle me-2"></i> Enter';
                                                    echo '</a>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '<p>No ongoing meetings found for today.</p>';
                                            }
                                        } else {
                                            echo '<p>Student not logged in.</p>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="container-fluid accordion-collapse collapse bordered" id="studentAllGrades"
                                    data-bs-parent="#shortcutLinks">
                                    <div class="accordion-body">
                                        <h1 class="bold text-center mb-4">Grades</h1>
                                        <?php
                                        // Include the database connection
                                        require_once 'processes/server/conn.php';

                                        // Get the logged-in student's ID
                                        $studentId = $_SESSION['student_id'] ?? null;

                                        if ($studentId) {
                                            // Query to get semesters and their start/end years
                                            $stmtSemesters = $pdo->query("
        SELECT name AS semester_name, 
               DATE_FORMAT(start_date, '%Y-%m-%d') AS start_year, 
               DATE_FORMAT(end_date, '%Y-%m-%d') AS end_year
        FROM semester
    ");
                                            $semesters = $stmtSemesters->fetchAll(PDO::FETCH_ASSOC);

                                            if ($semesters) {
                                                echo '<div class="container-fluid mt-5">';
                                                foreach ($semesters as $semester) {
                                                    $semesterName = htmlspecialchars($semester['semester_name']);
                                                    $startYear = (new DateTime($semester['start_year']))->format('F j, Y');
                                                    $endYear = (new DateTime($semester['end_year']))->format('F j, Y');

                                                    // Query classes for the semester
                                                    $stmtClasses = $pdo->prepare("
                SELECT 
                    c.id AS class_id, 
                    c.name AS class_name, 
                    c.subject AS subject_name
                FROM students_enrollments se
                JOIN classes c ON se.class_id = c.id
                WHERE se.student_id = :student_id AND c.semester = :semester_name
            ");
                                                    $stmtClasses->execute([':student_id' => $studentId, ':semester_name' => $semesterName]);

                                                    echo '<div class="card shadow">';
                                                    echo '<div class="card-header">';
                                                    echo '<h4> <span class="bold">Semester:</span> ' . $semesterName . ' <br> <br> <span class="bold">School Year and Date: </span>
													(' . $startYear . ' - ' . $endYear . ')</h4>';
                                                    echo '<br></div>';

                                                    if ($stmtClasses->rowCount() > 0) {
                                                        echo '<div class="card-body">';
                                                        echo '<div class="table-responsive text-center">';
                                                        echo '<table class="table table-striped table-hover table-bordered">';
                                                        echo '<thead class="table-secondary">';
                                                        echo '<tr>';
                                                        echo '<th><i class="bi bi-journal"></i> Subject</th>';
                                                        echo '<th><i class="bi bi-building"></i> Class</th>';
                                                        echo '<th><i class="bi bi-star"></i> Midterm Grade</th>';
                                                        echo '<th><i class="bi bi-award"></i> Final Grade</th>';
                                                        echo '</tr>';
                                                        echo '</thead>';
                                                        echo '<tbody>';

                                                        while ($row = $stmtClasses->fetch(PDO::FETCH_ASSOC)) {
                                                            $classId = $row['class_id'];
                                                            $subjectName = htmlspecialchars($row['subject_name']);
                                                            $className = htmlspecialchars($row['class_name']);

                                                            // Fetch grades for the class
                                                            $stmtGrades = $pdo->prepare("
                        SELECT midterm_grade, final_grade 
                        FROM student_grades 
                        WHERE class_id = :class_id AND student_id = :student_id
                    ");
                                                            $stmtGrades->execute([':class_id' => $classId, ':student_id' => $studentId]);
                                                            $grades = $stmtGrades->fetch(PDO::FETCH_ASSOC);

                                                            $midtermGrade = $grades ? htmlspecialchars($grades['midterm_grade']) : 'N/A';
                                                            $finalGrade = $grades ? htmlspecialchars($grades['final_grade']) : 'N/A';

                                                            echo '<tr>';
                                                            echo '<td>' . $subjectName . '</td>';
                                                            echo '<td>' . $className . '</td>';
                                                            echo '<td>' . $midtermGrade . '</td>';
                                                            echo '<td>' . $finalGrade . '</td>';
                                                            echo '</tr>';
                                                        }

                                                        echo '</tbody>';
                                                        echo '</table>';
                                                        echo '</div>'; // table-responsive
                                                        echo '</div>'; // card-body
                                                    } else {
                                                        echo '<div class="card-body">';
                                                        echo '<p class="text-muted">No classes found for this semester.</p>';
                                                        echo '</div>';
                                                    }

                                                    echo '</div>'; // card
                                                }
                                                echo '</div>'; // container
                                            } else {
                                                echo '<div class="alert alert-warning">No semesters found.</div>';
                                            }
                                        } else {
                                            echo '<div class="alert alert-danger">Student not logged in.</div>';
                                        }
                                        ?>

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



                                    <div class="modal fade" id="createClassModal" tabindex="-1"
                                        aria-labelledby="createClassModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="createClassModalLabel">Create Class
                                                        Meeting on
                                                        <span id="createModalDate"></span>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="createClassForm">
                                                        <div class="mb-3">
                                                            <label for="subject" class="form-label">Subject</label>
                                                            <input type="text" class="form-control" id="subject"
                                                                value="<?php echo $subjectName ?>" readonly required>
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
                                                            <input type="time" class="form-control" id="startTime"
                                                                value="<?php echo $startTime ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="endTime" class="form-label">End Time</label>
                                                            <input type="time" class="form-control" id="endTime"
                                                                value="<?php echo $endTime ?>" required>
                                                        </div>


                                                        <button type="submit" class="btn btn-primary">Create
                                                            Meeting</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="modal modal-lg fade" id="classModal" tabindex="-1"
                                        aria-labelledby="classModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="classModalLabel">Classes on <span
                                                            id="modalDate"></span></h5>
                                                    <button type="button" class="btn-close" data-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body" id="classDetails"></div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="editClassModal" tabindex="-1"
                                        aria-labelledby="editClassModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editClassModalLabel">Edit Class Status
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="editClassForm">
                                                        <input type="hidden" id="editClassId" name="classId">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Class Status</label>
                                                            <select class="form-select" id="status" name="status"
                                                                required>
                                                                <option value="">Select Status</option>
                                                                <option value="Ongoing">Ongoing</option>
                                                                <option value="Rescheduled">Rescheduled</option>
                                                                <option value="Cancelled">Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">Save
                                                            Changes</button>
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