<?php
require 'processes/server/conn.php';

session_start();
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if (!$userId || !$userType) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$groupId = $_POST['group_id'] ?? null;
$message = $_POST['message'] ?? null;

if (!$groupId || !$message) {
    echo json_encode(['error' => 'Group ID and message required']);
    exit;
}

// Check if user is a member of this group
$stmt = $pdo->prepare("
    SELECT 1 FROM group_members 
    WHERE group_id = ? AND user_id = ? AND user_type = ?
");
$stmt->execute([$groupId, $userId, $userType]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Not a member of this group']);
    exit;
}

// Insert message
$stmt = $pdo->prepare("
    INSERT INTO group_messages (group_id, sender_id, sender_type, message, status)
    VALUES (?, ?, ?, ?, 'sent')
");
$success = $stmt->execute([$groupId, $userId, $userType, $message]);

if ($success) {
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} else {
    echo json_encode(['error' => 'Failed to send message']);
}
