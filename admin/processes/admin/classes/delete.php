<?php
require '../../server/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    }

    try {
        // Step 1: Get the class details (e.g. teacher and enrolled students) to notify them
        $classQuery = "SELECT teacher FROM classes WHERE id = :id LIMIT 1";
        $classStmt = $pdo->prepare($classQuery);
        $classStmt->execute([':id' => $id]);
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            echo json_encode(['success' => false, 'message' => 'Class not found.']);
        }

        $teacher = $class['teacher']; // Save the teacher's name to notify later

        // Step 2: Delete the class
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);



        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "DELETE_CLASS_SUCCESS";

            // Step 3: Send notifications to the teacher and students

            // Get teacher's ID
            $getStaffIdQuery = "SELECT id FROM staff_accounts WHERE fullName = :fullName LIMIT 1";
            $getStaffIdStmt = $pdo->prepare($getStaffIdQuery);
            $getStaffIdStmt->execute([':fullName' => $teacher]);
            $staffAccount = $getStaffIdStmt->fetch(PDO::FETCH_ASSOC);

            if ($staffAccount) {
                $teacherId = $staffAccount['id'];

                // Notify teacher about class deletion
                $notificationTitle = 'Class ' . htmlspecialchars($id) . ' has been deleted!';
                $notificationDescription = 'The class you were assigned to teach (' . htmlspecialchars($id) . ') has been deleted. Please review your assignments.';
                $date = date('Y-m-d H:i:s');
                $link = '/staff/class_management.php';
                $type = 'class';

                $insertStaffNotificationQuery = "INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                                                 VALUES (:user_id, :type, :title, :description, :date, :link)";
                $insertStaffNotificationStmt = $pdo->prepare($insertStaffNotificationQuery);
                $insertStaffNotificationStmt->execute([
                    ':user_id' => $teacherId,
                    ':type' => $type,
                    ':title' => $notificationTitle,
                    ':description' => $notificationDescription,
                    ':date' => $date,
                    ':link' => $link,
                ]);

                echo "Notification sent to teacher regarding class deletion.";
            } else {
                echo "No teacher found with the name: " . htmlspecialchars($teacher);
            }

            // Step 4: Notify students enrolled in the class
            $getStudentsQuery = "SELECT student_id FROM students_enrollments WHERE class_id = :class_id";
            $getStudentsStmt = $pdo->prepare($getStudentsQuery);
            $getStudentsStmt->execute([':class_id' => $id]);
            $students = $getStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $notificationTitle = 'Class ' . htmlspecialchars($id) . ' has been deleted!';
            $notificationDescription = 'The class you were enrolled in (' . htmlspecialchars($id) . ') has been deleted. Please check for any further announcements.';
            $link = '/student/class_details.php'; // Link where students can check further details

            $insertStudentNotificationQuery = "INSERT INTO student_notifications (user_id, type, title, description, date, link, status) 
                                               VALUES (:user_id, :type, :title, :description, :date, :link, 'unread')";
            $insertStudentNotificationStmt = $pdo->prepare($insertStudentNotificationQuery);

            foreach ($students as $student) {
                $insertStudentNotificationStmt->execute([
                    ':user_id' => $student['student_id'],
                    ':type' => $type,
                    ':title' => $notificationTitle,
                    ':description' => $notificationDescription,
                    ':date' => $date,
                    ':link' => $link,
                ]);
            }

            echo "Notifications sent to all enrolled students.";

            // Step 1: Get all activity IDs based on class_id
            $getActivitiesQuery = "SELECT id FROM activities WHERE class_id = :class_id";
            $getActivitiesStmt = $pdo->prepare($getActivitiesQuery);
            $getActivitiesStmt->execute([':class_id' => $id]);

            // Fetch all activities
            $activities = $getActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Step 2: If there are activities, proceed with deleting related entries
            if (!empty($activities)) {

                // Prepare delete query for activity_submissions
                $deleteSubmissionsStmt = $pdo->prepare("DELETE FROM activity_submissions WHERE activity_id = :activity_id");

                // Prepare delete query for activity_attachments
                $deleteAttachmentsStmt = $pdo->prepare("DELETE FROM activity_attachments WHERE activity_id = :activity_id");

                // Step 3: Loop through each activity ID
                foreach ($activities as $activity) {
                    // Get the activity ID
                    $activity_id = $activity['id'];

                    // Delete corresponding records in activity_submissions
                    $deleteSubmissionsStmt->execute([':activity_id' => $activity_id]);

                    // Delete corresponding records in activity_attachments
                    $deleteAttachmentsStmt->execute([':activity_id' => $activity_id]);
                }

                // Step 4: Now delete activities based on class_id
                $deleteActivitiesStmt = $pdo->prepare("DELETE FROM activities WHERE class_id = :class_id");
                $deleteActivitiesStmt->execute([':class_id' => $id]);
            }

            $stmt = $pdo->prepare("DELETE FROM student_grades WHERE class_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM classes_meetings WHERE class_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM student_grades WHERE class_id = :id");
            $stmt->bindParam(':id', $id, type: PDO::PARAM_INT);
            $stmt->execute();


            $stmt = $pdo->prepare("DELETE FROM student_enrollments WHERE class_id = :id");
            $stmt->bindParam(':id', $id, type: PDO::PARAM_INT);
            $stmt->execute();


            

            $stmt = $pdo->prepare("DELETE FROM lecture_rubrics WHERE class_id = :id");
            $stmt->bindParam(':id', $id, type: PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM laboratory_rubrics WHERE class_id = :id");
            $stmt->bindParam(':id', $id, type: PDO::PARAM_INT);
            $stmt->execute();

            
            $stmt = $pdo->prepare("DELETE FROM learning_resources WHERE class_id = :id");
            $stmt->bindParam(':id', $id, type: PDO::PARAM_INT);
            $stmt->execute();


            $stmt = $pdo->prepare("DELETE FROM activities WHERE class_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();



        } else {
            $_SESSION['STATUS'] = "DELETE_CLASS_FAIL";
            header('Location: ../../../class_management.php');
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . htmlspecialchars($e->getMessage())]);
    }
}
header('Location: ../../../class_management.php');
?>