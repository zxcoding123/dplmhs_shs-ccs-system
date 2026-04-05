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
        background-color: #709775;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #709775;
    }

    .view-person {
        border: 1px solid #709775;
        border-radius: 10px;
        padding: 10px;
    }

    .linkism {
        border: 1px solid #709775;
        padding: 10px;
        border-radius: 10px;
        margin: 5px;
    }

    .linker {
        color: black !important;
    }

    .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .grades-table th,
    .grades-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .grade-input {
        width: 100%;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
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
                <span class="text-white">ADDU - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>

            <?php
            $class_id = $_GET['class_id'];
            $subject_id = $_GET['subject_id'];

            // Initialize variables
            $id = $name = $type = $subject = $subject_id = $code = $teacher = $semester = $studentTotal = $description = $classCode = $requestor = $status = $reason = $datetime_added = $is_archived = null;

            if ($class_id) {
                // Prepare the SQL query
                $stmt = $pdo->prepare("SELECT id, name, type, subject, subject_id, code, teacher, semester, studentTotal, description, classCode, requestor, status, reason, datetime_added, is_archived FROM classes WHERE id = :class_id LIMIT 1");
                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);

                // Execute the query
                $stmt->execute();

                // Fetch the result
                $classData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($classData) {
                    // Assign values to variables
                    $id = $classData['id'];
                    $name = $classData['name'];
                    $type = $classData['type'];
                    $subject = $classData['subject'];
                    $subject_id = $classData['subject_id'];
                    $code = $classData['code'];
                    $teacher = $classData['teacher'];
                    $semester = $classData['semester'];
                    $studentTotal = $classData['studentTotal'];
                    $description = $classData['description'];
                    $classCode = $classData['classCode'];
                    $requestor = $classData['requestor'];
                    $status = $classData['status'];
                    $reason = $classData['reason'];
                    $datetime_added = $classData['datetime_added'];
                    $is_archived = $classData['is_archived'];
                } else {
                    echo "Class not found.";
                }
            } else {
                echo "Invalid class ID.";
            }
            ?>

            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">


                        <main class="content">
                            <div id="page-content-wrapper">
                                <a href="class_management.php" class="d-flex align-items-center mb-3">
                                    <i class="bi bi-arrow-left-circle"
                                        style="font-size: 1.5rem; margin-right: 5px;"></i>
                                    <p class="m-0">Back</p>
                                </a>
                                <h2 class="bold">Subjects / <?php echo $subject ?> </h2>
                                <?php
                                require 'processes/server/conn.php';
                                // Set class_id and semester (these could come from URL parameters or form inputs)
                                $class_id = $_GET['class_id'] ?? null;
                                $semester = $_GET['semester'] ?? null;
                                $classData = null;
                                if ($class_id) {
                                    // Query database to get class details
                                    $stmt = $pdo->prepare("SELECT id, name, subject, teacher, semester, type FROM classes WHERE id = :class_id LIMIT 1");
                                    $stmt->execute(['class_id' => $class_id]);
                                    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
                                }
                                $semester_matcher = $classData['semester'];
                                $stmt = $pdo->prepare("SELECT school_year FROM semester WHERE name = :name");
                                $stmt->bindParam(':name', $semester_matcher, PDO::PARAM_STR);
                                $stmt->execute();
                                $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($semester_found) {
                                    $schoolYear = $semester_found['school_year'];
                                }
                                ?>
                                <br>
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



                            </div>
                            <hr>
                            <div class="card-body">

                                <div class="row text-center">
                                    <div class="col linkism">
                                        <a class="linker"
                                            href="subject_activities.php?url=people&class_id=<?php echo $class_id ?>&subject_id=<?php echo $subject_id ?>">People</a>
                                    </div>
                                    <div class="col linkism">
                                        <a class="linker"
                                            href="subject_activities.php?url=activity&class_id=<?php echo $class_id ?>&subject_id=<?php echo $subject_id ?>">Activity</a>
                                    </div>
                                    <div class="col linkism">
                                        <a class="linker"
                                            href="subject_activities.php?url=scores&class_id=<?php echo $class_id ?>&subject_id=<?php echo $subject_id ?>">Scores</a>
                                    </div>
                                    <div class="col linkism">
                                        <a class="linker"
                                            href="subject_activities.php?url=grades&class_id=<?php echo $class_id ?>&subject_id=<?php echo $subject_id ?>">Grades</a>
                                    </div>
                                    <div class="col linkism">
                                        <a class="linker"
                                            href="subject_activities.php?url=resources&class_id=<?php echo $class_id ?>&subject_id=<?php echo $subject_id ?>">Resources</a>
                                    </div>
                                </div>

                                <br>
                                <?php

                                // people separator

                                $url = $_GET['url'];
                                if ($url == 'people') {
                                ?>
                                    <div class="d-flex align-items-center">
                                        <h1 class="mb-4 bold">People</h1>
                                        <div class="ms-auto" aria-hidden="true">
                                            <button type="button" class="btn btn-primary" onclick="printClassList()">
                                                <i class="bi bi-printer-fill"></i> Print Class List
                                            </button>
                                        </div>
                                    </div>
                                    <h1 class="bold"><i class="bi bi-person-circle icon"></i> Teacher</h1>
                                    <h2 class="view-person"><i class="bi bi-person icon"></i> <?php echo $teacher ?> </h2>
                                    <div class="d-flex align-items-center">
                                        <h1 class="mb-4 bold"><i class="bi bi-people icon"></i> Students</h1>
                                        <div class="ms-auto" aria-hidden="true">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#enrollStudentModal">
                                                <i class="bi bi-plus-circle-fill"></i> Add Student
                                            </button>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="enrollStudentModal" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Enroll Student in
                                                        Class</h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Form to enroll student in class -->
                                                    <form
                                                        action="processes/teachers/classes/enrollStudent.php?class_id=<?php echo $_GET['class_id'] ?>"
                                                        method="POST">
                                                        <!-- Search bar to select a student -->
                                                        <div class="mb-3">
                                                            <label for="studentSearch" class="form-label">Search
                                                                Student</label>
                                                            <input type="text" id="studentSearch" class="form-control"
                                                                placeholder="Search for student...">
                                                            <ul id="studentResults" class="list-group mt-2"
                                                                style="display:none;"></ul>
                                                        </div>

                                                        <!-- Hidden input to store student ID -->
                                                        <input type="hidden" name="student_id" id="selectedStudentId"
                                                            required>

                                                        <!-- Show enrolled students for this class -->
                                                        <div class="mt-3">
                                                            <label for="enrolledStudents" class="form-label">Already
                                                                Enrolled Students</label>
                                                            <?php
                                                            if (isset($_GET['class_id'])) {
                                                                $class_id = $_GET['class_id'];
                                                                try {
                                                                    // Fetch students who are already enrolled in this class
                                                                    $enrolledStmt = $pdo->prepare(
                                                                        "SELECT s.fullName, se.student_id
                                     FROM students_enrollments se
                                     JOIN students s ON se.student_id = s.student_id
                                     WHERE se.class_id = :class_id"
                                                                    );
                                                                    $enrolledStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                                    $enrolledStmt->execute();
                                                                    $enrolledStudents = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);
                                                                } catch (PDOException $e) {
                                                                    echo "Error: " . $e->getMessage();
                                                                }
                                                                if (!empty($enrolledStudents)) {
                                                                    echo "<ul>";
                                                                    foreach ($enrolledStudents as $student) {
                                                                        echo "<li>" . htmlspecialchars($student['fullName']) . "</li>";
                                                                    }
                                                                    echo "</ul>";
                                                                } else {
                                                                    echo "<p>No students are enrolled in this class yet.</p>";
                                                                }
                                                            }
                                                            ?>
                                                        </div>

                                                        <!-- Modal Footer -->
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Enroll
                                                                Student</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>



                                    <script>
                                        document.getElementById('studentSearch').addEventListener('input', function() {
                                            const searchTerm = this.value.trim().toLowerCase();
                                            const resultContainer = document.getElementById('studentResults');
                                            const selectedStudentId = document.getElementById('selectedStudentId');

                                            if (searchTerm.length >= 2) { // Start searching after two characters are typed
                                                // Fetch search results using AJAX
                                                fetch('searchStudent.php?searchTerm=' + encodeURIComponent(searchTerm) + '&class_id=<?php echo $_GET["class_id"] ?>')
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        resultContainer.innerHTML = ''; // Clear previous results
                                                        if (data.length > 0) {
                                                            resultContainer.style.display = 'block'; // Display results container
                                                            data.forEach(student => {
                                                                const li = document.createElement('li');
                                                                li.classList.add('list-group-item');
                                                                li.textContent = student.fullName + ' (' + student.course + ' - ' + student.year_level + ')';
                                                                li.dataset.studentId = student.student_id;
                                                                // Select student from list
                                                                li.addEventListener('click', function() {
                                                                    selectedStudentId.value = this.dataset.studentId; // Update hidden input with student ID
                                                                    document.getElementById('studentSearch').value = this.textContent; // Set input value to selected student
                                                                    resultContainer.style.display = 'none'; // Hide results after selection
                                                                });
                                                                resultContainer.appendChild(li);
                                                            });
                                                        } else {
                                                            // No students found
                                                            resultContainer.style.display = 'block'; // Display results container
                                                            const noResultsMessage = document.createElement('li');
                                                            noResultsMessage.classList.add('list-group-item', 'text-muted');
                                                            noResultsMessage.textContent = 'No Students Found';
                                                            resultContainer.appendChild(noResultsMessage);
                                                        }
                                                    });
                                            } else {
                                                resultContainer.style.display = 'none'; // Hide results if search term is too short
                                            }
                                        });
                                    </script>


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
                                                echo '<a href="processes/teachers/classes/unenrollStudent.php?class_id=' . $class_id . '&student_id=' . $student_id . '" class="btn btn-danger btn-sm">Unenroll</a>';
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
                                    <div id="classList" style="display: none; font-family: Arial;">
                                        <!-- Header Section -->
                                        <div style="text-align: center; margin-bottom: 20px; font-family: Arial;"">
        <div style=" display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                            <img src="external/img/ADDU_logo-removebg-preview.png" alt="ADDU Logo"
                                                style="height: 80px; width: auto;">
                                            <div>
                                                <h3 style="margin: 0;">Ateneo de Davao University</h3>
                                                <h4 style="margin: 0;">College of Computing Studies</h4>
                                            </div>
                                            <img src="external/img/ccs_logo-removebg-preview.png" alt="CCS Logo"
                                                style="height: 80px; width: auto;">
                                        </div>
                                    </div>

                                    <!-- Class Details Section -->
                                    <div
                                        style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 16px; background-color: #f4f4f4; padding: 15px; border-radius: 8px; font-family: Arial;">
                                        <div>
                                            <h4><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                    Teacher:</b>
                                                <span><?php echo htmlspecialchars($classData['teacher'] ?? 'Not Assigned'); ?></span>
                                            </h4>
                                            <h4><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                                <span><?php echo htmlspecialchars($classData['subject'] ?? 'No Subject'); ?></span>
                                            </h4>
                                            <h4><b><i class="bi bi-building" style="margin-right: 5px;"></i> Year and
                                                    Section:</b>
                                                <span><?php echo htmlspecialchars($classData['name'] ?? 'Not Available'); ?></span>
                                            </h4>
                                        </div>
                                        <div>
                                            <h4><b><i class="bi bi-calendar3" style="margin-right: 5px;"></i> Semester:</b>
                                                <span><?php echo htmlspecialchars($classData['semester'] ?? 'No Semester'); ?></span>
                                            </h4>
                                            <h4><b><i class="bi bi-calendar-range" style="margin-right: 5px;"></i> School
                                                    Year:</b>
                                                <span><?php echo $schoolYear ?> - <?php echo $schoolYear + 1 ?></span>
                                            </h4>
                                        </div>
                                    </div>

                                    <!-- Class List Title -->
                                    <h4 style="text-align: center; margin-bottom: 20px; font-family: Arial;">Class List</h4>

                                    <!-- Student List Section -->
                                    <div style="border: 1px solid #ddd; border-radius: 8px; overflow-x: auto;">
                                        <table
                                            style="width: 100%; border-collapse: collapse; padding: 10px; font-family: Arial;">
                                            <thead>
                                                <tr style="background-color: #f1f1f1; text-align: left; height: 40px;">
                                                    <th style="padding-left: 10px;">#</th>
                                                    <th>Full Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = :class_id");
                                                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                $stmt->execute();
                                                $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                if ($enrolledStudents) {
                                                    $counter = 1; // Used to number the list
                                                    foreach ($enrolledStudents as $enrollment) {
                                                        $student_id = $enrollment['student_id'];

                                                        $studentStmt = $pdo->prepare("SELECT fullName FROM students WHERE student_id = :student_id");
                                                        $studentStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                                                        $studentStmt->execute();
                                                        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

                                                        if ($student) {
                                                            echo '<tr style="border-bottom: 1px solid #ddd;">';
                                                            echo '<td style="padding-left: 10px;">' . $counter . '</td>';
                                                            echo '<td>' . htmlspecialchars($student['fullName']) . '</td>';
                                                            echo '</tr>';
                                                            $counter++;
                                                        }
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="2" style="text-align: center;">No students enrolled in this class.</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                            </div>

                            <script>
                                function printClassList() {
                                    const classList = document.getElementById('classList').innerHTML;
                                    const newWindow = window.open('', '', 'height=500, width=800');
                                    newWindow.document.write('<html><head><title>Class List</title></head><body>');
                                    newWindow.document.write(classList);
                                    newWindow.document.write('</body></html>');
                                    newWindow.document.close();
                                    newWindow.print();
                                }
                            </script>

                        <?php
                                } else if ($url == 'activity') { ?>
                            <div class="container-fluid">
                                <div class="d-flex align-items-center">
                                    <h1 class="mb-4 bold">Activities</h1>
                                    <div class="ms-auto">
                                        <button class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#createActivityModal">Create Activity</button>
                                    </div>
                                </div>

                                <!-- Category Buttons -->
                                <div id="filterStatus" class="mb-3 text-muted">Showing all activities</div>
                                <div class="container-fluid my-5">
                                    <!-- Filter Buttons -->

                                    <div class="d-flex align-items-center">
                                        <div class="mb-3">
                                            <button class="btn btn-outline-primary"
                                                onclick="filterActivities('all')">All</button>
                                            <button class="btn btn-outline-primary"
                                                onclick="filterActivities('quiz')">Quizzes</button>
                                            <button class="btn btn-outline-primary"
                                                onclick="filterActivities('activity')">Activities</button>
                                            <button class="btn btn-outline-primary"
                                                onclick="filterActivities('project')">Projects</button>
                                            <button class="btn btn-outline-primary"
                                                onclick="filterActivities('exam')">Exams</button>
                                        </div>

                                    </div>


                                    <div id="activityList" class="container-fluid">


                                    </div>
                                </div>

                                <script>
                                    $(document).ready(function() {
                                        // Replace with your actual class_id and subject_id
                                        const classId = '<?php echo $class_id; ?>'; // Get from PHP
                                        const subjectId = '<?php echo $subject_id; ?>'; // Get from PHP

                                        // Fetch activities
                                        $.ajax({
                                            type: 'GET',
                                            url: 'fetch_activities.php?class_id=' + classId + '&subject_id=' + subjectId,
                                            success: function(response) {
                                                console.log(response); // Log the response to see its structure

                                                // Check if the activities array exists
                                                if (response.activities && Array.isArray(response.activities)) {
                                                    // Clear existing activity list
                                                    $('#activityList').empty();

                                                    // Create separate sections for Midterms and Finals
                                                    const midtermSection = $('<div>').addClass('term-section').attr('id', 'midterms');
                                                    const finalsSection = $('<div>').addClass('term-section').attr('id', 'finals');

                                                    // Add headers for the terms
                                                    midtermSection.append('<h3 class="bold">Midterms</h3>');
                                                    finalsSection.append('<h3 class="bold">Finals</h3>');

                                                    let midtermAdded = false; // Flag to track midterm activities
                                                    let finalAdded = false; // Flag to track final activities

                                                    // Initialize counts for activity types
                                                    let quizCount = 0,
                                                        activityCount = 0,
                                                        projectCount = 0,
                                                        examCount = 0;

                                                    // Group activities by term and type
                                                    let midtermActivities = {
                                                        quiz: [],
                                                        activity: [],
                                                        project: [],
                                                        exam: []
                                                    };
                                                    let finalActivities = {
                                                        quiz: [],
                                                        activity: [],
                                                        project: [],
                                                        exam: []
                                                    };

                                                    // Loop through the activities and categorize them
                                                    response.activities.forEach(function(activity) {
                                                        // Convert the due_date to a readable format
                                                        const dueDate = new Date(activity.due_date);
                                                        const formattedDate = dueDate.toLocaleDateString('en-US', {
                                                            weekday: 'long',
                                                            year: 'numeric',
                                                            month: 'long',
                                                            day: 'numeric'
                                                        });

                                                        const activityCard = `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="row mb-3 activity" data-type="${activity.type}" data-term="${activity.term}">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="col">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="card shadow-sm">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="card-body d-flex align-items-center justify-content-between">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <h5 class="card-title mb-1">${activity.title}</h5>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <p class="card-text mb-1">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <span class="badge bg-primary">Type: ${activity.type.charAt(0).toUpperCase() + activity.type.slice(1)}</span>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <p class="mb-0"><strong>Due Date:</strong> ${formattedDate} on ${activity.due_time}</p>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <button class="btn btn-primary btn-sm me-2" onclick="viewActivity(${activity.id})">View</button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <button class="btn btn-info btn-sm me-2" onclick="editActivity(${activity.id})">Edit</button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <button class="btn btn-danger btn-sm" onclick="deleteActivity(${activity.id}, this)">Delete</button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                `;

                                                        // Categorize by term and type
                                                        let midtermQuizCount = 0,
                                                            midtermActivityCount = 0,
                                                            midtermProjectCount = 0,
                                                            midtermExamCount = 0;
                                                        let finalQuizCount = 0,
                                                            finalActivityCount = 0,
                                                            finalProjectCount = 0,
                                                            finalExamCount = 0;

                                                        if (activity.term.toLowerCase() === 'midterm') {
                                                            midtermActivities[activity.type].push(activityCard);
                                                            if (activity.type === 'quiz') quizCount++;
                                                            midtermQuizCount++;
                                                            if (activity.type === 'activity') activityCount++;
                                                            midtermActivityCount++;
                                                            if (activity.type === 'project') projectCount++;
                                                            midtermProjectCount++;
                                                            if (activity.type === 'exam') examCount++;
                                                            midtermExamCount++;
                                                            midtermAdded = true;
                                                        } else if (activity.term.toLowerCase() === 'final') {

                                                            finalActivities[activity.type].push(activityCard);
                                                            if (activity.type === 'quiz') quizCount++;
                                                            finalQuizCount++;
                                                            if (activity.type === 'activity') activityCount++;
                                                            finalActivityCount++;
                                                            if (activity.type === 'project') projectCount++;
                                                            finalProjectCount++;
                                                            if (activity.type === 'exam') examCount++;
                                                            finalExamCount++;
                                                            finalAdded = true;
                                                        }
                                                    });

                                                    // Add activity sections for Midterms
                                                    if (midtermAdded) {
                                                        // Display quizzes if available
                                                        if (midtermActivities.quiz.length > 0) {
                                                            midtermSection.append(`<h4>Quizzes (${midtermActivities.quiz.length})</h4>`);
                                                            midtermActivities.quiz.forEach(card => midtermSection.append(card));
                                                        }
                                                        // Display activities if available
                                                        if (midtermActivities.activity.length > 0) {
                                                            midtermSection.append(`<h4>Activities (${midtermActivities.activity.length})</h4>`);
                                                            midtermActivities.activity.forEach(card => midtermSection.append(card));
                                                        }
                                                        // Display projects if available
                                                        if (midtermActivities.project.length > 0) {
                                                            midtermSection.append(`<h4>Projects (${midtermActivities.project.length})</h4>`);
                                                            midtermActivities.project.forEach(card => midtermSection.append(card));
                                                        }
                                                        // Display exams if available
                                                        if (midtermActivities.exam.length > 0) {
                                                            midtermSection.append(`<h4>Exams (${midtermActivities.exam.length})</h4>`);
                                                            midtermActivities.exam.forEach(card => midtermSection.append(card));
                                                        }
                                                    }

                                                    // Add activity sections for Finals
                                                    if (finalAdded) {
                                                        // Reset counts for finals
                                                        let finalQuizCount = 0,
                                                            finalActivityCount = 0,
                                                            finalProjectCount = 0,
                                                            finalExamCount = 0;

                                                        // Display quizzes if available
                                                        if (finalActivities.quiz.length > 0) {
                                                            finalsSection.append(`<h4>Quizzes (${finalActivities.quiz.length})</h4>`);
                                                            finalActivities.quiz.forEach(card => finalsSection.append(card));
                                                        }
                                                        // Display activities if available
                                                        if (finalActivities.activity.length > 0) {
                                                            finalsSection.append(`<h4>Activities (${finalActivities.activity.length})</h4>`);
                                                            finalActivities.activity.forEach(card => finalsSection.append(card));
                                                        }
                                                        // Display projects if available
                                                        if (finalActivities.project.length > 0) {
                                                            finalsSection.append(`<h4>Projects (${finalActivities.project.length})</h4>`);
                                                            finalActivities.project.forEach(card => finalsSection.append(card));
                                                        }
                                                        // Display exams if available
                                                        if (finalActivities.exam.length > 0) {
                                                            finalsSection.append(`<h4>Exams (${finalActivities.exam.length})</h4>`);
                                                            finalActivities.exam.forEach(card => finalsSection.append(card));
                                                        }
                                                    }

                                                    // Display a message if no activities were added
                                                    if (!midtermAdded) {
                                                        midtermSection.append('<p class="text-muted">No midterm activities available.</p>');
                                                    }
                                                    if (!finalAdded) {
                                                        finalsSection.append('<p class="text-muted">No final activities available.</p>');
                                                    }

                                                    // Append sections to the activity list container
                                                    $('#activityList').append(midtermSection);
                                                    $('#activityList').append(finalsSection);

                                                    // Display course requirements and stats
                                                    displayCourseRequirements(response.requirements, response.isLaboratory, response.activities, quizCount, activityCount, projectCount, examCount);
                                                } else {
                                                    console.error('No activities found or unexpected response structure:', response);
                                                    $('#activityList').append('<p class="text-muted">No activities found.</p>');
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error fetching activities:', error);
                                            }
                                        });

                                        // Function to display the course requirements and stats
                                        function displayCourseRequirements(requirements, isLaboratory, activities, quizCount, activityCount, projectCount, examCount) {
                                            let requirementsHTML = `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="row mt-5">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <!-- Course Requirements Section -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="col-md-6">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="requirements-section">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <h4 class="text-center mb-4 bold">Course Requirements</h4>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <!-- Major Written Exams -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="alert alert-info">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <strong>Major Written Exams:</strong> 2 (Midterms and Finals)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <!-- Weekly Laboratory Exercises -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ${isLaboratory ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="alert alert-success">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <strong>Laboratory Activities:</strong> Weekly (for laboratory classes only)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ` : ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ${isLaboratory ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="alert alert-primary">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <strong>Laboratory Projects:</strong> Weekly (for laboratory classes only)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ` : ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <!-- Quizzes Requirement -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          ${!isLaboratory ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="alert alert-warning">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <strong>Quizzes Requirement:</strong> At least 3 (3 Midterms, 3 Finals)
                                                                                                                                                                                                                                                                            
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             ` : ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
        
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <!-- Current Stats Section -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div class="col-md-6">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="stats-section">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <h4 class="text-center mb-4 bold">Current Academic Stats</h4>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <!-- Stats Info -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ${!isLaboratory ? `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <li><strong>Quizzes:</strong> ${quizCount} quizzes</li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <li>  
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <div class="mb-3">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <strong>Current Quizzes:</strong> Midterm: ${requirements.midterm_quizzes || 0}, Final: ${requirements.final_quizzes || 0}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ` : ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <li><strong>Activities:</strong> ${activityCount} activities</li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <li><strong>Projects:</strong> ${projectCount} projects</li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <li><strong>Exams:</strong> ${requirements.midterm_exams + requirements.final_exams} exams</li>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </ul>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <hr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `;
                                            $('#activityList').prepend(requirementsHTML);
                                        }
                                    });
                                </script>





                                <script>
                                    // Function to filter activities
                                    function filterActivities(type) {
                                        const activities = document.querySelectorAll('.activity');
                                        const filterStatus = document.getElementById('filterStatus');

                                        let filterText = 'Showing all activities'; // Default message

                                        activities.forEach(activity => {
                                            if (type === 'all' || activity.getAttribute('data-type') === type) {
                                                activity.style.display = 'flex';
                                            } else {
                                                activity.style.display = 'none';
                                            }
                                        });

                                        // Update filter status message
                                        switch (type) {
                                            case 'all':
                                                filterText = 'Showing all activities';
                                                break;
                                            case 'quiz':
                                                filterText = 'Filtered by: Quizzes';
                                                break;
                                            case 'activity':
                                                filterText = 'Filtered by: Activities';
                                                break;
                                            case 'project':
                                                filterText = 'Filtered by: Projects';
                                                break;
                                            case 'exam':
                                                filterText = 'Filtered by: Exams';
                                                break;
                                            default:
                                                filterText = 'Showing all activities';
                                        }

                                        filterStatus.textContent = filterText;
                                    }
                                </script>

                                <!-- Create Activity Modal -->
                                <div class="modal fade" id="createActivityModal" tabindex="-1"
                                    aria-labelledby="createActivityModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="createActivityForm" enctype="multipart/form-data" method="POST"
                                                action="processes/teachers/assessments/add.php">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="createActivityModalLabel">Create New
                                                        Activity</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="activityTitle" class="form-label">Title</label>
                                                        <input type="text" class="form-control" id="activityTitle"
                                                            name="title" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="activityType" class="form-label">Type</label>
                                                        <select class="form-select" id="activityType" name="type"
                                                            required>
                                                            <option value="quiz">Quiz</option>
                                                            <option value="activity">Activity</option>
                                                            <option value="project">Project</option>
                                                            <option value="exam">Exam</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="activityTerm" class="form-label">Term</label>
                                                        <select class="form-select" id="activityTerm" name="term"
                                                            required>
                                                            <option value="midterm" default>Midterms</option>
                                                            <option value="final">Finals</option>

                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="activityMessage" class="form-label">Message</label>
                                                        <textarea class="form-control" id="activityMessage"
                                                            name="message" rows="3" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="activityAttachment" class="form-label">File
                                                            Attachment</label>
                                                        <input type="file" class="form-control" id="activityAttachment"
                                                            name="attachment">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="dueDate" class="form-label">Due Date</label>
                                                        <input type="date" class="form-control" id="dueDate"
                                                            name="due_date" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="dueTime" class="form-label">Due Time</label>
                                                        <input type="time" class="form-control" id="dueTime"
                                                            name="due_time" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="minPoints" class="form-label">Minimum
                                                            Points</label>
                                                        <input type="number" class="form-control" id="minPoints"
                                                            name="min_points" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="maxPoints" class="form-label">Maximum
                                                            Points</label>
                                                        <input type="number" class="form-control" id="maxPoints"
                                                            name="max_points" required>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="class_id"
                                                    value="<?php echo $_GET['class_id'] ?>">
                                                <input type="hidden" name="subject_id"
                                                    value="<?php echo $_GET['subject_id'] ?>">

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Create</button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>



                                <!-- Edit Activity Modal -->
                                <div class="modal fade" id="editActivityModal" tabindex="-1"
                                    aria-labelledby="editActivityModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editActivityForm" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editActivityModalLabel">Edit
                                                        Activity</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" id="editActivityId" name="id">
                                                    <!-- Hidden field for the activity ID -->
                                                    <div class="mb-3">
                                                        <label for="editActivityTitle" class="form-label">Title</label>
                                                        <input type="text" class="form-control" id="editActivityTitle"
                                                            name="title" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editActivityType" class="form-label">Type</label>
                                                        <select class="form-select" id="editActivityType" name="type"
                                                            required>
                                                            <option value="quiz">Quiz</option>
                                                            <option value="activity">Activity</option>
                                                            <option value="project">Project</option>
                                                            <option value="exam">Exam</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editActivityMessage"
                                                            class="form-label">Message</label>
                                                        <textarea class="form-control" id="editActivityMessage"
                                                            name="message" rows="3" required></textarea>
                                                    </div>
                                                    <div id="attachmentContainer" class="mb-3">
                                                        <label class="form-label">File Attachments</label>
                                                        <input type="file" class="form-control mb-2"
                                                            name="attachment[]">
                                                    </div>
                                                    <button type="button" class="btn btn-secondary"
                                                        id="addAttachmentButton">Add Another Attachment</button>
                                                    <div class="mb-3">
                                                        <label for="editDueDate" class="form-label">Due Date</label>
                                                        <input type="date" class="form-control" id="editDueDate"
                                                            name="due_date" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editDueTime" class="form-label">Due Time</label>
                                                        <input type="time" class="form-control" id="editDueTime"
                                                            name="due_time" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editMinPoints" class="form-label">Minimum
                                                            Points</label>
                                                        <input type="number" class="form-control" id="editMinPoints"
                                                            name="min_points" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="editMaxPoints" class="form-label">Maximum
                                                            Points</label>
                                                        <input type="number" class="form-control" id="editMaxPoints"
                                                            name="max_points" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>



                                <script>
                                    function editActivity(activityId) {
                                        $.ajax({
                                            type: 'GET',
                                            url: 'fetch_activity_details.php?id=' + activityId,
                                            dataType: 'json',
                                            success: function(response) {
                                                if (response.error) {
                                                    console.error(response.error);
                                                    return; // Handle error if activity not found
                                                }

                                                $('#editActivityId').val(activityId);
                                                $('#editActivityTitle').val(response.title);
                                                $('#editActivityType').val(response.type);
                                                $('#editActivityMessage').val(response.message);
                                                $('#editDueDate').val(response.due_date);
                                                $('#editDueTime').val(response.due_time);
                                                $('#editMinPoints').val(response.min_points);
                                                $('#editMaxPoints').val(response.max_points);

                                                // Clear existing file inputs (if any) before adding new ones
                                                $('#attachmentContainer').find('input[type="file"]').not(':first').remove();

                                                response.attachments.forEach(function(attachment) {
                                                    $('#attachmentContainer').append(`
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="mb-2">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <a href="${attachment.url}" target="_blank">${attachment.file_name}</a>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <button type="button" class="btn btn-danger btn-sm remove-attachment" data-id="${attachment.id}">Remove</button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    `);
                                                });

                                                $('#editActivityModal').modal('show');
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error fetching activity details:', error);
                                            }
                                        });
                                    }

                                    // Event delegation to handle removal of attachments
                                    $(document).on('click', '.remove-attachment', function() {
                                        const attachmentId = $(this).data('id'); // Get the attachment ID from a data attribute

                                        if (confirm('Are you sure you want to remove this attachment?')) {
                                            $.ajax({
                                                type: 'POST',
                                                url: 'remove_attachment.php',
                                                data: {
                                                    attachment_id: attachmentId
                                                },
                                                success: function(response) {
                                                    const data = JSON.parse(response);
                                                    if (data.success) {
                                                        // Display success message using SweetAlert
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Success',
                                                            text: data.success,
                                                            confirmButtonText: 'Okay'
                                                        });
                                                        $(this).parent().remove(); // Remove the attachment link and button
                                                    } else {
                                                        // Display error message using SweetAlert
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Error',
                                                            text: data.error,
                                                            confirmButtonText: 'Okay'
                                                        });
                                                    }
                                                }.bind(this), // Use .bind(this) to maintain context for $(this)
                                                error: function(xhr, status, error) {
                                                    console.error('Error removing attachment:', error);
                                                }
                                            });
                                        }
                                    });

                                    // Handle form submission
                                    $('#editActivityForm').on('submit', function(event) {
                                        event.preventDefault();
                                        const formData = new FormData(this);

                                        $.ajax({
                                            type: 'POST',
                                            url: 'update_activity.php',
                                            data: formData,
                                            contentType: false,
                                            processData: false,
                                            success: function(response) {
                                                const data = JSON.parse(response);
                                                if (data.success) {
                                                    // Display success message using SweetAlert
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Success',
                                                        text: data.success,
                                                        confirmButtonText: 'Okay'
                                                    });
                                                    $('#editActivityModal').modal('hide');
                                                    window.location.reload();
                                                } else {
                                                    // Display error message using SweetAlert
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: data.error,
                                                        confirmButtonText: 'Okay'
                                                    });
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error updating activity:', error);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'An unexpected error occurred. Please try again later.',
                                                    confirmButtonText: 'Okay'
                                                });
                                            }
                                        });
                                    });
                                </script>



                                <!-- View Activity Modal -->
                                <div class="modal fade" id="viewActivityModal" tabindex="-1"
                                    aria-labelledby="viewActivityModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewActivityModalLabel">View Activity
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h5 id="activityTitle" class="text-primary mb-3"></h5>
                                                <p id="activityType" class="mb-1"><strong>Type:</strong> <span
                                                        class="badge bg-info"></span></p>
                                                <p id="activityMessage" class="mb-4"></p>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Due Date:</strong> <span id="activityDueDate"></span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Due Time:</strong> <span id="activityDueTime"></span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Minimum Points:</strong> <span
                                                                id="activityMinPoints"></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Maximum Points:</strong> <span
                                                                id="activityMaxPoints"></span></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Class ID:</strong> <span id="activityClassId"></span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Subject ID:</strong> <span
                                                                id="activitySubjectId"></span></p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Created At:</strong> <span
                                                                id="activityCreatedAt"></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Updated At:</strong> <span
                                                                id="activityUpdatedAt"></span></p>
                                                    </div>
                                                </div>

                                                <h6 class="bold">Message:</h6>
                                                <div class="mt-3">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <p id="message">No message available.</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Attachments Section -->
                                                <h6 class="bold">Attachments:</h6>
                                                <div id="activityAttachments" class="mt-3">
                                                    <!-- Attachments will be dynamically added here -->
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <p>No attachments available.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>





                                <script>
                                    function deleteActivity(activityId) {
                                        Swal.fire({
                                            title: 'Are you sure?',
                                            text: "You won't be able to revert this!",
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#3085d6',
                                            cancelButtonColor: '#d33',
                                            confirmButtonText: 'Yes, delete it!'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Redirect to the delete link with the activityId in the query string
                                                window.location.href = `processes/teachers/assessments/delete.php?activityId=${activityId}`;
                                            }
                                        });
                                    }
                                </script>




                                <script>
                                    function viewActivity(activityId) {
                                        // Make an AJAX request to fetch activity details
                                        $.ajax({
                                            type: 'GET',
                                            url: 'fetch_activity_details.php?id=' +
                                                activityId, // Replace with your PHP script URL
                                            dataType: 'json', // Ensure response is parsed as JSON
                                            success: function(response) {
                                                if (response) {
                                                    // Populate modal with the activity details
                                                    $('#activityId').text(response.id || 'N/A');
                                                    $('#activityTitle').text(response.title ||
                                                        'No title available');
                                                    $('#activityType .badge').text(response.type ? response
                                                        .type.charAt(0).toUpperCase() + response.type
                                                        .slice(1) : 'N/A');
                                                    $('#message').text(response.message ||
                                                        'No message available');
                                                    $('#activityDueDate').text(response.due_date || 'N/A');
                                                    $('#activityDueTime').text(response.due_time || 'N/A');
                                                    $('#activityMinPoints').text(response.min_points ||
                                                        'N/A');
                                                    $('#activityMaxPoints').text(response.max_points ||
                                                        'N/A');
                                                    $('#activityClassId').text(response.class_id || 'N/A');
                                                    $('#activitySubjectId').text(response.subject_id ||
                                                        'N/A');
                                                    $('#activityCreatedAt').text(response.created_at ||
                                                        'N/A');
                                                    $('#activityUpdatedAt').text(response.updated_at ||
                                                        'N/A');

                                                    // Handle attachments
                                                    let attachmentList = '';
                                                    if (response.attachments && response.attachments
                                                        .length > 0) {
                                                        attachmentList = response.attachments.map(function(
                                                            attachment) {
                                                            return '<p><a href="' + attachment
                                                                .file_path + '" target="_blank">' +
                                                                attachment.file_name + '</a></p>';
                                                        }).join('');
                                                    } else {
                                                        attachmentList = '<p>No attachments available.</p>';
                                                    }
                                                    $('#activityAttachments').html(attachmentList);

                                                    // Show the modal
                                                    $('#viewActivityModal').modal('show');
                                                } else {
                                                    console.error('Invalid response data');
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error fetching activity details:', error);
                                                alert(
                                                    'Failed to fetch activity details. Please try again.'
                                                );
                                            }
                                        });
                                    }
                                </script>


                            <?php
                                } else if ($url == 'scores') { ?>
                                <div class="container-fluid">
                                    <h1 class="mb-4 bold">Scores</h1>

                                    <!-- Filter Options -->
                                    <div class="mb-4">
                                        <form id="filterForm" class="row g-3">
                                            <div class="col-md-4">
                                                <label for="studentFilter" class="form-label">Student Name</label>
                                                <input type="text" class="form-control" id="studentFilter"
                                                    placeholder="Enter student name">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="activityTypeFilter" class="form-label">Activity
                                                    Type</label>
                                                <select class="form-select" id="activityTypeFilter">
                                                    <option value="">All</option>
                                                    <option value="Quiz">Quiz</option>
                                                    <option value="Activity">Activity</option>
                                                    <option value="Project">Project</option>
                                                    <option value="Exam">Exam</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="activityNameFilter" class="form-label">Activity
                                                    Name</label>
                                                <input type="text" class="form-control" id="activityNameFilter"
                                                    placeholder="Enter activity name">
                                            </div>

                                            <div class="col-md-4">
                                                <label for="dateFilter" class="form-label">Due Date</label>
                                                <input type="date" class="form-control" id="dateFilter">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="sortOrder" class="form-label">Sort By</label>
                                                <select class="form-select" id="statusFilter">
                                                    <option value="">All</option>
                                                    <option value="Submitted">Submitted</option>
                                                    <option value="Graded">Graded</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Graded Late">Graded Late</option>
                                                    <option value="No Output">No Output</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Grade Table -->
                                    <?php

                                    // Assuming class_id and subject_id are passed as GET parameters or session variables
                                    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
                                    $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;

                                    if ($class_id && $subject_id) {
                                        try {
                                            // Fetch activity submissions joined with activities
                                            $stmt = $pdo->prepare("
            SELECT asub.id, asub.student_id, asub.submission_date, asub.score, asub.status, asub.feedback, a.title, a.type, a.max_points, a.due_date, a.due_time
            FROM activity_submissions asub
            JOIN activities a ON asub.activity_id = a.id
            WHERE a.class_id = :class_id AND a.subject_id = :subject_id
        ");
                                            $stmt->execute(['class_id' => $class_id, 'subject_id' => $subject_id]);
                                            // Fetch all results
                                            $submissions = $stmt->fetchAll();

                                            // Fetch student details (assuming you have a students table)
                                            $student_details = [];
                                            $student_stmt = $pdo->prepare("SELECT id, fullName, student_id, course, year_level, email, password FROM students");
                                            $student_stmt->execute();
                                            while ($row = $student_stmt->fetch()) {
                                                $student_details[$row['student_id']] = $row;
                                            }

                                    ?>
                                            <!-- HTML Table -->
                                            <table class="table table-striped" id="gradesTable">
                                                <thead class="text-center">
                                                    <tr>
                                                        <th>Student Name</th>
                                                        <th>Activity</th>
                                                        <th>Type</th>
                                                        <th>Score</th>
                                                        <th>Total Score</th>
                                                        <th>Due Date</th>
                                                        <th>Date of Submission</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    foreach ($submissions as $submission) {
                                                        $student_info = $student_details[$submission['student_id']] ?? null;
                                                        $student_name = $student_info['fullName'] ?? 'Unknown'; // Use fullName
                                                        $score = !empty($submission['score']) ? $submission['score'] : 'N/A'; // Default if score is empty
                                                        $submission_date = $submission['submission_date'] ? $submission['submission_date'] : 'N/A';
                                                        $status = $submission['status'] ?: 'Pending'; // Default if status is empty
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student_name); ?></td>
                                                            <td><?php echo htmlspecialchars($submission['title']); ?></td>
                                                            <td><?php echo htmlspecialchars(ucfirst($submission['type'])); ?>
                                                            </td>
                                                            <td><span
                                                                    class="score-placeholder"><?php echo htmlspecialchars($score); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($submission['max_points']); ?></td>
                                                            <td>
                                                                <?php
                                                                $formatted_due_date = new DateTime($submission['due_date']);
                                                                $formatted_due_time = new DateTime($submission['due_time']);

                                                                echo $formatted_due_date->format('F j, Y'); // Example: December 8, 2024
                                                                echo ' at ' . $formatted_due_time->format('g:i A'); // Example: 3:45 PM
                                                                ?>
                                                            </td>

                                                            <td>
                                                                <?php
                                                                if ($submission_date && strtotime($submission_date)) {
                                                                    // Only format if submission_date is not null and is a valid date
                                                                    $formatted_date = new DateTime($submission_date);
                                                                    echo $formatted_date->format('F j, Y'); // Example: December 8, 2024
                                                                } else {
                                                                    echo "No submission yet"; // Display "null" if submission_date is empty, invalid, or null
                                                                }
                                                                ?>
                                                            </td>


                                                            <td class="status-cell">
                                                                <?php
                                                                // Check the current status and apply corresponding badge classes
                                                                $status_class = '';
                                                                $status_text = '';

                                                                switch ($submission['status']) {
                                                                    case 'submitted':
                                                                        $status_class = 'bg-primary';  // Green for graded
                                                                        $status_text = 'Submitted';
                                                                        break;
                                                                    case 'graded':

                                                                        $status_class = 'bg-success';  // Green for graded
                                                                        $status_text = 'Graded';
                                                                        break;
                                                                    case 'pending':

                                                                        $status_class = 'bg-warning';  // Green for graded
                                                                        $status_text = 'Pending';
                                                                        break;
                                                                    case 'graded_late':
                                                                        $status_class = 'bg-warning';  // Yellow for graded late
                                                                        $status_text = 'Graded Late';
                                                                        break;
                                                                    case 'none':
                                                                        $status_class = 'bg-secondary';  // Gray for no output
                                                                        $status_text = 'No Output';
                                                                        break;
                                                                    default:
                                                                        $status_class = 'bg-info';  // Blue for unknown status
                                                                        $status_text = 'Unknown';
                                                                        break;
                                                                }
                                                                ?>
                                                                <!-- Display status as a badge -->
                                                                <span
                                                                    class="badge <?php echo $status_class; ?> text-light"><?php echo htmlspecialchars($status_text); ?></span>
                                                            </td>

                                                            </td>
                                                            <td>
                                                                <?php if ($submission['status'] == 'submitted' || $submission['status'] == 'graded' || $submission['status'] == 'graded_late') { ?>
                                                                    <button class="btn btn-success" data-bs-toggle="modal"
                                                                        data-bs-target="#viewGradeModal<?php echo $submission['id'] ?>"><i
                                                                            class="bi bi-eye-fill"></i> View</button>
                                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                                        data-bs-target="#editGradeModal<?php echo $submission['id'] ?>"><i
                                                                            class="bi bi-pen-fill"></i> Edit</button>
                                                                <?php } else { ?>

                                                                    <button class="btn btn-warning"
                                                                        onclick="notifyToPassActivity(<?php echo $submission['id']; ?>, <?php echo $submission['student_id'] ?>)"><i
                                                                            class="bi bi-exclamation-diamond-fill"></i>
                                                                        Notify</button>
                                                                <?php } ?>
                                                            </td>

                                                        </tr>



                                                        <script>
                                                            function notifyToPassActivity(activityId, studentId) {
                                                                const notificationData = {
                                                                    activity_id: activityId,
                                                                    student_id: studentId
                                                                };

                                                                fetch('notify_grade.php', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/json'
                                                                        },
                                                                        body: JSON.stringify(notificationData)
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            Swal.fire({
                                                                                icon: 'success',
                                                                                title: 'Notification Sent!',
                                                                                text: 'The student has been successfully notified to pass the activity.',
                                                                                confirmButtonText: 'OK'
                                                                            });
                                                                        } else {
                                                                            Swal.fire({
                                                                                icon: 'error',
                                                                                title: 'Failed to Send Notification',
                                                                                text: data.error,
                                                                                confirmButtonText: 'Try Again'
                                                                            });
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        console.error("Error sending notification:", error);
                                                                        Swal.fire({
                                                                            icon: 'error',
                                                                            title: 'Error Occurred',
                                                                            text: 'An unexpected error occurred while sending the notification.',
                                                                            confirmButtonText: 'Try Again'
                                                                        });
                                                                    });
                                                            }
                                                        </script>


                                                        <div class="modal fade" id="viewGradeModal<?php echo $submission['id'] ?>"
                                                            tabindex="-1" aria-labelledby="viewGradeModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="viewGradeModalLabel">View
                                                                            Grade</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <!-- View content -->
                                                                        <div class="mb-3">
                                                                            <h5 for="gradeView" class="form-label bold">Score
                                                                            </h5>
                                                                            <p id="gradeView" class="form-control-plaintext">
                                                                                <?php echo htmlspecialchars($submission['score']); ?>
                                                                            </p>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <h5 for="commentsView" class="form-label bold">
                                                                                Comments</h5>
                                                                            <p id="commentsView" class="form-control-plaintext">
                                                                                <?php if (empty($submission['feedback'])) {
                                                                                    echo "No comments added from you.";
                                                                                } else {
                                                                                    echo nl2br(htmlspecialchars($submission['feedback']));
                                                                                } ?>
                                                                            </p>
                                                                        </div>

                                                                        <div class="mb-3">
                                                                            <h5 for="statusView" class="form-label bold">Status
                                                                            </h5>
                                                                            <p id="statusView" class="form-control-plaintext">
                                                                                <?php
                                                                                switch ($submission['status']) {
                                                                                    case 'submitted':
                                                                                        echo 'Submitted for Grading';
                                                                                        break;
                                                                                    case 'graded':
                                                                                        echo 'Graded';
                                                                                        break;
                                                                                    case 'graded_late':
                                                                                        echo 'Graded Late';
                                                                                        break;
                                                                                    case 'none':
                                                                                        echo 'No Output';
                                                                                        break;
                                                                                    default:
                                                                                        echo 'Unknown';
                                                                                }
                                                                                ?>
                                                                            </p>
                                                                        </div>

                                                                        <!-- Check for file attachment -->
                                                                        <div class="mb-3">
                                                                            <h5 for="submissionFileView" class="form-label bold">
                                                                                Submission</h5>
                                                                            <?php if (!empty($submission['file_path'])): ?>
                                                                                <p id="submissionFileView"
                                                                                    class="form-control-plaintext">
                                                                                    <a href="<?php echo htmlspecialchars($submission['file_path']); ?>"
                                                                                        target="_blank">
                                                                                        View Attached File
                                                                                    </a>
                                                                                </p>
                                                                            <?php else: ?>
                                                                                <p id="submissionFileView"
                                                                                    class="form-control-plaintext text-muted">
                                                                                    Nothing attached
                                                                                </p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>




                                                        <div class="modal fade" id="editGradeModal<?php echo $submission['id'] ?>"
                                                            tabindex="-1" aria-labelledby="editGradeModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="editGradeModalLabel">Edit
                                                                            Grade</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <!-- Form content -->
                                                                        <form method="POST" action="update_grade.php">
                                                                            <input type="hidden" name="student_id"
                                                                                value="<?php echo $submission['student_id']; ?>">

                                                                            <input type="hidden" name="submission_id"
                                                                                value="<?php echo $submission['id']; ?>">
                                                                            <!-- Hidden field to store submission ID -->

                                                                            <div class="mb-3">
                                                                                <label for="gradeInput"
                                                                                    class="form-label">Score</label>
                                                                                <input type="number" class="form-control"
                                                                                    id="gradeInput" name="score"
                                                                                    value="<?php echo htmlspecialchars($submission['score']); ?>"
                                                                                    placeholder="Enter score" required>
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label for="comments"
                                                                                    class="form-label">Comments</label>
                                                                                <textarea class="form-control" id="comments"
                                                                                    name="comments" rows="3"
                                                                                    required><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                                                                            </div>

                                                                            <!-- Dropdown for Status -->
                                                                            <div class="mb-3">
                                                                                <label for="status"
                                                                                    class="form-label">Status</label>
                                                                                <select class="form-select" id="status"
                                                                                    name="status" required>
                                                                                    <option value="graded" <?php echo $submission['status'] === 'graded' ? 'selected' : ''; ?>>
                                                                                        Graded
                                                                                    </option>
                                                                                    <option value="graded_late" <?php echo $submission['status'] === 'graded_late' ? 'selected' : ''; ?>>
                                                                                        Graded Late
                                                                                    </option>
                                                                                    <option value="none" <?php echo $submission['status'] === 'none' ? 'selected' : ''; ?>>
                                                                                        No Output
                                                                                    </option>
                                                                                </select>
                                                                            </div>

                                                                            <button type="submit" class="btn btn-primary">Save
                                                                                Changes</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    <?php
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>

                                    <?php
                                        } catch (Exception $e) {
                                            echo "Error: " . $e->getMessage();
                                        }
                                    } else {
                                        echo "Invalid class or subject ID.";
                                    }

                                    ?>

                                    <!-- No Results Message -->
                                    <div id="noResultsMessage" class="alert alert-warning d-none" role="alert">
                                        No grades found matching your criteria.
                                    </div>
                                </div>
                                <!-- Modal for Editing Grade -->



                                <script>
                                    // Add event listeners for each filter input
                                    document.getElementById('studentFilter').addEventListener('input', applyFilters);
                                    document.getElementById('activityTypeFilter').addEventListener('change', applyFilters);
                                    document.getElementById('activityNameFilter').addEventListener('input', applyFilters);
                                    document.getElementById('dateFilter').addEventListener('input', applyFilters);
                                    document.getElementById('statusFilter').addEventListener('change', applyFilters);

                                    function applyFilters() {
                                        // Get filter values
                                        const studentFilter = document.getElementById('studentFilter').value.toLowerCase();
                                        const activityTypeFilter = document.getElementById('activityTypeFilter').value;
                                        const activityNameFilter = document.getElementById('activityNameFilter').value.toLowerCase();
                                        const dateFilter = document.getElementById('dateFilter').value;
                                        const statusFilter = document.getElementById('statusFilter').value;

                                        // Get table and rows
                                        const table = document.getElementById('gradesTable').getElementsByTagName('tbody')[0];
                                        const rows = Array.from(table.getElementsByTagName('tr'));

                                        let hasResults = false;

                                        rows.forEach(row => {
                                            // Extract row data
                                            const studentName = row.cells[0].textContent.toLowerCase();
                                            const activityName = row.cells[1].textContent.toLowerCase();
                                            const activityType = row.cells[2].textContent;
                                            const dueDate = row.cells[5].textContent;
                                            const status = row.querySelector('.status-cell').textContent.trim();

                                            // Check matches for each filter
                                            const matchesStudent = studentName.includes(studentFilter);
                                            const matchesType = !activityTypeFilter || activityType === activityTypeFilter;
                                            const matchesActivityName = activityName.includes(activityNameFilter);
                                            const matchesDate = !dateFilter || (new Date(dueDate) <= new Date(dateFilter));
                                            const matchesStatus = !statusFilter || status === statusFilter;

                                            // Display row if all filters match
                                            if (matchesStudent && matchesType && matchesActivityName && matchesDate && matchesStatus) {
                                                row.style.display = ''; // Show matching row
                                                hasResults = true;
                                            } else {
                                                row.style.display = 'none'; // Hide non-matching row
                                            }
                                        });

                                        // Handle case where all filters are cleared
                                        const allFiltersCleared = !studentFilter &&
                                            !activityTypeFilter &&
                                            !activityNameFilter &&
                                            !dateFilter &&
                                            !statusFilter;

                                        if (allFiltersCleared) {
                                            rows.forEach(row => row.style.display = ''); // Show all rows
                                            hasResults = true; // Reset to ensure "No Results" is hidden
                                        }

                                        // Show or hide the "no results" message
                                        document.getElementById('noResultsMessage').classList.toggle('d-none', hasResults);
                                    }
                                </script>


                            <?php } else if ($url == 'grades') { ?>

                                <?php
                                    // Assuming class_id is passed via GET or POST
                                    $class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : null;

                                    if ($class_id) {
                                        try {

                                            // Check for 6 quizzes (only for regular classes)
                                            if ($type != 'laboratory') {
                                                $quizStmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE class_id = :class_id AND type = 'quiz'");
                                                $quizStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                                $quizStmt->execute();
                                                $quizCount = $quizStmt->fetchColumn();
                                            } else {
                                                $quizCount = 0;  // No quizzes needed for laboratory classes
                                            }




                                            // Check for activity
                                            $activityStmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE class_id = :class_id AND type = 'activity'");
                                            $activityStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                            $activityStmt->execute();
                                            $activityCount = $activityStmt->fetchColumn();



                                            // Check for attendance
                                            $attendanceStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE class_id = :class_id");
                                            $attendanceStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                            $attendanceStmt->execute();
                                            $attendanceCount = $attendanceStmt->fetchColumn();



                                            // Check for midterm and final exams
                                            $examStmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE class_id = :class_id AND type = 'exam' AND term IN ('midterm', 'final')");
                                            $examStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                            $examStmt->execute();
                                            $examCount = $examStmt->fetchColumn();

                                            // Check for projects 
                                            $projectStmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE class_id = :class_id AND type = 'project'");
                                            $projectStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                                            $projectStmt->execute();
                                            $projectCount = $projectStmt->fetchColumn();



                                            // Check if all conditions are met based on class type
                                            if ($type == 'Laboratory') {

                                                // For laboratory class, only check for activity, attendance, and exams
                                                $allConditionsMet = ($activityCount >= 1 && $attendanceCount >= 1 && $examCount >= 1 && $projectCount >= 1);
                                            } else {
                                                // For regular class, check for quizzes, activity, attendance, and exams
                                                $allConditionsMet = ($quizCount > 2 && $activityCount >= 1 && $attendanceCount >= 1 && $examCount >= 1);
                                            }
                                        } catch (PDOException $e) {
                                            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                                        }
                                    } else {
                                        echo "<p class='error'>Class not found.</p>";
                                    }
                                ?>



                                <div class="student-grades">
                                    <div class="d-flex align-items-center">
                                        <h1 class="mb-4 bold">Enrolled Students and Grades</h1>

                                    </div>

                                    <?php
                                    if (isset($_GET['class_id'])) {
                                        $class_id = (int) $_GET['class_id']; // Ensure class_id is an integer
                                        $action = isset($_GET['action']) ? $_GET['action'] : ''; // Get action from URL (either 'save' or 'submit')

                                        try {
                                            // SQL query to fetch students with their grades and status for the specified class
                                            $stmt = $pdo->prepare(
                                                "SELECT s.student_id, s.fullName, s.gender, sg.midterm_grade, sg.final_grade, sg.status
                 FROM students s
                 LEFT JOIN student_grades sg ON s.student_id = sg.student_id 
                 AND sg.class_id = :class_id1
                 JOIN students_enrollments se ON s.student_id = se.student_id
                 WHERE se.class_id = :class_id2"
                                            );
                                            $stmt->bindParam(':class_id1', $class_id, PDO::PARAM_INT); // Bind the parameter securely
                                            $stmt->bindParam(':class_id2', $class_id, PDO::PARAM_INT); // Bind the parameter securely
                                            $stmt->execute();
                                            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (PDOException $e) {
                                            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                                        }

                                        // Check if there are students enrolled in the class
                                        if (!empty($students)) {
                                            // Separate male and female students
                                            $maleStudents = [];
                                            $femaleStudents = [];

                                            foreach ($students as $student) {
                                                if ($student['gender'] == 'Male') {
                                                    $maleStudents[] = $student;
                                                } elseif ($student['gender'] == 'Female') {
                                                    $femaleStudents[] = $student;
                                                }
                                            }



                                            function renderStudentsWithGrades($students, $gradeOptions, $editable)
                                            {
                                                foreach ($students as $student) {
                                                    $studentId = htmlspecialchars($student['student_id']);
                                                    $fullName = htmlspecialchars($student['fullName']);
                                                    $midtermGrade = htmlspecialchars($student['midterm_grade'] ?? '');
                                                    $finalGrade = htmlspecialchars($student['final_grade'] ?? '');
                                                    $status = htmlspecialchars($student['status']); // Check the status
                                                    // Allow editing only if the status is not 'for_approval'



                                                    // Determine if the fields are editable based on the status
                                                    $isEditable = ($status === 'for_approval') ? false : true;

                                                    // Render the table row
                                                    echo "<tr>
        <td>$fullName</td>
        <td>
            Midterm:
            <select name='grades[$studentId][midterm]' class='grade-select' " . ($isEditable ? "" : "disabled") . ">
                " . renderGradeOptions($midtermGrade, $gradeOptions) . "
            </select>
            " . (!$isEditable ? "<input type='hidden' name='grades[$studentId][midterm]' value='$midtermGrade'>" : "") . "
        </td>
        <td>
            Final:
            <select name='grades[$studentId][final]' class='grade-select' " . ($isEditable ? "" : "disabled") . ">
                " . renderGradeOptions($finalGrade, $gradeOptions) . "
            </select>
            " . (!$isEditable ? "<input type='hidden' name='grades[$studentId][final]' value='$finalGrade'>" : "") . "
        </td>
      </tr>";
                                                }
                                            }

                                            // Function to render grade options
                                            // Function to render grade options while including the student's current grade
                                            function renderGradeOptions($selectedGrade, $gradeOptions = [])
                                            {
                                                $optionsHtml = '';

                                                // Always add the student's current grade as the first option
                                                if (!empty($selectedGrade)) {
                                                    $optionsHtml .= "<option value='$selectedGrade' selected>$selectedGrade</option>";
                                                }

                                                // Add other grade options while avoiding duplicates
                                                foreach ($gradeOptions as $option) {
                                                    if ($option !== $selectedGrade) { // Avoid adding the same grade twice
                                                        $optionsHtml .= "<option value='$option'>$option</option>";
                                                    }
                                                }

                                                return $optionsHtml;
                                            }


                                            // Begin rendering students
                                            if (!empty($maleStudents) || !empty($femaleStudents)) {
                                                echo "<form method='POST' action='save_grades.php?class_id=" . $class_id . "&subject_id=" . $subject_id . "'>";

                                                // Display Male Students
                                                if (!empty($maleStudents)) {
                                                    echo "<h4 class='text-center bold'>Male Students</h4>";
                                                    echo "<table class='grades-table'>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Midterm Grade</th>
                            <th>Final Grade</th>
                        </tr>
                    </thead>
                    <tbody>";
                                                    renderStudentsWithGrades($maleStudents, ['3.0', 'N/A', 'INC', 'AW', 'UW'], $action === 'save');
                                                    echo "</tbody>
                </table>";
                                                } else {
                                                    echo "<p class='no-students text-center'>No male students are enrolled in this class.</p>";
                                                }

                                                // Display Female Students
                                                if (!empty($femaleStudents)) {
                                                    echo "<h4 class='text-center bold'>Female Students</h4>";
                                                    echo "<table class='grades-table'>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Midterm Grade</th>
                            <th>Final Grade</th>
                        </tr>
                    </thead>
                    <tbody>";
                                                    renderStudentsWithGrades($femaleStudents, ['3.0', 'N/A', 'INC', 'AW', 'UW'], $action === 'save');
                                                    echo "</tbody>
                </table>";
                                                } else {
                                                    echo "<p class='no-students text-center'>No female students are enrolled in this class.</p>";
                                                }


                                                // Buttons for saving and submitting grades
                                                echo "<div class='button-group text-center'>
    <br><br>";

                                                // "Save Grades" button is always visible (regardless of conditions)
                                                echo "<button type='submit' name='action' value='save' class='btn btn-primary'>Save Grades</button> &nbsp;";

                                                if ($status == 'for_approval') {

                                                    // If already submitted, show the 'Revert to For Approval' button
                                                    echo "<button type='submit' name='action' value='submit' class='btn btn-warning' disabled >Submit Grades</button> &nbsp;
      <button type='submit' name='action' value='revert' class='btn btn-warning'>Revert to For Approval</button>";
                                                } else {
                                                    // If not submitted, allow the "Submit Grades" button
                                                    echo "<button type='submit' name='action' value='submit' class='btn btn-warning'>Submit Grades</button>";
                                                }
                                                echo "</div>";


                                                echo "</form>";
                                            } else {
                                                echo "<p class='no-students text-center'>No students are enrolled in this class yet.</p>";
                                            }
                                        }
                                    }

                                    ?>

                                    <!-- Button display logic -->

                                    <div class="modal fade" id="alertModal" tabindex="-1"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Class
                                                        requirements are lacking!</h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-secondary">
                                                    <ul>
                                                        <li class="text-secondary">
                                                            Midterm Components Requirement: (3 quizzes, 1 activity,
                                                            attendance/s, 1 or
                                                            more projects and and examination)
                                                        </li>
                                                        <li class="text-secondary">
                                                            Finals Components Requirement: (3 quizzes, 1 activity,
                                                            attendance/s, 1 or
                                                            more projects and and examination)
                                                        </li>
                                                        <li class="text-secondary">
                                                            <b>All</b> necessary components (6 quizzes, 1 activity,
                                                            attendance, 1 or
                                                            more projects and 2 exams)
                                                            must be before being able to submit this to the dean!
                                                        </li>
                                                    </ul>

                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>

                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </div>



                            <?php } else { ?>


                                <div class="container-fluid">
                                    <div class="d-flex align-items-center">
                                        <h2 class="bold">Learning Resources</h2>
                                        <div class="ms-auto" aria-hidden="true">
                                            <button class="btn btn-primary mb-4" data-bs-toggle="modal"
                                                data-bs-target="#uploadResourceModal">
                                                <i class="bi bi-plus-circle-fill"></i> Upload Learning Resource
                                            </button>
                                        </div>
                                    </div>



                                    <?php

                                    // Initialize $resources as an empty array
                                    $resources = [];

                                    // Get class_id and subject_id from the URL
                                    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
                                    $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

                                    if ($class_id > 0 && $subject_id > 0) {
                                        // Fetch learning resources for the given class_id and subject_id
                                        $stmt = $pdo->prepare("SELECT * FROM learning_resources WHERE class_id = :class_id AND subject_id = :subject_id ORDER BY resource_id DESC");
                                        $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
                                        $resources = $stmt->fetchAll();
                                    }

                                    // Initialize counts for each resource type
                                    $countByType = [
                                        'document' => 0,
                                        'video' => 0,
                                        'audio' => 0,
                                        'image' => 0,
                                    ];

                                    // Calculate counts if there are resources
                                    if (!empty($resources)) {
                                        foreach ($resources as $resource) {
                                            $type = strtolower($resource['resource_type']);
                                            if (isset($countByType[$type])) {
                                                $countByType[$type]++;
                                            }
                                        }
                                    }

                                    $countByType = [
                                        'document' => 0,
                                        'video' => 0,
                                        'audio' => 0,
                                        'image' => 0,
                                    ];

                                    // Calculate counts if there are resources
                                    foreach ($resources as $resource) {
                                        $type = strtolower($resource['resource_type']);
                                        if (isset($countByType[$type])) {
                                            $countByType[$type]++;
                                        }
                                    }
                                    ?>

                                    <!-- Display Count Summary -->
                                    <div class="row mb-3">
                                        <div class="col">
                                            <div class="card shadow-sm">
                                                <ul class="list-unstyled mt-3">
                                                    <li>
                                                        <i class="bi bi-file-earmark-text text-info"></i>
                                                        <strong>Total Resources:</strong> <?= count($resources) ?>
                                                    </li>
                                                    <li>
                                                        <i class="bi bi-file-earmark-text text-secondary"></i>
                                                        <strong>Documents:</strong> <?= $countByType['document'] ?>
                                                    </li>
                                                    <li>
                                                        <i class="bi bi-film text-warning"></i>
                                                        <strong>Videos:</strong> <?= $countByType['video'] ?>
                                                    </li>
                                                    <li>
                                                        <i class="bi bi-volume-up text-danger"></i>
                                                        <strong>Audio Files:</strong> <?= $countByType['audio'] ?>
                                                    </li>
                                                    <li>
                                                        <i class="bi bi-image text-success"></i>
                                                        <strong>Images:</strong> <?= $countByType['image'] ?>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filter Controls -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="resourceTypeFilter" class="form-label">Filter by Resource
                                                Type</label>
                                            <select id="resourceTypeFilter" class="form-select">
                                                <option value="">All Types</option>
                                                <option value="document">Document</option>
                                                <option value="video">Video</option>
                                                <option value="audio">Audio</option>
                                                <option value="image">Image</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="resourceSearchFilter" class="form-label">Search Resource
                                                Name</label>
                                            <input type="text" id="resourceSearchFilter" class="form-control"
                                                placeholder="Search by name">
                                        </div>
                                    </div>
                                </div>

                                <!-- Resource List -->
                                <div id="resourceList" class="container-fluid">
                                    <?php if (empty($resources)): ?>
                                        <div class="alert alert-warning" role="alert">
                                            No learning resources available for this class or subject.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($resources as $resource): ?>
                                            <div class="row mb-3 resource-item"
                                                data-resource-type="<?= strtolower($resource['resource_type']) ?>"
                                                data-resource-name="<?= strtolower($resource['resource_name']) ?>">
                                                <div class="col">
                                                    <div class="card shadow-sm">
                                                        <div class="card-body">
                                                            <h5 class="card-title">
                                                                <?php
                                                                // Determine icon based on resource type
                                                                switch (strtolower($resource['resource_type'])) {
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
                                                                        $icon = 'bi-question-circle'; // Default icon for unknown types
                                                                }
                                                                ?>
                                                                <i class="bi <?= $icon ?>"></i>
                                                                <?= htmlspecialchars($resource['resource_name']) ?>
                                                            </h5>
                                                            <p class="card-text">Description:
                                                                <?= htmlspecialchars($resource['resource_description']) ?>
                                                            </p>
                                                            <p class="card-text">Type:
                                                                <?= ucfirst(htmlspecialchars($resource['resource_type'])) ?>
                                                            </p>
                                                            <button class="btn btn-info" data-bs-toggle="modal"
                                                                data-bs-target="#viewResourceModal<?= $resource['resource_id'] ?>">View</button>
                                                            <button class="btn btn-danger"
                                                                onclick="confirmDelete(<?= $resource['resource_id'] ?>)">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal for Viewing Resource -->
                                            <div class="modal fade" id="viewResourceModal<?= $resource['resource_id'] ?>"
                                                tabindex="-1"
                                                aria-labelledby="viewResourceModalLabel<?= $resource['resource_id'] ?>"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="viewResourceModalLabel<?= $resource['resource_id'] ?>">
                                                                View Resource</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Resource Title:</strong>
                                                                <?= htmlspecialchars($resource['resource_name']) ?></p>
                                                            <p><strong>Description:</strong>
                                                                <?= htmlspecialchars($resource['resource_description']) ?>
                                                            </p>
                                                            <p><strong>Type:</strong>
                                                                <?= ucfirst(htmlspecialchars($resource['resource_type'])) ?>
                                                            </p>
                                                            <p><strong>File:</strong>
                                                                <a
                                                                    href="../uploads/materials/<?= htmlspecialchars($resource['resource_url']) ?>">View</a>
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>


                                <!-- JavaScript for filtering the resources -->
                                <script>
                                    // Get DOM elements for filters
                                    const typeFilter = document.getElementById('resourceTypeFilter');
                                    const searchFilter = document.getElementById('resourceSearchFilter');
                                    const resourceItems = document.querySelectorAll('.resource-item');
                                    const noResourcesMessage = document.querySelector('.alert');

                                    // Event listener for filtering based on resource type
                                    typeFilter.addEventListener('change', filterResources);
                                    searchFilter.addEventListener('input', filterResources);

                                    function filterResources() {
                                        const typeValue = typeFilter.value.toLowerCase();
                                        const searchValue = searchFilter.value.toLowerCase();

                                        let visibleCount = 0;

                                        resourceItems.forEach(item => {
                                            const resourceType = item.getAttribute('data-resource-type');
                                            const resourceName = item.getAttribute('data-resource-name');

                                            // Show or hide resources based on filter conditions
                                            if (
                                                (typeValue === '' || resourceType.includes(typeValue)) &&
                                                (resourceName.includes(searchValue))
                                            ) {
                                                item.style.display = '';
                                                visibleCount++;
                                            } else {
                                                item.style.display = 'none';
                                            }
                                        });

                                        // Show or hide the "No resources" message
                                        if (visibleCount === 0) {
                                            if (noResourcesMessage) {
                                                noResourcesMessage.style.display = 'block';
                                            }
                                        } else {
                                            if (noResourcesMessage) {
                                                noResourcesMessage.style.display = 'none';
                                            }
                                        }
                                    }

                                    // Call filter function initially in case there is a preselected filter
                                    filterResources();
                                </script>


                            </div>

                            <!-- Upload Resource Modal -->
                            <div class="modal fade" id="uploadResourceModal" tabindex="-1"
                                aria-labelledby="uploadResourceModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="uploadResourceModalLabel">Upload Learning
                                                Resource</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="uploadResourceForm"
                                                action="processes/teachers/materials/upload.php?class_id=<?php echo $_GET['class_id'] ?>&subject_id=<?php echo $_GET['subject_id'] ?>"
                                                enctype="multipart/form-data" method="POST">
                                                <div class="mb-3">
                                                    <label for="resourceTitle" class="form-label">Resource
                                                        Title</label>
                                                    <input type="text" class="form-control" id="resourceTitle"
                                                        name="resource_title" required
                                                        placeholder="Enter resource title">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="resourceDescription"
                                                        class="form-label">Description</label>
                                                    <textarea class="form-control" id="resourceDescription"
                                                        name="resource_description" rows="3" required
                                                        placeholder="Enter a brief description of the resource"></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="resourceType" class="form-label">Resource
                                                        Type</label>
                                                    <select class="form-select" id="resourceType" name="resource_type"
                                                        required>
                                                        <option value="document">Document</option>
                                                        <option value="video">Video</option>
                                                        <option value="audio">Audio</option>
                                                        <option value="image">Image</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="resourceFile" class="form-label">Select File</label>
                                                    <input type="file" class="form-control" id="resourceFile"
                                                        name="resource_file" required>
                                                    <small id="fileHelp" class="form-text text-muted">Accepted file
                                                        types: .pdf, .doc, .docx</small>
                                                </div>
                                                <div class="mb-3">
                                                    <button type="submit" class="btn btn-primary">Upload
                                                        Resource</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const resourceType = document.getElementById('resourceType');
                                    const resourceFile = document.getElementById('resourceFile');
                                    const fileHelp = document.getElementById('fileHelp');

                                    const fileTypeMap = {
                                        'document': {
                                            types: '.pdf,.doc,.docx',
                                            message: 'Accepted file types: .pdf, .doc, .docx'
                                        },
                                        'video': {
                                            types: '.mp4,.avi,.mpeg',
                                            message: 'Accepted file type: .mp4, .avi, .mpeg'
                                        },
                                        'audio': {
                                            types: '.mp3,.ogg',
                                            message: 'Accepted file type: .mp3, .ogg'
                                        },
                                        'image': {
                                            types: '.jpg,.jpeg,.png,.gif',
                                            message: 'Accepted file types: .jpg, .jpeg, .png, .gif'
                                        }
                                    };

                                    resourceType.addEventListener('change', function() {
                                        const selectedType = resourceType.value;
                                        const fileInfo = fileTypeMap[selectedType] || {
                                            types: '',
                                            message: ''
                                        };

                                        resourceFile.setAttribute('accept', fileInfo.types);
                                        fileHelp.textContent = fileInfo.message;
                                    });

                                    // Set initial file type filter and message
                                    const initialType = resourceType.value;
                                    resourceFile.setAttribute('accept', fileTypeMap[initialType].types);
                                    fileHelp.textContent = fileTypeMap[initialType].message;
                                });
                            </script>


                            <!-- View Resource Modal -->


                            <script>
                                function confirmDelete(resourceId) {
                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: "You won't be able to revert this!",
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#3085d6',
                                        cancelButtonColor: '#d33',
                                        confirmButtonText: 'Yes, delete it!'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // AJAX request to delete the resource
                                            fetch(`processes/teachers/materials/delete.php?resource_id=${resourceId}`, {
                                                    method: 'GET'
                                                }).then(response => response.text())
                                                .then(data => {
                                                    Swal.fire(
                                                        'Deleted!',
                                                        'Your resource has been deleted.',
                                                        'success'
                                                    ).then(() => {
                                                        location.reload(); // Reload the page to update the resource list
                                                    });
                                                }).catch(error => {
                                                    console.error('Error:', error);
                                                    Swal.fire(
                                                        'Error!',
                                                        'Failed to delete the resource.',
                                                        'error'
                                                    );
                                                });
                                        }
                                    });
                                }
                            </script>
                        <?php } ?>
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

</html>

<?php
include('processes/server/alerts.php');
?>