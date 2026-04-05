<?php
session_start();
if (!isset($_SESSION['student_id'])) {
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
        background-color: #121ba3;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #1E28B6FF;
    }

    .view-person {
        border: 1px solid #121ba3;
        border-radius: 10px;
        padding: 10px;
    }

    .linkism {
        border: 1px solid #121ba3;
        padding: 10px;
        border-radius: 10px;
        margin: 5px;
    }

    .linker {
        color: black !important;
    }

    .container-bordered {
        border: 1px solid black;
        margin: auto !important;
        padding: 10px;
        margin-bottom: 5px !important;
    }

    .bordered {
        border: 1px solid black;
        margin: 10px;
        padding: 10px;
    }

    .color-white {
        color: white !important;
    }
</style>

<body>
    <div class="wrapper">
        <?php
        include('sidebar.php')
        ?>

        <div class="main">
            <?php
            include('topbar.php')
            ?>

            <?php
            $class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;


            if ($class_id) {

                try {
                    // Fetch the class details using the class_id
                    $stmt = $pdo->prepare("
            SELECT c.name AS class_name, c.teacher, c.type AS subject_type, ss.start_time, ss.end_time, ss.meeting_days, c.subject
            FROM classes c
            INNER JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN subjects_schedules ss ON s.id = ss.subject_id
            WHERE c.id = :class_id
        ");

                    // Bind the class_id to the prepared statement
                    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);

                    // Execute the query
                    $stmt->execute();

                    // Fetch the class details
                    $class = $stmt->fetch(PDO::FETCH_ASSOC);


                    if ($class) {
                        // Assign class details to variables

                        $className = htmlspecialchars($class['class_name']);
                        $classTeacher = htmlspecialchars($class['teacher']);
                        $subjectType = htmlspecialchars($class['subject_type']);
                        $subjectName = htmlspecialchars($class['subject']);
                        $startTime = htmlspecialchars($class['start_time']);
                        $endTime = htmlspecialchars($class['end_time']);
                        $meetingDays = htmlspecialchars($class['meeting_days']);
                    } else {
                        // If the class is not found, assign a message
                        $className = "Class not found";
                        $classTeacher = "";
                        $subjectType = "";
                        $startTime = "";
                        $endTime = "";
                    }
                } catch (PDOException $e) {
                    // Error message if fetching class details fails
                    $className = "Error fetching class details";
                    $classTeacher = $e->getMessage();
                    $subjectType = "";
                    $startTime = "";
                    $endTime = "";
                }
            } else {
                // If no class is selected, assign a message
                $className = "No class selected";
                $classTeacher = "";
                $subjectType = "";
                $startTime = "";
                $endTime = "";
            }
            ?>
            <!-- Now you can output the variables -->

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="container-fluid actual-content">
                                <div class="container-fluid">
                                    <hr>

                                    <div class="container-fluid">
                                        <a href="student_dashboard.php" style="color:black !important">
                                            <h5 class="bold"><i class="bi bi-arrow-left"></i> Back</h5>
                                        </a>
                                    </div>
                                    <br>
                                    <div class="d-flex align-items-center">
                                        <h3 class=bold> Subject / </h3>
                                        <h3 class="bold">&nbsp;<?php echo $subjectName ?> (<?php echo $subjectType ?>)</h3>
                                        <div class="ms-auto" aria-hidden="true">
                                            <div class="row align-items-center">
                                                Schedule: <?php echo $meetingDays ?>
                                                <div class="col">
                                                    <small>
                                                        | <?php echo $subjectType; ?>:
                                                        <?php echo $startTime ? date("g:i A", strtotime($startTime)) : 'N/A'; ?>
                                                        -
                                                        <?php echo $endTime ? date("g:i A", strtotime($endTime)) : 'N/A'; ?>
                                                    </small>


                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="container-fluid">
                                    <div class="row text-center m-auto">
                                        <h1 class="bold">Manage:</h1>
                                        <div class="col linkism">
                                            <button class="btn wide-btn btn-csms-1 " data-bs-toggle="collapse"
                                                data-bs-target="#students" aria-expanded="true"
                                                aria-controls="students">Students</button>
                                        </div>
                                        <div class="col linkism">
                                            <!-- <button class="btn wide-btn btn-csms-1" data-bs-toggle="collapse"
                                                data-bs-target="#activity" aria-expanded="true"
                                                aria-controls="students">Activity</button> -->

                                            <button class="btn wide-btn btn-csms-1" id="activityButton" data-bs-toggle="collapse"
                                                data-bs-target="#activity" aria-expanded="true"
                                                aria-controls="students">Activity</button>
                                        </div>

                                        <div class="col linkism">
                                            <div class="dropdown">
                                                <button class="btn wide-btn btn-csms-1" href="#" role="button"
                                                    data-bs-toggle="collapse" data-bs-target="#grades"
                                                    aria-expanded="true" aria-controls="students" aria-expanded="false">
                                                    Grades
                                                </button>

                                            </div>

                                        </div>
                                        <div class="col linkism">
                                            <button class="btn wide-btn btn-csms-1" onclick="goToLectures()"
                                                data-bs-toggle="collapse" data-bs-target="#lectures"
                                                aria-expanded="false" aria-controls="collapseThree">Lectures</button>
                                        </div>
                                        <div class="col linkism">
                                            <button class="btn wide-btn btn-csms-1" data-bs-toggle="collapse"
                                                data-bs-target="#attendance" aria-expanded="false"
                                                aria-controls="collapseThree">Attendance</button>
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="accordion" id="navlinkers">
                                        <div id="students" class="accordion-collapse collapse show"
                                            data-bs-parent="#navlinkers">
                                            <div class="accordion-body">
                                                <div class="container-fluid">
                                                    <h1 class="mb-4 bold text-center">Students Enrolled</h1>
                                                    <h1 class="bold"><i class="bi bi-person-circle icon"></i> Teacher
                                                    </h1>
                                                    <div
                                                        class=" bordered-none align-middle teacher-container-individual">
                                                        <h2 class="view-person"><i class="bi bi-person-fill"></i>
                                                            <?php echo $classTeacher ?></h2>
                                                    </div>
                                                </div>


                                                <div class="container-fluid student-container">
                                                    <h1 class="mb-4 bold"><i class="bi bi-people icon"></i> Students
                                                    </h1>

                                                    <?php
                                                    $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = :class_id");
                                                    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                    $stmt->execute();
                                                    $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                    if ($enrolledStudents) {
                                                        // Iterate through each student_id to get their fullName from the 'students' table
                                                        foreach ($enrolledStudents as $enrollment) {
                                                            $student_id = $enrollment['student_id'];

                                                            // Prepare the query to get the full name of the student
                                                            $studentStmt = $pdo->prepare("SELECT fullName FROM students WHERE student_id = :student_id");
                                                            $studentStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                                                            $studentStmt->execute();
                                                            $student = $studentStmt->fetch(PDO::FETCH_ASSOC);



                                                            if ($student) {
                                                                // Display the student's full name and add the unenroll button
                                                                echo '<div class="d-flex align-items-center view-person">';
                                                                echo '<h2><i class="bi bi-person icon"></i> ' . htmlspecialchars($student['fullName']) . '</h2>';
                                                                echo '<div class="ms-auto">';
                                                                echo '</div>';
                                                                echo '</div>
                                                        <br>';
                                                            } else {
                                                                // Handle case where the student is not found in the 'students' table
                                                                echo '<h2 class="view-person"><i class="bi bi-person icon"></i> Student not found</h2>';
                                                            }
                                                        }
                                                    } else {
                                                        echo '<h2 class="view-person">No students enrolled in this class.</h2>';
                                                    }
                                                    ?>



                                                </div>
                                            </div>
                                        </div>
                                        <div id="activity" class="accordion-collapse collapse"
                                            data-bs-parent="#navlinkers">
                                            <?php
                                            require_once 'processes/server/conn.php'; // Database connection

                                            $class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null; // Get the class_id from the URL

                                            if ($class_id) {

                                                // Query to retrieve the `is_archived` field from the `classes` table
                                                $sql = "SELECT is_archived FROM classes WHERE id = :class_id";

                                                $stmt = $pdo->prepare($sql);
                                                // Bind the class ID parameter
                                                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                // Execute the query
                                                $stmt->execute();

                                                // Fetch the result
                                                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                                                if ($row) {
                                                    // Determine archived status
                                                    $archived_status = ($row['is_archived'] == 1) ? 'archived' : 'not_archived';
                                                } else {
                                                    $archived_status = 'Class not found';
                                                }


                                                try {
                                                    // Fetch activities based on the class_id
                                                    $stmt = $pdo->prepare("
            SELECT id, title, type, message, due_date, due_time, max_points, min_points
            FROM activities
            WHERE class_id = :class_id
            ORDER BY id
        ");
                                                    $stmt->execute(['class_id' => $class_id]);
                                                    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                } catch (Exception $e) {
                                                    echo "Error: " . $e->getMessage();
                                                    $activities = [];
                                                }
                                            } else {
                                                $activities = [];
                                            }


                                            ?>



                                            <div class="accordion-body">
                                                <h1 class="text-center" style="font-weight: bold;">Your Class Activities
                                                </h1>
                                                <ul class="list-unstyled">
                                                    <?php if (!empty($activities)): ?>
                                                        <?php foreach ($activities as $activity):
                                                            $stmt_attachments = $pdo->prepare("
                                                            SELECT file_name, file_path, uploaded_at 
                                                            FROM activity_attachments 
                                                            WHERE activity_id = :activity_id
                                                        ");
                                                            $stmt_attachments->execute(['activity_id' => $activity['id']]);
                                                            $attachments = $stmt_attachments->fetchAll(PDO::FETCH_ASSOC);

                                                            $stmt_submissions = $pdo->prepare("
                                                    SELECT status, score
                                                    FROM activity_submissions 
                                                    WHERE activity_id = :activity_id AND student_id = :student_id
                                                ");

                                                            $stmt_submissions->execute([
                                                                'activity_id' => $activity['id'],
                                                                'student_id' => $_SESSION['student_id']
                                                            ]);

                                                            $submission = $stmt_submissions->fetch(PDO::FETCH_ASSOC); // Use fetch instead of fetchAll

                                                        ?>

                                                            <li class="mb-3">
                                                                <div class="d-flex align-items-center p-3"
                                                                    style="border: 1px solid #e5e5e5; border-radius: 8px; background-color: #f9f9f9;">
                                                                    <div>
                                                                        <p class="mb-1"
                                                                            style="font-size: 16px; color: #333; font-weight: 500;">
                                                                            <?php echo htmlspecialchars($activity['title']); ?>
                                                                            <a href="#" data-bs-toggle="modal"
                                                                                data-bs-target="#activityModal<?php echo $activity['id']; ?>"
                                                                                style="color: #5b8b5e; font-size: 14px; text-decoration: underline;">(View)</a>
                                                                        </p>
                                                                        <p class="mb-0"
                                                                            style="font-size: 14px; color: #6c757d;">
                                                                            <?php echo htmlspecialchars($activity['type']); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div class="ms-auto text-end">
                                                                        <p class="mb-0"
                                                                            style="font-size: 14px; color: #6c757d;">
                                                                            Due on <span
                                                                                style="color: #5b8b5e;"><?php echo htmlspecialchars($activity['due_date']); ?></span>
                                                                        </p>
                                                                        <p class="mb-0"
                                                                            style="font-size: 14px; font-weight: bold; color: #333;">
                                                                            <?php
                                                                            if ($submission['status'] == 'graded') {
                                                                                // Check if the score is passing (greater than or equal to min_points)
                                                                                if ($submission['score'] >= $activity['min_points']) {

                                                                                    echo '<span class="alert alert-success d-inline-block mb-0" style="font-size: 12px; padding: 2px 6px; margin-right: 5px;">Passed</span>';
                                                                                    echo "Score: ";
                                                                                    echo $submission['score'];
                                                                                } else {

                                                                                    // Display "Passed" in a small alert beside the score
                                                                                    echo '<span class="alert alert-warning d-inline-block mb-0" style="font-size: 12px; padding: 2px 6px; margin-right: 5px;">Failed</span>';
                                                                                    echo "Score: ";
                                                                                    echo $submission['score'] . ' ';
                                                                                }
                                                                            } elseif ($submission['status'] == 'submitted') {
                                                                                echo '<span class="alert alert-success d-inline-block mb-0" style="font-size: 12px; padding: 2px 6px; margin-right: 5px;">Submitted</span>';
                                                                                echo "Score: ";
                                                                                echo $submission['score'] . ' ';
                                                                            } else {

                                                                                // Display "Passed" in a small alert beside the score
                                                                                echo '<span class="alert alert-warning d-inline-block mb-0" style="font-size: 12px; padding: 2px 6px; margin-right: 5px;">Missing Submission</span>';
                                                                                echo "Score: ";
                                                                                echo $submission['score'] . ' ';
                                                                            }
                                                                            ?>
                                                                            </span> /
                                                                            <?php echo htmlspecialchars($activity['max_points']); ?>
                                                                            pts
                                                                        </p>


                                                                    </div>
                                                                </div>
                                                            </li>

                                                            <!-- Modal for Activity Details -->
                                                            <div class="modal fade"
                                                                id="activityModal<?php echo $activity['id']; ?>" tabindex="-1"
                                                                aria-labelledby="activityModalLabel<?php echo $activity['id']; ?>"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title"
                                                                                id="activityModalLabel<?php echo $activity['id']; ?>">
                                                                                Viewing Activity:
                                                                                <?php echo htmlspecialchars($activity['title']); ?>
                                                                            </h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal"
                                                                                aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="row">
                                                                                <div class="col">
                                                                                    <p><strong>Title:</strong>
                                                                                        <?php echo htmlspecialchars($activity['title']); ?>
                                                                                    </p>
                                                                                </div>
                                                                                <div class="col">
                                                                                    <p><strong>Type:</strong>
                                                                                        <?php echo htmlspecialchars($activity['type']); ?>
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <?php
                                                                                // Convert the due date and time to a more readable format
                                                                                $due_date = new DateTime($activity['due_date']);
                                                                                $due_time = new DateTime($activity['due_time']);
                                                                                ?>

                                                                                <p><b>Due on:</b>
                                                                                    <span><?php echo $due_date->format('F j, Y'); ?></span>
                                                                                    at
                                                                                    <span><?php echo $due_time->format('g:i A'); ?></span>
                                                                                </p>

                                                                            </div>

                                                                            <p><strong>Points:</strong>
                                                                                <?php echo htmlspecialchars($activity['min_points']) . ' -  ' . htmlspecialchars($activity['max_points']); ?>
                                                                            </p>

                                                                            <p><strong>Description:</strong>
                                                                                <?php echo htmlspecialchars($activity['message']); ?>
                                                                            </p>

                                                                            <p><b>Attachments:</b></p>
                                                                            <?php if (!empty($attachments)): ?>
                                                                                <ul>
                                                                                    <?php foreach ($attachments as $attachment): ?>
                                                                                        <li>
                                                                                            <a href="../uploads/files/<?php echo htmlspecialchars($attachment['file_path']); ?>"
                                                                                                target="_blank">
                                                                                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                                                            </a>
                                                                                            <span class="text-muted">(Uploaded on
                                                                                                <?php echo (new DateTime($attachment['uploaded_at']))->format('F j, Y g:i A'); ?>)</span>
                                                                                        </li>
                                                                                    <?php endforeach; ?>
                                                                                </ul>
                                                                            <?php else: ?>
                                                                                <p class="text-muted">No attachments available for
                                                                                    this
                                                                                    activity.</p>
                                                                            <?php endif; ?>


                                                                            <br>

                                                                            <?php
                                                                            if ($archived_status == 'archived') {
                                                                                echo "Class has already been archived. You cannot submit anymore!";
                                                                            } else {
                                                                            ?>
                                                                                <p>
                                                                                    <small>Submission: If your teacher hasn't
                                                                                        prompted you
                                                                                        to submit anything, leave file upload as
                                                                                        blank.</small>
                                                                                </p>

                                                                                <?php if ($submission['status'] !== 'submitted' && $submission['status'] !== 'graded'): ?>

                                                                                    <!-- File Submission Form -->
                                                                                    <form method="POST"
                                                                                        action="processes/students/activities/add.php?class_id=<?php echo $class_id ?>"
                                                                                        enctype="multipart/form-data" class="mt-4">
                                                                                        <input type="hidden" name="activity_id"
                                                                                            value="<?php echo $activity['id']; ?>">

                                                                                        <div class="mb-3">
                                                                                            <label
                                                                                                for="fileUpload<?php echo $activity['id']; ?>"
                                                                                                class="form-label">Upload Your
                                                                                                File</label>
                                                                                            <input type="file" class="form-control"
                                                                                                id="fileUpload<?php echo $activity['id']; ?>"
                                                                                                name="submission_file">
                                                                                        </div>
                                                                                        <button type="submit"
                                                                                            class="btn btn-success">Submit</button>
                                                                                    </form>

                                                                                <?php elseif ($submission['status'] === 'submitted'): ?>
                                                                                    <!-- Show message if the submission is complete but not graded -->
                                                                                    <div class="alert alert-info d-flex align-items-center"
                                                                                        role="alert">
                                                                                        <i class="bi bi-check-circle me-2"></i>
                                                                                        <span>Your submission for this activity has been
                                                                                            completed. Awaiting grading.</span>
                                                                                    </div>

                                                                                    <!-- Reset submission form -->
                                                                                    <form method="POST"
                                                                                        action="processes/students/activities/add.php?class_id=<?php echo $class_id; ?>"
                                                                                        class="mt-3">
                                                                                        <input type="hidden" name="activity_id"
                                                                                            value="<?php echo $activity['id']; ?>">

                                                                                        <!-- Button to reset the submission -->
                                                                                        <button type="submit" class="btn btn-danger"
                                                                                            name="reset_submission">Reset
                                                                                            Submission</button>
                                                                                    </form>

                                                                                <?php elseif ($submission['status'] === 'graded'): ?>
                                                                                    <!-- Show message if the submission is graded -->
                                                                                    <div class="alert alert-success d-flex align-items-center"
                                                                                        role="alert">
                                                                                        <i class="bi bi-check-circle me-2"></i>
                                                                                        <span>Your submission has been graded. Score:
                                                                                            <strong><?php echo $submission['score']; ?></strong></span>
                                                                                    </div>

                                                                            <?php

                                                                                endif;
                                                                            } ?>


                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        <?php endforeach; ?>

                                                    <?php else: ?>

                                                        <li>
                                                            <p style="font-size: 16px; color: #6c757d;">No activities
                                                                available for
                                                                this class.</p>
                                                        </li>

                                                    <?php endif; ?>

                                                </ul>
                                            </div>
                                        </div>


                                        <div id="grades" class="accordion-collapse collapse" data-bs-parent="#navlinkers">
                                            <div class="accordion-body">
                                                <h1 class="text-center" style="font-weight: bold;">Your Grades</h1>
                                                <?php


                                                // Get the logged-in student's ID
                                                $student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;

                                                // Get the class_id from the URL parameter
                                                $class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;

                                                if ($student_id && $class_id) {
                                                    try {
                                                        // Fetch class details based on the class_id (optional, if you want the class name)
                                                        $stmt_class = $pdo->prepare("
                SELECT name AS class_name,  is_archived
                FROM classes 
                WHERE id = :class_id
            ");
                                                        $stmt_class->execute(['class_id' => $class_id]);
                                                        $class = $stmt_class->fetch(PDO::FETCH_ASSOC);
                                                        $is_archived_status = $class['is_archived'];

                                                        if ($class) {


                                                            // Fetch activities for this specific class_id
                                                            $stmt_activities = $pdo->prepare("
                    SELECT id AS activity_id, title, due_date, due_time, min_points, max_points, type
                    FROM activities
                    WHERE class_id = :class_id
                    ORDER BY type, due_date, due_time DESC
                ");
                                                            $stmt_activities->execute(['class_id' => $class_id]);
                                                            $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);

                                                            if ($activities) {
                                                                // Group activities by their type
                                                                $grouped_activities = [];
                                                                foreach ($activities as $activity) {
                                                                    $grouped_activities[$activity['type']][] = $activity;
                                                                }

                                                                // Loop through each type and display its activities
                                                                foreach ($grouped_activities as $type => $activities_by_type) {
                                                                    echo '<h4 class="bold">' . ucfirst($type) . '</h4>';
                                                                    echo '<table class="table table-bordered"><thead><tr><th>Activity</th><th>Due Date</th><th>Status</th><th>Score</th></tr></thead><tbody>';

                                                                    foreach ($activities_by_type as $activity) {
                                                                        // Fetch the student's submission for each activity
                                                                        $stmt_submission = $pdo->prepare("
                                SELECT * 
                                FROM activity_submissions 
                                WHERE activity_id = :activity_id AND student_id = :student_id
                            ");
                                                                        $stmt_submission->execute([
                                                                            'activity_id' => $activity['activity_id'],
                                                                            'student_id' => $student_id
                                                                        ]);
                                                                        $submission = $stmt_submission->fetch(PDO::FETCH_ASSOC);

                                                                        // Default status and score if there's no submission
                                                                        if ($submission) {
                                                                            $status = $submission['status'];
                                                                            $score = $submission['score'];
                                                                        } else {
                                                                            $status = 'Pending';
                                                                            $score = 'N/A';
                                                                        }

                                                                        // Set the badge color based on status
                                                                        $badge_class = '';
                                                                        switch ($status) {
                                                                            case 'submitted':
                                                                                $badge_class = 'badge bg-primary'; // Blue badge for submitted
                                                                                break;
                                                                            case 'graded':
                                                                                $badge_class = 'badge bg-success'; // Green badge for graded
                                                                                break;
                                                                            case 'Pending':
                                                                                $badge_class = 'badge bg-warning'; // Yellow badge for pending
                                                                                break;
                                                                            default:
                                                                                $badge_class = 'badge bg-secondary'; // Default gray badge for undefined status
                                                                        }

                                                                        // Display the activity with its status as a badge
                                                                        echo '<tr>';
                                                                        echo '<td>' . htmlspecialchars($activity['title']) . '</td>';
                                                                        // Convert the due date and time into a readable format
                                                                        $due_date_time = $activity['due_date'] . ' ' . $activity['due_time'];
                                                                        $formatted_due_date = date('l, F j, Y', strtotime($due_date_time)); // Format date as 'Day, Month Day, Year'
                                                                        $formatted_due_time = date('g:i A', strtotime($due_date_time)); // Format time as '12-hour AM/PM'

                                                                        // Display formatted due date and time
                                                                        echo '<td>' . $formatted_due_date . ' at ' . $formatted_due_time . '</td>';

                                                                        echo '<td><span class="' . $badge_class . '">' . ucfirst($status) . '</span></td>';
                                                                        echo '<td>' . htmlspecialchars($score) . '</td>';
                                                                        echo '</tr>';
                                                                    }

                                                                    echo '</tbody></table>';
                                                                }
                                                            } else {
                                                                echo '<p>No activities found for this class.</p>';
                                                            }
                                                        } else {
                                                            echo '<p>Class not found.</p>';
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo "Error: " . $e->getMessage();
                                                    }
                                                } else {
                                                    echo '<p>No student ID found or invalid class ID. Please log in or try again.</p>';
                                                }
                                                ?>
                                            </div>


                                        </div>
                                        <div id="lectures" class="accordion-collapse collapse" data-bs-parent="#navlinkers">
                                            <h1 class="text-center" style="font-weight: bold;">Your Class Lectures</h1>
                                            <div class="accordion-body">
                                                <?php
                                                // Assuming the class_id is passed via the URL (e.g., ?class_id=64)
                                                $class_id = isset($_GET['class_id']) ? $_GET['class_id'] : null;

                                                if ($class_id) {
                                                    try {
                                                        // Fetch unique resource types for the filter dropdown
                                                        $stmtFilter = $pdo->prepare("
                    SELECT DISTINCT resource_type
                    FROM learning_resources
                    WHERE class_id = :class_id
                ");
                                                        $stmtFilter->execute(['class_id' => $class_id]);
                                                        $resourceTypes = $stmtFilter->fetchAll(PDO::FETCH_ASSOC);

                                                        echo '<div class="filter-container mb-4 text-center">';
                                                        echo '<label for="resourceTypeFilter" class="form-label">Filter By Type:</label>';
                                                        echo '<select id="resourceTypeFilter" class="form-select w-50 d-inline" onchange="filterResources()">';
                                                        echo '<option value="all" selected>All</option>';
                                                        foreach ($resourceTypes as $type) {
                                                            echo '<option value="' . htmlspecialchars($type['resource_type']) . '">' . ucfirst(htmlspecialchars($type['resource_type'])) . '</option>';
                                                        }
                                                        echo '</select>';
                                                        echo '</div>';

                                                        // Initial resource fetch query based on the class_id
                                                        $stmt = $pdo->prepare("
                    SELECT resource_id, resource_name, resource_type, resource_description, resource_url, uploaded_at 
                    FROM learning_resources
                    WHERE class_id = :class_id
                ");
                                                        $stmt->execute(['class_id' => $class_id]);
                                                        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        if ($resources) {
                                                            echo '<div id="resourcesContent">';
                                                            foreach ($resources as $resource) {
                                                                $resource_name = htmlspecialchars($resource['resource_name']);
                                                                $resource_type = htmlspecialchars($resource['resource_type']);
                                                                $uploaded_at = date('F j, Y', strtotime($resource['uploaded_at']));
                                                                $resource_url = htmlspecialchars($resource['resource_url']);
                                                                $resource_description = htmlspecialchars($resource['resource_description']);

                                                                // Prepare the icon based on resource type
                                                                $icon = '';
                                                                switch ($resource_type) {
                                                                    case 'document':
                                                                        $icon = 'bi-file-earmark-text';
                                                                        break;
                                                                    case 'video':
                                                                        $icon = 'bi-film';
                                                                        break;
                                                                    case 'audio':
                                                                        $icon = 'bi-volume-up';
                                                                        break;
                                                                    case 'image':
                                                                        $icon = 'bi-image';
                                                                        break;
                                                                    default:
                                                                        $icon = 'bi bi-file-earmark';
                                                                }

                                                                // Start displaying the resource
                                                                echo '<div class="container-fluid file-container" data-type="' . $resource_type . '">';
                                                                echo '  <div class="row mb-3">';
                                                                echo '    <div class="col-sm-1 text-center">';
                                                                echo '      <h1 class="' . $icon . '" style="font-size:64px;"></h1>';
                                                                echo '    </div>';
                                                                echo '    <div class="col">';
                                                                echo '      <div class="d-flex align-items-center justify-content-between">';
                                                                echo '        <div>';
                                                                echo '          <h5 class="bold">' . ucfirst($resource_name) . '</h5>';
                                                                echo '          <p><strong>Type:</strong> ' . ucfirst($resource_type) . '</p>';
                                                                echo '          <p><strong>Description:</strong> ' . ucfirst($resource_description) . '</p>';
                                                                echo '        </div>';
                                                                echo '        <div class="ms-auto text-center">';
                                                                echo '          <p class="bold">' . $uploaded_at . '</p>';
                                                                // Check if resource is downloadable (i.e., URL exists)
                                                                if ($resource_url) {
                                                                    echo '          <a href="../uploads/materials/' . basename($resource_url) . '" style="color: black !important">';
                                                                    echo '            <h5 class="bi bi-download bold"></h5>';
                                                                    echo '          </a>';
                                                                } else {
                                                                    echo '          <h5 class="bi bi-download bold"></h5>'; // Placeholder if no download URL
                                                                }
                                                                echo '        </div>';
                                                                echo '      </div>';
                                                                echo '    </div>';
                                                                echo '  </div>';
                                                                echo '</div>';
                                                                echo '<hr>';
                                                            }
                                                            echo '</div>';
                                                        } else {
                                                            echo '<p>No learning resources found for this class.</p>';
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo "Error: " . $e->getMessage();
                                                    }
                                                } else {
                                                    echo '<p>No class ID found.</p>';
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <script>
                                            // JavaScript function to filter resources based on selected type
                                            function filterResources() {
                                                const filterValue = document.getElementById("resourceTypeFilter").value;
                                                const resourceElements = document.querySelectorAll(".file-container");

                                                resourceElements.forEach(resource => {
                                                    if (filterValue === "all" || resource.dataset.type === filterValue) {
                                                        resource.style.display = "block";
                                                    } else {
                                                        resource.style.display = "none";
                                                    }
                                                });
                                            }
                                        </script>

                                        <div id="attendance" class="accordion-collapse collapse" data-bs-parent="#navlinkers">
                                            <div class="accordion-body">
                                                <?php
                                                // Include the database connection
                                                require_once 'processes/server/conn.php';

                                                // Get the logged-in student's ID
                                                $studentId = $_SESSION['student_id'] ?? null;

                                                // Get the class_id from the URL (GET parameter)
                                                $classIdFromGet = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

                                                if ($studentId && $classIdFromGet) {
                                                    // Query to get class details based on the provided class_id
                                                    $stmtClasses = $pdo->prepare("
                SELECT c.id AS class_id, c.type AS type, c.name AS class_name, c.subject AS subject_name
                FROM students_enrollments se
                JOIN classes c ON se.class_id = c.id
                WHERE se.student_id = :student_id AND c.id = :class_id
            ");
                                                    $stmtClasses->execute([':student_id' => $studentId, ':class_id' => $classIdFromGet]);

                                                    if ($stmtClasses->rowCount() > 0) {
                                                        // Fetch class data
                                                        $class = $stmtClasses->fetch(PDO::FETCH_ASSOC);
                                                        $className = htmlspecialchars($class['class_name']);
                                                        $subjectName = htmlspecialchars($class['subject_name']);
                                                        $type = htmlspecialchars($class['type']);

                                                        echo '<div class="table-responsive">';
                                                        echo '<table class="table table-bordered">';
                                                        echo '<thead>';
                                                        echo '<tr>';
                                                        echo '<th>Class</th>';
                                                        echo '<th>Subject</th>';
                                                        echo '<th>Attendance Date</th>';
                                                        echo '<th>Attendance Status</th>';
                                                        echo '<th>Meeting Time</th>';
                                                        echo '</tr>';
                                                        echo '</thead>';
                                                        echo '<tbody>';

                                                        // Query to get attendance records for the specific class
                                                        $stmtAttendance = $pdo->prepare("
                    SELECT a.date AS attendance_date, a.status AS attendance_status, 
                           cm.start_time, cm.end_time, cm.date AS meeting_date
                    FROM attendance a
                    JOIN classes_meetings cm ON a.meeting_id = cm.id
                    WHERE a.student_id = :student_id AND a.class_id = :class_id
                    ORDER BY cm.date DESC
                ");
                                                        $stmtAttendance->execute([':student_id' => $studentId, ':class_id' => $classIdFromGet]);

                                                        // Display attendance data
                                                        if ($stmtAttendance->rowCount() > 0) {
                                                            while ($attendance = $stmtAttendance->fetch(PDO::FETCH_ASSOC)) {
                                                                $attendanceDate = htmlspecialchars($attendance['attendance_date']);
                                                                $attendanceStatus = htmlspecialchars($attendance['attendance_status']);
                                                                $startTime = htmlspecialchars($attendance['start_time']);
                                                                $endTime = htmlspecialchars($attendance['end_time']);
                                                                $meetingDate = htmlspecialchars($attendance['meeting_date']);

                                                                // Formatting the time
                                                                $formattedTime = $startTime . ' - ' . $endTime;

                                                                // Conditional rendering of span class based on attendance status
                                                                if ($attendanceStatus == 'present') {
                                                                    $statusClass = 'badge text-bg-success color-white'; // Green badge for "Present"
                                                                } else if ($attendanceStatus == 'late') {
                                                                    $statusClass = 'badge text-bg-warning color-white'; // Yellow badge for "Late"
                                                                } else {
                                                                    $statusClass = 'badge text-bg-danger'; // Red badge for "Absent"
                                                                }

                                                                echo '<tr>';
                                                                echo '<td>' . $className . '</td>';
                                                                echo '<td>' . $subjectName . ' (' . $type . ')</td>';
                                                                echo '<td>' . $attendanceDate . '</td>';
                                                                echo '<td><span class="' . $statusClass . '">' . ucfirst($attendanceStatus) . '</span></td>';
                                                                echo '<td>' . $formattedTime . ' (' . $meetingDate . ')</td>';
                                                                echo '</tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="5">No attendance records found for this class.</td></tr>';
                                                        }

                                                        echo '</tbody>';
                                                        echo '</table>';
                                                        echo '</div>';
                                                    } else {
                                                        echo '<p>Class not found for this student.</p>';
                                                    }
                                                } else {
                                                    echo '<p>No class ID provided or student not logged in.</p>';
                                                }
                                                ?>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <br>
                                <hr>

                                <!-- <div class="row text-center d-flex justify-content-center">
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
							</div> -->

                                <?php
                                if (isset($_SESSION['student_id'])) {

                                    $studentId = $_SESSION['student_id'];

                                    $sql = "SELECT * FROM students WHERE student_id = :studentId";
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->bindParam(':studentId', $studentId);
                                    $stmt->execute();

                                    // Assuming there is one row of data
                                    $student = $stmt->fetch(PDO::FETCH_ASSOC);

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
                                            <p><strong>ADDU Email:</strong>
                                                <?php echo htmlspecialchars($student['email'] ?? 'No information added yet.'); ?>
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
                                                                            </span><em><?php echo htmlspecialchars($class['subject']); ?> (<?php echo htmlspecialchars($class['type']); ?>)</em>
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
                                                    <?php else:
                                                        echo "
													<div class='alert alert-warning'>
													<p class='text-muted text-center'>No classes enrolled.</p>
													</div>"; ?>

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
                                                    $stmt = $pdo->prepare("SELECT e.class_id, c.subject, c.type
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
                                                                    <?php echo $subject; ?> (<?php echo $class['type']; ?>)
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
                                                                                        <!-- <button class="btn btn-success"><i
																							class="bi bi-door-open-fill"></i>View (Shortcut)
																					</button> -->
                                                                                    </td>
                                                                                </tr>
                                                                        <?php
                                                                            }
                                                                        } else {
                                                                            echo '<tr><td colspan="4" class="text-center text-muted">No activities found for this subject.</td></tr>';
                                                                        }
                                                                        ?>
                                                                    </tbody>
                                                                </table>


                                                            </div>
                                                <?php
                                                        }
                                                    } else {
                                                        echo "
													<div class='alert alert-warning'>
													<p class='text-muted text-center'>No classes enrolled.</p>
													</div>";
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
                   c.name AS class_name, c.subject AS subject_name, c.type as type, c.teacher AS teacher_name,
                   s.id AS semesterId
            FROM students_enrollments se
            JOIN classes_meetings cm ON se.class_id = cm.class_id
            JOIN classes c ON cm.class_id = c.id
            JOIN semester s ON c.semester = s.name
            WHERE se.student_id = :student_id
              AND cm.date = CURDATE()
         
          
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
                                                        echo '<h5 class="mb-1 text-primary"><strong>' . htmlspecialchars($row['class_name']) .  '</strong></h5>';
                                                        echo '<p class="mb-1"><strong>Subject:</strong> ' . htmlspecialchars($row['subject_name']) . ' (' . htmlspecialchars($row['type']) . ')</p>';

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
                                                    echo "
												<div class='alert alert-warning'>
												<p class='text-muted text-center'>No ongoing meetings found for today</p>
												</div>";
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
                    c.subject AS subject_name,
					c.type as type
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
                                                                $type = htmlspecialchars($row['type']);



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
                                                                echo '<td>' . $subjectName . ' (' . $type . ')</td>';

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
                                                    echo '<div class="alert alert-warning text-center">No semesters found.</div>';
                                                }
                                            } else {
                                                echo '<div class="alert alert-danger">Student not logged in.</div>';
                                            }
                                            ?>

                                        </div>




                                    </div>






                                </div>
                            </div>
                        </div>
                    </div>
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


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if the URL contains '?url=activities'
        if (window.location.search.includes('url=activity')) {
            var activityButton = document.getElementById('activityButton');
            if (activityButton) {
                activityButton.click();
            }
        }
    });
</script>