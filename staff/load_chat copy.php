<?php
session_start();
require "processes/server/conn.php";

$receiverId = $_GET['receiver_id'];
$receiverType = $_GET['receiver_type'];
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

$query = "
    SELECT sender_id, message, sender_type, sender_name, receiver_name, timestamp
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ? AND sender_type = ? AND receiver_type = ?)
       OR (sender_id = ? AND receiver_id = ? AND sender_type = ? AND receiver_type = ?)
    ORDER BY id ASC
";


$stmt = $pdo->prepare($query);
$stmt->execute([$userId, $receiverId, $userType, $receiverType, $receiverId, $userId, $receiverType, $userType]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);

?>