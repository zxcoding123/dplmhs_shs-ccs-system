<?php
header('Content-Type: application/json');
require 'processes/server/conn.php';

try {
    $messageId = $_POST['id'];

    // Delete message from database
    $stmt = $pdo->prepare("
        DELETE FROM messages 
        WHERE id = :message_id
    ");
    $stmt->execute(['message_id' => $messageId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>