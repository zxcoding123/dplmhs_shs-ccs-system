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
        $_SESSION['STATUS'] = "NEW_STATUS_SUCCESFUL";
        echo 'Status updated successfully.';
    } else {
        echo 'Failed to update status.';
    }
}
?>
