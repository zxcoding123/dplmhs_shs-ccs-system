

<?php
require '../../server/conn.php';
session_start();

// Ensure `user_id` is available
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Update the status to "read" for the specific notification and user
    $query = "DELETE FROM staff_notifications WHERE user_id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $userId]);

   $_SESSION['STATUS'] = "DELETE_ALL_NOTIFICATIONS";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Redirect if invalid access
    header('Location: ../../dashboard.php');
    exit;
}
?>
