<?php
session_start();
require 'processes/server/conn.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate input data
    $date = !empty($data['date']) ? htmlspecialchars($data['date']) : null;
    $subject = !empty($data['subject']) ? htmlspecialchars($data['subject']) : null;
    $startTime = !empty($data['start_time']) ? (new DateTime($data['start_time']))->format('g:i A') : null;
    $endTime = !empty($data['end_time']) ? (new DateTime($data['end_time']))->format('g:i A') : null;
    $classId = !empty($data['class_id']) ? intval($data['class_id']) : null;
    $type = !empty($data['type']) ? htmlspecialchars($data['type']) : "null";

    // Check if the provided date is today, and set status accordingly
    $today = (new DateTime())->format('Y-m-d');
    $status = ($date === $today) ? "Ongoing" : "Scheduled";

    // Ensure all required fields are present
    if ($date && $subject && $startTime && $endTime && $classId && $type) {
        try {
            // Prepare the SQL statement to insert the meeting
            $stmt = $pdo->prepare("
                INSERT INTO classes_meetings (date, status, start_time, end_time, class_id, type) 
                VALUES (:date, :status, :start_time, :end_time, :class_id, :type)
            ");

            // Bind parameters
            $stmt->bindParam(':date', $date);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':start_time', $startTime);
            $stmt->bindParam(':end_time', $endTime);
            $stmt->bindParam(':class_id', $classId);
            $stmt->bindParam(':type', $type);

            // Execute the insertion
            $stmt->execute();

            // Fetch the last inserted meeting ID
            $meetingId = $pdo->lastInsertId();

            if ($meetingId) {
                // Fetch all student IDs from students_enrollments for the given class_id
                $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = :class_id");
                $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
                $stmt->execute();
                $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Check if students exist for the given class_id
                if (count($studentIds) > 0) {
                    // Insert records into the attendance table
                    $insertStmt = $pdo->prepare("
                        INSERT INTO attendance (student_id, class_id, meeting_id, status, date)
                        VALUES (:student_id, :class_id, :meeting_id, 'absent', :date)
                    ");

                    $notificationStmt = $pdo->prepare("
                    INSERT INTO student_notifications (user_id, type, title, description, link, status)
                    VALUES (:user_id, :type, :title, :description, :link, :status)
                ");

                    foreach ($studentIds as $student_id) {
                        $insertStmt->execute([
                            ':student_id' => $student_id,
                            ':class_id' => $classId,
                            ':meeting_id' => $meetingId,
                            ':date' => $date
                        ]);

                        $teacher_name = $_SESSION['teacher_name'];
                        $notificationStmt->execute([
                            ':user_id' => $student_id,
                            ':type' => 'class',  // Example type: 'attendance' (adjust as needed)
                            ':title' => 'Class Attendance Added for Subject: ' . $subject . '!',
                            ':description' => 'There is an added attendance for subject: ' . $subject . ' Please monitor it regularly!',
                            ':link' => 'https://ccs-sms.com/capstone/students/student_dashboard.php?class_id='.$classId,  // Example link (could be a URL to class details or something else)
                            ':status' => 'unread'  // Notification status, 'unread' by default
                        ]);
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Class meeting and attendance records created']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to retrieve meeting ID']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

?>