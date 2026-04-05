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

        error_log("Processing enrollment for student_id: $student_id, class_id: $class_id");

        // Check if the student is already enrolled
        $checkEnrollmentStmt = $pdo->prepare("
            SELECT * FROM students_enrollments WHERE student_id = :student_id AND class_id = :class_id
        ");
        $checkEnrollmentStmt->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        if ($checkEnrollmentStmt->fetch()) {
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

        error_log("Class found: $class_name, Subject: $subject_name, Teacher: $teacher");

        // Insert notification
        $type = 'enrollment';
        $title = "You have been enrolled in the class '$subject_name' (Class: $class_name) by $teacher.";
        $description = "Congratulations! You have been successfully enrolled in the subject '$subject_name', instructed by $teacher. Class ID: $class_id.";
        $link = "/capstone/students/student_classes.php?class_id=$class_id";

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

        $pdo->prepare("
            UPDATE classes 
            SET studentTotal = COALESCE(studentTotal, 0) + 1 
            WHERE id = :class_id
        ")->execute(['class_id' => $class_id]);

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
        $getActivityStmt = $pdo->prepare("
            SELECT id 
            FROM activities 
            WHERE class_id = :class_id 
            AND id NOT IN (
                SELECT activity_id 
                FROM activity_submissions 
                WHERE student_id = :student_id
            )
        ");
        $getActivityStmt->execute(['class_id' => $class_id, 'student_id' => $student_id]);
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
            error_log("Added " . count($activityIds) . " activity submissions for student_id: $student_id");
        }

        // Add attendance for previous meetings
        $getMeetingsStmt = $pdo->prepare("
            SELECT id 
            FROM classes_meetings 
            WHERE class_id = :class_id
        ");
        $getMeetingsStmt->execute(['class_id' => $class_id]);
        $meetingIds = $getMeetingsStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($meetingIds)) {
            $checkAttendanceStmt = $pdo->prepare("
                SELECT meeting_id 
                FROM attendance 
                WHERE 
               class_id = :class_id
            ");
            $checkAttendanceStmt->execute([
             
                'class_id' => $class_id
            ]);
            $existingMeetingIds = $checkAttendanceStmt->fetchAll(PDO::FETCH_COLUMN);

            $newMeetingIds = array_diff($meetingIds, $existingMeetingIds);

            if (!empty($newMeetingIds)) {
                $placeholders = implode(', ', array_fill(0, count($newMeetingIds), '(?, ?, ?, ?, ?)'));
                $insertAttendanceStmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, class_id, meeting_id, status, date) 
                    VALUES $placeholders
                ");
                
                $params = [];
                foreach ($newMeetingIds as $meeting_id) {
                    $params[] = $student_id;
                    $params[] = $class_id;
                    $params[] = $meeting_id;
                    $params[] = 'absent';
                    $params[] = date('Y-m-d');
                }
                
                $insertAttendanceStmt->execute($params);
                error_log("Added " . count($newMeetingIds) . " attendance records for student_id: $student_id");
            } else {
                error_log("All meetings already have attendance records for student_id: $student_id, class_id: $class_id");
            }
        } else {
            error_log("No meetings found for class_id: $class_id, skipping attendance insertion");
        }

        $_SESSION['STATUS'] = "STUDENT_ENROLL_SUCCESSFUL";
        echo json_encode(['success' => true, 'message' => 'Enrollment successful.']);
        
    } catch (Exception $e) {
        $_SESSION['STATUS'] = "ENROLL_ERROR";
        error_log("Enrollment Error: " . $e->getMessage() . " | Line: " . $e->getLine() . " | Student ID: $student_id | Class ID: $class_id");
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}


if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>