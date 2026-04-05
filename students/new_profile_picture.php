<?php
require 'processes/server/conn.php'; // Include your PDO connection setup

session_start(); // Start the session to access session variables

$student_id = $_SESSION['student_id'];  // Get student ID from session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['student_picture']) && $_FILES['student_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['student_picture'];

        // Define directory for storing the uploaded images
        $upload_dir = '../uploads/profile_pictures/';

        // Make sure the directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate a unique file name for the uploaded file
        $fileName = uniqid('student_') . '_' . basename($uploadedFile['name']);
        $filePath = $upload_dir . $fileName;

        // Get the current profile picture of the student to delete it
        $stmt = $pdo->prepare("SELECT picture FROM student_pictures WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(':user_id', $student_id);
        $stmt->execute();
        $previousPicture = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete the previous picture record and file if it exists
        if ($previousPicture) {
            $previousPicturePath = $upload_dir . $previousPicture['picture'];

            // Delete the file from the filesystem
            if (file_exists($previousPicturePath)) {
                unlink($previousPicturePath); // Delete the previous file
            }

            // Delete the record from the student_pictures table
            $deleteStmt = $pdo->prepare("DELETE FROM student_pictures WHERE user_id = :user_id");
            $deleteStmt->bindParam(':user_id', $student_id);
            $deleteStmt->execute();
        }

        // Move the uploaded file to the destination directory
        if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            try {
                // Insert the new file path into the student_pictures table
                $stmt = $pdo->prepare("
                    INSERT INTO student_pictures (user_id, picture)
                    VALUES (:user_id, :picture)
                ");
                $stmt->bindParam(':user_id', $student_id);
                $stmt->bindParam(':picture', $fileName);
                $stmt->execute();

                echo "Picture uploaded successfully!";
                $_SESSION['STATUS'] = "NEW_PROF_PIC";
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['STATUS'] = "NEW_PROF_PIC_FAIL";
            echo "Failed to upload the file.";
        }
    } else {
        $_SESSION['STATUS'] = "NEW_PROF_PIC_FAIL";
        echo "No file uploaded or there was an error in the upload.";
    }
} else {
    $_SESSION['STATUS'] = "NEW_PROF_PIC_FAIL";
    echo "Invalid request method.";
}

header('Location: student_dashboard.php');
?>
