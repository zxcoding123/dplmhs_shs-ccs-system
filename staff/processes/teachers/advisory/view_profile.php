<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'TEACHER_NOT_LOGGED_IN';
    header('Location: ../../login/index.php');
    exit();
}

require '../../../processes/server/conn.php';

$student_id = $_GET['id'] ?? 0;

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo '<div class="alert alert-danger">Student not found</div>';
    exit();
}
?>
<div class="row">
    <div class="col-md-4 text-center">
        <?php if (!empty($student['photo'])): ?>
            <img src="../uploads/students/<?= htmlspecialchars($student['photo']) ?>"
                class="img-fluid rounded-circle mb-3"
                style="width: 200px; height: 200px; object-fit: cover;"
                alt="Student Photo">
        <?php else: ?>
            <img src="../external/img/ADNU_Logo.png"
                class="img-fluid rounded-circle mb-3"
                style="width: 200px; height: 200px; object-fit: cover;"
                alt="Default Photo">
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <h4><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars(string: $student['middle_name']) ?></h4>
        <hr>

        <div class="row mb-3">
            <div class="col-md-6">
                <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Course:</strong> <?= htmlspecialchars($student['course']) ?></p>
                <p><strong>Year Level:</strong> <?= htmlspecialchars($student['year_level']) ?></p>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- Attendance Section -->
<div class="container-fluid mt-4">
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary">
            <h4 class="bold text-center text-white">Attendance History</h4>
        </div>
        <div class="card-body">
            <?php
            // Query to get all meetings for classes the student is enrolled in, ordered by date
            $stmtMeetings = $pdo->prepare("
                SELECT 
                    cm.id AS meeting_id, 
                    cm.date, 
                    cm.class_id, 
                    cm.status, 
                    cm.start_time, 
                    cm.end_time, 
                    cm.type,
                    c.name AS class_name, 
                    c.subject AS subject_name, 
                    c.type as class_type, 
                    c.teacher AS teacher_name,
                    s.id AS semesterId,
                    sa.status AS attendance_status
                FROM students_enrollments se
                JOIN classes_meetings cm ON se.class_id = cm.class_id
                JOIN classes c ON cm.class_id = c.id
                JOIN semester s ON c.semester = s.name
                LEFT JOIN attendance sa ON sa.meeting_id = cm.id AND sa.student_id = se.student_id
                WHERE se.student_id = :student_id
                ORDER BY cm.date DESC, cm.start_time DESC
            ");
            $stmtMeetings->execute([':student_id' => $student_id]);

            // Check if there are results
            if ($stmtMeetings->rowCount() > 0) {
                // Group meetings by date
                $groupedMeetings = [];
                while ($row = $stmtMeetings->fetch(PDO::FETCH_ASSOC)) {
                    $date = $row['date'];
                    if (!isset($groupedMeetings[$date])) {
                        $groupedMeetings[$date] = [];
                    }
                    $groupedMeetings[$date][] = $row;
                }

                echo '<div class="accordion" id="attendanceAccordion">';
                
                foreach ($groupedMeetings as $date => $meetings) {
                    $formattedDate = date('F j, Y', strtotime($date));
                    $isToday = (date('Y-m-d') == $date);
                    $dateHeaderClass = $isToday ? 'bg-info text-white' : '';
                    
                    echo '<div class="accordion-item">';
                    echo '<h2 class="accordion-header" id="heading-' . htmlspecialchars($date) . '">';
                    echo '<button class="accordion-button ' . $dateHeaderClass . '" type="button" data-bs-toggle="collapse" 
                          data-bs-target="#collapse-' . htmlspecialchars($date) . '" aria-expanded="true" 
                          aria-controls="collapse-' . htmlspecialchars($date) . '">';
                    echo '<strong>' . $formattedDate . '</strong>';
                    if ($isToday) {
                        echo '<span class="badge bg-white text-info ms-2">Today</span>';
                    }
                    echo '</button>';
                    echo '</h2>';
                    
                    echo '<div id="collapse-' . htmlspecialchars($date) . '" class="accordion-collapse collapse show" 
                          aria-labelledby="heading-' . htmlspecialchars($date) . '" data-bs-parent="#attendanceAccordion">';
                    echo '<div class="accordion-body p-0">';
                    echo '<div class="list-group">';
                    
                    foreach ($meetings as $row) {
                        $attendanceStatus = $row['attendance_status'] ?? 'Not Recorded';
                        $statusBadgeClass = 'bg-secondary';
                        
                        if ($attendanceStatus === 'Present') {
                            $statusBadgeClass = 'bg-success';
                        } elseif ($attendanceStatus === 'Absent') {
                            $statusBadgeClass = 'bg-danger';
                        } elseif ($attendanceStatus === 'Late') {
                            $statusBadgeClass = 'bg-warning text-dark';
                        }
                        
                        echo '<div class="list-group-item border-0 mb-2 shadow-sm rounded">';
                        echo '<div class="d-flex justify-content-between align-items-center">';
                        echo '<div class="pe-3">';
                        echo '<h5 class="mb-1 text-primary"><strong>' . htmlspecialchars($row['class_name']) . '</strong></h5>';
                        echo '<p class="mb-1"><strong>Subject:</strong> ' . htmlspecialchars($row['subject_name']) . ' (' . htmlspecialchars($row['class_type']) . ')</p>';
                        echo '<p class="mb-1"><strong>Teacher:</strong> ' . htmlspecialchars($row['teacher_name']) . '</p>';
                        echo '<p class="mb-1"><strong>Time:</strong> ' . htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']) . '</p>';
                        echo '<p class="mb-1"><strong>Attendance:</strong> <span class="badge ' . $statusBadgeClass . '">' . htmlspecialchars($attendanceStatus) . '</span></p>';
                        echo '</div>';
                        
                        echo '<div>';
                        if ($isToday && $row['status'] === 'Ongoing') {
                            $attendanceUrl = 'class_attendance_qr.php?class_id=' . urlencode($row['class_id']) .
                                '&classAttendanceId=' . urlencode($row['meeting_id']) .
                                '&semesterId=' . urlencode($row['semesterId']);
                            
                            echo '<a href="' . htmlspecialchars($attendanceUrl) . '" class="btn btn-outline-primary btn-lg d-flex align-items-center">';
                            echo '<i class="bi bi-arrow-right-circle me-2"></i> Enter';
                            echo '</a>';
                        } else {
                            echo '<span class="text-muted">Class completed</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // list-group
                    echo '</div>'; // accordion-body
                    echo '</div>'; // accordion-collapse
                    echo '</div>'; // accordion-item
                }
                
                echo '</div>'; // accordion
            } else {
                echo '<div class="alert alert-warning">';
                echo '<p class="text-muted text-center">No classes meetings made yet.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Grades Section -->
<div class="container-fluid mt-4">
    <div class="card shadow">
        <div class="card-header bg-secondary">
            <h4 class="bold text-center text-white">Grades</h4>
        </div>
        <div class="card-body">
            <?php
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
                            c.type AS type
                        FROM students_enrollments se
                        JOIN classes c ON se.class_id = c.id
                        WHERE se.student_id = :student_id AND c.semester = :semester_name
                    ");
                    $stmtClasses->execute([':student_id' => $student_id, ':semester_name' => $semesterName]);
                    $classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

              
                    echo '<h4> <span class="bold">Semester:</span> ' . $semesterName . ' <br> <br> <span class="bold">School Year and Date: </span>
                    (' . $startYear . ' - ' . $endYear . ')</h4>';
                    echo '<br></div>';

                    if (count($classes) > 0) {
                        // Analyze subjects to identify which have both lab and lecture
                        $subjectAnalysis = [];
                        $lecOnlyClasses = [];
                        $regularClasses = [];

                        // First pass: Gather all subjects and their component types
                        foreach ($classes as $class) {
                            $subjectName = $class['subject_name'];
                            $classType = strtolower($class['type'] ?? '');

                            if (!isset($subjectAnalysis[$subjectName])) {
                                $subjectAnalysis[$subjectName] = [
                                    'has_lab' => false,
                                    'has_lec' => false,
                                    'classes' => []
                                ];
                            }

                            // Add this class to the subject's class list
                            $subjectAnalysis[$subjectName]['classes'][] = $class;

                            // Determine if this is a lab or lecture
                            if (strpos($classType, 'laboratory') !== false || strpos($classType, 'lab') !== false) {
                                $subjectAnalysis[$subjectName]['has_lab'] = true;
                            }
                            if (strpos($classType, 'lecture') !== false || strpos($classType, 'lec') !== false) {
                                $subjectAnalysis[$subjectName]['has_lec'] = true;
                            }
                        }

                        // Second pass: Categorize classes
                        foreach ($subjectAnalysis as $subjectName => $info) {
                            // If subject has lecture only (no lab)
                            if ($info['has_lec'] && !$info['has_lab']) {
                                foreach ($info['classes'] as $class) {
                                    $lecOnlyClasses[] = $class;
                                }
                            } else {
                                // All other classes (including those with both lab and lecture)
                                foreach ($info['classes'] as $class) {
                                    $regularClasses[] = $class;
                                }
                            }
                        }

                        // SECTION 1: Regular Classes Table (includes subjects with both lab and lecture)
                        if (!empty($regularClasses)) {
                            echo '<div class="card-body">';
                            echo '<h5 class="card-title mb-3">Regular Classes</h5>';
                            echo '<div class="table-responsive text-center">';
                            echo '<table class="table table-striped table-hover table-bordered">';
                            echo '<thead class="table-secondary">';
                            echo '<tr>';
                            echo '<th><i class="bi bi-journal"></i> Subject</th>';
                            echo '<th><i class="bi bi-building"></i> Class</th>';
                            echo '<th><i class="bi bi-star"></i> Midterm Grade</th>';
                            echo '<th><i class="bi bi-star-fill"></i> Final Grade</th>';
                            echo '<th><i class="bi bi-award"></i> Numerical Grade Rating</th>';
                            echo '<th><i class="bi bi-info-circle"></i> Overall Grade <br>
                            <small>(Lecture and Laboratory)</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            // Cache to store subject components and grades for efficiency
                            $subjectComponentsCache = [];
                            $subjectGradesCache = [];

                            foreach ($regularClasses as $class) {
                                $classId = $class['class_id'];
                                $subjectName = htmlspecialchars($class['subject_name']);
                                $className = htmlspecialchars($class['class_name']);
                                $classType = strtolower($class['type'] ?? '');

                                // Check if we've already analyzed this subject's components and grades
                                if (!isset($subjectComponentsCache[$subjectName])) {
                                    // Fetch all classes with the same subject to check for related Lecture/Lab classes
                                    $relatedClassesStmt = $pdo->prepare("SELECT id, type FROM classes WHERE subject = ?");
                                    $relatedClassesStmt->execute([$subjectName]);
                                    $relatedClasses = $relatedClassesStmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Detect Lecture and Lab based on all related classes
                                    $hasLab = false;
                                    $hasLec = false;
                                    $lecClassId = null;
                                    $labClassId = null;

                                    foreach ($relatedClasses as $relatedClass) {
                                        $relatedType = strtolower($relatedClass['type'] ?? '');
                                        if (strpos($relatedType, 'laboratory') !== false || strpos($relatedType, 'lab') !== false) {
                                            $hasLab = true;
                                            $labClassId = $relatedClass['id'];
                                        }
                                        if (strpos($relatedType, 'lecture') !== false || strpos($relatedType, 'lec') !== false) {
                                            $hasLec = true;
                                            $lecClassId = $relatedClass['id'];
                                        }
                                    }
                                    $hasBothLabAndLec = $hasLab && $hasLec;

                                    // Store the result for this subject
                                    $subjectComponentsCache[$subjectName] = [
                                        'has_both' => $hasBothLabAndLec,
                                        'has_lab' => $hasLab,
                                        'has_lec' => $hasLec,
                                        'lec_class_id' => $lecClassId,
                                        'lab_class_id' => $labClassId
                                    ];

                                    // If subject has both lab and lecture, fetch grades for both
                                    if ($hasBothLabAndLec) {
                                        // Fetch lecture grades
                                        $lecGradesStmt = $pdo->prepare("
                                            SELECT overall_grade
                                            FROM student_grades 
                                            WHERE class_id = :class_id AND student_id = :student_id
                                        ");
                                        $lecGradesStmt->execute([':class_id' => $lecClassId, ':student_id' => $student_id]);
                                        $lecGrades = $lecGradesStmt->fetch(PDO::FETCH_ASSOC);
                                        $lecOverallGrade = $lecGrades ? floatval($lecGrades['overall_grade']) : null;

                                        // Fetch lab grades
                                        $labGradesStmt = $pdo->prepare("
                                            SELECT overall_grade
                                            FROM student_grades 
                                            WHERE class_id = :class_id AND student_id = :student_id
                                        ");
                                        $labGradesStmt->execute([':class_id' => $labClassId, ':student_id' => $student_id]);
                                        $labGrades = $labGradesStmt->fetch(PDO::FETCH_ASSOC);
                                        $labOverallGrade = $labGrades ? floatval($labGrades['overall_grade']) : null;
                                        // Calculate combined overall grade (70% Lec + 30% Lab)
                                        $combinedOverallGrade = null;

                                        // Calculation
                                        $allowedGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 'INC', 'N/A', 0];
                                        $numericAllowedGrades = array_filter($allowedGrades, 'is_numeric');
                                        $combinedOverallGrade = null;

                                        if ($lecOverallGrade !== null && $labOverallGrade !== null) {
                                            if ($lecOverallGrade == '0' || $labOverallGrade == '0') {
                                                $combinedOverallGrade = 'INC';
                                            } else {
                                                if (is_numeric($lecOverallGrade) && is_numeric($labOverallGrade)) {
                                                    $combinedOverallGrade = ($lecOverallGrade * 0.7) + ($labOverallGrade * 0.3);
                                                    $nearestGrade = $numericAllowedGrades[0];
                                                    $minDifference = abs($combinedOverallGrade - $nearestGrade);
                                                    foreach ($numericAllowedGrades as $grade) {
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
                                        }

                                        // Store grades in cache
                                        $subjectGradesCache[$subjectName] = [
                                            'lec_grade' => $lecOverallGrade,
                                            'lab_grade' => $labOverallGrade,
                                            'combined_grade' => $combinedOverallGrade
                                        ];
                                    }
                                }

                                // Fetch grades for the current class
                                $stmtGrades = $pdo->prepare("
                                    SELECT midterm_grade, final_grade, overall_grade
                                    FROM student_grades 
                                    WHERE class_id = :class_id AND student_id = :student_id
                                ");
                                $stmtGrades->execute([':class_id' => $classId, ':student_id' => $student_id]);
                                $grades = $stmtGrades->fetch(PDO::FETCH_ASSOC);

                                $midtermGrade = $grades ? htmlspecialchars($grades['midterm_grade']) : 'N/A';
                                $finalGrade = $grades ? htmlspecialchars($grades['final_grade']) : 'N/A';
                                $overallGrade = $grades ? htmlspecialchars($grades['overall_grade']) : 'N/A';

                                echo '<tr>';
                                echo '<td>' . $subjectName . ' (' . htmlspecialchars($class['type']) . ')</td>';
                                echo '<td>' . $className . '</td>';
                                echo '<td' . ($midtermGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($midtermGrade) . '</td>';
                                echo '<td' . ($finalGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($finalGrade) . '</td>';
                                echo '<td' . ($overallGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($overallGrade) . '</td>';

                                // Display components based on analysis
                                $components = '';
                                if ($subjectComponentsCache[$subjectName]['has_both']) {
                                    // For classes with both lab and lecture, show combined grade if this is lecture
                                    if (strpos($classType, 'lecture') !== false || strpos($classType, 'lec') !== false) {
                                        $combinedGrade = $subjectGradesCache[$subjectName]['combined_grade'];
                                        $components = $combinedGrade !== null ? (is_numeric($combinedGrade) ? number_format($combinedGrade, 2) : $combinedGrade) : 'N/A';
                                    } else {
                                        $combinedGrade = $subjectGradesCache[$subjectName]['combined_grade'];
                                        $components = $combinedGrade !== null ? (is_numeric($combinedGrade) ? number_format($combinedGrade, 2) : $combinedGrade) : 'N/A';
                                    }
                                } elseif ($subjectComponentsCache[$subjectName]['has_lec']) {
                                    $components = 'Lec';
                                } elseif ($subjectComponentsCache[$subjectName]['has_lab']) {
                                    $components = 'Lab';
                                } else {
                                    $components = htmlspecialchars($class['type']); // Fallback to original type
                                }

                                // Apply number_format if it's a numeric value
                                $displayValue = is_numeric($components) ? number_format($components, 2) : htmlspecialchars($components);

                                echo '<td' . ($displayValue === 'INC' ? ' style="color: crimson;"' : '') . '>' . $displayValue . '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>'; // table-responsive
                            echo '</div>'; // card-body
                        }

                        // SECTION 2: Lecture-Only Classes Table
                        if (!empty($lecOnlyClasses)) {
                            echo '<div class="card-body' . (!empty($regularClasses) ? ' border-top' : '') . '">';
                            echo '<h5 class="card-title mb-3">Lecture-Only Classes</h5>';
                            echo '<div class="table-responsive text-center">';
                            echo '<table class="table table-striped table-hover table-bordered">';
                            echo '<thead class="table-secondary">';
                            echo '<tr>';
                            echo '<th><i class="bi bi-journal"></i> Subject</th>';
                            echo '<th><i class="bi bi-building"></i> Class</th>';
                            echo '<th><i class="bi bi-star"></i> Midterm Grade</th>';
                            echo '<th><i class="bi bi-star-fill"></i> Final Grade</th>';
                            echo '<th><i class="bi bi-award"></i> Numerical Grade Rating</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($lecOnlyClasses as $class) {
                                $classId = $class['class_id'];
                                $subjectName = htmlspecialchars($class['subject_name']);
                                $className = htmlspecialchars($class['class_name']);

                                // Fetch grades for the class
                                $stmtGrades = $pdo->prepare("
                                    SELECT midterm_grade, final_grade, overall_grade
                                    FROM student_grades 
                                    WHERE class_id = :class_id AND student_id = :student_id
                                ");
                                $stmtGrades->execute([':class_id' => $classId, ':student_id' => $student_id]);
                                $grades = $stmtGrades->fetch(PDO::FETCH_ASSOC);

                                $midtermGrade = $grades ? htmlspecialchars($grades['midterm_grade']) : 'N/A';
                                $finalGrade = $grades ? htmlspecialchars($grades['final_grade']) : 'N/A';
                                $overallGrade = $grades ? htmlspecialchars($grades['overall_grade']) : 'N/A';

                                echo '<tr>';
                                echo '<td>' . $subjectName . ' (' . htmlspecialchars($class['type']) . ')</td>';
                                echo '<td>' . $className . '</td>';
                                echo '<td' . ($midtermGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($midtermGrade) . '</td>';
                                echo '<td' . ($finalGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($finalGrade) . '</td>';
                                echo '<td' . ($overallGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($overallGrade) . '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>'; // table-responsive
                            echo '</div>'; // card-body
                        }
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
            ?>
        </div>
    </div>
</div>