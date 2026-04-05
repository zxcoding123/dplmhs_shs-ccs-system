<?php

function updateGrades($class_id, $student_id, $midtermGrade, $finalGrade, $pdo)
{
    try {
        // Check if the grades already exist and if the grade status is valid for update
        $stmt = $pdo->prepare("
            SELECT id, midterm_grade, final_grade FROM student_grades 
            WHERE class_id = :class_id AND student_id = :student_id
        ");
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $existingGrade = $stmt->fetch();

        // Check if either midterm_grade or final_grade contains 'INC', 'AW', or 'UW'
        if ($existingGrade) {
            $invalidStatuses = ['INC', 'AW', 'UW'];
            if (in_array($existingGrade['midterm_grade'], $invalidStatuses) || in_array($existingGrade['final_grade'], $invalidStatuses)) {

                return;  // Stop further processing if the status is invalid
            }
        }

        $midtermNumericalRating = convertToNumericalRating($midtermGrade);
        $finalNumericalRating = convertToNumericalRating($finalGrade);

        if ($existingGrade) {
            // If grades exist and status is valid, update the record
            $stmt = $pdo->prepare("
                UPDATE student_grades 
                SET midterm_grade = :midterm_grade, final_grade = :final_grade
                WHERE class_id = :class_id AND student_id = :student_id
            ");
            $stmt->bindParam(':midterm_grade', $midtermNumericalRating, PDO::PARAM_STR);
            $stmt->bindParam(':final_grade', $finalNumericalRating, PDO::PARAM_STR);
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // If no existing grades, insert new record
            $stmt = $pdo->prepare("
                INSERT INTO student_grades (class_id, student_id, midterm_grade, final_grade)
                VALUES (:class_id, :student_id, :midterm_grade, :final_grade)
            ");
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':midterm_grade', $midtermNumericalRating, PDO::PARAM_STR);
            $stmt->bindParam(':final_grade', $finalNumericalRating, PDO::PARAM_STR);
            $stmt->execute();
        }

    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}


function calculateAttendance($class_id, $student_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("
SELECT cm.id AS meeting_id, 
COUNT(a.id) AS total_attendance, 
SUM(CASE WHEN a.status = 'present' THEN 1 
WHEN a.status = 'late' THEN 0.5 ELSE 0 END) AS total_score
FROM classes_meetings cm
LEFT JOIN attendance a 
ON cm.id = a.meeting_id 
AND a.class_id = :class_id1 
AND a.student_id = :student_id
WHERE cm.class_id = :class_id2
GROUP BY cm.id
");
        $stmt->bindParam(':class_id1', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id2', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalMeetings = count($results);
        $totalScore = 0;

        // Display attendance details
        foreach ($results as $row) {
            $totalScore += $row['total_score'];
        }

        $attendancePercentage = $totalMeetings > 0 ? ($totalScore / $totalMeetings) * 100 : 0;

        return $attendancePercentage;  // Returning the percentage
    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        return 0;
    }
}

// Get the class_id from the URL parameter
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : null;

if ($class_id) {
    try {
        // Fetch class details and types
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id");
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        $class = $stmt->fetch();

        if ($class) {
            $subject = $class['subject'];

            // Fetch all types for the same subject
            $stmt2 = $pdo->prepare("SELECT DISTINCT type, id FROM classes WHERE subject = :subject");
            $stmt2->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt2->execute();
            $types = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Initialize variables to track if the subject has lecture or laboratory
            $hasLecture = false;
            $hasLaboratory = false;

            // Determine if the subject has lecture or laboratory
            foreach ($types as $typeRow) {
                if (strcasecmp($typeRow['type'], 'lecture') == 0) {
                    $hasLecture = true;
                }
                if (strcasecmp($typeRow['type'], 'laboratory') == 0) {
                    $hasLaboratory = true;
                }
            }

            // Determine weights based on whether the subject has lecture and/or laboratory
            $lectureWeight = $hasLaboratory ? 60 : 60;
            $labWeight = $hasLaboratory ? 40 : 40;

            function calculateTermGrades($class_id, $term, $lectureWeight, $labWeight, $pdo, $hasLecture, $hasLaboratory)
            {
                // Fetch activities and grades, including max_points
                $stmt = $pdo->prepare("
                    SELECT a.id AS activity_id, a.type, a.max_points, s.student_id, s.score
                    FROM activities a
                    LEFT JOIN activity_submissions s ON a.id = s.activity_id
                    WHERE a.class_id = :class_id AND a.term = :term
                ");
                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                $stmt->bindParam(':term', $term, PDO::PARAM_STR);
                $stmt->execute();
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $grades = [];
                foreach ($activities as $activity) {
                    $student_id = $activity['student_id'];
                    $type = $activity['type'];
                    $score = $activity['score'] ?? 0;
                    $max_points = $activity['max_points'];  // Default to 100 if max_points is null

                    // Ensure grades array is initialized for each student
                    if (!isset($grades[$student_id])) {
                        $grades[$student_id] = [
                            'lecture' => [
                                'exam' => [],
                                'quiz' => [],
                                'activity' => [],
                            ],
                            'laboratory' => [
                                'exam' => [],
                                'exercise' => [],
                                'activity' => [],
                            ]
                        ];
                    }

                    // Normalize the score by dividing by max_points to get percentage score
                    $percentageScore = $score / $max_points * 100;

                    // Categorize scores by type (lecture or laboratory)
                    if (strcasecmp($type, 'exam') == 0) {
                        $grades[$student_id]['lecture']['exam'][] = $percentageScore;
                    } elseif (strcasecmp($type, 'quiz') == 0) {
                        $grades[$student_id]['lecture']['quiz'][] = $percentageScore;
                    } elseif (strcasecmp($type, 'activity') == 0) {
                        $grades[$student_id]['lecture']['activity'][] = $percentageScore;
                    }
                }

                $termGrades = [];
                foreach ($grades as $student_id => $studentScores) {
                    // Calculate lecture score averages using percentage scores
                    $lectureExamAvg = !empty($studentScores['lecture']['exam']) ? array_sum($studentScores['lecture']['exam']) / count($studentScores['lecture']['exam']) : 0;
                    $lectureQuizAvg = !empty($studentScores['lecture']['quiz']) ? array_sum($studentScores['lecture']['quiz']) / count($studentScores['lecture']['quiz']) : 0;
                    $lectureActivityAvg = !empty($studentScores['lecture']['activity']) ? array_sum($studentScores['lecture']['activity']) / count($studentScores['lecture']['activity']) : 0;

                    // Attendance score (from previous function)
                    $attendancePercentage = calculateAttendance($class_id, $student_id, $pdo);
                    $attendanceScore = ($attendancePercentage / 100) * 30;  // 30% weight for attendance in lecture

                    // Calculate the final lecture grade considering weighted components
                    $lectureGrade = ($lectureExamAvg * 0.4) + ($lectureQuizAvg * 0.3) + ($lectureActivityAvg * 0.3) + $attendanceScore;

                    // Add calculated grade to term grades
                    $termGrades[$student_id] = [
                        'percentage' => round($lectureGrade, 2),
                        'numerical_rating' => convertToNumericalRating($lectureGrade, $student_id, $class_id, $pdo),
                    ];
                }

                return $termGrades;
            }



            // Function to convert percentage to numerical rating
            function convertToNumericalRating($percentage)
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



            // Calculate Midterm and Final Grades
            $midtermGrades = calculateTermGrades($class_id, 'midterm', $lectureWeight, $labWeight, $pdo, $hasLecture, $hasLaboratory);
            $finalGrades = calculateTermGrades($class_id, 'final', $lectureWeight, $labWeight, $pdo, $hasLecture, $hasLaboratory);

            // Combine Midterm and Final Grades
            $overallGrades = [];
            foreach ($midtermGrades as $student_id => $midtermGrade) {
                // Make sure the midterm and final grades are properly handled as arrays with 'percentage' key
                $finalGrade = $finalGrades[$student_id] ?? ['percentage' => 0];  // Default to 0 if not found
                $overallPercentage = round(($midtermGrade['percentage'] * 0.4) + ($finalGrade['percentage'] * 0.6), 2);
                $overallRating = convertToNumericalRating($overallPercentage, $student_id, $class_id, $pdo);

                $overallGrades[$student_id] = [
                    'percentage' => $overallPercentage,
                    'numerical_rating' => $overallRating
                ];

                updateGrades($class_id, $student_id, $midtermGrade['percentage'], $finalGrade['percentage'], $pdo);
            }



        } else {

        }
    } catch (PDOException $e) {

    }
} else {

}
?>