<?php
session_start();
require_once '../../server/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the required fields are set
    if (isset($_POST['reminder_content'], $_POST['reminder_date'], $_SESSION['teacher_name'])) {
        $teacher_name = $_SESSION['teacher_name'];
        $reminder_content = trim($_POST['reminder_content']);
        $reminder_date = $_POST['reminder_date'];

        // Validate the input
        if (!empty($reminder_content) && !empty($reminder_date)) {
            try {
                // Insert the reminder into the database
                $sql = "INSERT INTO teacher_reminders (teacher_name, reminder_content, reminder_date, created_at) 
                        VALUES (:teacher_name, :reminder_content, :reminder_date, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
                $stmt->bindParam(':reminder_content', $reminder_content, PDO::PARAM_STR);
                $stmt->bindParam(':reminder_date', $reminder_date, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    // Success: Reminder added
                    $_SESSION['STATUS'] = "ADD_REMINDER_SUCCESS";
                    $_SESSION['success_message'] = "Reminder added successfully.";
                    header('Location: ../../../index.php'); // Redirect location
                    exit();
                } else {
                    // Failure: Database insertion error
                    $_SESSION['STATUS'] = "ADD_REMINDER_FAILURE";
                    $_SESSION['error_message'] = "Failed to add the reminder. Please try again.";
                    header('Location: ../../../index.php'); // Redirect location
                    exit();
                }
            } catch (Exception $e) {
                // Failure: Exception occurred
                $_SESSION['STATUS'] = "ADD_REMINDER_ERROR";
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: ../../../index.php'); // Redirect location
                exit();
            }
        } else {
            // Validation failure
            $_SESSION['STATUS'] = "ADD_REMINDER_VALIDATION_FAILURE";
            $_SESSION['error_message'] = "Reminder content and date cannot be empty.";
            header('Location: ../../../index.php'); // Redirect location
            exit();
        }
    } else {
        // Invalid request
        $_SESSION['STATUS'] = "ADD_REMINDER_INVALID_REQUEST";
        $_SESSION['error_message'] = "Invalid request. Please fill in all required fields.";
        header('Location: ../../../index.php'); // Redirect location
        exit();
    }
} else {
    // Unauthorized access
    $_SESSION['STATUS'] = "ADD_REMINDER_UNAUTHORIZED";
    header('Location: ../../../index.php'); // Redirect location
    exit();
}
