<?php
require_once '../../server/conn.php'; // Database connection

session_start(); // Start the session to access $_SESSION variables

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $activity_id = isset($_GET['activityId']) ? intval($_GET['activityId']) : null;

    if (!$activity_id) {
        $_SESSION['STATUS'] = "ACT_ERROR"; // Set session status
        header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Fetch file path from activity_attachments
        $stmt_fetch = $pdo->prepare("SELECT file_path FROM activity_attachments WHERE activity_id = :activity_id");
        $stmt_fetch->execute(['activity_id' => $activity_id]);
        $file_to_delete = $stmt_fetch->fetchColumn();

        // Delete related records from activity_submissions
        $stmt_delete_submission = $pdo->prepare("DELETE FROM activity_submissions WHERE activity_id = :activity_id");
        $stmt_delete_submission->execute(['activity_id' => $activity_id]);

        // Delete attachment from activity_attachments
        $stmt_delete_attachment = $pdo->prepare("DELETE FROM activity_attachments WHERE activity_id = :activity_id");
        $stmt_delete_attachment->execute(['activity_id' => $activity_id]);

        // Delete activity from activities
        $stmt_delete_activity = $pdo->prepare("DELETE FROM activities WHERE id = :activity_id");
        $stmt_delete_activity->execute(['activity_id' => $activity_id]);

        $pdo->commit();

        // Delete the file if it exists
        if ($file_to_delete && file_exists('../../../../uploads/files/' . $file_to_delete)) {
            unlink('../../../../uploads/files/' . $file_to_delete);
        }

        $_SESSION['STATUS'] = "ACT_DELETED_SUCCESS"; // Set session status on success
        header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = "ACT_ERROR"; // Set session status on error
        header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
        exit();
    }
} else {
    $_SESSION['STATUS'] = "ACT_ERROR"; // Set session status for invalid request method
    header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
    exit();
}
?>
