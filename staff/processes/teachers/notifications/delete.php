<?php
require '../../server/conn.php';
session_start();

// Ensure `user_id` is available
$userId = $_SESSION['user_id'] ?? null;

if ($userId && isset($_POST['id'])) {
    $notificationId = $_POST['id'];

    // Delete the specific notification for the user
    $query = "DELETE FROM staff_notifications WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);

    $_SESSION['STATUS'] = "DELETE_NOTIFICATIONS";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Redirect if invalid access
    header('Location: ../../index.php');
    exit;
}
?>
