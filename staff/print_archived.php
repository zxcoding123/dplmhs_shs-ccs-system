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
            <a href="class_attendance_archived.php?class_id=<?php echo $_GET['class_id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>"
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

        <div class="table-container">
            <?php
            // Fetch all attendance records grouped by date
            $class_id = $_GET['class_id'] ?? null;

            if ($class_id) {
                $stmt = $pdo->prepare("
                    SELECT a.date, a.student_id, s.fullName AS name, a.status, s.gender, s.course, s.year_level
                    FROM attendance a
                    INNER JOIN students s ON a.student_id = s.student_id
                    WHERE a.class_id = :class_id
                    ORDER BY a.date, s.gender, s.fullName
                ");
                $stmt->execute(['class_id' => $class_id]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Group attendance by date
                $attendanceByDate = [];
                foreach ($records as $record) {
                    $attendanceByDate[$record['date']][] = $record;
                }

                // Function to render table for gender
                function renderTable($attendees, $gender)
                {
                    $rows = '';
                    $index = 1;
                    foreach ($attendees as $attendee) {
                        if (strtolower($attendee['gender']) === $gender) {
                            $statusClass = $attendee['status'] === 'Present' ? 'btn-success' : 'btn-danger';
                            $rows .= "
                                <tr>
                                    <td>" . $index++ . "</td>
                                    <td>" . htmlspecialchars($attendee['student_id']) . "</td>
                                    <td>" . htmlspecialchars($attendee['name']) . "</td>
                                                  <td>" . htmlspecialchars($attendee['course']) . ' - ' . htmlspecialchars($attendee['year_level']) . "</td>
                                    <td><button class='btn $statusClass'>" . htmlspecialchars(ucfirst($attendee['status'])) . "</button></td>
                                </tr>
                            ";
                        }
                    }
                    if ($rows === '') {
                        return "<tr><td colspan='4'>No records for this gender</td></tr>";
                    }
                    return $rows;
                }
                ?>

                <?php
                // Loop through attendance dates
                foreach ($attendanceByDate as $date => $records) {
                    ?>
                    <div class="date-header">
                        <?php
                        $formatted_date = date('F j, Y', strtotime($date));
                        ?>
                        Date: <?php echo htmlspecialchars($formatted_date); ?>
                    </div>

                    <h5 class="mt-3">Male Attendees:</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Course and Year</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo renderTable($records, 'male'); ?>
                        </tbody>
                    </table>

                    <h5 class="mt-3">Female Attendees:</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Course and Year</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo renderTable($records, 'female'); ?>
                        </tbody>
                    </table>
                    <?php
                    echo "<hr>";
                }

            }
            ?>
        </div>
    </div>
</body>


</html>