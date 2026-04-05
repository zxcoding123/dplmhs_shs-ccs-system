<?php
session_start();
require '../../../processes/server/conn.php';

if (isset($_GET['class_id']) && isset($_GET['student_id'])) {
    
    $class_id = $_GET['class_id'];
    $student_id = $_GET['student_id'];


    $_SESSION['STATUS'] = "STUDENT_UNENROLL_SUCCESSFUL";

    try {

        $_SESSION['STATUS'] = "STUDENT_UNENROLL_SUCCESSFUL";
        
        // Prepare the query to remove the student from the enrollment table
        $unenrollStmt = $pdo->prepare("DELETE FROM students_enrollments WHERE class_id = :class_id AND student_id = :student_id");
        $unenrollStmt->execute(['class_id' => $class_id, 'student_id' => $student_id]);

        // Update the studentTotal in the classes table
        $pdo->prepare("
            UPDATE classes 
            SET studentTotal = GREATEST(COALESCE(studentTotal, 0) - 1, 0)
            WHERE id = :class_id
        ")->execute(['class_id' => $class_id]);

        // Fetch activity IDs specific to this class only
        $getActivityStmt = $pdo->prepare("
            SELECT id 
            FROM activities 
            WHERE class_id = :class_id
        ");
        $getActivityStmt->execute(['class_id' => $class_id]);
        $activityIds = $getActivityStmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove activity submissions for this class only
        if (!empty($activityIds)) {
            $activityIdsPlaceholders = implode(',', array_map(function ($key) {
                return ":activity_id_$key";
            }, array_keys($activityIds)));

            $unenrollActivityStmt = $pdo->prepare("
                DELETE FROM activity_submissions 
                WHERE student_id = :student_id 
                AND activity_id IN ($activityIdsPlaceholders)
            ");
            $unenrollActivityStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            foreach ($activityIds as $key => $activityId) {
                $unenrollActivityStmt->bindValue(":activity_id_$key", $activityId, PDO::PARAM_INT);
            }
            $unenrollActivityStmt->execute();
        }

        // Remove attendance records for this class only
        $unenrollAttendanceStmt = $pdo->prepare("
            DELETE FROM attendance 
            WHERE student_id = :student_id 
            AND class_id = :class_id
        ");
        $unenrollAttendanceStmt->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        // Remove student grades for this class only
        $unenrollGradesStmt = $pdo->prepare("
            DELETE FROM student_grades 
            WHERE student_id = :student_id 
            AND class_id = :class_id
        ");
        $unenrollGradesStmt->execute(['student_id' => $student_id, 'class_id' => $class_id]);

        // Fetch class details for notification
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
        $type = 'unenrollment';
        $title = "You have been unenrolled from the class '$subject_name' (Class: $class_name) by $teacher.";
        $description = "You have been unenrolled from the subject '$subject_name', instructed by $teacher. Class ID: $class_id.";
        $link = "/capstone/students/student_classes.php?class_id=$class_id";

        $notificationStmt = $pdo->prepare("
            INSERT INTO student_notifications (user_id, type, title, description, link, status) 
            VALUES (:user_id, :type, :title, :description, :link, 'unread')
        ");
        $notificationStmt->execute([
            'user_id' => $student_id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'link' => $link,
        ]);

        $_SESSION['STATUS'] = "STUDENT_UNENROLL_SUCCESSFUL";
        if (isset($_SERVER['HTTP_REFERER'])) {
            $_SESSION['STATUS'] = "STUDENT_UNENROLL_SUCCESSFUL";
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: ../../index.php");
        }
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>