<?php
session_Start();
require_once 'processes/server/conn.php'; // Make sure the path is correct



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_name = $_SESSION['teacher_name'];
    $reminder_content = $_POST['reminder_content'];

    $sql = "INSERT INTO teacher_reminders (teacher_name, reminder_content, created_at) VALUES (:teacher_name, :reminder_content, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
    $stmt->bindParam(':reminder_content', $reminder_content, PDO::PARAM_STR);

    if ($stmt->execute()) {
        header('Location: index.php'); // Redirect back to the main page
    } else {
        echo "Error adding reminder.";
    }
}
?>
