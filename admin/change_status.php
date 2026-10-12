<?php
session_start();
require 'processes/server/conn.php'; // Include your PDO connection setup

if (isset($_POST['user_id']) && isset($_POST['new_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];

    // Prepare the SQL to update status
    $stmt = $pdo->prepare("UPDATE staff_accounts SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $user_id);
    if ($stmt->execute()) {
        // Keep the shared users record in sync so activation/deactivation actually affects login
        $lowerStatus = strtolower($new_status);
        $syncStmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = (SELECT user_id FROM staff_accounts WHERE id = :id)");
        $syncStmt->bindParam(':status', $lowerStatus);
        $syncStmt->bindParam(':id', $user_id);
        $syncStmt->execute();

        $_SESSION['STATUS'] = "NEW_STATUS_SUCCESFUL";
        echo 'Status updated successfully.';
    } else {
        echo 'Failed to update status.';
    }
}
?>
