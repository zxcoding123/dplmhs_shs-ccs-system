<?php
// Start session and include database connection
session_start();
require_once '../../server/conn.php';

// Get the 'id' parameter from the URL (the assessment ID)
$assessment_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($assessment_id) {
    try {
        // Get the current attachment from the database
        $sql = "SELECT attachment FROM assessments WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $assessment_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // If there is an attachment, attempt to delete it from the file system
        if ($result && !empty($result['attachment'])) {
            $filePath = '../../../../uploads/files/' . $result['attachment'];

            // Check if the file exists before trying to delete it
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    // File deleted successfully, now update the database to set attachment to NULL
                    $sql = "UPDATE assessments SET attachment = NULL WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $assessment_id, PDO::PARAM_INT);
                    $stmt->execute();

                    // Set success message in session
                    $_SESSION['STATUS'] = "ATTACHMENT_DELETE_SUCCESS";
                } else {
                    // If unable to delete the file, set an error message
                    $_SESSION['STATUS'] = "ATTACHMENT_FILE_DELETE_ERROR";
                }
            } else {
                // File doesn't exist on the server
                $_SESSION['STATUS'] = "ATTACHMENT_FILE_NOT_FOUND";
            }
        } else {
            // No attachment found in the database for this assessment
            $_SESSION['STATUS'] = "NO_ATTACHMENT_FOUND";
        }
    } catch (PDOException $e) {
        // Set error message in session for database errors
        $_SESSION['STATUS'] = "ATTACHMENT_DELETE_ERROR";
    }

    // Redirect back to the assessment management page
    header("Location: ../../../teacher_subject_management_activity.php?id=$assessment_id");
    exit();
} else {
    // Redirect back if there's no valid assessment ID
    $_SESSION['STATUS'] = "ASSESSMENT_NOT_FOUND";
    header("Location: ../../../teacher_subject_management_activity.php");
    exit();
}
