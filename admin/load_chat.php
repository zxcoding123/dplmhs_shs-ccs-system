<?php
session_start();
require "processes/server/conn.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? $_GET['receiver_id'] : null;
$receiver_type = isset($_GET['receiver_type']) ? $_GET['receiver_type'] : null;

if (!$receiver_id || !$receiver_type) {
    echo json_encode(["error" => "Missing receiver_id or receiver_type"]);
    exit;
}

try {
    // Fetch messages where user is sender or receiver
    $stmt = $pdo->prepare("
        SELECT id, sender_id, sender_type, receiver_id, receiver_type, message, timestamp, sender_name, receiver_name, status
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ? AND receiver_type = ?) 
           OR (sender_id = ? AND receiver_id = ? AND sender_type = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->execute([$user_id, $receiver_id, $receiver_type, $receiver_id, $user_id, $receiver_type]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($messages)) {
        error_log("No messages found for user_id: $user_id, receiver_id: $receiver_id, receiver_type: $receiver_type");
    } else {
        error_log("Fetched " . count($messages) . " messages: " . json_encode($messages));
    }

    // Update status to 'read' for messages where user is receiver
    $updateStmt = $pdo->prepare("
        UPDATE messages 
        SET status = 'read'
        WHERE receiver_id = ? AND sender_id = ? AND status = 'unread'
    ");
    $updateStmt->execute([$user_id, $receiver_id]);
    $updatedRows = $updateStmt->rowCount();
    error_log("Marked $updatedRows messages as read for user_id: $user_id from sender_id: $receiver_id");

    header('Content-Type: application/json');
    echo json_encode($messages);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>