<?php
require '../../server/conn.php';
session_start();

// Ensure `user_id` is available
$userId = $_SESSION['user_id'] ?? null;

if ($userId && isset($_POST['id'])) {
    $notificationId = $_POST['id'];

    // Update the status to "read" for the specific notification and user
    $query = "UPDATE staff_notifications SET status = 'read' WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
    $_SESSION['STATUS'] = "READ_NOTIFICATIONS";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Redirect if invalid access
    header('Location: ../../index.php');
    exit;
}
?>
