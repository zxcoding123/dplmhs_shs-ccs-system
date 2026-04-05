<?php
include('conn.php');
$class_id = $_GET['class_id']; // Example class ID (change as needed)
// Fetch students
$stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE gender = 'Male'");
$stmt->execute();
$male_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, fullName FROM students where gender ='Female'");
$stmt->execute();
$female_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                SUM(CASE WHEN type = 'exercise' THEN 1 ELSE 0 END) AS total_exercises,
                                SUM(CASE WHEN type = 'assignment' THEN 1 ELSE 0 END) AS total_assignments,
                                SUM(CASE WHEN type = 'activity' THEN 1 ELSE 0 END) AS total_activities,
                                SUM(CASE WHEN type = 'exam' THEN 1 ELSE 0 END) AS total_exams
                              FROM activities
                              WHERE class_id = :class_id";

$stmt = $pdo->prepare($query);
$stmt->execute(['class_id' => $class_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$totalExercises = $result['total_exercises'] ?? 0;
$totalTripleA = $result['total_assignments'] + $result['total_activities'] + $totalMeetings + 3;
$totalExams = $result['total_exams'] ?? 0;


//GRADER

$classId = $_GET['class_id']; // Class ID from GET request

// Fetch rubrics for grading
$stmt = $pdo->prepare("SELECT * FROM laboratory_rubrics WHERE class_id = :class_id");
$stmt->execute(['class_id' => $classId]);
$rubrics = $stmt->fetch(PDO::FETCH_ASSOC);

// Default rubric values (if not found in DB)
$majorExamPercentage = $rubrics ? floatval($rubrics['major_exam']) : 40;
$exercisesPercentage = $rubrics ? floatval($rubrics['exercises']) : 30;
$aaaPercentage = $rubrics ? floatval($rubrics['assignments_activities_attendance']) : 30;

// Convert percentages to decimal
$majorExamWeight = $majorExamPercentage / 100;
$exerciseWeight = $exercisesPercentage / 100;
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



// Function to get the numerical rating
function getNumericalRating($grade)
{
    if ($grade >= 99)
        return 1.0;
    if ($grade >= 95)
        return 1.25;
    if ($grade >= 90)
        return 1.5;
    if ($grade >= 85)
        return 1.75;
    if ($grade >= 80)
        return 2.0;
    if ($grade >= 75)
        return 2.25;
    if ($grade >= 70)
        return 2.5;
    if ($grade >= 65)
        return 2.75;
    if ($grade >= 60)
        return 3.0;
    return 5.0; // Below 60 is a failing grade
}
foreach ($students as $student) {
    $studentId = (int) $student['id'];
    $classId = (int) $class_id; // Ensure `$class_id` is set in your script

    $midtermGrade = 0;
    $finalGrade = 0;
    $midtermGradePercentage = 0;
    $finalGradePercentage = 0;
    $midtermGradeNumerical = 0;
    $finalGradeNumerical = 0;

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
                $score = $studentScores[$studentId][$activityId] ?? 0;

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

        // Avoid division by zero
        $exerciseGrade = ($totalExerciseMax > 0) ? ($totalExerciseScore / $totalExerciseMax) * 100 : 0;
        $aaaGrade = ($totalAaaMax > 0) ? ($totalAaaScore / $totalAaaMax) * 100 : 0;
        $examGrade = ($totalExamMax > 0) ? ($totalExamScore / $totalExamMax) * 100 : 0;

        // Compute weighted grade
        $weightedGrade = ($exerciseGrade * $exerciseWeight) + ($aaaGrade * $aaaWeight) + ($examGrade * $majorExamWeight);

        if ($term === 'midterm') {
            $midtermGradePercentage = $weightedGrade;  // Store original percentage
            $midtermGradeNumerical = getNumericalRating($weightedGrade);  // Store numerical rating
        } else {
            $finalGradePercentage = $weightedGrade;  // Store original percentage
            $finalGradeNumerical = getNumericalRating($weightedGrade);  // Store numerical rating
        }
    }

    // Debug output (optional)
    echo "Student: {$student['fullName']} <br>";
    echo "Midterm Percentage: $midtermGradePercentage | Numerical: $midtermGradeNumerical <br>";
    echo "Final Percentage: $finalGradePercentage | Numerical: $finalGradeNumerical <br><br>";

         // Avoid division by zero
         $exerciseGrade = ($totalExerciseMax > 0) ? ($totalExerciseScore / $totalExerciseMax) * 100 : 0;
         $aaaGrade = ($totalAaaMax > 0) ? ($totalAaaScore / $totalAaaMax) * 100 : 0;
         $examGrade = ($totalExamMax > 0) ? ($totalExamScore / $totalExamMax) * 100 : 0;
 
         // Compute final term grade based on rubrics
         $finalGradeTerm = ($exerciseGrade * $exerciseWeight) + ($aaaGrade * $aaaWeight) + ($examGrade * $majorExamWeight);
      
         $numericalRating = getNumericalRating($finalGradeTerm);
 
         // Determine column to update
         $column = ($term === 'midterm') ? 'midterm_grade' : 'final_grade';
 
         // Store values for final calculation
       
             $midtermGrade = $numericalRating;
              // Update student_grades table
         $stmt = $pdo->prepare("UPDATE student_grades SET midterm_grade = :grade WHERE student_id = :student_id AND class_id = :class_id");
         $stmt->execute([
             'grade' => $midtermGradeNumerical,
             'student_id' => $studentId,
             'class_id' => $classId
         ]);
         
       
        
            $stmt = $pdo->prepare("UPDATE student_grades SET final_grade = :grade WHERE student_id = :student_id AND class_id = :class_id");
            $stmt->execute([
                'grade' => $finalGradeNumerical,
                'student_id' => $studentId,
                'class_id' => $classId
            ]);
             $finalGrade = $numericalRating;

                 // Calculate overall grade
     $overallGrade = ($midtermGradePercentage * 0.35) + ($finalGradePercentage * 0.55);
     $overallNumericalRating = getNumericalRating($overallGrade);
 
     echo $overallNumericalRating;
 
     // Update overall grade in student_grades table
     $stmt = $pdo->prepare("UPDATE student_grades SET overall_grade = :overall WHERE student_id = :student_id AND class_id = :class_id");
     $stmt->execute([
         'overall' => $overallNumericalRating,
         'student_id' => $studentId,
         'class_id' => $classId
     ]);

        
     }
 
 


?>