<?php
session_start();
require_once '../../server/conn.php';
$selectedDate = $_GET['date'];

if (isset($_GET['class_id'])) {
    $classId = $_GET['class_id'];

    try {
        // Check if the date already exists for the given class
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM classes_meetings WHERE class_id = :class_id AND date = :date");
        $checkStmt->bindParam(':class_id', $classId);
        $checkStmt->bindParam(':date', $selectedDate);
        $checkStmt->execute();

        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            $_SESSION['STATUS'] = "DATE_ALREADY_EXISTS";
            header("Location: ../../../teacher_class_attendance.php?id=$classId&date=$selectedDate");
            exit();
        } else {
            // Get all student IDs for the given class
            $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = :class_id");
            $stmt->bindParam(':class_id', $classId);
            $stmt->execute();
            $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Start transaction
            $pdo->beginTransaction();

            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO classes_meetings (class_id, date, student_id, attendance) 
                    VALUES (:class_id, :date, :student_id, 'absent')
                ");
                // Insert into classes_meetings table for each student
                foreach ($studentIds as $studentId) {
                    $insertStmt->bindParam(':class_id', $classId);
                    $insertStmt->bindParam(':date', $selectedDate);
                    $insertStmt->bindParam(':student_id', $studentId);
                    $insertStmt->execute();
                    
                    // Insert attendance for the student in attendance table
                    $attendanceStmt = $pdo->prepare("
                        INSERT INTO attendance (student_id, class_id, meeting_id, status, date, ip_address) 
                        VALUES (:student_id, :class_id, LAST_INSERT_ID(), 'absent', :date, :ip_address)
                    ");
                    $attendanceStmt->bindParam(':student_id', $studentId);
                    $attendanceStmt->bindParam(':class_id', $classId);
                    $attendanceStmt->bindParam(':date', $selectedDate);
                    $attendanceStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);  // Get the student's IP address

                    $attendanceStmt->execute();
                }

                // Commit transaction
                $pdo->commit();

                $_SESSION['STATUS'] = "NEW_DATE_ADDED";
                // header("Location: ../../../teacher_class_attendance.php?id=$classId&date=$selectedDate");
                exit();
            } catch (PDOException $e) {
                // Rollback transaction in case of error
                $pdo->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Class ID not found in session.";
}
