<?php
require '../../server/conn.php'; // Ensure this points to your database connection file

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $_POST['title'];
        $type = $_POST['type'];
        $message = $_POST['message'];
        $due_date = $_POST['due_date'];
        $due_time = $_POST['due_time'];
        $min_points = $_POST['min_points'];
        $max_points = $_POST['max_points'];
        $class_id = $_POST['class_id']; // Ensure this is passed in the form
        $subject_id = $_POST['subject_id']; // Ensure this is passed in the form
        $term = $_POST['term'];

        $due_time_12hr = date("h:iA", strtotime($due_time));
        $current_time = date('Y-m-d H:i:s');

        // Check for duplicate activity
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE title = ? AND class_id = ? AND subject_id = ?");
        $stmt->execute([$title, $class_id, $subject_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['STATUS'] = "ACT_ERROR_SAME";
            $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../teacher_dashboard.php';
            header("Location: $referrer");
            exit;
        }

        // Insert activity
        $stmt = $pdo->prepare("INSERT INTO activities (title, type, message, due_date, due_time, min_points, max_points, class_id, subject_id, created_at, updated_at, term) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $type, $message, $due_date, $due_time_12hr, $min_points, $max_points, $class_id, $subject_id, $current_time, $current_time, $term])) {
            $activity_id = $pdo->lastInsertId();

            // Add submissions for each student in the class
            $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $student) {
                $student_id = $student['student_id'];

                $stmt = $pdo->prepare("INSERT INTO activity_submissions (activity_id, student_id, submission_date, score, feedback, status) VALUES (?, ?, NULL, 0, NULL, 'pending')");
                $stmt->execute([$activity_id, $student_id]);
            }

            // Handle file upload if exists
            if (!empty($_FILES['attachment']['tmp_name'])) {
                $file_name = $_FILES['attachment']['name'];
                $file_tmp_path = $_FILES['attachment']['tmp_name'];
                $upload_dir = '../../../../uploads/files/';
                $new_file_name = uniqid() . '-' . $file_name;
                $destination = $upload_dir . $new_file_name;

                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($file_tmp_path, $destination)) {
                    $stmt = $pdo->prepare("INSERT INTO activity_attachments (activity_id, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$activity_id, $file_name, $destination, $current_time]);
                } else {
                    $_SESSION['STATUS'] = "ACT_ATTACHMENT_ERROR";
                    throw new Exception("Failed to upload the attachment.");
                }
            }

            $stmt = $pdo->prepare("
            SELECT student_id 
            FROM students_enrollments 
            WHERE class_id = :class_id
        ");
            $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($students)) {
                throw new Exception("No students are enrolled in this class.");
            }

            $subjectStmt = $pdo->prepare("
        SELECT subject 
        FROM classes 
        WHERE id = :class_id
    ");
            $subjectStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $subjectStmt->execute();
            $subject = $subjectStmt->fetchColumn();

            if (!$subject) {
                throw new Exception("Class not found or subject is unavailable.");
            }


            // Prepare the query to insert notifications for each student
            $notificationStmt = $pdo->prepare("
            INSERT INTO student_notifications (user_id, type, title, description, date, link, status) 
            VALUES (:user_id, 'assessment', :title, :description, NOW(), :link, 'unread')
        ");

            // Notification details
            $title = "New Assessment Added: $title" . " at subject: " . $subject;
            $link = "https://ccs-sms.com/capstone/students/student_classes.php?class_id=" . $class_id;

            // Loop through the students and insert notifications
            foreach ($students as $student_id) {
                $description = "A new assessment titled '$assessment_name' of type: '$type' has been added to your class for the subject: '$subject', due on: $due_time_12hr.";
                $notificationStmt->execute([
                    'user_id' => $student_id,
                    'title' => $title,
                    'description' => $description,
                    'link' => $link,
                ]);
            }

            // Success response
            echo json_encode(['success' => true, 'message' => 'Notifications have been sent to all students.']);


            $_SESSION['STATUS'] = "ACT_ADDED_SUCCESS";
            $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../teacher_dashboard.php';
            header("Location: $referrer");
            exit;
        } else {
            throw new Exception("Failed to create activity.");
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    // Handle invalid request method (GET)
    $_SESSION['STATUS'] = "ACT_ERROR";
    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../../teacher_dashboard.php';
    header("Location: $referrer");
    exit;
}
?>