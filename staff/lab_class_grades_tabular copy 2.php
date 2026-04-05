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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#gradingModal">
                            <i class="bi bi-pen-fill"></i> Update Grading
                        </button>

                    </div>
                </div>

                <!-- Grading Modal -->
                <?php
                include('processes/server/conn.php');
                $class_id = $_GET['id']; // Get the class_id from the URL or form
                $stmt = $pdo->prepare("SELECT major_exam, exercises, assignments_activities_attendance FROM laboratory_rubrics WHERE class_id = :class_id");
                $stmt->execute([':class_id' => $class_id]);
                $gradingData = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <!-- This part remains the same as your modal structure -->
                <div class="modal fade" id="gradingModal" tabindex="-1" aria-labelledby="gradingModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="gradingModalLabel">Update Tabular Grading</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Grading Form -->
                                <form id="gradingForm">
                                    <div class="mb-3">
                                        <label for="exercises" class="form-label">Exercises</label>
                                        <input type="number" class="form-control" id="exercises"
                                            placeholder="Enter percentage"
                                            value="<?php echo $gradingData['exercises'] ?? ''; ?>" required>
                                    </div>


                                    <div class="mb-3">
                                        <label for="activities" class="form-label">Assignments /
                                            Attendance</label>
                                        <input type="number" class="form-control" id="assignments_activities_attendance"
                                            placeholder="Enter percentage"
                                            value="<?php echo $gradingData['assignments_activities_attendance'] ?? ''; ?>"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="majorExam" class="form-label">Major Exams</label>
                                        <input type="number" class="form-control" id="majorExam"
                                            placeholder="Enter percentage"
                                            value="<?php echo $gradingData['major_exam'] ?? ''; ?>" required>
                                    </div>

                                    <!-- Hidden input to pass class_id -->
                                    <input type="hidden" id="classId" value="<?php echo $class_id; ?>" />
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                                        class="bi bi-x-circle-fill"></i> Close</button>
                                <button type="button" class="btn btn-warning"
                                    onclick="deleteRubric(<?php echo $class_id; ?>)"><i class="bi bi-repeat"></i>
                                    Reset</button>
                                <button type="button" class="btn btn-primary" onclick="submitGrading()"><i
                                        class="bi bi-save-fill"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    function deleteRubric(classId) {
                        // Confirm with the user before proceeding
                        if (confirm('Are you sure you want to delete this rubric? This action cannot be undone.')) {
                            // Send AJAX request to delete the rubric record
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'delete_rubric_lab.php', true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                            // Send class_id to backend for deletion
                            xhr.send('class_id=' + classId);

                            // Handle server response
                            xhr.onload = function() {
                                if (xhr.status === 200) {
                                    alert('Rubric successfully deleted.');
                                    // Optionally reload or update the page here to reflect changes
                                    window.location.reload(); // Reload the page
                                } else {
                                    alert('Error deleting rubric. Please try again.');
                                }
                            };
                        }
                    }
                </script>
                <?php
                $classId = $_GET['id'];
                $stmt = $pdo->prepare("SELECT * FROM laboratory_rubrics WHERE class_id = :class_id");
                $stmt->execute(['class_id' => $classId]);

                // Fetch the rubrics from the database
                $rubrics = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if rubrics are available
                if ($rubrics) {

                    // Calculate percentage based on the retrieved rubrics
                    $majorExamPercentage = isset($rubrics['major_exam']) ? floatval($rubrics['major_exam']) : 0;
                    $exercisesPercentage = isset($rubrics['exercises']) ? floatval($rubrics['exercises']) : 0;
                    $assignments_activities_attendancePercentage = isset($rubrics['assignments_activities_attendance']) ? floatval($rubrics['assignments_activities_attendance']) : 0;

                    // Total percentage (optional, you can decide how to use this or store)
                    $totalPercentage = $majorExamPercentage + $exercisesPercentage + $assignments_activities_attendancePercentage;
                } else {

                    $majorExamPercentage = 40;
                    $exercisesPercentage = 50;
                    $assignments_activities_attendancePercentage = 10;
                }


                global $TruemajorExamPercentage;
                global $TrueexercisesPercentage;
                global $Trueassignments_activities_attendancePercentage;


                $TruemajorExamPercentage = $majorExamPercentage / 100;  // 0.4
                $TrueexercisesPercentage = $exercisesPercentage / 100;      // 0.3
                $Trueassignments_activities_attendancePercentage = $assignments_activities_attendancePercentage / 100; // 0.3

                $totalPercentage = $TruemajorExamPercentage + $TrueexercisesPercentage + $Trueassignments_activities_attendancePercentage;




                ?>
                <script>
                    // Function to submit the grading changes to the server
                    function submitGrading() {
                        const majorExam = document.getElementById("majorExam").value;
                        const exercises = document.getElementById("exercises").value;
                        const assignments_activities_attendance = document.getElementById(
                            "assignments_activities_attendance").value;
                        const classId = document.getElementById("classId").value;

                        // Validate input
                        if (majorExam && exercises && assignments_activities_attendance) {
                            // Call AJAX to update the grading schema in the server
                            fetch('laboratory_update_grading.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        class_id: classId,
                                        major_exam: majorExam,
                                        assignments_activities_attendance: assignments_activities_attendance,
                                        exercises: exercises

                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Grading updated successfully!');
                                        location.reload(); // Reload page after update
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error updating grading:', error);
                                    alert('Error updating grading. Try again!');
                                });
                        } else {
                            alert('All fields are required.');
                        }
                    }
                </script>
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
                                            <h5 class="bold mb-1">Ateneo de Davao University</h5>
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
                                    }

                                    td {
                                        text-align: center;
                                        vertical-align: middle;
                                        border-bottom: 1px solid black;
                                        border: 1px solid black
                                    }
                                </style>


                                <?php
                                $class_id = $_GET['id']; // Example class ID (change as needed)
                                // Step 1: Get all student IDs from student_enrollments for the given class_id
                                $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
                                $stmt->execute([$classId]);
                                $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch student IDs as an array

                                if (!empty($studentIds)) {
                                    // Step 2: Convert IDs to placeholders for SQL IN clause
                                    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

                                    // Step 3: Fetch male students matching those IDs
                                    $stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE gender = 'Male' AND id IN ($placeholders)");
                                    $stmt->execute($studentIds);
                                    $male_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Step 4: Fetch female students matching those IDs
                                    $stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE gender = 'Female' AND id IN ($placeholders)");
                                    $stmt->execute($studentIds);
                                    $female_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    $male_students = [];
                                    $female_students = [];
                                }


                                // Fetch activities for the given class_id
                                $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ?");
                                $activitiesStmt->execute([$class_id]);
                                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Organize activities by type
                                $exercises = $assignments = $activitiesList = $exams = [];
                                $activityIds = []; // Store activity IDs for filtering submissions

                                foreach ($activities as $activity) {
                                    $activityIds[] = $activity['id']; // Collect activity IDs for filtering submissions
                                    switch ($activity['type']) {
                                        case 'exercise':
                                            $exercise[] = $activity;
                                            break;
                                        case 'assignment':
                                            $assignments[] = $activity;
                                            break;
                                        case 'activity':
                                            $activitiesList[] = $activity;
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
                                $query = "SELECT COUNT(id) AS total_meetings 
          FROM classes_meetings 
          WHERE class_id = :class_id AND status = 'Finished'";
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

                                // Initialize total storage
                                $totalPoints = [
                                    'exercise' => 0,
                                    'assignment' => 0,
                                    'activity' => 0,
                                    'exam' => 0
                                ];

                                // Store values
                                foreach ($totals as $row) {
                                    $totalPoints[$row['type']] = $row['total_max_points'];
                                }

                                $query = "SELECT 
                                SUM(CASE WHEN type = 'exercise' THEN 1 ELSE 0 END) AS total_exercise,
                                SUM(CASE WHEN type = 'assignment' THEN 1 ELSE 0 END) AS total_assignments,
                                SUM(CASE WHEN type = 'activity' THEN 1 ELSE 0 END) AS total_activities,
                                SUM(CASE WHEN type = 'exam' THEN 1 ELSE 0 END) AS total_exams
                              FROM activities
                              WHERE class_id = :class_id";

                                $stmt = $pdo->prepare($query);
                                $stmt->execute(['class_id' => $class_id]);
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                                $totalExercises = $result['total_exercise'] ?? 0;
                                $totalTripleA = ($result['total_assignments'] + 1) + ($result['total_activities'] + 1) + 2;
                                $totalExams = $result['total_exams'] ?? 0;


                                //GRADER

                                $classId = $_GET['id']; // Class ID from GET request

                                // Fetch rubrics for grading
                                $stmt = $pdo->prepare("SELECT * FROM laboratory_rubrics WHERE class_id = :class_id");
                                $stmt->execute(['class_id' => $classId]);
                                $rubrics = $stmt->fetch(PDO::FETCH_ASSOC);

                                // Default rubric values (if not found in DB)
                                $majorExamPercentage = $rubrics ? floatval($rubrics['major_exam']) : 40;
                                $exercisePercentage = $rubrics ? floatval($rubrics['exercises']) : 30;
                                $aaaPercentage = $rubrics ? floatval($rubrics['assignments_activities_attendance']) : 30;

                                // Convert percentages to decimal
                                $majorExamWeight = $majorExamPercentage / 100;
                                $exerciseWeight = $exercisePercentage / 100;
                                $aaaWeight = $aaaPercentage / 100;

                                // Fetch students in class
                                $stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE id IN (SELECT student_id FROM student_grades WHERE class_id = :class_id)");
                                $stmt->execute(['class_id' => $classId]);
                                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Fetch activities for the given class_id
                                $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ?");
                                $activitiesStmt->execute([$class_id]);
                                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Organize activities by type
                                $exercises = $assignments = $activitiesList = $exams = [];
                                $activityIds = []; // Store activity IDs for filtering submissions

                                foreach ($activities as $activity) {
                                    $activityIds[] = $activity['id']; // Collect activity IDs for filtering submissions
                                    switch ($activity['type']) {
                                        case 'exercise':
                                            $exercises[] = $activity;
                                            break;
                                        case 'assignment':
                                            $assignments[] = $activity;
                                            break;
                                        case 'activity':
                                            $activitiesList[] = $activity;
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


                                // Organize student scores by student_id and activity_id
                                $studentScores = [];
                                foreach ($submissions as $submission) {
                                    $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
                                }


                                // Fetch all activities for this class
                                $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = :class_id");
                                $activitiesStmt->execute(['class_id' => $classId]);
                                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Categorize activities by type and term
                                $activityTypes = ['midterm' => ['exercise' => [], 'aaa' => [], 'exam' => []], 'final' => ['exercise' => [], 'aaa' => [], 'exam' => []]];
                                foreach ($activities as $activity) {
                                    if ($activity['term'] === 'midterm') {
                                        if ($activity['type'] === 'exercise')
                                            $activityTypes['midterm']['exercise'][] = $activity;
                                        elseif (in_array($activity['type'], ['assignment', 'activity']))
                                            $activityTypes['midterm']['aaa'][] = $activity;
                                        elseif ($activity['type'] === 'exam')
                                            $activityTypes['midterm']['exam'][] = $activity;
                                    } elseif ($activity['term'] === 'final') {
                                        if ($activity['type'] === 'exercise')
                                            $activityTypes['final']['exercise'][] = $activity;
                                        elseif (in_array($activity['type'], ['assignment', 'activity']))
                                            $activityTypes['final']['aaa'][] = $activity;
                                        elseif ($activity['type'] === 'exam')
                                            $activityTypes['final']['exam'][] = $activity;
                                    }
                                }

                                // Fetch attendance records
                                $attendanceStmt = $pdo->prepare("SELECT student_id, COUNT(*) AS attended FROM attendance WHERE class_id = :class_id AND status = 'present' GROUP BY student_id");
                                $attendanceStmt->execute(['class_id' => $classId]);
                                $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Get total class meetings
                                $classMeetingsStmt = $pdo->prepare("SELECT COUNT(*) AS total_meetings FROM classes_meetings WHERE class_id = :class_id");
                                $classMeetingsStmt->execute(['class_id' => $classId]);
                                $totalMeetings = $classMeetingsStmt->fetch(PDO::FETCH_ASSOC)['total_meetings'];

                                // Organize attendance records by student ID
                                $studentAttendance = [];
                                foreach ($attendanceRecords as $record) {
                                    $studentAttendance[$record['student_id']] = $record['attended'];
                                }



                                // Define function to convert grade to numerical rating
                                function getNumericalRating($grade)
                                {
                                    if ($grade >= 99)
                                        return 1.0;
                                    elseif ($grade >= 95)
                                        return 1.25;
                                    elseif ($grade >= 90)
                                        return 1.5;
                                    elseif ($grade >= 85)
                                        return 1.75;
                                    elseif ($grade >= 80)
                                        return 2.0;
                                    elseif ($grade >= 75)
                                        return 2.25;
                                    elseif ($grade >= 70)
                                        return 2.5;
                                    elseif ($grade >= 65)
                                        return 2.75;
                                    elseif ($grade >= 60)
                                        return 3.0;
                                    else
                                        return 5.0; // Below 60 is 5.0 (Failed)
                                }

                                // Compute grades per student
                                foreach ($students as $student) {
                                    $studentId = (int) $student['id'];


                                    foreach (['midterm', 'final'] as $term) {
                                        $totalExerciseScore = 0;
                                        $totalExerciseMax = 0;
                                        $totalAaaScore = 0;
                                        $totalAaaMax = 0;
                                        $totalExamScore = 0;
                                        $totalExamMax = 0;

                                        foreach ($activityTypes[$term] as $type => $activitiesList) {
                                            foreach ($activitiesList as $activity) {
                                                $activityId = $activity['id'];
                                                $maxPoints = $activity['max_points'];
                                                $score = $studentScores[$studentId][$activityId] ?? 0; // Default to 0 if no score

                                                if ($type === 'exercise') {
                                                    $totalExerciseScore += $score;
                                                    $totalExerciseMax += $maxPoints;
                                                } elseif ($type === 'aaa') {
                                                    $totalAaaScore += $score;
                                                    $totalAaaMax += $maxPoints;
                                                } elseif ($type === 'exam') {
                                                    $totalExamScore += $score;
                                                    $totalExamMax += $maxPoints;
                                                }
                                            }
                                        }

                                        // Compute attendance score (out of 100)
                                        $attendedClasses = $studentAttendance[$studentId] ?? 0;
                                        $attendanceScore = $totalMeetings > 0 ? ($attendedClasses / $totalMeetings) * 100 : 0;

                                        // Compute quiz, AAA (including attendance), and major exam scores
                                        $exerciseScore = $totalExerciseMax > 0 ? ($totalExerciseScore / $totalExerciseMax) * 100 : 0;
                                        $aaaScoreRaw = $totalAaaMax > 0 ? ($totalAaaScore / $totalAaaMax) * 100 : 0;

                                        // Blend AAA components (Assignments/Activities + Attendance)
                                        $aaaScore = ($aaaScoreRaw * 0.7) + ($attendanceScore * 0.3);

                                        $majorExamScore = $totalExamMax > 0 ? ($totalExamScore / $totalExamMax) * 100 : 0;

                                        // Compute final lecture grade for the term
                                        $lectureGrade = ($majorExamScore * 0.4) + ($exerciseScore * 0.3) + ($aaaScore * 0.3);

                                        // Compute weighted term grade (Lecture 60%, Exam 40%)
                                        $lectureGradeWeighted = $lectureGrade * 0.6;
                                        $examWeighted = $majorExamScore * 0.4;
                                        $termGrade = $lectureGradeWeighted + $examWeighted;

                                        if ($term === 'midterm') {
                                            $midtermGrade = $termGrade;
                                        } else {
                                            $finalGrade = $termGrade;
                                        }
                                    }

                                    // Compute total final grade (Midterm 50%, Finals 50%)
                                    $totalGrade = ($midtermGrade * 0.5) + ($finalGrade * 0.5);

                                    // Convert to numerical rating
                                    $numericalRating = getNumericalRating($totalGrade);

                                    // Determine if grade is INC (Incomplete)
                                    $isIncomplete = ($totalExamMax == 0 || $totalExerciseMax  == 0 || $totalAaaMax == 0 || $totalMeetings == 0);
                                }

                                // Fetch student grades from the database
                                $studentGrades = [];
                                $query = "SELECT student_id, midterm_grade, final_grade, overall_grade FROM student_grades WHERE class_id = :class_id";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute(['class_id' => $class_id]);

                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $studentGrades[$row['student_id']] = [
                                        'midterm' => $row['midterm_grade'],
                                        'final' => $row['final_grade'],
                                        'overall' => $row['overall_grade']
                                    ];
                                }

                                ?>

                                <table border="1" class="print-text" id="class"
                                    style="width: 100%; border: 1px solid black;">
                                    <tr>
                                        <th colspan="20" class="border-1 text-center">Worksheet</th>
                                    </tr>

                                    <tr class="text-center">
                                        <th class="border-1 text-center" colspan="2">Criteria</th>
                                        <th class="border-1" colspan="<?php echo $totalExercises ?> ">Exercises
                                            (<?php echo $exercisePercentage ?>%)
                                        </th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1" colspan="<?php echo $totalTripleA  ?>">Assignments /
                                            Attendance
                                            (<?php echo $assignments_activities_attendancePercentage ?>%)
                                        </th>
                                        <th class="border-1" colspan="<?php echo $totalExams ?>">Exams
                                            (<?php echo $majorExamPercentage ?>%)
                                        </th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1">Midterms</th>
                                        <th class="border-1">Finals</th>
                                        <th class="border-1">GPA</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1">Student ID</th>
                                        <th class="border-1">Student Name</th>
                                        <?php foreach ($exercises as $index => $exercise): ?>
                                            <th class="border-1">E<?= $index + 1 ?> <br> (<?= $exercise['max_points'] ?>)</th>
                                        <?php endforeach; ?>
                                        <th class="border-1"><?= $totalPoints['exercise'] ?></th>
                                        <?php foreach ($assignments as $index => $assignment): ?>
                                            <th class="border-1">ASS<?= $index + 1 ?> <br>
                                                (<?= $assignment['max_points'] ?>)</th>
                                        <?php endforeach; ?>
                                        <th class="border-1">
                                            TOTAL <small>(ASS.)</small>
                                            <hr>
                                            <?= $totalPoints['assignment'] ?>
                                        </th>
                                        <th class="border-1">Attendance</th>
                                        <th class="border-1">TOTAL (ATT.)
                                            <hr> <?php echo $totalMeetings ?>
                                        </th>


                                        <?php foreach ($exams as $exam): ?>
                                            <th class="border-1"> <?= ucfirst($exam['term']) ?> <br>
                                                (<?= $exam['max_points'] ?>)</th>
                                        <?php endforeach; ?>
                                        <th class="border-1">
                                            TOTAL
                                            <hr>
                                            <?= $totalPoints['exam'] ?>
                                        </th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                    </tr>
                                    <tr>
                                        <th colspan="20" class="text-center"
                                            style="background-color: grey; color: white;">Male</th>
                                    </tr>
                                    <?php foreach ($male_students as $student): ?>

                                        <tr class="text-center">
                                            <td class="border-1"> <?= $student['id'] ?> </td>
                                            <td class="border-1"> <?= $student['fullName'] ?> </td>
                                            <?php
                                            $exerciseTotal = 0;
                                            foreach ($exercises as $exercise) {

                                                $studentId = $student['id'];
                                                $exerciseId = $exercise['id'];

                                                $score = $studentScores[$student['id']][$exercise['id']] ?? 0;
                                                $exerciseTotal += $score;
                                                echo "<td class='border-1'> $score </td>";
                                            }

                                            echo "<td class='border-1'> $exerciseTotal</td>";

                                            $assignmentTotal = 0;
                                            foreach ($assignments as $assignment) {

                                                $score = $studentScores[$student['id']][$assignment['id']] ?? 0;
                                                $assignmentTotal += $score;
                                                echo "<td class='border-1'> $score </td>";
                                            }
                                            echo "<td class='border-1'>$assignmentTotal</td>";

                                            $attendanceScore = $attendanceCounts[$student['id']] ?? 0;
                                            echo "<td class='border-1'>$attendanceScore</td>";
                                            echo "<td class='border-1'>$attendanceScore</td>";


                                            $examTotal = 0;
                                            foreach ($exams as $exam) {
                                                $score = $studentScores[$student['id']][$exam['id']] ?? 0;
                                                $examTotal += $score;
                                                echo "<td class='border-1'> $score </td>";
                                            }
                                            echo "<td class='border-1'>$examTotal</td>";
                                            // Fetch Grades from student_grades
                                            $midterm = $studentGrades[$student['id']]['midterm'] ?? '-';
                                            $final = $studentGrades[$student['id']]['final'] ?? '-';
                                            $overall = $studentGrades[$student['id']]['overall'] ?? '-';

                                            echo "<td class='border-1'>$midterm</td>";
                                            echo "<td class='border-1'>$final</td>";
                                            echo "<td class='border-1'>$overall</td>";
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>



                                </table>
                            </div>
                            <button onclick="printDiv('printTable')" class="btn btn-primary mb-3">
                                <i class="bi bi-printer"></i> Print
                            </button>
            </main>


        </div>


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
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .grey-bg { background-color: #f8f9fa; }
                    table, th, td { border: 1px solid black; border-collapse: collapse; padding: 5px; }
                    .grade { background: none; border: none; cursor: pointer; color: inherit; }
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