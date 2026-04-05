<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}

include('processes/server/conn.php');
// include('processes/server/automatic_grader_cron.php');
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


                        <div class="card">
                            <div class="card-body">
                                <h1><b>Teacher Dashboard Overview</b></h1>
                                <p>Welcome back, <?php echo $_SESSION['teacher_name']; ?>! Here's an overview of your
                                    schedule and tasks.</p>

                                <!-- Dashboard Summary -->
                                <div class="row mb-4">
                                    <div class="col">
                                        <div class="card">
                                            <?php
                                            // Ensure teacher is logged in
                                            if (!isset($_SESSION['teacher_id'])) {
                                                echo "Teacher is not logged in.";
                                            }

                                            try {
                                                global $pdo;

                                                // Get the teacher's full name
                                                $teacher_id = $_SESSION['teacher_id'];
                                                $teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                                                $teacher_stmt->execute(['teacher_id' => $teacher_id]);
                                                $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

                                                if (!$teacher) {
                                                    echo "Teacher not found.";
                                                    exit;
                                                }

                                                $teacher_name = $teacher['fullName'];

                                                // Fetch all subject_ids for the teacher, excluding classes with status 'pending'
                                                $classes_stmt = $pdo->prepare("SELECT subject_id, name, status FROM classes WHERE teacher = :teacher_name AND status != 'pending'");
                                                $classes_stmt->execute(['teacher_name' => $teacher_name]);
                                                $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                // If no classes are assigned or they are all 'pending'
                                                // If no classes are assigned or they are all 'pending'
                                                if (!$classes) {
                                                    echo '<div class="card-body">';
                                                    echo '<h5 class="card-title">Today’s Classes for</h5>';
                                                    echo '<p>No active classes are assigned for today.</p>';  // Classes are either not assigned or pending
                                                    echo '</div>';
                                                } else {
                                                    $subject_ids = array_column($classes, 'subject_id');
                                                    if (!empty($subject_ids)) {
                                                        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
                                                        $schedules_stmt = $pdo->prepare("
                        SELECT 
                            ss.subject_id, 
                            ss.meeting_days, 
                            ss.start_time, 
                            ss.end_time, 
                            s.name AS subject_name, 
							s.course as course,
							s.year_level as year_level
                        FROM 
                            subjects_schedules ss
                        JOIN 
                            subjects s 
                        ON 
                            ss.subject_id = s.id
                        WHERE 
                            ss.subject_id IN ($placeholders)
                    ");
                                                        $schedules_stmt->execute($subject_ids);
                                                        $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        // Filter schedules for today's classes
                                                        $today = date('l'); // Get the current day of the week (e.g., "Monday")
                                                        $todays_classes = array_filter($schedules, function ($schedule) use ($today) {
                                                            return stripos($schedule['meeting_days'], $today) !== false;
                                                        });

                                                        // Display today's classes

                                                        echo '<div class="card-body">';
                                                        echo '<h5 class="card-title">Today’s Classes for ' . $today . ' </h5>';

                                                        if (!empty($todays_classes)) {
                                                            echo '<ul>';
                                                            foreach ($todays_classes as $class) {
                                                                echo '<li><b>'
                                                                    . htmlspecialchars($class['subject_name']) . ' </b>@ ' // Use the subject_name
                                                                    . htmlspecialchars(date("g:i A", strtotime($class['start_time'])))
                                                                    . ' - '
                                                                    . htmlspecialchars(date("g:i A", strtotime($class['end_time'])))
                                                                    .  ' - <b>(' .
                                                                    htmlspecialchars($class['course']) . ' - ' .  htmlspecialchars($class['year_level'])
                                                                    .  ')</b>' .
                                                                    '</li>';
                                                            }
                                                            echo '</ul>';
                                                        } else {
                                                            // No active classes for today
                                                            echo '<p>No classes are scheduled for today.</p>';
                                                        }

                                                        echo '</div>';
                                                    }
                                                }
                                            } catch (PDOException $e) {
                                                error_log("Error fetching today's classes: " . $e->getMessage());
                                                echo "An error occurred while fetching today's classes.";
                                            }
                                            ?>
                                        </div>

                                    </div>
                                    <div class="col">
                                        <div class="card">
                                            <?php

                                            function getAbsenceBadge($absences)
                                            {
                                                if ($absences == 2) {
                                                    return '<span class="badge bg-warning text-dark">2 Absences</span>';
                                                }
                                                if ($absences == 3) {
                                                    return '<span class="badge bg-orange text-white">3 Absences</span>';
                                                }
                                                if ($absences > 3) {
                                                    return '<span class="badge bg-danger text-white">3+ Absences</span>';
                                                }
                                                return ''; // No badge
                                            }

                                            // Ensure teacher is logged in
                                            if (!isset($_SESSION['teacher_id'])) {
                                                echo "Teacher is not logged in.";
                                                exit;
                                            }

                                            try {
                                                // Get teacher name
                                                $teacher_id = $_SESSION['teacher_id'];
                                                $teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                                                $teacher_stmt->execute(['teacher_id' => $teacher_id]);
                                                $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

                                                if (!$teacher) {
                                                    echo "Teacher not found.";
                                                    exit;
                                                }

                                                $teacher_name = $teacher['fullName'];

                                                // Fetch classes handled by teacher
                                                $classes_stmt = $pdo->prepare("
      SELECT 
    id, 
    CONCAT(name, ' - ', subject) AS class_name, 
    status 
FROM classes 
WHERE teacher = :teacher_name AND status != 'pending';
    ");
                                                $classes_stmt->execute(['teacher_name' => $teacher_name]);
                                                $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                $classes_handling = count($classes);

                                                echo '<div class="card-body">';
                                                echo '<h5 class="card-title">Attendance Summary</h5>';

                                                if ($classes_handling === 0) {
                                                    echo '<p>You haven\'t been assigned to any active classes yet!</p>';
                                                    echo '</div>';
                                                    exit;
                                                }

                                                // Display list of classes handled
                                                echo '<p><b>Classes Handling (' . $classes_handling . '):</b></p>';
                                                echo '<ul>';
                                                foreach ($classes as $class) {
                                                    echo '<li>' . htmlspecialchars($class['class_name']) . '</li>';
                                                }
                                                echo '</ul>';

                                                // Initialize attendance totals
                                                $total_classes = 0;
                                                $total_present = 0;

                                                // Loop through classes to calculate attendance
                                                foreach ($classes as $class) {
                                                    $class_id = $class['id'];

                                                    $meetings_stmt = $pdo->prepare("SELECT id FROM classes_meetings WHERE class_id = :class_id");
                                                    $meetings_stmt->execute(['class_id' => $class_id]);
                                                    $meetings = $meetings_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                    foreach ($meetings as $meeting) {
                                                        $meeting_id = $meeting['id'];

                                                        $attendance_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE meeting_id = :meeting_id");
                                                        $attendance_stmt->execute(['meeting_id' => $meeting_id]);
                                                        $total_attendance = $attendance_stmt->fetchColumn();

                                                        $present_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE meeting_id = :meeting_id AND status = 'present'");
                                                        $present_stmt->execute(['meeting_id' => $meeting_id]);
                                                        $total_present_entries = $present_stmt->fetchColumn();

                                                        $total_classes += $total_attendance;
                                                        $total_present += $total_present_entries;
                                                    }
                                                }

                                                if ($total_classes > 0) {
                                                    $average_attendance_rate = ($total_present / $total_classes) * 100;
                                                    echo '<p><b>Average Attendance Rate:</b> ' . number_format($average_attendance_rate, 2) . '%</p>';
                                                } else {
                                                    echo '<p>No attendance records found.</p>';
                                                }

                                                // Fetch students with excessive absences (<75%) or 3+ absences
                                                $excessive_absences = [];

                                                foreach ($classes as $class) {
                                                    $class_id = $class['id'];

                                                    // Get enrolled students + full names
                                                    $students_stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.fullName 
        FROM students_enrollments se
        INNER JOIN students s ON se.student_id = s.id
        WHERE se.class_id = :class_id
    ");
                                                    $students_stmt->execute(['class_id' => $class_id]);
                                                    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                    // Total meetings for this class
                                                    $meetings_stmt = $pdo->prepare("
        SELECT id FROM classes_meetings WHERE class_id = :class_id
    ");
                                                    $meetings_stmt->execute(['class_id' => $class_id]);
                                                    $total_meetings = $meetings_stmt->rowCount();

                                                    if ($total_meetings === 0) continue;

                                                    foreach ($students as $student) {

                                                        $student_id = $student['student_id'];

                                                        // Total presents for this student
                                                        $present_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM attendance 
            WHERE meeting_id IN (
                SELECT id FROM classes_meetings WHERE class_id = :class_id
            )
            AND student_id = :student_id
            AND status = 'present'
        ");
                                                        $present_stmt->execute(['class_id' => $class_id, 'student_id' => $student_id]);
                                                        $total_present = $present_stmt->fetchColumn();

                                                        // Compute absences
                                                        $total_absences = $total_meetings - $total_present;

                                                        $badge = getAbsenceBadge($total_absences);

                                                        // Attendance %
                                                        $attendance_rate = ($total_present / $total_meetings) * 100;

                                                        // Flag if below 75% OR has 3+ absences
                                                        if ($attendance_rate < 75 || $total_absences >= 3) {
                                                            $excessive_absences[] = [
                                                                'student_name' => $student['fullName'],
                                                                'class_name' => $class['class_name'],
                                                                'attendance_rate' => number_format($attendance_rate, 2),
                                                                'present_count' => $total_present,
                                                                'absence_count' => $total_absences,
                                                                'badge'        => $badge
                                                            ];
                                                        }
                                                    }
                                                }
                                                echo '</div>';
                                            } catch (PDOException $e) {
                                                error_log("Error fetching attendance summary: " . $e->getMessage());
                                                echo $e;
                                            }
                                            ?>
                                            <!-- Button to trigger modal -->
                                            <button type="button" class="btn btn-danger mx-auto"
                                                style="width: 50%; margin-bottom: 20px;"
                                                data-bs-toggle="modal" data-bs-target="#excessiveAbsencesModal">
                                                View Students with Absences
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="excessiveAbsencesModal" tabindex="-1" aria-labelledby="excessiveAbsencesModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header text-white">
                                                            <h5 class="modal-title" id="excessiveAbsencesModalLabel">Students with Excessive Absences</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php if (count($excessive_absences) === 0): ?>
                                                                <p>No students with excessive absences.</p>
                                                            <?php else: ?>
                                                                <table class="table table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Student Name</th>
                                                                            <th>Class</th>
                                                                            <th>Present</th>
                                                                            <th>Absences</th>
                                                                            <th>Attendance Rate (%)</th>
                                                                        </tr>
                                                                    </thead>

                                                                    <tbody>
                                                                        <?php foreach ($excessive_absences as $student): ?>
                                                                            <tr>
                                                                                <td><?= htmlspecialchars($student['student_name']) ?></td>
                                                                                <td><?= htmlspecialchars($student['class_name']) ?></td>
                                                                                <td><?= $student['present_count'] ?></td>
                                                                                <td class="<?= $student['absence_count'] >= 3 ? 'text-danger fw-bold' : '' ?>">
                                                                                    <?= $student['absence_count'] ?>
                                                                                    <?= $student['badge'] ?>
                                                                                </td>
                                                                                <td><?= $student['attendance_rate'] ?></td>
                                                                            </tr>

                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>


                                </div>
                                <div class="col">
                                    <div class="card">
                                        <?php
                                        // Ensure teacher is logged in
                                        if (!isset($_SESSION['teacher_id'])) {
                                            echo "Teacher is not logged in.";
                                            exit;
                                        }

                                        try {
                                            // Get the teacher's full name
                                            $teacher_id = $_SESSION['teacher_id'];
                                            $teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                                            $teacher_stmt->execute(['teacher_id' => $teacher_id]);
                                            $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

                                            if (!$teacher) {
                                                echo "Teacher not found.";
                                                exit;
                                            }

                                            $teacher_name = $teacher['fullName'];

                                            // Get the classes handled by the teacher
                                            $classes_stmt = $pdo->prepare("SELECT id FROM classes WHERE teacher = :teacher_name");
                                            $classes_stmt->execute(['teacher_name' => $teacher_name]);
                                            $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

                                            // Check if the teacher has classes assigned
                                            if (count($classes) === 0) {
                                                echo '<div class="card-body">';
                                                echo '<h5 class="card-title">Pending Activities</h5>';
                                                echo '<p>You haven\'t been assigned to any classes, yet!</p>';
                                                echo '</div>';
                                            }

                                            // Initialize variables for the pending activities
                                            $pending_activities = 0;

                                            // For each class, get the activities and check the submissions
                                            foreach ($classes as $class) {
                                                $class_id = $class['id'];

                                                // Fetch activities for the class
                                                $activities_stmt = $pdo->prepare("SELECT id FROM activities WHERE class_id = :class_id");
                                                $activities_stmt->execute(['class_id' => $class_id]);
                                                $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                if ($activities) {
                                                    // For each activity, check the number of graded submissions
                                                    foreach ($activities as $activity) {
                                                        $activity_id = $activity['id'];

                                                        // Count submissions with status 'graded' for this activity
                                                        $graded_stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_submissions WHERE activity_id = :activity_id AND status = 'graded'");
                                                        $graded_stmt->execute(['activity_id' => $activity_id]);
                                                        $graded_count = $graded_stmt->fetchColumn();

                                                        // If there are ungraded submissions, increment pending activities
                                                        if ($graded_count == 0) {
                                                            $pending_activities++; // This activity is pending
                                                        }
                                                    }
                                                }
                                            }

                                            // Display the Pending Activities
                                            if ($pending_activities > 0) {
                                                echo '<div class="card-body">';
                                                echo '<h5 class="card-title">Pending Activities</h5>';
                                                echo '<p>Activities to Grade: ' . htmlspecialchars($pending_activities) . '</p>';
                                                echo '</div>';
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Error fetching pending activities: " . $e->getMessage());
                                            echo "An error occurred while fetching the pending activities.";
                                        }
                                        ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Class Management Section -->

                    <?php
                    // Assuming you have already established the $pdo connection

                    // Step 1: Get the teacher's full name
                    $teacher_id = $_SESSION['teacher_id']; // Get the teacher ID from the session
                    $teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                    $teacher_stmt->execute(['teacher_id' => $teacher_id]);
                    $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

                    $teacher_name = $teacher['fullName'];

                    // Step 2: Get classes for the teacher
                    $class_stmt = $pdo->prepare("
    SELECT 
        c.id AS id,
        c.classCode AS class_code,
        c.name AS class_section,
        c.subject AS subject_name,
        c.code AS subject_code,
        c.subject_Id as subject_id,
        c.semester AS semester,
        c.status as class_status,
        c.type  as class_type,
        c.is_archived AS class_archived,
        s.is_archived AS subject_archived
    FROM classes c
    JOIN subjects s ON c.subject_id = s.id
    WHERE c.teacher = :teacherName
");
                    $class_stmt->execute(['teacherName' => $teacher_name]);
                    $classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Attach semester ID to each class
                    foreach ($classes as &$class) {
                        $semester_name = $class['semester'];

                        // Query to get semester ID based on semester name
                        $semester_stmt = $pdo->prepare("SELECT id FROM semester WHERE name = :semester_name");
                        $semester_stmt->execute(['semester_name' => $semester_name]);
                        $semester = $semester_stmt->fetch(PDO::FETCH_ASSOC);

                        // Store the semester ID in the class array
                        $class['semester_id'] = $semester ? $semester['id'] : null;
                    }

                    // Unset reference to prevent unintended side effects
                    unset($class);
                    ?>

                    <div class="card">
                        <div class="card-body">
                            <h2><b>Subject Management</b></h2>
                            <div class="table-responsive">
                                <table id="classesTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Class Code</th>
                                            <th>Class Section</th>
                                            <th>Subject Name</th>
                                            <th>Subject Type</th>
                                            <th>Subject Code</th>
                                            <th>Semester</th>
                                            <th>Archive Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($class['class_code']) ?></td>
                                                <td><?= htmlspecialchars($class['class_section']) ?></td>
                                                <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($class['class_type']) ?></td>
                                                <td><?= htmlspecialchars($class['subject_code']) ?></td>

                                                <td><?= htmlspecialchars($class['semester']) ?></td>
                                                <td>
                                                    <button class="btn <?= $class['class_archived'] == 1 ? 'btn-danger' : 'btn-success' ?>">
                                                        <?= $class['class_archived'] == 1 ? 'Archived' : 'Not Archived' ?>
                                                    </button>
                                                </td>


                                                <td>
                                                    <?php if ($class['class_archived'] == 1): ?>
                                                        <a
                                                            href="class_attendance_archived.php?class_id=<?= urlencode($class['id']) ?>&semester_id=<?= urlencode($class['semester_id']) ?>">
                                                            <button class="btn btn-primary btn-sm">
                                                                <i class="bi bi-calendar-check"></i> Classes
                                                            </button>
                                                        </a>
                                                        <a
                                                            href="class_grades.php?class_id=<?= urlencode($class['id']) ?>&semester_id=<?= urlencode($class['semester_id']) ?>&subject_id=<?= urlencode($class['subject_id']) ?>">
                                                            <button class="btn btn-success btn-sm">
                                                                <i class="bi bi-bar-chart-line"></i> Grades
                                                            </button>
                                                        </a>
                                                        <a
                                                            href="subject_activities_archived.php?url=people&class_id=<?= urlencode($class['id']) ?>&subject_id=<?= urlencode($class['subject_id']) ?>">
                                                            <button class="btn btn-warning btn-sm">
                                                                <i class="bi bi-pencil-square"></i> Manage
                                                            </button>
                                                        </a>
                                                    <?php elseif ($class['class_status'] == 'pending'): ?>
                                                        <span class="text-danger">Class is pending on approval</span>
                                                    <?php elseif ($class['class_status'] == 'disapproved'): ?>
                                                        <span class="text-danger">Class has been disapproved</span>
                                                    <?php else: ?>
                                                        <a
                                                            href="class_attendance.php?class_id=<?= urlencode($class['id']) ?>&semester_id=<?= urlencode($class['semester_id']) ?>">
                                                            <button class="btn btn-primary btn-sm">
                                                                <i class="bi bi-calendar-check"></i> Classes
                                                            </button>
                                                        </a>
                                                        <a
                                                            href="class_grades.php?class_id=<?= urlencode($class['id']) ?>&semester_id=<?= urlencode($class['semester_id']) ?>&subject_id=<?= urlencode($class['subject_id']) ?>">
                                                            <button class="btn btn-success btn-sm">
                                                                <i class="bi bi-bar-chart-line"></i> Grades
                                                            </button>
                                                        </a>
                                                        <a
                                                            href="subject_activities.php?url=people&class_id=<?= urlencode($class['id']) ?>&subject_id=<?= urlencode($class['subject_id']) ?>">
                                                            <button class="btn btn-warning btn-sm">
                                                                <i class="bi bi-pencil-square"></i> Manage
                                                            </button>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <?php
                // Query the current semester information
                $sql = "SELECT s.name, s.start_date, s.end_date 
        FROM current_semester cs
        JOIN semester s ON cs.semester = s.name 
        LIMIT 1";
                $stmt = $pdo->query($sql);
                $currentSemester = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>

                <!-- Conditionally display the "Add a Class" button -->
                <?php if ($currentSemester): ?>
                    <div class="d-flex align-items-center justify-content-center">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                            <i class="bi bi-plus-circle-fill"></i> Add a Class to Teach
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        No current semester found. Please contact the administrator.
                    </div>
                <?php endif; ?>


        </div>
    </div>

    <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Create a Class</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClassForm" action="processes/teachers/classes/add.php" method="POST">
                        <div class="mb-3">
                            <label for="class" class="form-label bold">Select Class:</label>
                            <select class="form-select" name="class" id="classSelect" required>
                                <option selected disabled>Select a class</option>
                                <optgroup label="Department of Information Technology">
                                    <option value="BSIT-1A">BSIT - 1A</option>
                                    <option value="BSIT-1B">BSIT - 1B</option>
                                    <option value="BSIT-2A">BSIT - 2A</option>
                                    <option value="BSIT-2B">BSIT - 2B</option>
                                    <option value="BSIT-3A">BSIT - 3A</option>
                                    <option value="BSIT-3B">BSIT - 3B</option>
                                    <option value="BSIT-4A">BSIT - 4A</option>
                                    <option value="BSIT-4B">BSIT - 4B</option>
                                </optgroup>
                                <optgroup label="Department of Computer Science">
                                    <option value="BSCS-1A">BSCS - 1A</option>
                                    <option value="BSCS-1B">BSCS - 1B</option>
                                    <option value="BSCS-2A">BSCS - 2A</option>
                                    <option value="BSCS-2B">BSCS - 2B</option>
                                    <option value="BSCS-3A">BSCS - 3A</option>
                                    <option value="BSCS-3B">BSCS - 3B</option>
                                    <option value="BSCS-4A">BSCS - 4A</option>
                                    <option value="BSCS-4B">BSCS - 4B</option>
                                </optgroup>
                            </select>
                        </div>



                        <div class="mb-3">
                            <label for="adviser" class="form-label bold">Assigned Adviser:</label>
                            <input type="text" class="form-control" id="assignedAdviser" name="assignedAdviser"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label for="subjectName" class="form-label bold">Select Subject Name: </label>
                            <?php
                            try {
                                // Query to fetch subjects that are not already in the classes table
                                $stmt = $pdo->prepare("
            SELECT s.id, s.name, s.semester, s.type
            FROM subjects s
            LEFT JOIN classes c ON s.id = c.subject_id
            WHERE c.subject_id IS NULL
            ORDER BY s.name
        ");
                                $stmt->execute();
                                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                echo "Error: " . $e->getMessage();
                            }
                            ?>

                            <select class="form-select" name="subjectId">
                                <?php if (!empty($subjects)): ?>
                                    <option value="" selected>Select a subject</option>
                                    <?php foreach ($subjects as $row): ?>
                                        <option value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <?php echo htmlspecialchars($row['name']) . ' [' . htmlspecialchars($row['type']) . '] ' . '(' . htmlspecialchars($row['semester']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" selected>No available subjects</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="semester" class="form-label bold">Select Semester:</label>
                            <select class="form-select" name="semester" required>
                                <?php
                                $sql = "SELECT name FROM semester ORDER BY name ";
                                $stmt = $pdo->query($sql);
                                $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php if (!empty($semesters)): ?>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?php echo htmlspecialchars($semester['name']); ?>">
                                            <?php echo htmlspecialchars($semester['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No semesters available</option>
                                <?php endif; ?>

                            </select>
                        </div>



                        <div class="mb-3">
                            <label for="classDesc" class="form-label bold">Class Description:</label>
                            <textarea class="form-control" id="classDesc" name="classDesc" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Assignment Management Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentModalLabel">Manage Assignments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="processes/assignments/manage.php" method="POST">
                        <div class="mb-3">
                            <label for="classSelect" class="form-label">Select Class:</label>
                            <select class="form-select" name="class" required>
                                <option value="BSIT-1A">BSIT-1A - Introduction to IT</option>
                                <!-- More options dynamically loaded -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentTitle" class="form-label">Assignment Title:</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description:</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="dueDate" class="form-label">Due Date:</label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Assignment</button>
                    </form>
                </div>
            </div>
        </div>
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
            console.log(newTime);
            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
    </script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#classesTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true
            });
        });
    </script>

    <script>
        document.getElementById('classSelect').addEventListener('change', function() {
            const selectedClass = this.value;
            fetch('getAdviser.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'class=' + encodeURIComponent(selectedClass)
                })
                .then(response => response.json())
                .then(data => {
                    const adviserField = document.getElementById('assignedAdviser');
                    if (data.fullName) {
                        adviserField.value = data.fullName;
                    } else {
                        adviserField.value = 'No adviser assigned';
                    }
                })
                .catch(error => console.error('Error fetching adviser:', error));
        });
    </script>

</html>

<?php
include('processes/server/alerts.php');
?>