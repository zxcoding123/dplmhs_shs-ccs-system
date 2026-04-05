<?php
session_Start();
require_once 'processes/server/conn.php'; // Make sure the path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_id = $_POST['reminder_id'];

    $sql = "DELETE FROM teacher_reminders WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $reminder_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        http_response_code(500);
        echo "Error deleting reminder.";
    }
}
?>
