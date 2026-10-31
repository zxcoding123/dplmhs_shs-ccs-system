<?php
// Switched off: only the unlinked lecture_class_grades_tabular_newest.php called this, and it
// changed grades and scores without checking who was signed in or whether grades were locked.
// Grades are saved through staff/save_grades.php.
http_response_code(410);
header("Content-Type: application/json");
echo json_encode(["success" => false, "message" => "This endpoint is no longer used."]);
exit;
header('Content-Type: application/json');
require __DIR__ . '/../../../../database/connection.php';

try {
    $studentId = $_POST['student_id'];
    $meetingId = $_POST['meeting_id'];
    $status = $_POST['status'];
    $classId = $_POST['class_id'];

    // Check if record exists first
    $checkStmt = $pdo->prepare("
        SELECT status FROM attendance 
        WHERE student_id = :student_id AND meeting_id = :meeting_id
    ");
    $checkStmt->execute([
        'student_id' => $studentId,
        'meeting_id' => $meetingId
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update only if record exists
        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET status = :status, timestamp = NOW()
            WHERE student_id = :student_id AND meeting_id = :meeting_id
        ");
        $stmt->execute([
            'student_id' => $studentId,
            'meeting_id' => $meetingId,
            'status' => $status
        ]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No existing attendance record found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>