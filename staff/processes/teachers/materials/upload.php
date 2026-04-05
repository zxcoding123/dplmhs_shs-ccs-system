<?php
session_start();
require_once '../../server/conn.php'; // Ensure correct path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_GET['class_id'];
    // Validate and sanitize inputs
    $resourceTitle = filter_input(INPUT_POST, 'resource_title', FILTER_SANITIZE_STRING);
    $resourceDescription = filter_input(INPUT_POST, 'resource_description', FILTER_SANITIZE_STRING);
    $resourceType = filter_input(INPUT_POST, 'resource_type', FILTER_SANITIZE_STRING);

    // File upload configuration
    $uploadDir = '../../../../uploads/materials/'; // Ensure this directory exists and is writable
    $file = $_FILES['resource_file'];
    $fileName = basename($file['name']);
    $targetFilePath = $uploadDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Allowed file types
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'mp3', 'avi', 'mpg', 'gif'];

    if (in_array($fileType, $allowedTypes)) {
        // Move uploaded file to the server directory
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                // Insert data into the database
                $stmt = $pdo->prepare(
                    "INSERT INTO learning_resources (class_id, subject_id, resource_name, resource_type, resource_description, resource_url)
                    VALUES (:class_id, :subject_id, :resource_name, :resource_type, :resource_description, :resource_url)"
                );
                $stmt->execute([
                    ':class_id' => $_GET['class_id'], // Pass class_id from form or session
                    ':subject_id' => $_GET['subject_id'], // Pass subject_id from form or session
                    ':resource_name' => $resourceTitle,
                    ':resource_type' => $resourceType,
                    ':resource_description' => $resourceDescription,
                    ':resource_url' => $fileName,
                ]);

                // Fetch enrolled students (optional notification step)
                $stmt = $pdo->prepare("
                    SELECT student_id 
                    FROM students_enrollments 
                    WHERE class_id = :class_id
                ");
                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                $stmt->execute();
                $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Fetch subject for notifications
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

                // If there are students, send notifications
                if (!empty($students)) {
                    $notificationStmt = $pdo->prepare("
                        INSERT INTO student_notifications (user_id, type, title, description, date, link, status) 
                        VALUES (:user_id, 'assessment', :title, :description, NOW(), :link, 'unread')
                    ");

                    // Notification details
                    $title = "New Learning Material Added: $resourceTitle at subject: $subject";
                    $link = "/capstone/students/student_classes.php?class_id=" . $class_id;

                    // Loop through the students and insert notifications
                    foreach ($students as $student_id) {
                        $description = "A new learning material titled '$resourceTitle' has been added to your class. Subject: '$subject'.";
                        $notificationStmt->execute([
                            'user_id' => $student_id,
                            'title' => $title,
                            'description' => $description,
                            'link' => $link,
                        ]);
                    }
                }

                $_SESSION['STATUS'] = "NEW_LEARNING_MATERIAL_ADDED";

                // Success response
                $message = empty($students) 
                    ? "Resource uploaded successfully! No students enrolled, so no notifications sent."
                    : "Resource uploaded successfully! Notifications have been sent to all students.";
                echo json_encode(['success' => true, 'message' => $message]);

            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => "Failed to upload resource: " . $e->getMessage()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload the file. Please try again.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Invalid file type. Only " . implode(', ', $allowedTypes) . " files are allowed."]);
    }
}

// Redirect logic
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Fallback in case HTTP_REFERER is not set
    header("Location: ../../index.php"); // Replace with your fallback URL
    exit;
}
?>