<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the SQL query
    $sql = "SELECT * FROM teacher_reminders WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the reminder data
    $reminder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reminder) {
        // Return reminder content as JSON
        echo json_encode(['reminder_content' => $reminder['reminder_content']]);
    } else {
        echo json_encode(['error' => 'Reminder not found']);
    }
}
?>
