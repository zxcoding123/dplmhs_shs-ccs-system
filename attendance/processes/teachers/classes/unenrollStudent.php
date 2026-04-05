<?php
session_start();
require '../../../processes/server/conn.php';

if (isset($_GET['class_id']) && isset($_GET['student_id'])) {
    $class_id = $_GET['class_id'];
    $student_id = $_GET['student_id'];

    try {
        // Prepare the query to remove the student from the enrollment table
        $unenrollStmt = $pdo->prepare("DELETE FROM students_enrollments WHERE class_id = :class_id AND student_id = :student_id");
        $unenrollStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $unenrollStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $unenrollStmt->execute();


        // Prepare the query to fetch all matching activity_ids from the activities table
        $getActivityStmt = $pdo->prepare("
SELECT id 
FROM activities
");
        $getActivityStmt->execute();
        $activityIds = $getActivityStmt->fetchAll(PDO::FETCH_COLUMN);

        // Check if there are activity IDs to process
        if (!empty($activityIds)) {
            // Create a dynamic list of named placeholders for activity_ids
            $activityIdsPlaceholders = implode(',', array_map(function ($key) {
                return ":activity_id_$key";
            }, array_keys($activityIds)));

            // Prepare the query to remove the student from the activity_submissions table
            $unenrollStmt = $pdo->prepare("
    DELETE FROM activity_submissions 
    WHERE student_id = :student_id 
    AND activity_id IN ($activityIdsPlaceholders)
");

            // Bind the student_id
            $unenrollStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

            // Bind each activity ID using named placeholders
            foreach ($activityIds as $key => $activityId) {
                $unenrollStmt->bindValue(":activity_id_$key", $activityId, PDO::PARAM_INT);
            }

            // Execute the query
            $unenrollStmt->execute();
        }

        // Prepare the query to fetch all matching activity_ids from the activities table
        $getActivityStmt = $pdo->prepare("
SELECT id 
FROM activities
");
        $getActivityStmt->execute();
        $activityIds = $getActivityStmt->fetchAll(PDO::FETCH_COLUMN);

        // Check if there are activity IDs to process
        if (!empty($activityIds)) {
            // Create a dynamic list of named placeholders for activity_ids
            $activityPlaceholders = implode(',', array_map(function ($key) {
                return ":activity_id_$key";
            }, array_keys($activityIds)));

            // Prepare the query to remove the student from the activity_submissions table
            $unenrollActivityStmt = $pdo->prepare("
    DELETE FROM activity_submissions 
    WHERE student_id = :student_id 
    AND activity_id IN ($activityPlaceholders)
");

            // Bind the student_id
            $unenrollActivityStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

            // Bind each activity ID using named placeholders
            foreach ($activityIds as $key => $activityId) {
                $unenrollActivityStmt->bindValue(":activity_id_$key", $activityId, PDO::PARAM_INT);
            }

            // Execute the query
            $unenrollActivityStmt->execute();
        }

        // Prepare the query to fetch all matching class_ids from the attendance table
        $getClassStmt = $pdo->prepare("
SELECT DISTINCT class_id 
FROM attendance 
WHERE student_id = :student_id
");
        $getClassStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $getClassStmt->execute();
        $classIds = $getClassStmt->fetchAll(PDO::FETCH_COLUMN);

        // Check if there are class IDs to process
        if (!empty($classIds)) {
            // Create a dynamic list of named placeholders for class_ids
            $classPlaceholders = implode(',', array_map(function ($key) {
                return ":class_id_$key";
            }, array_keys($classIds)));

            // Prepare the query to remove the student from the attendance table
            $unenrollAttendanceStmt = $pdo->prepare("
    DELETE FROM attendance 
    WHERE student_id = :student_id 
    AND class_id IN ($classPlaceholders)
");

            // Bind the student_id
            $unenrollAttendanceStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

            // Bind each class ID using named placeholders
            foreach ($classIds as $key => $classId) {
                $unenrollAttendanceStmt->bindValue(":class_id_$key", $classId, PDO::PARAM_INT);
            }

            // Execute the query
            $unenrollAttendanceStmt->execute();
        }

        // Prepare the query to remove the student from the enrollment table
        $unenrollStmt = $pdo->prepare("DELETE FROM student_grades WHERE student_id = :student_id");
        $unenrollStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $unenrollStmt->execute();

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
        ;
        // Insert notification
        $type = 'unenrollment'; // Changed 'enrollment' to 'unenrollment' to reflect the action
        $title = "You have been unenrolled from the class '$subject_name' (Class: $class_name) by $teacher.";
        $description = "You have been  unenrolled from the subject '$subject_name', instructed by $teacher. Class ID: $class_id.";
        $link = "https://ccs-sms.com/capstone/students/student_classes.php?class_id=$class_id";

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




        $_SESSION['STATUS'] = "STUDENT_UNERNOLL_SUCCESSFUL";
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            // If no referer, you can fallback to a default page
            header("Location: ../../index.php");
        }


        exit;
    } catch (PDOException $e) {
        // Handle error
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>