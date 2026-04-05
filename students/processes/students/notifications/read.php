<?php
session_start();
require '../../server/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['student_id'];
    // Update the notification status in the database
    $stmt = $pdo->prepare("UPDATE student_notifications SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $_SESSION['STATUS'] = "READ_NOTIFICATION_SUCCESSFUL";
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: ../../index.php'); // Fallback if no referrer
    exit();
}
?>