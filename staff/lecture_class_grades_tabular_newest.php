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
    <title>ADDU - CCS | Student Management System</title>
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
        border: 1px solid black
    }

    .btn-csms {
        background-color: #709775;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #709775;
    }

    .grey-bg {
        background-color: grey;
        color: white;
    }

    .small-logo {
        height: 125px;
        width: 125px;
    }
</style>

<body>
    <div class="wrapper">
        <div class="main">
            <main class="content">
                <div class="d-flex align-items-center">
                    <a href="class_grades.php?class_id=<?php echo $_GET['id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>"
                        class="d-flex align-items-center mb-3">
                        <i class="bi bi-arrow-left-circle" style="font-size: 1.5rem; margin-right: 5px;"></i>
                        <p class="m-0">Back</p>
                    </a>
                    <div class="ms-auto" aria-hidden="true">

                    </div>
                </div>






                <div id="printTable">
                    <div id="page-content-wrapper">
                        <div class="card bg-light border-0 shadow-sm"
                            style="background-color: white !important; padding: 5px;">

                            <div class="card-body">
                                <div
                                    class="container text-center d-flex align-items-center justify-content-center my-3">
                                    <div class="row w-100 d-flex align-items-center justify-content-center">
                                        <div class="col-2 d-flex justify-content-center">
                                            <img src="../external/img/ADDU_Logo-removebg-preview.png"
                                                class="img-fluid small-logo">
                                        </div>
                                        <div class="col-8 text-center">
                                            <!-- <h5 class="bold mb-1">Ateneo de Davao University</h5> -->
                                            <h5 class="mb-1">College of Computing Studies</h5>
                                            <h5>Zamboanga City</h5>
                                        </div>
                                        <div class="col-2 d-flex justify-content-center">
                                            <img src="../external/img/ccs_logo-removebg-preview.png"
                                                class="img-fluid small-logo">
                                        </div>
                                    </div>
                                </div>

                                <hr>



                                <?php

                                // Get the class_id from the URL parameter
                                $class_id = $_GET['id'];

                                // Prepare the SQL statement
                                $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id");

                                // Bind the class_id parameter
                                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);

                                // Execute the query
                                $stmt->execute();

                                // Fetch the result
                                $class = $stmt->fetch();

                                if ($class) {
                                    // Extract the class details from the fetched data
                                    $adviser = $class['teacher'];
                                    $subject = $class['subject'];
                                    $year_section = $class['code']; // Assuming 'code' is for year/section
                                    $semester = $class['semester'];
                                    $school_year = date('Y', strtotime($class['datetime_added']));
                                    $type = $class['type'];
                                } else {
                                    // If no class is found, display a message
                                    echo "Class not found.";
                                    exit;
                                }

                                ?>



                                <!-- HTML Structure -->
                                <div class="row">
                                    <div class="col">
                                        <h3><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                Adviser:</b> <span><?php echo htmlspecialchars($adviser); ?></span></h3>
                                        <h3><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                            <span><?php echo htmlspecialchars($subject); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-building" style="margin-right: 5px;"></i> Year and
                                                Section:</b> <span><?php echo htmlspecialchars($year_section); ?></span>
                                        </h3>
                                    </div>
                                    <div class="col">
                                        <h3><b><i class="bi bi-calendar3" style="margin-right: 5px;"></i> Semester:</b>
                                            <span><?php echo htmlspecialchars($semester); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-range" style="margin-right: 5px;"></i> School
                                                Year:</b> <span><?php echo htmlspecialchars($school_year); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-check" style="margin-right: 5px;"></i> Class
                                                Type
                                            </b> <span><?php echo htmlspecialchars($type); ?></span>
                                        </h3>
                                    </div>
                                </div>
                                <hr>





                                <style>
                                    table.dataTable {
                                        font-size: 12px;
                                        border: 1px solid black;
                                        padding: 8px;
                                        text-align: center;
                                    }



                                    .table tr,
                                    td {
                                        border: 1px solid black !important;
                                        text-align: center;
                                    }

                                    .grader {
                                        border: none !important;
                                        text-align: center;
                                    }

                                    input,
                                    select {
                                        width: 100%;
                                        box-sizing: border-box;
                                    }

                                    input[type="number"]::-webkit-outer-spin-button,
                                    input[type="number"]::-webkit-inner-spin-button {
                                        -webkit-appearance: none;
                                        margin: 0;
                                    }

                                    .grader:focus {
                                        border: none;
                                        outline: none;
                                    }
                                </style>

                                <?php
                                // Fetch activities for the given class_id
                                $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ?");
                                $activitiesStmt->execute([$class_id]);
                                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Organize activities by type
                                $quizzes = $assignments = $activitiesAll = $exams = [];
                                $activityIds = []; // Store activity IDs for filtering submissions

                                foreach ($activities as $activity) {
                                    $activityIds[] = $activity['id']; // Collect activity IDs for filtering submissions
                                    switch ($activity['type']) {
                                        case 'quiz':
                                            $quizzes[] = $activity;
                                            break;
                                        case 'assignment':
                                            $assignments[] = $activity;
                                            break;
                                        case 'activity':
                                            $activitiesAll[] = $activity;
                                            break;
                                        case 'exam':
                                            $exams[] = $activity;
                                            break;
                                    }
                                }

                                // Fetch student submissions, filtering only by the relevant activity IDs
                                if (!empty($activityIds)) {
                                    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
                                    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($placeholders)");
                                    $submissionsStmt->execute($activityIds);
                                    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $submissions = []; // No activities found, so no submissions exist
                                }
                                $studentScores = [];
                                $studentScores = [];
                                foreach ($submissions as $submission) {
                                    $studentId = $submission['student_id'];
                                    $activityId = $submission['activity_id'];
                                    $score = $submission['score'];
                                    $studentScores[$studentId][$activityId] = $score;
                                }
                                $stmt = $pdo->prepare("SELECT id, date FROM classes_meetings WHERE class_id = :class_id AND status = 'Finished'  ORDER BY date ASC");
                                $stmt->execute(['class_id' => $class_id]);
                                $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $query = "SELECT COUNT(id) AS total_meetings 
FROM classes_meetings 
WHERE class_id = :class_id";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute(['class_id' => $class_id]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $totalMeetings = $result['total_meetings'] ?? 0;
                                // Fetch attendance
                                $attendanceStmt = $pdo->prepare("SELECT student_id, meeting_id, status FROM attendance WHERE class_id = ?");
                                $attendanceStmt->execute([$class_id]);
                                $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
                                $attendanceCounts = [];
                                foreach ($attendanceRecords as $record) {
                                    $studentId = $record['student_id'];
                                    if (!isset($attendanceCounts[$studentId])) {
                                        $attendanceCounts[$studentId] = 0;
                                    }
                                    if ($record['status'] === 'present') {
                                        $attendanceCounts[$studentId]++;
                                    }
                                }
                                $query = "SELECT type, SUM(max_points) AS total_max_points 
FROM activities 
WHERE class_id = :class_id 
GROUP BY type";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute(['class_id' => $class_id]);
                                $totals = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $totalPoints = [
                                    'quiz' => 0,
                                    'assignment' => 0,
                                    'activity' => 0,
                                    'exam' => 0
                                ];
                                foreach ($totals as $row) {
                                    $totalPoints[$row['type']] = $row['total_max_points'];
                                }
                                $query = "SELECT 
        SUM(CASE WHEN type = 'quiz' THEN 1 ELSE 0 END) AS total_quizzes,
        SUM(CASE WHEN type = 'assignment' THEN 1 ELSE 0 END) AS total_assignments,
        SUM(CASE WHEN type = 'activity' THEN 1 ELSE 0 END) AS total_activities,
        SUM(CASE WHEN type = 'exam' THEN 1 ELSE 0 END) AS total_exams
      FROM activities
      WHERE class_id = :class_id";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute(['class_id' => $class_id]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $totalQuizzes = $result['total_quizzes'] + 1 ?? 1;
                                $totalTripleA = (isset($result['total_assignments']) ? $result['total_assignments']  + 2 : 0) +
                                    (isset($result['total_activities'])   ? $result['total_activities'] + 2 : 0);
                                $totalTripleA = $totalTripleA ?: 3; // Use 9 if the result is 0 (or a falsy value)          
                                $totalExams = $result['total_exams'] + 1 ?? 1;


                                $total = $totalQuizzes + $totalTripleA + $totalExams + 100;



                                ?>


                                <style>
                                    /* Existing styles */
                                    .table-smaller-text {
                                        font-size: 0.85rem;
                                    }

                                    .table-smaller-text th,
                                    .table-smaller-text td {
                                        padding: 4px;
                                    }

                                    /* Adjusted input styles */
                                    .score-input,
                                    .total-score {
                                        padding: 2px 4px;
                                        /* Minimal padding: 2px vertical, 4px horizontal for breathing room */
                                        font-size: 0.8rem;
                                        width: auto;
                                        /* Base width to fit small numbers */
                                        max-width: 70px;
                                        /* Cap width to prevent over-expansion */
                                        min-width: 40px;
                                        /* Ensure minimum width for single digits */
                                        box-sizing: border-box;
                                        /* Include padding in width */
                                        text-align: center;
                                        /* Center numbers for better fit */
                                    }

                                    .attendance-select {
                                        padding: 2px 4px;
                                        /* Minimal padding: 2px vertical, 4px horizontal for breathing room */
                                        font-size: 0.8rem;
                                        width: 85px;
                                        /* Base width to fit small numbers */
                                        box-sizing: border-box;
                                        /* Include padding in width */
                                        text-align: center;
                                        /* Center numbers for better fit */
                                    }


                                    .grade-select {
                                        padding: 2px 4px;
                                        /* Minimal padding: 2px vertical, 4px horizontal for breathing room */
                                        font-size: 0.8rem;
                                        width: 55px;
                                        /* Base width to fit small numbers */
                                        box-sizing: border-box;
                                        /* Include padding in width */
                                        text-align: center;
                                        /* Center numbers for better fit */
                                    }
                                </style>

                                <table class="table table-smaller-text">
                                    <!-- Your existing thead remains largely unchanged -->
                                    <thead>
                                        <tr>
                                            <td class="text-center" colspan="<?php echo 2 + count($quizzes) + 1 + count($assignments) + 1 + count($activitiesAll) + 1 + count($meetings) + 1 + count($exams) + 1 + 3; ?>">
                                                <b>Worksheets</b>
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2"><b>Criteria</b></td>
                                            <td colspan="<?php echo $totalQuizzes ?>" class="text-center">
                                                <b>Quizzes (<input class="grader" id="quizzesPercentage" type="number"
                                                        style="width:11%;" value="<?php echo $quizzesPercentage ?>"
                                                        oninput="updateRubric('quizzes', this.value)">%)</b>
                                            </td>
                                            <td colspan="<?php echo $totalTripleA ?>">
                                                <b>Assignments / Attendance / Activities (<input class="grader" id="assignmentsPercentage"
                                                        type="number" style="width:10%;"
                                                        value="<?php echo $assignments_activities_attendancePercentage ?>"
                                                        oninput="updateRubric('assignments', this.value)">%)</b>
                                            </td>
                                            <td colspan="<?php echo $totalExams ?>">
                                                <b>Exams (<input id="examPercentage" class="grader" type="number" style="width:11%;"
                                                        value="<?php echo $majorExamPercentage ?>"
                                                        oninput="updateRubric('exam', this.value)">%)</b>
                                            </td>
                                            <td><b>Midterms</b></td>
                                            <td><b>Finals</b></td>
                                            <td><b>GPA</b></td>
                                        </tr>

                                        <!-- Your header row remains unchanged -->
                                        <tr>
                                            <td scope="col"><b>Student ID</b></td>
                                            <td scope="col"><b>Student Name</b></td>
                                            <?php if (!empty($quizzes)): ?>
                                                <?php foreach ($quizzes as $index => $quiz): ?>
                                                    <td scope="col"><b>Q<?= $index + 1 ?> <br> (<?= $quiz['max_points'] ?>)</b></td>
                                                <?php endforeach; ?>
                                                <td scope="col"><b>TOTAL <br> (<?= $totalPoints['quiz'] ?? 0 ?>)</b></td>
                                            <?php else: ?>
                                                <td colspan="<?php echo $totalQuizzes ?>" class="text-center">No quizzes yet</td>
                                            <?php endif; ?>

                                            <?php if (!empty($assignments)): ?>
                                                <?php foreach ($assignments as $index => $assignment): ?>
                                                    <td class="border-1"><b>ASS<?= $index + 1 ?> <br> (<?= $assignment['max_points'] ?>)</b></td>
                                                <?php endforeach; ?>
                                                <td scope="col"><b>TOTAL <br> (<?= $totalPoints['assignment'] ?? 0 ?>)</b></td>
                                            <?php else: ?>
                                                <td class="text-center">No assignments yet</td>
                                            <?php endif; ?>

                                            <?php if (!empty($activitiesAll)): ?>
                                                <?php foreach ($activitiesAll as $index => $activity): ?>
                                                    <td class="border-1"><b>ACT<?= $index + 1 ?> <br> (<?= $activity['max_points'] ?>)</b></td>
                                                <?php endforeach; ?>
                                                <td scope="col"><b>TOTAL <br> (<?= $totalPoints['activity'] ?? 0 ?>)</b></td>
                                            <?php else: ?>
                                                <td class="text-center">No activities yet</td>
                                            <?php endif; ?>

                                            <?php
                                            $countMeetings = 0;
                                            if (!empty($meetings)): ?>
                                                <?php foreach ($meetings as $meeting):
                                                    $countMeetings++;
                                                    $formattedDate = date("m/d/Y", strtotime($meeting['date']));
                                                ?>
                                                    <td scope="col"><b>ATT<?= $countMeetings ?> <br> (<?= $formattedDate ?>)</b></td>
                                                <?php endforeach; ?>
                                                <td scope="col"><b>TOTAL <br> (<?= $countMeetings ?>)</b></td>
                                            <?php else: ?>
                                                <td colspan="<?php echo $countMeetings ?>" class="text-center">No attendance records yet</td>
                                            <?php endif; ?>

                                            <?php if (!empty($exams)): ?>
                                                <?php foreach ($exams as $exam): ?>
                                                    <td class="border-1"><b><?= ucfirst($exam['term']) ?> <br> (<?= $exam['max_points'] ?>)</b></td>
                                                <?php endforeach; ?>
                                                <td class="border-1"><b>TOTAL <br> (<?= $totalPoints['exam'] ?? 0 ?>)</b></td>
                                            <?php else: ?>
                                                <td colspan="<?php echo count($exams) ?>" class="text-center">No exams yet</td>
                                            <?php endif; ?>

                                            <td><b>1 - 5</b></td>
                                            <td><b>1 - 5</b></td>
                                            <td><b>1 - 5</b></td>
                                        </tr>

                                        <?php if (!empty($students)): ?>
                                            <?php foreach ($students as $student): ?>
                                                <?php
                                                if (!empty($activityIds)) {
                                                    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
                                                    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($placeholders)");
                                                    $submissionsStmt->execute($activityIds);
                                                    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
                                                } else {
                                                    $submissions = [];
                                                }

                                                $studentScores = [];
                                                foreach ($submissions as $submission) {
                                                    $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
                                                }

                                                $gradeStmt = $pdo->prepare("
                    SELECT midterm_grade, final_grade, overall_grade 
                    FROM student_grades 
                    WHERE student_id = :student_id AND class_id = :class_id
                ");
                                                $gradeStmt->execute([
                                                    'student_id' => $student['id'],
                                                    'class_id' => $classId
                                                ]);
                                                $grades = $gradeStmt->fetch(PDO::FETCH_ASSOC);
                                                $midtermGrade = $grades['midterm_grade'] ?? 'INC';
                                                $finalGrade = $grades['final_grade'] ?? 'INC';
                                                $overallGrade = $grades['overall_grade'] ?? 'INC';
                                                ?>

                                                <tr>
                                                    <td><b><?php echo htmlspecialchars($student['id']); ?></b></td>
                                                    <td><b><?php echo htmlspecialchars($student['fullName']); ?></b></td>

                                                    <?php if (!empty($quizzes)): ?>
                                                        <?php
                                                        $quizTotal = 0;
                                                        foreach ($quizzes as $quiz) {
                                                            $studentId = $student['id'];
                                                            $quizId = $quiz['id'];
                                                            $score = $studentScores[$student['id']][$quiz['id']] ?? 0;
                                                            $quizTotal += $score;
                                                        ?>
                                                            <td class='border-1'>
                                                                <input type='number' name='scores[<?= $studentId ?>][<?= $quizId ?>]'
                                                                    value='<?= $score ?>' class='form-control score-input' min='0'
                                                                    data-student-id='<?= $studentId ?>' data-activity-id='<?= $quizId ?>'>
                                                            </td>
                                                        <?php } ?>
                                                        <td class='border-1'>
                                                            <input type='number' name='total[<?= $studentId ?>]'
                                                                value='<?= $quizTotal ?>' class='form-control total-score' readonly>
                                                        </td>
                                                    <?php else: ?>
                                                        <td colspan="<?= count($quizzes) ?>" class="text-center">No quizzes yet</td>
                                                    <?php endif; ?>

                                                    <?php if (!empty($assignments)): ?>
                                                        <?php
                                                        $assignmentsTotal = 0;
                                                        foreach ($assignments as $assignment) {
                                                            $studentId = $student['id'];
                                                            $assignmentId = $assignment['id'];
                                                            $score = $studentScores[$student['id']][$assignment['id']] ?? 0;
                                                            $assignmentsTotal += $score;
                                                        ?>
                                                            <td class='border-1'>
                                                                <input type='number' name='scores[<?= $studentId ?>][<?= $assignmentId ?>]'
                                                                    value='<?= $score ?>' class='form-control score-input' min='0'
                                                                    data-student-id='<?= $studentId ?>' data-activity-id='<?= $assignmentId ?>'>
                                                            </td>
                                                        <?php } ?>
                                                        <td class='border-1'>
                                                            <input type='number' name='total[<?= $studentId ?>]'
                                                                value='<?= $assignmentsTotal ?>' class='form-control total-score' readonly>
                                                        </td>
                                                    <?php else: ?>
                                                        <td colspan="<?= count($assignments) ?>" class="text-center">No assignments yet</td>
                                                    <?php endif; ?>

                                                    <?php if (!empty($activitiesAll)): ?>
                                                        <?php
                                                        $activitiesTotal = 0;
                                                        foreach ($activitiesAll as $activity) {
                                                            $studentId = $student['id'];
                                                            $activityId = $activity['id'];
                                                            $score = $studentScores[$student['id']][$activity['id']] ?? 0;
                                                            $activitiesTotal += $score;
                                                        ?>
                                                            <td class='border-1'>
                                                                <input type='number' name='scores[<?= $studentId ?>][<?= $activityId ?>]'
                                                                    value='<?= $score ?>' class='form-control score-input' min='0'
                                                                    data-student-id='<?= $studentId ?>' data-activity-id='<?= $activityId ?>'>
                                                            </td>
                                                        <?php } ?>
                                                        <td class='border-1'>
                                                            <input type='number' name='total[<?= $studentId ?>]'
                                                                value='<?= $activitiesTotal ?>' class='form-control total-score' readonly>
                                                        </td>
                                                    <?php else: ?>
                                                        <td colspan="<?= count($activitiesAll) ?>" class="text-center">No activities yet</td>
                                                    <?php endif; ?>

                                                    <?php
                                                    $meetings = [];
                                                    $totalPresents = 0;
                                                    try {
                                                        $stmt = $pdo->prepare("
                            SELECT DISTINCT cm.id, cm.date, cm.class_id, a.student_id, a.status as attendance_status
                            FROM classes_meetings cm
                            LEFT JOIN attendance a ON cm.id = a.meeting_id 
                                AND a.student_id = :student_id
                            WHERE cm.status = 'Finished' 
                            AND cm.class_id = :class_id
                            ORDER BY cm.date ASC
                        ");
                                                        $stmt->execute([
                                                            'student_id' => $student['id'],
                                                            'class_id' => $classId
                                                        ]);
                                                        $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        foreach ($meetings as $meeting) {
                                                            if ($meeting['attendance_status'] === 'present') {
                                                                $totalPresents++;
                                                            }
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo "Error fetching meetings for student {$student['id']}: " . $e->getMessage();
                                                    }
                                                    ?>
                                                    <?php if (!empty($meetings)): ?>

                                                        <?php
                                                        $meetingCount = 0;
                                                        $processedMeetings = [];
                                                        foreach ($meetings as $meeting):

                                                            if (in_array($meeting['id'], $processedMeetings)) continue;
                                                            $processedMeetings[] = $meeting['id'];
                                                            $meetingCount++;
                                                        ?>
                                                            <td scope="col text-center">
                                                                <select name="attendance[<?= $student['id'] ?>][<?= $meeting['id'] ?>]"
                                                                    class="form-control attendance-select"
                                                                    data-student-id='<?= $student['id'] ?>' data-meeting-id='<?= $meeting['id'] ?>'>
                                                                    <option value="present" <?= ($meeting['attendance_status'] === 'present') ? 'selected' : '' ?>>Present</option>
                                                                    <option value="absent" <?= ($meeting['attendance_status'] === 'absent') ? 'selected' : '' ?>>Absent</option>
                                                                </select>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td scope="col">
                                                            <input type="number" name="total_presents[<?= $student['id'] ?>]"
                                                                value="<?= $totalPresents ?>" class="form-control total-presents total-score" readonly>
                                                        </td>
                                                    <?php else: ?>
                                                        <td colspan="1" class="text-center">No attendance records found for this record.</td>
                                                    <?php endif; ?>

                                                    <?php if (!empty($exams)): ?>
                                                        <?php
                                                        $examsTotal = 0;
                                                        foreach ($exams as $exam) {
                                                            $studentId = $student['id'];
                                                            $examId = $exam['id'];
                                                            $score = $studentScores[$student['id']][$exam['id']] ?? 0;
                                                            $examsTotal += $score;
                                                        ?>
                                                            <td class='border-1'>
                                                                <input type='number' name='scores[<?= $studentId ?>][<?= $examId ?>]'
                                                                    value='<?= $score ?>' class='form-control score-input' min='0'
                                                                    data-student-id='<?= $studentId ?>' data-activity-id='<?= $examId ?>'>
                                                            </td>
                                                        <?php } ?>
                                                        <td class='border-1'>
                                                            <input type='number' name='total[<?= $studentId ?>]'
                                                                value='<?= $examsTotal ?>' class='form-control total-score' readonly>
                                                        </td>
                                                    <?php else: ?>
                                                        <td colspan="<?= count($exams) ?>" class="text-center">No exams yet</td>
                                                    <?php endif; ?>

                                                    <td>
                                                        <select name="midterms[<?= $student['id'] ?>]" class="form-control grade-select"
                                                            data-student-id='<?= $student['id'] ?>' data-type="midterm">
                                                            <option value="1.00" <?= $midtermGrade === '1.00' ? 'selected' : '' ?>>1.00</option>
                                                            <option value="1.25" <?= $midtermGrade === '1.25' ? 'selected' : '' ?>>1.25</option>
                                                            <option value="1.75" <?= $midtermGrade === '1.75' ? 'selected' : '' ?>>1.75</option>
                                                            <option value="2.00" <?= $midtermGrade === '2.00' ? 'selected' : '' ?>>2.00</option>
                                                            <option value="2.25" <?= $midtermGrade === '2.25' ? 'selected' : '' ?>>2.25</option>
                                                            <option value="2.50" <?= $midtermGrade === '2.50' ? 'selected' : '' ?>>2.50</option>
                                                            <option value="2.75" <?= $midtermGrade === '2.75' ? 'selected' : '' ?>>2.75</option>
                                                            <option value="3.00" <?= $midtermGrade === '3.00' ? 'selected' : '' ?>>3.00</option>
                                                            <option value="5.00" <?= $midtermGrade === '5.00' ? 'selected' : '' ?>>5.00</option>
                                                            <option value="INC" <?= $midtermGrade === 'INC' ? 'selected' : '' ?>>INC</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="finals[<?= $student['id'] ?>]" class="form-control grade-select"
                                                            data-student-id='<?= $student['id'] ?>' data-type="final">
                                                            <option value="1.00" <?= $finalGrade === '1.00' ? 'selected' : '' ?>>1.00</option>
                                                            <option value="1.25" <?= $finalGrade === '1.25' ? 'selected' : '' ?>>1.25</option>
                                                            <option value="1.75" <?= $finalGrade === '1.75' ? 'selected' : '' ?>>1.75</option>
                                                            <option value="2.00" <?= $finalGrade === '2.00' ? 'selected' : '' ?>>2.00</option>
                                                            <option value="2.25" <?= $finalGrade === '2.25' ? 'selected' : '' ?>>2.25</option>
                                                            <option value="2.50" <?= $finalGrade === '2.50' ? 'selected' : '' ?>>2.50</option>
                                                            <option value="2.75" <?= $finalGrade === '2.75' ? 'selected' : '' ?>>2.75</option>
                                                            <option value="3.00" <?= $finalGrade === '3.00' ? 'selected' : '' ?>>3.00</option>
                                                            <option value="5.00" <?= $finalGrade === '5.00' ? 'selected' : '' ?>>5.00</option>
                                                            <option value="INC" <?= $finalGrade === 'INC' ? 'selected' : '' ?>>INC</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="gpa[<?= $student['id'] ?>]" class="form-control grade-select"
                                                            data-student-id='<?= $student['id'] ?>' data-type="overall">
                                                            <option value="1.00" <?= $overallGrade === '1.00' ? 'selected' : '' ?>>1.00</option>
                                                            <option value="1.25" <?= $overallGrade === '1.25' ? 'selected' : '' ?>>1.25</option>
                                                            <option value="1.75" <?= $overallGrade === '1.75' ? 'selected' : '' ?>>1.75</option>
                                                            <option value="2.00" <?= $overallGrade === '2.00' ? 'selected' : '' ?>>2.00</option>
                                                            <option value="2.25" <?= $overallGrade === '2.25' ? 'selected' : '' ?>>2.25</option>
                                                            <option value="2.50" <?= $overallGrade === '2.50' ? 'selected' : '' ?>>2.50</option>
                                                            <option value="2.75" <?= $overallGrade === '2.75' ? 'selected' : '' ?>>2.75</option>
                                                            <option value="3.00" <?= $overallGrade === '3.00' ? 'selected' : '' ?>>3.00</option>
                                                            <option value="5.00" <?= $overallGrade === '5.00' ? 'selected' : '' ?>>5.00</option>
                                                            <option value="INC" <?= $overallGrade === 'INC' ? 'selected' : '' ?>>INC</option>
                                                            <option value="AW" <?= $overallGrade === 'AW' ? 'selected' : '' ?>>AW</option>
                                                            <option value="UW" <?= $overallGrade === 'UW' ? 'selected' : '' ?>>UW</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="21" class="text-center">No students enrolled.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>


                            </div>
                            <button onclick="printDiv('printTable')" class="btn btn-primary mb-3">
                                <i class="bi bi-printer"></i> Print
                            </button>
            </main>


        </div>
        <script>
            // Update Rubric
            function updateRubric(type, value) {
                let classId = new URLSearchParams(window.location.search).get('id');
                fetch('update_rubric_lecture.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `class_id=${classId}&type=${type}&value=${value}`
                    })
                    .then(response => response.text())
                    .then(data => console.log(data))
                    .catch(error => console.error('Error:', error));
            }

            // Update Scores
            document.querySelectorAll('.score-input').forEach(input => {
                input.addEventListener('change', function() {
                    const studentId = this.dataset.studentId;
                    const activityId = this.dataset.activityId;
                    const score = this.value;
                    const row = this.closest('tr');

                    fetch('processes/teachers/grading/update_scoring_lecture.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `student_id=${studentId}&activity_id=${activityId}&score=${score}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.value = data.score;
                                updateCategoryTotal(row, this);
                                updateGrades(studentId, row);
                            } else {
                                console.error('Error updating score:', data.message);
                            }
                        })
                        .catch(error => console.error('AJAX error:', error));
                });
            });

            // Update Attendance
            document.querySelectorAll('.attendance-select').forEach(select => {
                select.addEventListener('change', function() {
                    const studentId = this.dataset.studentId;
                    const meetingId = this.dataset.meetingId;
                    const status = this.value;
                    const row = this.closest('tr');
                    let presentCount = 0;

                    row.querySelectorAll('.attendance-select').forEach(attSelect => {
                        if (attSelect.value === 'present') presentCount++;
                    });
                    row.querySelector('.total-presents').value = presentCount;

                    fetch('processes/teachers/grading/update_attendance_lecture.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `student_id=${studentId}&meeting_id=${meetingId}&status=${status}&class_id=${<?php echo $classId; ?>}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateGrades(studentId, row);
                            } else {
                                console.error('Error updating attendance:', data.message);
                            }
                        })
                        .catch(error => console.error('AJAX error:', error));
                });
            });

            // Update Grades with Locking Logic
            document.querySelectorAll('.grade-select').forEach(select => {
                // Initial check on page load
                checkAndLockInputs(select);

                select.addEventListener('change', function() {
                    const studentId = this.dataset.studentId;
                    const type = this.dataset.type;
                    const grade = this.value;
                    const row = this.closest('tr');

                    // Send manual grade update to server
                    fetch('processes/teachers/grading/update_grading_lecture.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `student_id=${studentId}&type=${type}&grade=${grade}&class_id=${<?php echo $classId; ?>}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.value = data.grade;
                                checkAndLockInputs(this); // Lock/unlock inputs based on grade
                            } else {
                                console.error('Error updating grade:', data.message);
                            }
                        })
                        .catch(error => console.error('AJAX error:', error));
                });
            });

            // Helper function to update category total
            function updateCategoryTotal(row, changedInput) {
                const allInputs = Array.from(row.querySelectorAll('.score-input'));
                const changedIndex = allInputs.indexOf(changedInput);
                let categoryInputs = [];
                let totalInput;

                if (changedIndex < <?php echo count($quizzes); ?>) {
                    categoryInputs = allInputs.slice(0, <?php echo count($quizzes); ?>);
                    totalInput = row.querySelectorAll('.total-score')[0];
                } else if (changedIndex < <?php echo count($quizzes) + count($assignments); ?>) {
                    categoryInputs = allInputs.slice(<?php echo count($quizzes); ?>,
                        <?php echo count($quizzes) + count($assignments); ?>);
                    totalInput = row.querySelectorAll('.total-score')[1];
                } else if (changedIndex < <?php echo count($quizzes) + count($assignments) + count($activitiesAll); ?>) {
                    categoryInputs = allInputs.slice(<?php echo count($quizzes) + count($assignments); ?>,
                        <?php echo count($quizzes) + count($activitiesAll); ?>);
                    totalInput = row.querySelectorAll('.total-score')[2];
                } else {
                    categoryInputs = allInputs.slice(
                        <?php echo count($quizzes) + count($assignments) + count($activitiesAll); ?>);
                    totalInput = row.querySelectorAll('.total-score')[3];
                }

                let total = 0;
                categoryInputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                if (totalInput) {
                    totalInput.value = total;
                }
            }

            // Helper function to update grades (auto-calculation)
            function updateGrades(studentId, row) {
                const types = ['midterm', 'final', 'overall'];
                types.forEach(type => {
                    fetch('processes/teachers/grading/update_grading_lecture.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `student_id=${studentId}&type=${type}&grade=auto&class_id=${<?php echo $classId; ?>}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const select = row.querySelector(`.grade-select[data-type="${type}"]`);
                                if (select) {
                                    select.value = data.grade;
                                    checkAndLockInputs(select); // Check locking after auto-update
                                }
                            } else {
                                console.error(`Error updating ${type} grade:`, data.message);
                            }
                        })
                        .catch(error => console.error('AJAX error:', error));
                });
            }

            // Helper function to lock/unlock inputs based on grade
            function checkAndLockInputs(select) {
                const row = select.closest('tr');
                const grade = select.value;
                const scoreInputs = row.querySelectorAll('.score-input');
                const attendanceSelects = row.querySelectorAll('.attendance-select');

                if (grade === 'UW' || grade === 'AW') {
                    scoreInputs.forEach(input => input.disabled = true);
                    attendanceSelects.forEach(select => select.disabled = true);
                } else {
                    scoreInputs.forEach(input => input.disabled = false);
                    attendanceSelects.forEach(select => select.disabled = false);
                }
            }
        </script>

        <script src="js/app.js"></script>

        <script>
            function printDiv(divId) {
                var content = document.getElementById(divId).innerHTML;
                var printWindow = window.open('', '_blank');
                printWindow.document.open();
                printWindow.document.write(`
        <html>
            <head>
                <title>Print Content</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">
                <style>
                    body { font-family: Arial, sans-serif; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .grey-bg { background-color: #f8f9fa; }
                    table, th, td { border: 1px solid black; border-collapse: collapse; padding: 1px; }
                    .grade { background: none; border: none; cursor: pointer; color: inherit; }
                    @media print {
                        @page { size: landscape; }
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                ${content}
            </body>
        </html>
    `);
                printWindow.document.close();
            }
        </script>
        <?php
        include('processes/server/modals.php');
        ?>




</html>

<?php
include('processes/server/alerts.php');
?>