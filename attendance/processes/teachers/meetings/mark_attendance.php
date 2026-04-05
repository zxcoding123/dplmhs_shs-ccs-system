<?php
session_start();
require_once '../../server/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $studentId = $_GET['student_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $classId = $_GET['id'] ?? null;

    if ($studentId && $date && $classId) {
        // Use preg_match to extract only the numeric part of the student_id
        if (preg_match('/\d+/', $studentId, $matches)) {
            $studentId = $matches[0]; // Extracted numeric student_id
        }

        // First, check if the record exists
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM classes_meetings 
            WHERE class_id = :class_id AND student_id = :student_id AND date = :date
        ");
        $checkStmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
        $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $checkStmt->bindParam(':date', $date);
        $checkStmt->execute();
        $recordExists = $checkStmt->fetchColumn();

        if ($recordExists) {
            // If record exists, update the attendance
            $updateStmt = $pdo->prepare("
                UPDATE classes_meetings
                SET attendance = 'present'
                WHERE class_id = :class_id AND student_id = :student_id AND date = :date
            ");
            $updateStmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
            $updateStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $updateStmt->bindParam(':date', $date);

            if ($updateStmt->execute()) {
                echo "Attendance updated successfully.";
                $_SESSION['STATUS'] = "ATTENDANCE_SUCCESFUL";
            } else {
                $_SESSION['STATUS'] = "ATTENDANCE_ERROR";
            }
        } else {
            $_SESSION['STATUS'] = "ATTENDANCE_ERROR";
        }
    } else {
        $_SESSION['STATUS'] = "ATTENDANCE_ERROR";
    }
}
header('Location: ../../../teacher_class_attendance.php?id=' . urlencode($classId) . '&date=' . urlencode($date));
exit();
?>
