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
                // Include the database connection
                include('processes/server/conn.php');

                $class_id = $_GET['id']; // Get the class_id from the URL or form


                $stmt = $pdo->prepare("SELECT major_exam, quizzes, assignments_activities_attendance FROM lecture_rubrics WHERE class_id = :class_id");
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
                                        <label for="quizzes" class="form-label">Quizzes</label>
                                        <input type="number" class="form-control" id="quizzes"
                                            placeholder="Enter percentage"
                                            value="<?php echo $gradingData['quizzes'] ?? ''; ?>" required>
                                    </div>


                                    <div class="mb-3">
                                        <label for="activities" class="form-label">Assignments / Activities /
                                            Attendance</label>
                                        <input type="number" class="form-control" id="assignments_activities_attendance"
                                            placeholder="Enter percentage"
                                            value="<?php echo $gradingData['assignments_activities_attendance'] ?? ''; ?>" required>
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
                            xhr.open('POST', 'delete_rubric.php', true);
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
                $stmt = $pdo->prepare("SELECT * FROM lecture_rubrics WHERE class_id = :class_id");
                $stmt->execute(['class_id' => $classId]);

                // Fetch the rubrics from the database
                $rubrics = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if rubrics are available
                if ($rubrics) {
                    echo " may rubrics";
                    // Calculate percentage based on the retrieved rubrics
                    $majorExamPercentage = isset($rubrics['major_exam']) ? floatval($rubrics['major_exam']) : 0;
                    $quizzesPercentage = isset($rubrics['quizzes']) ? floatval($rubrics['quizzes']) : 0;
                    $assignments_activities_attendancePercentage = isset($rubrics['assignments_activities_attendance']) ? floatval($rubrics['assignments_activities_attendance']) : 0;

                    // Total percentage (optional, you can decide how to use this or store)
                    $totalPercentage = $majorExamPercentage + $quizzesPercentage + $assignments_activities_attendancePercentage;
                } else {
                    echo " no rubrics";
                    $majorExamPercentage = 40;
                    $quizzesPercentage = 30;
                    $assignments_activities_attendancePercentage = 30;
                }


                global $TruemajorExamPercentage;
                global $TruequizzesPercentage;
                global $Trueassignments_activities_attendancePercentage;


                $TruemajorExamPercentage = $majorExamPercentage / 100;  // 0.4
                $TruequizzesPercentage = $quizzesPercentage / 100;      // 0.3
                $Trueassignments_activities_attendancePercentage = $assignments_activities_attendancePercentage / 100; // 0.3

                $totalPercentage = $TruemajorExamPercentage + $TruequizzesPercentage + $Trueassignments_activities_attendancePercentage;




                ?>

                <script>
                    // Function to submit the grading changes to the server
                    function submitGrading() {
                        const majorExam = document.getElementById("majorExam").value;
                        const quizzes = document.getElementById("quizzes").value;
                        const assignments_activities_attendance = document.getElementById("assignments_activities_attendance").value;
                        const classId = document.getElementById("classId").value;

                        // Validate input
                        if (majorExam && quizzes && assignments_activities_attendance) {
                            // Call AJAX to update the grading schema in the server
                            fetch('lecture_update_grading.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        class_id: classId,
                                        major_exam: majorExam,
                                        assignments_activities_attendance: assignments_activities_attendance,
                                        quizzes: quizzes

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

                                <table border="1" class="print-text" id="class"
                                    style="width: 100%; border: 1px solid black;">
                                    <tr>
                                        <th colspan="20" class="border-1 text-center">Worksheet</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center" colspan="2">Criteria</th>
                                        <th class="border-1" colspan="2">Quizzes (<?php echo $quizzesPercentage ?>%)</th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1" colspan="8">Assignments / Attendance / Activities
                                            (<?php echo $assignments_activities_attendancePercentage ?>%)
                                        </th>
                                        <th class="border-1" colspan="2">Exams (<?php echo $majorExamPercentage ?>%)</th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1">Midterms</th>
                                        <th class="border-1">Finals</th>
                                        <th class="border-1">GPA</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center">Student ID</th>
                                        <th class="border-1 text-center">Name of Student</th>
                                        <th class="border-1">Q1 <br> (25)</th>
                                        <th class="border-1">Q2 <br> (25)</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">ASS1 <br> (25) </th>
                                        <th class="border-1">ASS2 <br> (25) </th>
                                        <th class="border-1">Total
                                            <hr> 50
                                        </th>
                                        <th class="border-1">ATT. <br> (2) </th>
                                        <th class="border-1">Total
                                            <hr> 2
                                        </th>
                                        <th class="border-1">ACT1 <br> (25) </th>
                                        <th class="border-1">ACT2 <br> (25) </th>
                                        <th class="border-1">Total
                                            <hr> 50
                                        </th>
                                        <th class="border-1">Midterms <br> (25)</th>
                                        <th class="border-1">Finals <br> (25)</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                    </tr>
                                    <tr>
                                        <th colspan="20" class="text-center"
                                            style="background-color: grey; color: white;">Male</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center">2020-01524</th>
                                        <th class="border-1 text-center">Ahmad Pandaog Aquino</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1"> 50</th>
                                        <th class="border-1">2</th>
                                        <th class="border-1">2</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">1</th>
                                        <th class="border-1">1</th>
                                        <th class="border-1">1</th>
                                    </tr>
                                </table>






                                <hr> ORIGINAL

                                <table border="1" class="print-text" id="class"
                                    style="width: 100%; border: 1px solid black;">
                                    <tr>
                                        <th colspan="20" class="border-1 text-center">Worksheet</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center" colspan="2">Criteria</th>
                                        <th class="border-1" colspan="2">Quizzes (30%)</th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1" colspan="8">Assignments / Attendance / Activities (30%)
                                        </th>
                                        <th class="border-1" colspan="2">Exams (40%)</th>
                                        <th class="border-1">Total</th>
                                        <th class="border-1">Midterms</th>
                                        <th class="border-1">Finals</th>
                                        <th class="border-1">GPA</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center">Student ID</th>
                                        <th class="border-1 text-center">Name of Student</th>
                                        <th class="border-1">Q1 <br> (25)</th>
                                        <th class="border-1">Q2 <br> (25)</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">ASS1 <br> (25) </th>
                                        <th class="border-1">ASS2 <br> (25) </th>
                                        <th class="border-1">Total
                                            <hr> 50
                                        </th>
                                        <th class="border-1">ATT. <br> (2) </th>
                                        <th class="border-1">Total
                                            <hr> 2
                                        </th>
                                        <th class="border-1">ACT1 <br> (25) </th>
                                        <th class="border-1">ACT2 <br> (25) </th>
                                        <th class="border-1">Total
                                            <hr> 50
                                        </th>
                                        <th class="border-1">Midterms <br> (25)</th>
                                        <th class="border-1">Finals <br> (25)</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                        <th class="border-1">1 - 5</th>
                                    </tr>
                                    <tr>
                                        <th colspan="20" class="text-center"
                                            style="background-color: grey; color: white;">Male</th>
                                    </tr>
                                    <tr class="text-center">
                                        <th class="border-1 text-center">2020-01524</th>
                                        <th class="border-1 text-center">Ahmad Pandaog Aquino</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1"> 50</th>
                                        <th class="border-1">2</th>
                                        <th class="border-1">2</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">25</th>
                                        <th class="border-1">50</th>
                                        <th class="border-1">1</th>
                                        <th class="border-1">1</th>
                                        <th class="border-1">1</th>
                                    </tr>
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