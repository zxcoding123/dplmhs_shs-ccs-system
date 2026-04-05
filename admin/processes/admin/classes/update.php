<?php
require '../../server/conn.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_GET['id'];
    $class = $_POST['selectedClassAdd'];
    $subjectName = $_POST['subjectNameClass'];
    $teacher = $_POST['teacher'];
    $semester = $_POST['semester'];
    $classDesc = $_POST['classDesc'];

    // Check if any required field is empty
    if (empty($id) || empty($class) || empty($subjectName) || empty($teacher) || empty($semester)) {
        $_SESSION['STATUS'] = "EDIT_CLASS_FIELDS_REQUIRED";
        header('Location: ../../../class_management.php');
        exit;
    }

    try {
        // Step 1: Get the existing teacher
        $existingTeacherQuery = "SELECT teacher FROM classes WHERE id = :id LIMIT 1";
        $existingTeacherStmt = $pdo->prepare($existingTeacherQuery);
        $existingTeacherStmt->execute([':id' => $id]);
        $existingTeacher = $existingTeacherStmt->fetchColumn();

        // Step 2: Update class details (always run this)
        $stmt = $pdo->prepare("UPDATE classes SET name = :class, subject = :subjectName, semester = :semester, description = :description WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':class', $class, PDO::PARAM_STR);
        $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
        $stmt->bindParam(':description', $classDesc, PDO::PARAM_STR);
        $stmt->execute();

        // Step 3: Handle teacher change (if applicable)
        if ($existingTeacher !== $teacher) {
            // Update teacher in the class table
            $updateTeacherStmt = $pdo->prepare("UPDATE classes SET teacher = :teacher WHERE id = :id");
            $updateTeacherStmt->bindParam(':teacher', $teacher, PDO::PARAM_STR);
            $updateTeacherStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $updateTeacherStmt->execute();

            // Step 4: Fetch new teacher's ID
            $getStaffIdQuery = "SELECT id FROM staff_accounts WHERE fullName = :fullName LIMIT 1";
            $getStaffIdStmt = $pdo->prepare($getStaffIdQuery);
            $getStaffIdStmt->execute([':fullName' => $teacher]);
            $staffAccount = $getStaffIdStmt->fetch(PDO::FETCH_ASSOC);

            if (!$staffAccount) {
                $_SESSION['STATUS'] = "EDIT_CLASS_TEACHER_NOT_FOUND";
                header('Location: ../../../class_management.php');
                exit;
            }

            $teacherId = $staffAccount['id'];

            // Step 5: Notify the new teacher
            $notificationTitle = 'You have been assigned as a teacher for ' . htmlspecialchars($class) . ' to be taught under subject of: ' . htmlspecialchars($subjectName) . '!';
            $notificationDescription = 'You have been assigned as the teacher for ' . htmlspecialchars($class) . ', under the subject of ' . htmlspecialchars($subjectName) . '. Please review the class details and prepare for its integration.';
            $date = date('Y-m-d H:i:s');
            $link = '/staff/class_management.php';
            $type = "class";

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

            // Step 6: Notify students
            $getStudentsQuery = "SELECT student_id FROM students_enrollments WHERE class_id = :class_id";
            $getStudentsStmt = $pdo->prepare($getStudentsQuery);
            $getStudentsStmt->execute([':class_id' => $id]);
            $students = $getStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $notificationTitle = 'Class details changed for ' . htmlspecialchars($class);
            $notificationDescription = 'The current teacher for ' . htmlspecialchars($class) . ' has been changed to: ' . htmlspecialchars($teacher);
            $link = '/student/student_dashboard.php';

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
        }

        $_SESSION['STATUS'] = "EDIT_CLASS_SUCCESS";
        header('Location: ../../../class_management.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "EDIT_CLASS_ERROR";
        header('Location: ../../../class_management.php');
        exit;
    }
}
?>