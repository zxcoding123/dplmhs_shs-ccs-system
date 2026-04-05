<?php
session_start();
require_once '../../server/conn.php'; // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the entered class code and current student ID from the session
    $classCode = trim($_POST['classCode']);
    $studentId = $_SESSION['student_id'];
    $student_id = $_SESSION['student_id'];

    // Check if the class exists with the provided class code
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE classCode = ?");
    $stmt->execute([$classCode]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($class) {
        // Check if the student is already enrolled in the class
        $classId = $class['id'];
        $subjectId = $class['subject']; // Assuming 'subject' stores subject_id
        $class_id = $classId;

        echo $classId;

        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $fetch_teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetch_teacher) {
            $teacher_name = $fetch_teacher['teacher'];
        }

        $stmt = $pdo->prepare("SELECT id FROM staff_accounts WHERE fullName = ?");
        $stmt->execute([$teacher_name]);
        $fetch_teacher_id = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetch_teacher_id) {
            $teacher_id = $fetch_teacher_id['id'];
        }

        $stmt = $pdo->prepare("SELECT * FROM students_enrollments WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$classId, $studentId]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enrollment) {
            // If the student is already enrolled, display an error message
            $_SESSION['error'] = "You are already enrolled in this class.";
            header("Location: ../../../student_dashboard.php");
            exit();
        } else {
            // Enroll the student in the class
            $stmt = $pdo->prepare("INSERT INTO students_enrollments (class_id, student_id) VALUES (?, ?)");
            if ($stmt->execute([$classId, $studentId])) {
                $stmt = $pdo->prepare("UPDATE classes SET studentTotal = studentTotal + 1 WHERE id = ?");
                $stmt->execute([$classId]);

                // Add initial grades
                $pdo->prepare("
                    INSERT INTO student_grades (student_id, class_id) 
                    VALUES (:student_id, :class_id)
                ")->execute(['student_id' => $student_id, 'class_id' => $class_id]);

                // Add activity submissions (only for activities not already submitted by the student)
                $getActivityStmt = $pdo->prepare("
                    SELECT id 
                    FROM activities 
                    WHERE id NOT IN (
                        SELECT activity_id 
                        FROM activity_submissions 
                        WHERE student_id = :student_id
                    )
                ");
                $getActivityStmt->execute(['student_id' => $student_id]);
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

                $notificationTitle = 'A new student has entered your class ID: ' . htmlspecialchars($classId) . ' with student ID of: ' . htmlspecialchars($student_id);
                $notificationDescription = 'A new student with Student ID: ' . htmlspecialchars($student_id) . ' has joined your class (ID: ' . htmlspecialchars($classId) . '). 
                Visit the class management page for more details.';
                $date = date('Y-m-d H:i:s'); // Correct date format
                $link = '/staff/class_management.php';
                $type = "class";

                $insertStaffNotificationQuery = "INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                                                VALUES (:user_id, :type, :title, :description, :date, :link)";
                $insertStaffNotificationStmt = $pdo->prepare($insertStaffNotificationQuery);
                $insertStaffNotificationStmt->execute([
                    ':user_id' => $teacher_id,
                    ':type' => $type,
                    ':title' => $notificationTitle,
                    ':description' => $notificationDescription,
                    ':date' => $date,
                    ':link' => $link,
                ]);
                echo "Notification successfully inserted!";

                // Success, redirect with a success message
                $_SESSION['STATUS'] = "SUCCESS_CLASS_JOIN";
                header("Location: ../../../student_dashboard.php");
                exit();
            } else {
                // If there was a problem with the insertion
                $_SESSION['STATUS'] = "SUCCESS_CLASS_ERROR";
                header("Location: ../../../student_dashboard.php");
                exit();
            }
        }
    } else {
        // If class not found
        $_SESSION['STATUS'] = "CLASS_ALREADY_JOINED";
        header("Location: ../../../student_dashboard.php");
        exit();
    }
} else {
    // Handle non-POST requests if needed
}