<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}
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
    <title>AdNU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>




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
                <img src="external/img/ccs_logo-removebg-preview.png" class="logo-small">
                <span class="text-white">AdNU - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>


            <?php
            $class_id = $_GET['class_id'];
            $startTime = $endTime = '';

            if ($class_id) {
                $stmt = $pdo->prepare("SELECT subject_id FROM classes WHERE id = :class_id LIMIT 1");
                $stmt->execute(['class_id' => $class_id]);
                $classData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($classData && isset($classData['subject_id'])) {
                    $subject_id = $classData['subject_id'];

                    $stmt = $pdo->prepare("SELECT start_time, end_time FROM subjects_schedules WHERE subject_id = :subject_id");
                    $stmt->execute(['subject_id' => $subject_id]);
                    $scheduleData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($scheduleData) {
                        $startTime = date('H:i', strtotime($scheduleData['start_time']));
                        $endTime = date('H:i', strtotime($scheduleData['end_time']));
                    }

                    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = :subject_id");
                    $stmt->execute(['subject_id' => $subject_id]);
                    $subjectData = $stmt->fetch(PDO::FETCH_ASSOC);

                    $subjectName = $subjectData['name'];
                }
            }
            ?>

            <main class="content">
                <div id="page-content-wrapper">
                    <div class="container-fluid">

                        <div class="card mb-4">
                            <div class="card-header">

                            </div>
                            <div class="card-body mb-4">
                                <a href="class_management.php" class="d-flex align-items-center mb-3">
                                    <i class="bi bi-arrow-left-circle"
                                        style="font-size: 1.5rem; margin-right: 5px;"></i>
                                    <p class="m-0">Back</p>
                                </a>

                                <?php
                                require 'processes/server/conn.php';

                                // Set class_id and semester (these could come from URL parameters or form inputs)
                                $class_id = $_GET['class_id'] ?? null;
                                $semester = $_GET['semester'] ?? null;

                                $classData = null;
                                if ($class_id) {
                                    // Query database to get class details
                                    $stmt = $pdo->prepare("SELECT id, name, subject, teacher, semester, datetime_added, type FROM classes WHERE id = :class_id LIMIT 1");
                                    $stmt->execute(['class_id' => $class_id]);
                                    $classData = $stmt->fetch(PDO::FETCH_ASSOC);

                                    $semester_matcher = $classData['semester'];

                                    $stmt = $pdo->prepare("SELECT school_year FROM semester WHERE name = :name");
                                    $stmt->bindParam(':name', $semester_matcher, PDO::PARAM_STR);
                                    $stmt->execute();
                                    $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($semester_found) {
                                        $schoolYear = $semester_found['school_year'];
                                    }

                                    $type = $classData['type'];
                                }
                                ?>

                                <div class="row">
                                    <h2 class="bold">Class Details</h2>

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
                                            <span><?php echo $schoolYear ?> - <?php echo $schoolYear + 1 ?></span>
                                            <!-- Replace with actual school year if dynamic -->
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-check" style="margin-right: 5px;"></i> Class
                                                Type:</b> <span><?php echo htmlspecialchars($type); ?></span></h3>
                                    </div>

                                </div>

                                <hr>
                                <div class="d-flex align-items-center justify-content-center place-items-center">
                                    <h2 class="bold">Class Attendance</h2>
                                    <div class="ms-auto" aria-hidden="true">
                                        <a
                                            href="print.php?class_id=<?php echo $_GET['class_id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>#"><button
                                                class="btn btn-primary mt-3" id="printButton">Print
                                                All</button></a>
                                    </div>
                                </div>

                                <br>

                                <?php
                                // Get the class_id from the URL
                                $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;


                                if ($class_id > 0) {
                                    // Query class_meetings
                                    $stmt = $pdo->prepare("SELECT * FROM classes_meetings WHERE class_id = :class_id");
                                    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (empty($meetings)) {
                                        echo '<div class="alert alert-warning text-center" role="alert">';
                                        echo 'No meetings found for this specified class, yet.';
                                        echo '</div>';
                                        return;
                                    }

                                    // Group meetings by month (using DateTime::format('F') for month name)
                                    $months = [];
                                    foreach ($meetings as $meeting) {
                                        $monthName = date('F', strtotime($meeting['date']));
                                        $formattedDate = date('Y-m-d', strtotime($meeting['date']));
                                        $months[$monthName][] = ['date' => $formattedDate, 'raw_date' => $meeting['date'], 'meeting_id' => $meeting['id']];
                                    }

                                    // Display Attendance Table
                                    echo '<div class="table-responsive">';
                                    echo '<h5 class="text-center"><strong>Attendance Records:</strong></h5><br>';
                                    echo '<table class="table table-bordered text-center">';
                                    echo '<thead><tr><th>Students</th>';

                                    // Display month columns with dates as header
                                    foreach ($months as $month => $dates) {
                                        echo '<th colspan="' . count($dates) . '">' . htmlspecialchars($month) . '</th>';
                                    }
                                    echo '</tr><tr><th></th>';

                                    // Display the dates for each month
                                    foreach ($months as $month => $dates) {
                                        foreach ($dates as $date) {
                                            echo '<th>' . date('j', strtotime($date['raw_date'])) . '</th>';
                                        }
                                    }

                                    echo '</tr></thead>';

                                    // Query attendance for all students
                                    $stmt2 = $pdo->prepare("SELECT DISTINCT a.student_id, s.fullName 
                                    FROM attendance a 
                                    JOIN students s ON a.student_id = s.student_id 
                                    WHERE a.class_id = :class_id 
                                    ORDER BY s.fullName DESC");
                                    $stmt2->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                    $stmt2->execute();
                                    $students = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                                    echo '<tbody>';
                                    foreach ($students as $student) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($student['fullName']) . '</td>';

                                        // Generate table data for each date in each month
                                        foreach ($months as $month => $dates) {
                                            foreach ($dates as $date) {

                                                // Query attendance data for the student and specific meeting date
                                                $stmt3 = $pdo->prepare("
                                                    SELECT a.status as status
                                                    FROM attendance a 
                                                    JOIN classes_meetings cm ON a.meeting_id = cm.id 
                                                    WHERE a.student_id = :student_id 
                                                    AND cm.date = :meeting_date");
                                                $stmt3->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                                                $stmt3->bindParam(':meeting_date', $date['date'], PDO::PARAM_STR);

                                                $stmt3->execute();
                                                $attendance = $stmt3->fetch(PDO::FETCH_ASSOC);

                                                // Default to 'absent' if no record found
                                                $status = isset($attendance['status']) ? $attendance['status'] : 'absent';



                                                // Display "X" for present, "/" for absent
                                                $attendanceSymbol = ($status === 'present') ? '/' : 'X';
                                                echo '<td class="text-bold">' . $attendanceSymbol . '</td>';
                                            }
                                        }
                                        echo '</tr>';
                                    }
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>'; // End table-responsive
                                } else {
                                    echo '<p>No valid class ID provided.</p>';
                                }
                                ?>
                                <script>
                                    function printMeetingContent(elementId) {
                                        const content = document.getElementById(elementId).innerHTML;
                                        const printWindow = window.open('', '_blank');
                                        printWindow.document.open();
                                        printWindow.document.write(`
        <html>
        <head>
            <title>Print Meeting Details</title>
        <style>
    /* General Reset */
    button {
        border: none !important;
    }

    body {
        font-family: 'Arial';
        margin: 0;
        padding: 20px;
        background-color: #f8f9fa;
        color: #333;
    }

    h1, h2, h3, h4, h5 {
        margin: 10px 0;
        text-align: center;
        color: black;
    }

    /* Flexbox Layout for Rows and Columns */
    .row {
    display: flex; /* Corrected to 'flex' instead of 'flexbox' */
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col {
    flex: 1; /* This gives each column a proportional width */
    padding: 0 15px;
    margin-bottom: 15px;
    box-sizing: border-box;
}

    /* Print Header */
    .print-header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 1px solid black;
        padding-bottom: 10px;
        display: flex;
        justify-content: space-between; /* Align items left and right */
        align-items: center;
    }

    .print-header img {
        height: 50px; /* Adjust the size of the images */
        margin: 0 10px;
    }

    /* Table Styling */
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 14px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .table th, .table td {
        text-align: left;
        padding: 12px;
        border: 1px solid #ddd;
    }

    .table th {
        background-color: #4b6cb7;
        color: #fff;
        text-transform: uppercase;
        font-weight: bold;
    }

    .table td {
        color: #555;
    }

    /* Footer Note */
    .footer {
        text-align: center;
        margin-top: 30px;
        font-size: 12px;
        color: #888;
    }

    /* Print-specific Styles */
    @media print {
        #printButton {
            display: none !important;
        }

        /* Header Flexibility for Print */
        .print-header {
            text-align: left;
            padding-bottom: 5px;
            border-bottom: none;
            flex-wrap: wrap;
        }

        /* Adjust Table Layout for Print */
        .table {
            font-size: 12px;
        }

        .table td, .table th {
            padding: 8px;
        }

        /* Adjust Layout for Print */
        .row {
            flex-wrap: wrap;
        }

        .col {
            flex: 100%;
            padding: 0;
            margin-bottom: 10px;
        }
    }

    /* Adjust for Smaller Screens when Printing */
    @media print and (max-width: 768px) {
        .col {
            flex: 100%;
        }
    }
</style>

        </head>
        <body onload="window.print();window.close();">
            <div class="print-header">
                <img src="external/img/ADDU_logo-removebg-preview.png" alt="University Logo 1">
                <div>
                    <h3>Ateneo de Davao University</h3>
                    <h3>College of Computing Studies</h3>
                    <h5>Meeting Details</h5>
                    <p>A summary of meeting details and attendance records.</p>
                </div>
                <img src="external/img/ccs_logo-removebg-preview.png" alt="University Logo 2">
            </div>
            ${content}
        </body>
        </html>
    `);
                                        printWindow.document.close();
                                    }
                                </script>





                                <hr>
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






        <script>
            // Retrieve `class_id` and `semester_id` from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            var classId = urlParams.get('class_id');
            var semesterId = urlParams.get('semester_id');

            console.log("URL:", window.location.href);
            console.log("Class ID:", classId);
            console.log("Semester ID:", semesterId);

            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },
                    events: `fetch_schedule_events.php?semester_id=${semesterId}&class_id=${classId}`,
                    dateClick: function(info) {
                        Swal.fire({
                            title: `Actions for ${info.dateStr}`,
                            text: "Do you want to create a new class or view existing classes?",
                            icon: 'question',
                            showDenyButton: true,
                            confirmButtonText: 'Create New Class',
                            denyButtonText: 'View Classes'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                createClassMeeting(info.dateStr, classId);
                            } else if (result.isDenied) {
                                viewClasses(info.dateStr, classId); // Pass the dynamic classId
                            }
                        });
                    },
                    datesSet: function(dateInfo) {
                        console.log("Fetching events...");
                        fetch(`fetch_schedule_events.php?semester_id=${semesterId}&class_id=${classId}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log("Event data:", data);
                                const meetingDays = data.meetingDays;

                                // Clear previous highlights
                                calendarEl.querySelectorAll('.fc-day').forEach(day => {
                                    day.classList.remove('meeting-day');
                                });

                                meetingDays.forEach(meetingDay => {
                                    const dayCell = calendarEl.querySelector(
                                        `.fc-day[data-date="${meetingDay}"]`);
                                    if (dayCell) {
                                        dayCell.classList.add('meeting-day');
                                    }
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching events:', error);
                            });
                    }
                });

                calendar.render();
            });

            function createClassMeeting(date, classId) {
                $('#createModalDate').text(date); // Set the date in the modal
                $('#createClassForm')[0].reset(); // Reset the form
                $('#createClassModal').modal('show'); // Show the modal

                $('#createClassForm').off('submit').on('submit', function(event) {
                    event.preventDefault(); // Prevent the default form submission

                    const subject = $('#subject').val();
                    const startTime = $('#startTimeCreate').val();
                    const endTime = $('#endTimeCreate').val();
                    const type = $('#type').val();
                    const status = $('#status').val();

                    const meetingData = {
                        date: date,
                        subject: subject,
                        start_time: startTime,
                        end_time: endTime,
                        class_id: classId,
                        type: type,
                        status: status,
                    };

                    fetch('create_class_meeting.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(meetingData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                return Swal.fire('Success!', 'Class meeting created successfully!', 'success'); // Return the SweetAlert promise
                            } else {
                                return Swal.fire('Error!', 'Error creating class meeting: ' + data.message, 'error');
                            }
                        })
                        .then(() => {
                            $('#createClassModal').modal('hide');
                            location.reload(); // Reload the page after closing the modal
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            Swal.fire('Error!', 'Error creating class meeting: ' + error.message, 'error');
                        });
                });
            }


            function viewClasses(date, classId) { // Accept classId as a parameter
                $('#modalDate').text(date); // Set the date in the modal
                $('#classDetails').html('<p>Loading...</p>'); // Show loading message

                // Fetch class details for the selected date and class ID
                fetch(`fetch_class_details.php?date=${date}&class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        let detailsHtml = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="text-center">
                                <th>Date</th>
                                <th>Class Section</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Class Type</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Manage</th>
                            </tr>
                        </thead>
                        <tbody>`;

                        // Check if the 'classes' array is present in the response
                        if (data.classes && data.classes.length > 0) {
                            data.classes.forEach(cls => {
                                let statusMessage;

                                // Determine the status message based on the cls.status
                                switch (cls.status) {
                                    case 'Scheduled':
                                        statusMessage = 'This meeting is scheduled regularly.';
                                        break;
                                    case 'Ongoing':
                                        statusMessage = 'This meeting is currently ongoing.';
                                        break;
                                    case 'Ended':
                                        statusMessage = 'This meeting has ended.';
                                        break;
                                    case 'Cancelled':
                                        statusMessage = 'This meeting has been cancelled.';
                                        break;
                                    default:
                                        statusMessage = 'Status unknown.';
                                }

                                // Build the HTML for each class meeting with Manage buttons
                                detailsHtml += `
                            <tr>
                                <td>${cls.date}</td>
                                <td><?php echo $classData['name'] ?></td>
                                <td>${cls.start_time}</td>
                                <td>${cls.end_time}</td>
                                <td>${cls.type}</td>
                                <td>${cls.status}</td>
                                <td>${statusMessage}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="viewAttendance(<?php echo $classData['id'] ?>, ${cls.id} , <?php echo $_GET['semester_id'] ?>)">Attendance</button>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="editClass(<?php echo $classData['id'] ?>,${cls.id})">Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteMeeting(${cls.id})">Delete</button>
                                    </div>
                                </td>
                            </tr>`;
                            });
                        } else {
                            detailsHtml += `
                    <tr>
                        <td colspan="8" class="text-center">No classes found for this date.</td>
                    </tr>`;
                        }

                        detailsHtml += `
                        </tbody>
                    </table>
                </div>`;

                        // Populate the modal body and show the modal
                        $('#classDetails').html(detailsHtml);
                        $('#classModal').modal('show');
                    })
                    .catch(error => {
                        console.error('Error fetching class details:', error);
                        $('#classDetails').html('<p>Error loading class details.</p>');
                        $('#classModal').modal('show'); // Show the modal even if there's an error
                    });
            }

            // Placeholder functions for attendance, edit, and delete actions
            function viewAttendance(classId, classAttendanceId, semesterId) {
                window.location.href = "class_attendance_qr.php?class_id=" + classId + "&classAttendanceId=" + classAttendanceId + "&semesterId=" + semesterId;
            }

            function editClass(classId, clsId) {
                // Close any open modals
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const bootstrapModal = bootstrap.Modal.getInstance(modal);
                    bootstrapModal.hide();
                });

                console.log("cls id", clsId);

                // Show the edit class status modal
                const editClassModal = new bootstrap.Modal(document.getElementById('editClassModal'));
                editClassModal.show();

                // Handle the form submission
                document.getElementById('editClassForm').addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent form default submit behavior

                    // Retrieve form values
                    const meetingId = clsId;
                    const status = document.getElementById('status').value;
                    const startTime = document.getElementById('startTime').value;
                    const endTime = document.getElementById('endTime').value;

                    // Log the data to ensure values are correct
                    console.log("meetingId: ", meetingId);
                    console.log("Status: ", status);
                    console.log("StartTime: ", startTime);
                    console.log("EndTime: ", endTime);

                    // Make the AJAX request
                    fetch('update_class_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                meetingId,
                                status,
                                startTime,
                                endTime
                            })
                        })
                        .then(response => response.json()) // Parse the response to JSON
                        .then(data => {
                            // Check the success status of the response
                            if (data.success) {
                                // Show success alert
                                Swal.fire('Success!', 'Class status updated successfully!', 'success')
                                    .then(() => {
                                        location.reload(); // Reload the page after closing the alert
                                    });
                            } else {
                                // Show error alert if the update failed
                                Swal.fire('Error!', 'Failed to update class status: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            // Log any errors during the fetch operation
                            console.error('Error updating class status:', error);
                            Swal.fire('Error!', 'An error occurred while updating the class status.', 'error');
                        });
                });

            }

            function deleteMeeting(meetingId) {
                // Show SweetAlert confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send the delete request to the server
                        fetch(`delete_meeting.php?meeting_id=${meetingId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    // Show success message and reload the page after dismissal
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'Your meeting has been deleted.',
                                        icon: 'success',
                                        willClose: () => {
                                            location.reload(); // Reload the page to refresh the meetings list
                                        }
                                    });
                                } else {
                                    // Show error message
                                    Swal.fire('Error!', 'Failed to delete meeting: ' + data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting meeting:', error);
                                Swal.fire('Error!', 'An error occurred while deleting the meeting.', 'error');
                            });
                    }
                });
            }
        </script>



</html>



<?php
include('processes/server/alerts.php');
?>