

<?php
session_start();
require 'processes/server/conn.php'; // Adjust the path as needed
if (isset($_POST['admin_id']) && isset($_POST['new_status'])) {
    $admin_id = $_POST['admin_id'];
    $new_status = $_POST['new_status'];

    // Debug: Log the status and ID to ensure they're received correctly
    error_log("Updating admin status for admin_id: $admin_id to status: $new_status");

    // Prepare SQL statement to update status in staff_accounts
    $stmt = $pdo->prepare("UPDATE  `admin` SET status = :status WHERE id = :id AND role = 'Admin'");

    // Bind parameters and execute the query
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $admin_id);

    $_SESSION['STATUS'] = "ADMIN_NEW_STATUS_SUCCESFUL";
    if ($stmt->execute()) {
        echo 'Status updated successfully.';
    } else {
        echo 'Failed to update status.';
    }
} else {
    echo 'Required data not received.';
}
?>
