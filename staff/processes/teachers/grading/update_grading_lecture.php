<?php
header('Content-Type: application/json');
require '../../../processes/server/conn.php';

try {
    $studentId = $_POST['student_id'];
    $type = $_POST['type'];
    $grade = $_POST['grade']; // Could be 'auto' or a specific grade
    $classId = $_POST['class_id'];

    $field = ($type === 'midterm') ? 'midterm_grade' : (($type === 'final') ? 'final_grade' : 'overall_grade');

    // If grade is 'auto', calculate it; otherwise, use the provided grade
    if ($grade === 'auto') {
        // Fetch rubric percentages
        $rubricStmt = $pdo->prepare("
            SELECT major_exam, quizzes, assignments_activities_attendance 
            FROM lecture_rubrics 
            WHERE class_id = :class_id
        ");
        $rubricStmt->execute(['class_id' => $classId]);
        $rubric = $rubricStmt->fetch(PDO::FETCH_ASSOC);

        if (!$rubric) {
            echo json_encode(['success' => false, 'message' => 'Rubric not found for this class']);
            exit;
        }

        $quizWeight = $rubric['quizzes'] / 100;
        $tripleAWeight = $rubric['assignments_activities_attendance'] / 100;
        $examWeight = $rubric['major_exam'] / 100;

        $term = ($type === 'midterm') ? 'midterm' : (($type === 'final') ? 'final' : null);

        // Calculate Quiz Score
        $quizStmt = $pdo->prepare("
            SELECT SUM(asub.score) as total_score, SUM(a.max_points) as total_max
            FROM activity_submissions asub
            JOIN activities a ON asub.activity_id = a.id
            WHERE asub.student_id = :student_id 
            AND a.class_id = :class_id 
            AND a.type = 'quiz'
            " . ($term ? "AND a.term = :term" : "") . "
        ");
        $params = ['student_id' => $studentId, 'class_id' => $classId];
        if ($term) $params['term'] = $term;
        $quizStmt->execute($params);
        $quizData = $quizStmt->fetch(PDO::FETCH_ASSOC);
        $quizScore = ($quizData['total_max'] > 0) ? ($quizData['total_score'] / $quizData['total_max']) * $quizWeight : 0;

        // Calculate Assignments/Activities/Attendance Score
        $tripleAStmt = $pdo->prepare("
            SELECT SUM(asub.score) as total_score, SUM(a.max_points) as total_max
            FROM activity_submissions asub
            JOIN activities a ON asub.activity_id = a.id
            WHERE asub.student_id = :student_id 
            AND a.class_id = :class_id 
            AND a.type IN ('assignment', 'activity', 'project', 'exercise')
            " . ($term ? "AND a.term = :term" : "") . "
        ");
        $tripleAStmt->execute($params);
        $tripleAData = $tripleAStmt->fetch(PDO::FETCH_ASSOC);
        $tripleAScore = ($tripleAData['total_max'] > 0) ? ($tripleAData['total_score'] / $tripleAData['total_max']) : 0;

        $attendanceStmt = $pdo->prepare("
            SELECT COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count, COUNT(*) as total_meetings
            FROM attendance a
            JOIN classes_meetings cm ON a.meeting_id = cm.id
            WHERE a.student_id = :student_id 
            AND cm.class_id = :class_id 
            AND cm.status = 'Finished'
        ");
        $attendanceStmt->execute(['student_id' => $studentId, 'class_id' => $classId]);
        $attendanceData = $attendanceStmt->fetch(PDO::FETCH_ASSOC);
        $attendanceScore = ($attendanceData['total_meetings'] > 0) ? ($attendanceData['present_count'] / $attendanceData['total_meetings']) : 0;

        $tripleAScore = ($tripleAScore + $attendanceScore) / 2 * $tripleAWeight;

        // Calculate Exam Score
        $examStmt = $pdo->prepare("
            SELECT SUM(asub.score) as total_score, SUM(a.max_points) as total_max
            FROM activity_submissions asub
            JOIN activities a ON asub.activity_id = a.id
            WHERE asub.student_id = :student_id 
            AND a.class_id = :class_id 
            AND a.type = 'exam'
            " . ($term ? "AND a.term = :term" : "") . "
        ");
        $examStmt->execute($params);
        $examData = $examStmt->fetch(PDO::FETCH_ASSOC);
        $examScore = ($examData['total_max'] > 0) ? ($examData['total_score'] / $examData['total_max']) * $examWeight : 0;

        $totalGrade = $quizScore + $tripleAScore + $examScore;
        $grade = mapToGrade($totalGrade);
    }

    // Check if record exists
    $checkStmt = $pdo->prepare("
        SELECT $field 
        FROM student_grades 
        WHERE student_id = :student_id AND class_id = :class_id
    ");
    $checkStmt->execute([
        'student_id' => $studentId,
        'class_id' => $classId
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE student_grades 
            SET $field = :grade, updated_at = NOW()
            WHERE student_id = :student_id AND class_id = :class_id
        ");
        $stmt->execute([
            'student_id' => $studentId,
            'class_id' => $classId,
            'grade' => $grade
        ]);
        echo json_encode(['success' => true, 'grade' => $grade]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No existing grade record found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function mapToGrade($percentage) {
    if ($percentage >= 0.95) return "1.00";
    if ($percentage >= 0.90) return "1.25";
    if ($percentage >= 0.85) return "1.75";
    if ($percentage >= 0.80) return "2.00";
    if ($percentage >= 0.75) return "2.25";
    if ($percentage >= 0.70) return "2.50";
    if ($percentage >= 0.65) return "2.75";
    if ($percentage >= 0.60) return "3.00";
    return "5.00";
}
?>