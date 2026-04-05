<?php
session_start();
require '../../server/conn.php';

header('Content-Type: application/json');

$class_id = $_GET['class_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

if (!$class_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Missing class_id or subject_id']);
    exit;
}

try {
    // Fetch all necessary data (similar to main page logic)
    $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $students = [];
    if (!empty($studentIds)) {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $query = "SELECT student_id, fullName FROM students WHERE student_id IN ($placeholders) ORDER BY fullName";
        if ($student_id) {
            $query .= " AND student_id = ?";
            $studentIds[] = $student_id;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($studentIds);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $rubricsStmt = $pdo->prepare("SELECT DISTINCT title, percentile FROM rubrics WHERE class_id = ?");
    $rubricsStmt->execute([$class_id]);
    $rubrics = $rubricsStmt->fetchAll(PDO::FETCH_ASSOC);
    $activityTypes = array_column($rubrics, 'title');
    $percentiles = array_column($rubrics, 'percentile', 'title');

    $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ? AND type IN (".implode(',', array_fill(0, count($activityTypes), '?')).")");
    $activitiesStmt->execute(array_merge([$class_id], $activityTypes));
    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

    $activitiesByType = [];
    $activityIds = [];
    foreach ($activities as $activity) {
        $activityIds[] = $activity['id'];
        if (!isset($activitiesByType[$activity['type']])) {
            $activitiesByType[$activity['type']] = ['midterm' => [], 'final' => []];
        }
        $activitiesByType[$activity['type']][$activity['term']][] = $activity;
    }

    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN (".implode(',', array_fill(0, count($activityIds), '?')).")");
    $submissionsStmt->execute($activityIds);
    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);

    $studentScores = [];
    foreach ($submissions as $submission) {
        $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
    }

    $stmt = $pdo->prepare("SELECT id, date FROM classes_meetings WHERE class_id = ? ORDER BY date ASC");
    $stmt->execute([$class_id]);
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalMeetings = count($meetings);
    $attendanceDates = array_column($meetings, 'date', 'id');

    $attendanceStmt = $pdo->prepare("SELECT student_id, meeting_id, status FROM attendance WHERE class_id = ?");
    $attendanceStmt->execute([$class_id]);
    $attendanceRecordsRaw = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

    $attendanceRecords = [];
    foreach ($attendanceRecordsRaw as $record) {
        $attendanceRecords[$record['student_id']][$record['meeting_id']] = $record['status'];
    }

    $classStmt = $pdo->prepare("SELECT subject, type FROM classes WHERE id = ?");
    $classStmt->execute([$class_id]);
    $classInfo = $classStmt->fetch(PDO::FETCH_ASSOC);
    $classSubject = $classInfo['subject'];

    $relatedClassesStmt = $pdo->prepare("SELECT id, type FROM classes WHERE subject = ?");
    $relatedClassesStmt->execute([$classSubject]);
    $relatedClasses = $relatedClassesStmt->fetchAll(PDO::FETCH_ASSOC);

    $hasLab = $hasLec = false;
    $lectureClassId = $labClassId = null;
    foreach ($relatedClasses as $relatedClass) {
        $type = strtolower($relatedClass['type']);
        if (strpos($type, 'laboratory') !== false || strpos($type, 'lab') !== false) {
            $hasLab = true;
            $labClassId = $relatedClass['id'];
        }
        if (strpos($type, 'lecture') !== false || strpos($type, 'lec') !== false) {
            $hasLec = true;
            $lectureClassId = $relatedClass['id'];
        }
    }
    $hasBothLabAndLec = $hasLab && $hasLec;

    $studentData = [];
    foreach ($students as $student) {
        $studentId = $student['student_id'];
        $gradesStmt = $pdo->prepare("SELECT midterm_grade, final_grade, overall_grade FROM student_grades WHERE class_id = ? AND student_id = ?");
        $gradesStmt->execute([$class_id, $studentId]);
        $grades = $gradesStmt->fetch(PDO::FETCH_ASSOC) ?: ['midterm_grade' => 'INC', 'final_grade' => 'INC', 'overall_grade' => 'INC'];

        $combinedGrade = $grades['overall_grade'];
        if ($hasBothLabAndLec) {
            $lecGrade = fetchGrade($lectureClassId, $studentId, $pdo);
            $labGrade = fetchGrade($labClassId, $studentId, $pdo);
            $combinedGrade = calculateCombinedGrade($lecGrade, $labGrade);
        }

        $studentData[] = [
            'student_id' => $studentId,
            'fullName' => $student['fullName'],
            'scores' => $studentScores[$studentId] ?? [],
            'attendance' => $attendanceRecords[$studentId] ?? [],
            'grades' => [
                'midterm' => $grades['midterm_grade'],
                'final' => $grades['final_grade'],
                'gpa' => $grades['overall_grade'],
                'overallGrade' => $combinedGrade
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'students' => $studentData,
        'activityTypes' => $activityTypes,
        'activitiesByType' => $activitiesByType,
        'percentiles' => $percentiles,
        'attendanceDates' => $attendanceDates,
        'totalMeetings' => $totalMeetings,
        'hasBothLabAndLec' => $hasBothLabAndLec
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function fetchGrade($classId, $studentId, $pdo) {
    $stmt = $pdo->prepare("SELECT overall_grade FROM student_grades WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$classId, $studentId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    return $record ? $record['overall_grade'] : 'INC';
}

function calculateCombinedGrade($lecGrade, $labGrade) {
    if ($lecGrade === 'INC' || $labGrade === 'INC') return 'INC';
    if ($lecGrade === 'AW' || $labGrade === 'AW') return 'AW';
    if ($lecGrade === 'UW' || $labGrade === 'UW') return 'UW';

    $lecNumeric = floatval($lecGrade);
    $labNumeric = floatval($labGrade);
    $weightedAverage = ($lecNumeric * 0.6) + ($labNumeric * 0.4);
    $validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00];
    return number_format(array_reduce($validGrades, function($closest, $grade) use ($weightedAverage) {
        return abs($grade - $weightedAverage) < abs($closest - $weightedAverage) ? $grade : $closest;
    }, $validGrades[0]), 2);
}
?>