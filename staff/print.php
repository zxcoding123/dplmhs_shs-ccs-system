<?php
include('processes/server/conn.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta and Links -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdNU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/app.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }

            .container {
                max-width: 100%;
            }

            .header,
            .table-container {
                width: 100%;
            }

            button {
                display: none;
            }

            .btn-print {
                display: none;
            }

            .logo {
                padding-top: 10px;
                width: 90px !important;
                height: 90px !important;
            }
            #backer{
                display:none !important;
            }
        }

        .date-header {
            font-weight: bold;
            font-size: 18px;
            margin-top: 20px;
        }

        .c {
            padding: 20px;
        }

        .logo {
            width: 125px;
            height: 125px;
        }

        h3 {
            font-size: 16px;
        }
    </style>
</head>
<?php
$class_id = $_GET['class_id'] ?? null;
$semester = $_GET['semester'] ?? null;
$classData = null;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT id, name, subject, teacher, semester FROM classes WHERE id = :class_id LIMIT 1");
    $stmt->execute(['class_id' => $class_id]);
    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<body>

    <div class="container c" style="border: 1px solid black">
        <div class="d-flex align-items-center" id="backer">
            <a href="class_attendance_general.php?class_id=<?php echo $_GET['class_id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>"
                class="d-flex align-items-center mb-3">
                <i class="bi bi-arrow-left-circle" style="font-size: 1.5rem; margin-right: 5px;"></i>
                <p class="m-0">Back</p>
            </a>
            <div class="ms-auto" aria-hidden="true">
                <button class="btn btn-primary mt-3 btn-print" onclick="window.print();">Print Page</button>
            </div>
        </div>

        <div class="header text-center">
            <div class="row">
                <div class="col-sm-3">
                    <img src="external/img/ADNU_Logo.png" class="img-fluid logo">

                </div>
                <div class="col">
                    <h3 class="mt-10 bold">Repblic of the Philippines</h3>
                    <h3 class="mt-10 bold">Ateneo de Naga University</h3>
                    <h3 class="mt-10 bold">College of Computing Studies</h3>
                    <p>Ateneo de Naga University, Ateneo Avenue, Naga City, 4400 Philippines</p>
                </div>
                <div class="col-sm-3">
                    <img src="external/img/ADNU_CCS_Logo.png" class="img-fluid logo">
                </div>
            </div>

            <br>
            <h3 class="mt-10 bold">Attendance Records</h3>
            <p>The following are the attendance records of the students under the class of
                <b>'<?php echo $classData['name'] ?>'</b> who are students of
                the subject: <b><?php echo $classData['subject'] ?></b> under the subject teacher of:
                <b><?php echo $classData['teacher'] ?></b>
            </p>

        </div>

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
    </div>
</body>


</html>