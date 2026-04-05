<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit();
}

include('processes/server/conn.php');

// Get teacher's advisory classes
$teacher_name = $_SESSION['full_name'];
$advisory_stmt = $pdo->prepare("
    SELECT id, class_advising 
    FROM staff_advising 
    WHERE fullName = :teacher_name
");
$advisory_stmt->execute(['teacher_name' => $teacher_name]);
$advisory_classes = $advisory_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build student counts by course and year level
$student_counts = [];
$yearMap = [
    '1' => '1st Year',
    '2' => '2nd Year',
    '3' => '3rd Year',
    '4' => '4th Year',
];

foreach ($advisory_classes as $cls) {
    $class_id = $cls['class_advising'];
    $sa_id = $cls['id'];

    if (preg_match('/^(?<course>[A-Z]+)-(?<year>[1-4])/', $class_id, $m)) {
        $course = $m['course'];
        $year = $m['year'];
        $label = $yearMap[$year];

        if (!isset($student_counts[$course][$label])) {
            $student_counts[$course][$label] = 0;
        }

        $cnt_stmt = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM students_advising sa
            JOIN students s ON s.id = sa.student_id
            WHERE sa.staff_advising_id = :sa_id
        ");
        $cnt_stmt->execute(['sa_id' => $sa_id]);
        $row = $cnt_stmt->fetch(PDO::FETCH_ASSOC);
        $student_counts[$course][$label] += (int)$row['cnt'];
    }
}

// Get current class_id from URL if available
$current_class_id = $_GET['class_id'] ?? ($advisory_classes[0]['class_advising'] ?? '');
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        .schedule-card {
            border-left: 4px solid #3c49a0;
            margin-bottom: 15px;
        }

        .class-selector {
            cursor: pointer;
            transition: all 0.3s;
            color: #3c49a0 !important;
        }

        .class-selector:hover {
            background-color: #f8f9fa;
        }

        .class-selector.active {
            background-color: #3c49a0;
            color: white !important;
        }

        .time-col {
            width: 120px;
        }

        .day-header {
            background-color: #3c49a0;
            color: white;
        }

        .student-count-badge {
            font-size: 0.75rem;
            margin-left: 5px;
        }

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
                <div class="container-fluid">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h1 class="h3 mb-0"><b>Class Schedules</b></h1>
                         

                                <a href="class_management.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                            <p class="text-secondary">View and manage class schedules for your advisory classes</p>


                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-2">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold ">My Advisory Classes</h6>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($advisory_classes as $class):
                                        $class_id = $class['class_advising'];
                                        if (preg_match('/^(?<course>[A-Z]+)-(?<year>[1-4])/', $class_id, $m)) {
                                            $course = $m['course'];
                                            $year = $m['year'];
                                            $label = $yearMap[$year];
                                            $count = $student_counts[$course][$label] ?? 0;
                                        }
                                    ?>
                                        <a href="class_schedule.php?class_id=<?= urlencode($class['class_advising']) ?>"
                                            class="list-group-item list-group-item-action class-selector <?= ($class['class_advising'] === $current_class_id) ? 'active' : '' ?> " style="color: white; font-weight:bold">
                                            <?= htmlspecialchars($class['class_advising']) ?>
                                            <span class="badge bg-secondary student-count-badge">
                                                <?= $count ?> students
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <?php if ($current_class_id): ?>
                                <?php
                                // Get schedule for selected class
                                $schedStmt = $pdo->prepare("
            SELECT
                sch.meeting_days AS day,
                sch.start_time,
                sch.end_time,
                sub.name AS subject_name,
                sa.fullName AS teacher_name
            FROM classes c
            JOIN subjects sub ON sub.id = c.subject_id
            JOIN subjects_schedules sch ON sch.subject_id = sub.id
            JOIN staff_accounts sa ON sa.fullName = c.teacher
            WHERE c.name = :class_name
            ORDER BY
                FIELD(sch.meeting_days,
                    'Monday','Tuesday','Wednesday',
                    'Thursday','Friday','Saturday','Sunday'),
                sch.start_time
        ");
                                $schedStmt->execute(['class_name' => $current_class_id]);
                                $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Group by day
                                $schedule_by_day = [
                                    'Monday'    => [],
                                    'Tuesday'   => [],
                                    'Wednesday' => [],
                                    'Thursday'  => [],
                                    'Friday'    => [],
                                    'Saturday'  => [],
                                    'Sunday'    => []
                                ];

                                foreach ($schedules as $schedule) {

                                    $schedule_by_day[$schedule['day']][] = $schedule;
                                }

                                ?>

                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="m-0 font-weight-bold">
                                            <b> Schedule for <?= htmlspecialchars($current_class_id) ?></b>
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="time-col">Time</th>
                                                        <th>Monday</th>
                                                        <th>Tuesday</th>
                                                        <th>Wednesday</th>
                                                        <th>Thursday</th>
                                                        <th>Friday</th>
                                                        <th>Saturday</th>
                                                        <th>Sunday</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php for ($hour = 7; $hour <= 19; $hour++): ?>
                                                        <tr>
                                                            <td class="time-col">
                                                                <?= sprintf("%02d:00", $hour) ?> - <?= sprintf("%02d:00", $hour + 1) ?>
                                                            </td>
                                                            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                                                <td style="font-size: small;">
                                                                    <?php foreach ($schedule_by_day[$day] as $schedule):
                                                                        $start = (int)substr($schedule['start_time'], 0, 2);
                                                                        $end = (int)substr($schedule['end_time'], 0, 2);
                                                                        if ($hour >= $start && $hour < $end): ?>
                                                                            <div class="schedule-card p-2 mb-2">
                                                                                <strong><i class="bi bi-book-fill me-1"></i> <?= htmlspecialchars($schedule['subject_name']) ?></strong><br>
                                                                                <i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($schedule['teacher_name']) ?><br>
                                                                                <i class="bi bi-clock-fill me-1"></i> <?= htmlspecialchars($schedule['start_time']) ?> - <?= htmlspecialchars($schedule['end_time']) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                        </div>
                    </div>

                    <!-- Upcoming Classes Table -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="m-0 font-weight-bold"><b>Upcoming Classes</b></h3>
                            <p class="text-muted mb-0">Here is the schedule for the upcoming classes of your students.</p>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="upcomingClassesTable" class="table table-bordered table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Day</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $upcomingStmt = $pdo->prepare("
                                               
 SELECT
    c.name                AS class_name,
    sch.meeting_days      AS day,
    cm.date,                          -- actual calendar date of the meeting
    sch.start_time,                   -- planned start
    sch.end_time,                     -- planned end
    sub.name              AS subject_name,
    sa.fullName           AS teacher_name,
    cm.status,                        -- e.g. “scheduled”, “cancelled”, …
    cm.type                           -- lecture, lab, etc. (if you use it)
FROM       classes               c
JOIN       subjects              sub  ON sub.id          = c.subject_id
JOIN       subjects_schedules    sch  ON sch.subject_id  = sub.id
JOIN       staff_accounts        sa   ON sa.id           = c.teacher       -- join on ID, not name
JOIN       classes_meetings      cm   ON cm.class_id     = c.id            -- only keep rows that have a real meeting
                                       AND cm.date       = CURRENT_DATE
                                       AND cm.start_time = sch.start_time   -- keep if you store these
                                       AND cm.end_time   = sch.end_time
WHERE  c.name = :class_name
  AND  sch.meeting_days = DAYNAME(CURRENT_DATE)          -- Monday, Tuesday, etc.
  AND  cm.status = 'scheduled'                           -- ignore cancelled/postponed
  AND  cm.start_time > CURRENT_TIME()                    -- only meetings still ahead today
ORDER BY cm.start_time
LIMIT 3;

                                                    ");
                                        $upcomingStmt->execute(['class_name' => $current_class_id]);
                                        $upcoming_classes = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($upcoming_classes as $class): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($class['class_name']) ?></td>
                                                <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($class['teacher_name']) ?></td>
                                                <td><?= htmlspecialchars($class['day']) ?></td>
                                                <td><?= htmlspecialchars($class['start_time']) ?> - <?= htmlspecialchars($class['end_time']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please select an advisory class to view its schedule.
                    </div>
                <?php endif; ?>
                </div>
        </div>
    </div>
    </main>
    </div>
    </div>





    <script src="js/app.js"></script>
    <?php include('processes/server/modals.php'); ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#upcomingClassesTable').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false
            });

            // Handle delete schedule
            $(document).on('click', '.delete-schedule', function() {
                const scheduleId = $(this).data('id');

                Swal.fire({
                    title: 'Confirm Deletion',
                    text: 'Are you sure you want to delete this schedule?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'processes/teachers/schedules/delete_schedule.php',
                            method: 'POST',
                            data: {
                                id: scheduleId
                            },
                            success: function(response) {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    Swal.fire('Deleted!', result.message, 'success')
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire('Error', result.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete schedule', 'error');
                            }
                        });
                    }
                });
            });

            // Handle add schedule form submission
            $('#addScheduleForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire('Success!', result.message, 'success')
                                .then(() => {
                                    $('#addScheduleModal').modal('hide');
                                    location.reload();
                                });
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to add schedule', 'error');
                    }
                });
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