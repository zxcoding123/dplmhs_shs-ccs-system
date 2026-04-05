<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

$classId = $_GET['class_id'] ?? null;
$meetingId = $_GET['meeting_id'] ?? null;

if (!$classId || !$meetingId) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.fullName, 
            se.student_id, 
            a.status
        FROM students_enrollments se
        JOIN students s ON se.student_id = s.id
        LEFT JOIN attendance a ON se.student_id = a.student_id 
            AND se.class_id = a.class_id 
            AND a.meeting_id = :meeting_id
        WHERE se.class_id = :class_id
    ");
    $stmt->execute([
        ':class_id' => $classId,
        ':meeting_id' => $meetingId,
    ]);

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($students);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>