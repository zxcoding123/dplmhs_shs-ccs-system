<?php 
// delete.php
session_start();
require '../../server/conn.php'; // Adjust the path based on your directory structure

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['student_id'];
    // Delete the notification from the database
    $stmt = $pdo->prepare("DELETE FROM student_notifications WHERE id = ?  AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $_SESSION['STATUS'] = "DELETE_NOTIFICATION_SUCCESFUL";
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    } else {
        header('Location: ../../index.php'); // Fallback if no referrer
        exit();
    }
}

?>