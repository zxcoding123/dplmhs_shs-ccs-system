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
include('processes/server/automatic_grader_cron.php');
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
        function convertToNumericalRatingg($percentage)
        {

            if ($percentage >= 99)
                return 1.0;
            if ($percentage >= 95)
                return 1.25;
            if ($percentage >= 90)
                return 1.5;
            if ($percentage >= 85)
                return 1.75;
            if ($percentage >= 80)
                return 2.0;
            if ($percentage >= 75)
                return 2.25;
            if ($percentage >= 70)
                return 2.5;
            if ($percentage >= 65)
                return 2.75;
            if ($percentage >= 60)
                return 3.0;
            return 5.0;
        }
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
                    <div class="card bg-light border-0 shadow-sm"
                        style="background-color: white !important; padding: 5px;">
                        <div class="container-fluid">
                            <div class="card-body">
                                <a href="class_management.php" class="d-flex align-items-center mb-3">
                                    <i class="bi bi-arrow-left-circle"
                                        style="font-size: 1.5rem; margin-right: 5px;"></i>
                                    <p class="m-0">Back</p>
                                </a>



                                <?php
                                // Get the class_id from the URL parameter
                                $class_id = $_GET['class_id'];

                                // Prepare the SQL statement to fetch the class details
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
                                    $year_section = $class['name']; // Assuming 'code' is for year/section
                                    $semester = $class['semester'];
                                    $subject_idd = $class['subject_id'];

                                    $stmt = $pdo->prepare("SELECT school_year FROM semester WHERE name = :name");
                                    $stmt->bindParam(':name', $semester, PDO::PARAM_STR);
                                    $stmt->execute();
                                    $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($semester_found) {

                                        $schoolYear = $semester_found['school_year'];
                                    }

                                    $type = $class['type'];

                                    $is_archived = $class['is_archived'];

                                    // Now, fetch all 'types' for the same subject
                                    $stmt2 = $pdo->prepare("SELECT DISTINCT type, id, subject_id
    FROM classes 
    WHERE subject = :subject AND teacher = :teacher");
                                    $stmt2->bindParam(':subject', $subject, PDO::PARAM_STR);
                                    $stmt2->bindParam(':teacher', $adviser, PDO::PARAM_STR);
                                    $stmt2->execute();
                                    $types = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                                    // Check if the subject has multiple types like Lecture and Laboratory
                                    $hasLaboratory = false;
                                    $hasLecture = false;
                                    $laboratoryClassId = null; // Variable to store the class_id of the laboratory class
                                    $lectureClassId = null; // Variable to store the class_id of the laboratory class

                                    $lecSubjId = null;
                                    $labSubjId = null;

                                    foreach ($types as $typeRow) {
                                        if (strcasecmp($typeRow['type'], 'lecture') == 0) {
                                            $hasLecture = true;
                                            $lectureClassId = $typeRow['id'];
                                            $lecSubjId = $typeRow['subject_id'];
                                        }
                                        if (strcasecmp($typeRow['type'], 'laboratory') == 0) {
                                            $hasLaboratory = true;
                                            $laboratoryClassId = $typeRow['id']; // Store the class_id of the laboratory
                                            $labSubjId = $typeRow['subject_id'];
                                        }
                                    }
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
                                                Year:</b>
                                            <span><?php echo $schoolYear . ' - ' . $schoolYear + 1; ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-check" style="margin-right: 5px;"></i> Class
                                                Type:</b> <span><?php echo htmlspecialchars($type); ?></span></h3>

                                    </div>
                                    <div class="col">
                                        <?php if ($hasLecture && $hasLaboratory): ?>
                                            <!-- If both Lecture and Laboratory types exist, show buttons to go to the opposite class type -->
                                            <div class="d-flex align-items-center">
                                                <div class="ms-auto" aria-hidden="true">
                                                    <!-- If the current class is Lecture, link to Laboratory -->
                                                    <?php if (strcasecmp($type, 'lecture') == 0): ?>
                                                        <a
                                                            href="class_grades.php?class_id=<?php echo $laboratoryClassId ?>&semester_id=<?php echo $_GET['semester_id'] ?>&subject_id=<?php echo $lecSubjId ?>">
                                                            <button class="btn btn-warning">Go to Laboratory</button>
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- If the current class is Laboratory, link to Lecture -->
                                                    <?php if (strcasecmp($type, 'laboratory') == 0): ?>
                                                        <a
                                                            href="class_grades.php?class_id=<?php echo $lectureClassId ?>&semester_id=<?php echo $_GET['semester_id'] ?>&subject_id=<?php echo $labSubjId ?>">
                                                            <button class="btn btn-primary">Go to Lecture</button>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        <?php endif; ?>

                                    </div>
                                </div>

                                <hr>
                                <?php
                                require_once 'processes/server/conn.php';

                                $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
                                $subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
                                $semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;

                                if (!$class_id || !$subject_id || !$semester_id) {
                                    echo "<p class='error'>Missing required parameters.</p>";
                                    exit;
                                }

                                try {
                                    // Fetch current class details
                                    $classStmt = $pdo->prepare("SELECT type, is_archived, teacher, subject, code, semester FROM classes WHERE id = :class_id");
                                    $classStmt->execute([':class_id' => $class_id]);
                                    $class = $classStmt->fetch(PDO::FETCH_ASSOC);
                                    if (!$class) {
                                        echo "<p class='error'>Class not found.</p>";
                                        exit;
                                    }
                                    $type = $class['type'] ?? null;
                                    $is_archived = $class['is_archived'] ?? 0;
                                    $currentTeacherId = $class['teacher'] ?? null;
                                    $subject = $class['subject'];
                                    $year_section = $class['code'];
                                    $semester = $class['semester'];

                                    // Fetch school year for the semester
                                    $stmt = $pdo->prepare("SELECT school_year FROM semester WHERE name = :name");
                                    $stmt->bindParam(':name', $semester, PDO::PARAM_STR);
                                    $stmt->execute();
                                    $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $schoolYear = $semester_found ? $semester_found['school_year'] : 'N/A';



                                    // Check for Lecture and Laboratory classes for the subject with teacher consistency
                                    $classCheckStmt = $pdo->prepare("SELECT id, type, teacher FROM classes WHERE subject = :subject");
                                    $classCheckStmt->execute([':subject' => $subject]);
                                    $classes = $classCheckStmt->fetchAll(PDO::FETCH_ASSOC);

                                    $lectureClassId = null;
                                    $lectureTeacherId = null;
                                    $labClassId = null;
                                    $labTeacherId = null;

                                    foreach ($classes as $c) {

                                        if ($c['type'] === 'Lecture') {
                                            $lectureClassId = $c['id'];
                                            $lectureTeacherId = $c['teacher'];
                                        } elseif ($c['type'] === 'Laboratory') {
                                            $labClassId = $c['id'];
                                            $labTeacherId = $c['teacher'];
                                        }
                                    }





                                    $classIds = $hasLecture && $hasLaboratory ? [$lectureClassId, $labClassId] : [$class_id];

                                    // Define allowed grades for combined overall
                                    $allowedGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00];
                                ?>

                                    <div class="d-flex align-items-center">
                                        <h3 class="text-center bold">Student Grades</h3>
                                        <div class="ms-auto">
                                            <?php
                                            if ($type == 'Laboratory' && $is_archived == 1) {
                                                echo "<a href='lab_class_grades_tabular_archived.php?id=$class_id&semester_id=$semester_id&subject_id=" . urlencode($subject_idd) . "'><button type='button' class='btn btn-primary'>View Grades in Tabular Form</button></a>";
                                            } elseif ($type == 'Lecture' && $is_archived == 1) {
                                                echo "<a href='lecture_class_grades_tabular_archived.php?id=$class_id&semester_id=$semester_id&subject_id=" . urlencode($subject_idd) . "'><button type='button' class='btn btn-primary'>View Grades in Tabular Form</button></a>";
                                            } elseif ($type == 'Lecture' && $is_archived == 0) {
                                                echo "<a href='lecture_class_grades_tabular.php?id=$class_id&semester_id=$semester_id&subject_id=" . urlencode($subject_idd) . "'><button type='button' class='btn btn-primary'>View Grades in Tabular Form</button></a>";
                                            } elseif ($type == 'Laboratory' && $is_archived == 0) {
                                                echo "<a href='lab_class_grades_tabular.php?id=$class_id&semester_id=$semester_id&subject_id=" . urlencode($subject_idd) . "'><button type='button' class='btn btn-primary'>View Grades in Tabular Form</button></a>";
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="container-fluid">
                                        <div class="accordion mt-5" id="accordionExample">
                                            <?php
                                            // Fetch enrolled students for the relevant class(es)
                                            $stmt = $pdo->prepare("SELECT DISTINCT student_id FROM students_enrollments WHERE class_id IN (:class_id1, :class_id2)");
                                            $stmt->execute([':class_id1' => $lectureClassId ?? $class_id, ':class_id2' => $labClassId ?? $class_id]);
                                            $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            if ($enrolledStudents) {
                                                $studentCounter = 1;

                                                foreach ($enrolledStudents as $enrollment) {
                                                    $student_id = $enrollment['student_id'];

                                                    $studentStmt = $pdo->prepare("SELECT fullName FROM students WHERE student_id = :student_id");
                                                    $studentStmt->execute([':student_id' => $student_id]);
                                                    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

                                                    if ($student) {
                                                        $accordionId = 'collapseStudent' . $studentCounter;
                                                        $headingId = 'headingStudent' . $studentCounter;

                                                        // Fetch rubric titles and activities for the relevant class IDs
                                                        $activitiesByClassAndRubric = [];
                                                        $gradesByClass = [];

                                                        // Fetch activities for the current class_id (Lecture or Lab)
                                                        $rubricStmt = $pdo->prepare("SELECT DISTINCT title FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id AND title != 'Attendance'");
                                                        $rubricStmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
                                                        $rubricTitles = $rubricStmt->fetchAll(PDO::FETCH_COLUMN);
                                                        sort($rubricTitles); // Sort alphabetically

                                                        $activitiesByClassAndRubric[$class_id] = [];
                                                        foreach ($rubricTitles as $title) {
                                                            $activityStmt = $pdo->prepare("SELECT title, type, max_points, id FROM activities WHERE class_id = :class_id AND LOWER(type) = LOWER(:type)");
                                                            $activityStmt->execute([':class_id' => $class_id, ':type' => $title]);
                                                            $activitiesByClassAndRubric[$class_id][$title] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
                                                        }

                                                        // Fetch grades for all relevant classes (Lecture and Laboratory if applicable)
                                                        $combinedOverallGrade = null;
                                                        foreach ($classIds as $cid) {

                                                            $gradeStmt = $pdo->prepare("SELECT midterm_grade, final_grade, overall_grade FROM student_grades WHERE student_id = :student_id AND class_id = :class_id");
                                                            $gradeStmt->execute([':student_id' => $student_id, ':class_id' => $cid]);
                                                            $grades = $gradeStmt->fetch(PDO::FETCH_ASSOC);
                                                            if ($grades) {
                                                                $gradesByClass[$cid] = $grades;
                                                            } else {
                                                                $gradesByClass[$cid] = ['midterm_grade' => 'N/A', 'final_grade' => 'N/A', 'overall_grade' => 'N/A'];
                                                            }
                                                        }

                                                        // Calculate combined overall grade if both Lecture and Lab exist (outside the loop)
                                                        if ($hasLecture && $hasLaboratory) {
                                                            $lecGrades = $gradesByClass[$lectureClassId] ?? ['overall_grade' => null];
                                                            $labGrades = $gradesByClass[$labClassId] ?? ['overall_grade' => null];
                                                            $lecOverallGrade = is_numeric($lecGrades['overall_grade']) ? floatval($lecGrades['overall_grade']) : null;
                                                            $labOverallGrade = is_numeric($labGrades['overall_grade']) ? floatval($labGrades['overall_grade']) : null;



                                                            if ($lecOverallGrade !== null && $labOverallGrade !== null) {
                                                                $combinedOverallGrade = ($lecOverallGrade * 0.6) + ($labOverallGrade * 0.4);


                                                                // Find the nearest allowed grade
                                                                $nearestGrade = $allowedGrades[0]; // Default to lowest grade
                                                                $minDifference = abs($combinedOverallGrade - $allowedGrades[0]);
                                                                foreach ($allowedGrades as $grade) {
                                                                    $difference = abs($combinedOverallGrade - $grade);
                                                                    if ($difference < $minDifference) {
                                                                        $minDifference = $difference;
                                                                        $nearestGrade = $grade;
                                                                    }
                                                                }

                                                                $combinedOverallGrade = $nearestGrade;
                                                            } else {
                                                                $combinedOverallGrade = 'N/A';
                                                            }
                                                        }

                                                        // Check if there are any activities for the current class_id
                                                        $hasActivities = false;
                                                        if (isset($activitiesByClassAndRubric[$class_id])) {
                                                            foreach ($activitiesByClassAndRubric[$class_id] as $activities) {
                                                                if (!empty($activities)) {
                                                                    $hasActivities = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                            ?>

                                                        <div class="accordion-item">
                                                            <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                                                <button class="accordion-button <?= $studentCounter == 1 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#<?php echo $accordionId; ?>" aria-expanded="<?= $studentCounter == 1 ? 'true' : 'false' ?>"
                                                                    aria-controls="<?php echo $accordionId; ?>">
                                                                    <?php echo 'Student ' . $studentCounter . ' - ' . htmlspecialchars($student['fullName']); ?>
                                                                </button>
                                                            </h2>
                                                            <div id="<?php echo $accordionId; ?>" class="accordion-collapse collapse <?= $studentCounter == 1 ? 'show' : '' ?>"
                                                                data-bs-parent="#accordionExample">
                                                                <div class="accordion-body">
                                                                    <?php
                                                                    if ($hasActivities) {
                                                                        // Display activities for the current class_id
                                                                        $classType = ($class_id == $lectureClassId) ? 'Lecture' : 'Laboratory';
                                                                        if ($hasLecture && $hasLaboratory) {
                                                                            echo "<h3 class='text-secondary text-center mb-4'><b>$classType Component</b></h3>";
                                                                        }

                                                                        foreach ($activitiesByClassAndRubric[$class_id] as $rubricTitle => $activities) {
                                                                            if (!empty($activities)) {
                                                                                echo '<h4 class="text-secondary mb-4"><b>Type: ' . htmlspecialchars($rubricTitle) . 's</b></h4>';
                                                                                echo '<div class="row g-3">';
                                                                                foreach ($activities as $activity) {
                                                                                    $activity_id = $activity['id'];
                                                                                    $scoreStmt = $pdo->prepare("SELECT score FROM activity_submissions WHERE activity_id = :activity_id AND student_id = :student_id");
                                                                                    $scoreStmt->execute([':activity_id' => $activity_id, ':student_id' => $student_id]);
                                                                                    $score = $scoreStmt->fetch(PDO::FETCH_ASSOC);
                                                                    ?>
                                                                                    <div class="col-md-6">
                                                                                        <div class="card shadow-sm border-0">
                                                                                            <div class="card-body">
                                                                                                <h5 class="card-title text-secondary mb-3">
                                                                                                    <b>Name of Activity:</b> <?= htmlspecialchars($activity['title']) ?>
                                                                                                </h5>
                                                                                                <p class="card-text text-secondary mb-0">
                                                                                                    <b>Scoring:</b> <?= $score ? $score['score'] : 'N/A' ?> / <?= $activity['max_points'] ?>
                                                                                                </p>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                    <?php
                                                                                }
                                                                                echo '</div>';
                                                                            }
                                                                        }
                                                                    } else {
                                                                        echo '<h3 class="text-center text-secondary">No activities found for this class yet!</h3>';
                                                                    }
                                                                    ?>

                                                                    <!-- Grades Display with Three Columns -->
                                                                    <div class="row mt-4 text-center">
                                                                        <?php if ($hasLecture && $hasLaboratory) { ?>
                                                                            <div class="col">
                                                                                <h5 class="text-secondary mb-2 bold">Lecture Grades</h5>
                                                                                <?php
                                                                                $lecGrades = $gradesByClass[$lectureClassId] ?? [];
                                                                                $midtermGradeLec = isset($lecGrades['midterm_grade']) && in_array($lecGrades['midterm_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $lecGrades['midterm_grade']
                                                                                    : (isset($lecGrades['midterm_grade']) && is_numeric($lecGrades['midterm_grade']) ? number_format((float)$lecGrades['midterm_grade'], 2) : 'N/A');
                                                                                $finalGradeLec = isset($lecGrades['final_grade']) && in_array($lecGrades['final_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $lecGrades['final_grade']
                                                                                    : (isset($lecGrades['final_grade']) && is_numeric($lecGrades['final_grade']) ? number_format((float)$lecGrades['final_grade'], 2) : 'N/A');
                                                                                $overallGradeLec = isset($lecGrades['overall_grade']) && in_array($lecGrades['overall_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $lecGrades['overall_grade']
                                                                                    : (isset($lecGrades['overall_grade']) && is_numeric($lecGrades['overall_grade']) ? number_format((float)$lecGrades['overall_grade'], 2) : 'N/A');
                                                                                ?>

                                                                                <p><b>Midterm:</b>
                                                                                    <span <?php echo in_array($midtermGradeLec, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($midtermGradeLec) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Final:</b>
                                                                                    <span <?php echo in_array($finalGradeLec, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($finalGradeLec) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Overall:</b>
                                                                                    <span <?php echo in_array($overallGradeLec, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($overallGradeLec) ?>
                                                                                    </span>
                                                                                </p>



                                                                            </div>
                                                                            <div class="col">
                                                                                <h5 class="text-secondary mb-2 bold">Laboratory Grades</h5>
                                                                                <?php
                                                                                $labGrades = $gradesByClass[$labClassId] ?? [];
                                                                                $midtermGradeLab = isset($labGrades['midterm_grade']) && in_array($labGrades['midterm_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $labGrades['midterm_grade']
                                                                                    : (isset($labGrades['midterm_grade']) && is_numeric($labGrades['midterm_grade']) ? number_format((float)$labGrades['midterm_grade'], 2) : 'N/A');
                                                                                $finalGradeLab = isset($labGrades['final_grade']) && in_array($labGrades['final_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $labGrades['final_grade']
                                                                                    : (isset($labGrades['final_grade']) && is_numeric($labGrades['final_grade']) ? number_format((float)$labGrades['final_grade'], 2) : 'N/A');
                                                                                $overallGradeLab = isset($labGrades['overall_grade']) && in_array($labGrades['overall_grade'], ['AW', 'UW', 'INC', 'N/A'])
                                                                                    ? $labGrades['overall_grade']
                                                                                    : (isset($labGrades['overall_grade']) && is_numeric($labGrades['overall_grade']) ? number_format((float)$labGrades['overall_grade'], 2) : 'N/A');
                                                                                ?>
                                                                                <p><b>Midterm:</b>
                                                                                    <span <?php echo in_array($midtermGradeLab, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($midtermGradeLab) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Final:</b>
                                                                                    <span <?php echo in_array($finalGradeLab, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($finalGradeLab) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Overall:</b>
                                                                                    <span <?php echo in_array($overallGradeLab, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($overallGradeLab) ?>
                                                                                    </span>
                                                                                </p>


                                                                            </div>
                                                                            <div class="col">
    <h5 class="text-secondary mb-2 bold">Combined Overall</h5>
    <p><b>Grade:</b> 
        <span <?php echo ($combinedOverallGrade === 'INC' || $combinedOverallGrade === 'N/A') ? 'style="color: crimson;"' : ''; ?>>
            <?php
            if ($combinedOverallGrade === 'INC') {
                echo htmlspecialchars($combinedOverallGrade);
            } elseif (is_numeric($combinedOverallGrade)) {
                echo htmlspecialchars(number_format($combinedOverallGrade, 2));
            } else {
                echo htmlspecialchars($combinedOverallGrade ?? 'N/A');
            }
            ?>
        </span>
    </p>
</div>
                                                                        <?php } else { ?>
                                                                            <div class="col-12">
                                                                                <h5 class="text-secondary mb-2 bold">Grades</h5>
                                                                                <?php
                                                                                $grades = $gradesByClass[$class_id] ?? [];
                                                                                $midtermGrade = $grades && in_array($grades['midterm_grade'], ['AW', 'UW', 'INC', 'N/A']) ? $grades['midterm_grade'] : ($grades ? number_format((float)$grades['midterm_grade'], 2) : 'N/A');
                                                                                $finalGrade = $grades && in_array($grades['final_grade'], ['AW', 'UW', 'INC', 'N/A']) ? $grades['final_grade'] : ($grades ? number_format((float)$grades['final_grade'], 2) : 'N/A');
                                                                                $overallGrade = $grades && in_array($grades['overall_grade'], ['AW', 'UW', 'INC', 'N/A']) ? $grades['overall_grade'] : ($grades ? number_format((float)$grades['overall_grade'], 2) : 'N/A');
                                                                                ?>
                                                                                <p><b>Midterm:</b>
                                                                                    <span <?php echo in_array($midtermGrade, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($midtermGrade) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Final:</b>
                                                                                    <span <?php echo in_array($finalGrade, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($finalGrade) ?>
                                                                                    </span>
                                                                                </p>

                                                                                <p><b>Overall:</b>
                                                                                    <span <?php echo in_array($overallGrade, ['5.00', 'INC', 'UW', 'AW', 'N/A']) ? 'style="color: crimson;"' : ''; ?>>
                                                                                        <?= htmlspecialchars($overallGrade) ?>
                                                                                    </span>
                                                                                </p>

                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                        <?php
                                                        $studentCounter++;
                                                    }
                                                }
                                            } else {
                                                echo '<div class="text-center mt-5"><h2>No students enrolled in this class.</h2></div>';
                                            }
                                        } catch (PDOException $e) {
                                            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                                        }
                                        ?>
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

</html>