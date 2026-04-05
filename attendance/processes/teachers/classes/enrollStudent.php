<?php
session_start();
require '../../../processes/server/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
        $class_id = filter_input(INPUT_GET, 'class_id', FILTER_SANITIZE_NUMBER_INT);

        if (!$student_id || !$class_id) {
            throw new Exception("Missing or invalid parameters.");
        }

        // Check if the student is already enrolled
        $checkEnrollmentStmt = $pdo->prepare("
            SELECT 1 
            FROM students_enrollments 
            WHERE student_id = :student_id AND class_id = :class_id
        ");
        $checkEnrollmentStmt->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        if ($checkEnrollmentStmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STUDENT_ALREADY_ENROLLED";
            echo json_encode(['success' => false, 'message' => 'Student is already enrolled.']);
            exit;
        }

        // Fetch class details
        $stmt = $pdo->prepare("
            SELECT name, subject, teacher 
            FROM classes 
            WHERE id = :class_id
        ");
        $stmt->execute(['class_id' => $class_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Class not found.");
        }

        $class_name = $result['name'];
        $subject_name = $result['subject'];
        $teacher = $result['teacher'];

        // Insert notification
        $type = 'enrollment';
        $title = "You have been enrolled in the class '$subject_name' (Class: $class_name) by $teacher.";
        $description = "Congratulations! You have been successfully enrolled in the subject '$subject_name', instructed by $teacher. Class ID: $class_id.";
        $link = "https://ccs-sms.com/capstone/students/student_classes.php?class_id=$class_id";

        $pdo->prepare("
            INSERT INTO student_notifications (user_id, type, title, description, link, status) 
            VALUES (:user_id, :type, :title, :description, :link, 'unread')
        ")->execute([
            'user_id' => $student_id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'link' => $link,
        ]);

        // Enroll the student
        $pdo->prepare("
            INSERT INTO students_enrollments (student_id, class_id) 
            VALUES (:student_id, :class_id)
        ")->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        // Add initial grades
        $pdo->prepare("
            INSERT INTO student_grades (student_id, class_id) 
            VALUES (:student_id, :class_id)
        ")->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        // Add activity submissions
        $getActivityStmt = $pdo->query("SELECT id FROM activities");
        $activityIds = $getActivityStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($activityIds)) {
            $placeholders = implode(', ', array_fill(0, count($activityIds), '(?, ?)'));
            $insertActivityStmt = $pdo->prepare("
                INSERT INTO activity_submissions (student_id, activity_id) 
                VALUES $placeholders
            ");
            $params = [];
            foreach ($activityIds as $activityId) {
                $params[] = $student_id;
                $params[] = $activityId;
            }
            $insertActivityStmt->execute($params);
        }

        // Add attendance
        $getMeetingsStmt = $pdo->prepare("
            SELECT id 
            FROM classes_meetings 
            WHERE class_id = :class_id
        ");
        $getMeetingsStmt->execute(['class_id' => $class_id]);
        $meetingIds = $getMeetingsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($meetingIds)) {
            $placeholders = implode(', ', array_fill(0, count($meetingIds), '(?, ?, ?, ?, ?)'));
            $insertAttendanceStmt = $pdo->prepare("
                INSERT INTO attendance (student_id, class_id, meeting_id, status, timestamp) 
                VALUES $placeholders
            ");
            $params = [];
            foreach ($meetingIds as $meeting_id) {
                $params[] = $student_id;
                $params[] = $class_id;
                $params[] = $meeting_id;
                $params[] = 'absent';
                $params[] = date('Y-m-d H:i:s');
            }
            $insertAttendanceStmt->execute($params);
        }

        $_SESSION['STATUS'] = "STUDENT_ENROLL_SUCCESSFUL";
        echo json_encode(['success' => true, 'message' => 'Enrollment successful.']);
    } catch (Exception $e) {
        $_SESSION['STATUS'] = "ENROLL_ERROR";
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}


// Redirect back to the referring page
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
