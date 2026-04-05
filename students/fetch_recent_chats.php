<?php
session_start();
require "processes/server/conn.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $query = "
        SELECT m.id, m.sender_id, m.receiver_id, m.sender_type, m.receiver_type, 
               m.message AS last_message, DATE_FORMAT(m.timestamp, '%h:%i %p') AS time,
               m.status,
               CASE 
                   WHEN m.sender_id = ? THEN m.receiver_id
                   ELSE m.sender_id
               END AS chat_partner,
               CASE 
                   WHEN m.sender_id = ? THEN m.receiver_type
                   ELSE m.sender_type
               END AS chat_partner_type,
               CASE 
                   WHEN m.sender_id = ? THEN m.receiver_name
                   ELSE m.sender_name
               END AS full_name,
               CASE 
                   WHEN m.sender_id = ? THEN 'You'
                   ELSE 'Them'
               END AS sender_display
        FROM messages m
        WHERE m.id = (
            SELECT MAX(id) FROM messages
            WHERE (sender_id = m.sender_id AND receiver_id = m.receiver_id)
               OR (sender_id = m.receiver_id AND receiver_id = m.sender_id)
        )
        AND (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.timestamp DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each chat entry
    $seenUsers = [];
    $filteredChats = [];

    foreach ($chats as &$chat) {
        // Ensure each chat partner appears only once
        if (!in_array($chat['chat_partner'], $seenUsers)) {
            $seenUsers[] = $chat['chat_partner'];
            
            // Mark unread messages
            $chat['is_new'] = ($chat['status'] === 'unread') ? true : false;

            // Add sender prefix
            $chat['last_message'] = ($chat['sender_display'] === "You") 
                ? "You: " . $chat['last_message'] 
                : $chat['last_message'];

            $filteredChats[] = $chat;
        }
    }

    echo json_encode($filteredChats);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
