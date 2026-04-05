<?php
require_once '../../server/conn.php'; // Adjust the path based on your folder structure

session_start(); // Start the session to access session variables
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
$class_id = $_GET['class_id'];
$activity_id = isset($_POST['activity_id']) ? $_POST['activity_id'] : null; // Getting the activity_id from query string

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_file = isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] !== UPLOAD_ERR_NO_FILE 
        ? $_FILES['submission_file'] 
        : null;

    $submission_date = date('Y-m-d H:i:s'); // Current date and time

    if ($activity_id && $student_id) {
        try {
       if (isset($_POST['reset_submission'])) {
    $stmt_check = $pdo->prepare("
        SELECT id, file_path FROM activity_submissions
        WHERE activity_id = :activity_id AND student_id = :student_id
    ");
    $stmt_check->execute([
        'activity_id' => $activity_id,
        'student_id' => $student_id
    ]);
    $existing_submission = $stmt_check->fetch();

    if ($existing_submission) {
        // File deletion with directory check
        $file_path = $existing_submission['file_path'];
        $file_to_delete = realpath('../../../../uploads/submissions/' . $file_path);
        
        if ($file_to_delete && is_file($file_to_delete) && file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        // Reset the submission with score set to 0
        $stmt_update = $pdo->prepare("
            UPDATE activity_submissions
            SET submission_date = NULL,  
                score = 0,                 -- Prevent NULL constraint error
                feedback = NULL,  
                file_path = NULL,  
                status = 'pending'
            WHERE activity_id = :activity_id AND student_id = :student_id
        ");
        $stmt_update->execute([
            'activity_id' => $activity_id,
            'student_id' => $student_id
        ]);

        $_SESSION['STATUS'] = "FILE_SUBMISSION_RESET_SUCCESS";
    }

    // Correct redirection with exit()
    header("Location: ../../../student_classes.php?class_id=" . $class_id . "&url=activity");
    exit();
}

 else {
                // Handle new submission
                $file_path = null;

                // Check if a file is uploaded and move it to the directory
                if ($submission_file) {
                    $upload_dir = '../../../../uploads/submissions/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $original_file_name = basename($submission_file['name']);
                    $unique_file_name = uniqid('submission_', true) . '_' . $original_file_name;
                    $target_file = $upload_dir . $unique_file_name;

                    if (move_uploaded_file($submission_file['tmp_name'], $target_file)) {
                        $file_path = $unique_file_name;
                    }
                }

                // Check if a submission already exists
                $stmt_check = $pdo->prepare("
                    SELECT id FROM activity_submissions
                    WHERE activity_id = :activity_id AND student_id = :student_id
                ");
                $stmt_check->execute([
                    'activity_id' => $activity_id,
                    'student_id' => $student_id
                ]);
                $existing_submission = $stmt_check->fetch();

                if ($existing_submission) {
                    // Update the existing submission
                    $stmt_update = $pdo->prepare("
                        UPDATE activity_submissions
                        SET submission_date = :submission_date,
                            status = 'submitted',
                            file_path = :file_path
                        WHERE activity_id = :activity_id AND student_id = :student_id
                    ");
                    $stmt_update->execute([
                        'submission_date' => $submission_date,
                        'file_path' => $file_path,
                        'activity_id' => $activity_id,
                        'student_id' => $student_id
                    ]);
                } else {
                    // Insert a new submission
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO activity_submissions (activity_id, student_id, submission_date, status, file_path)
                        VALUES (:activity_id, :student_id, :submission_date, 'submitted', :file_path)
                    ");
                    $stmt_insert->execute([
                        'activity_id' => $activity_id,
                        'student_id' => $student_id,
                        'submission_date' => $submission_date,
                        'file_path' => $file_path
                    ]);
                }

                $_SESSION['STATUS'] = "FILE_SUBMISSION_SUCCESS";
                header("Location: ../../../student_classes.php?class_id=" . $class_id."url=activity");
                exit();
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            $_SESSION['STATUS'] = "FILE_SUBMISSION_ERROR";
        }
    } else {
        echo "Activity ID, student ID, or file missing.";
        $_SESSION['STATUS'] = "FILE_SUBMISSION_ERROR";
    }
} else {
    echo "Invalid request method.";
    $_SESSION['STATUS'] = "FILE_SUBMISSION_ERROR";
}


header("Location: ../../../student_classes.php?class_id=" . $class_id."url=activity");
