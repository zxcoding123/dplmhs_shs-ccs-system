<?php
session_start();
require '../../server/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Update the notification status in the database
    $stmt = $pdo->prepare("UPDATE admin_notifications SET status = 'read' WHERE id = ?");
    $stmt->execute([$id]);
    // Redirect or return a success message

}

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: ../../index.php'); // Fallback if no referrer
    exit();
}
?>