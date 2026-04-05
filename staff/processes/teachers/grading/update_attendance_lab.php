<?php
header('Content-Type: application/json');
require '../../../processes/server/conn.php';

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