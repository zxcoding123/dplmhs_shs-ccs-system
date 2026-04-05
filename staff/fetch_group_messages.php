<?php
require 'processes/server/conn.php';

session_start();
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if (!$userId || !$userType) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$groupId = $_GET['group_id'] ?? null;
if (!$groupId) {
    echo json_encode(['error' => 'Group ID required']);
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

// Fetch messages with sender names from appropriate tables
$stmt = $pdo->prepare("
    SELECT gm.*, 
           CASE 
               WHEN gm.sender_type = 'admin' THEN (SELECT fullName FROM admin WHERE id = gm.sender_id)
               WHEN gm.sender_type = 'staff' THEN (SELECT fullName FROM staff_accounts WHERE id = gm.sender_id)
               WHEN gm.sender_type = 'student' THEN (SELECT fullName FROM students WHERE id = gm.sender_id)
           END as sender_name
    FROM group_messages gm
    WHERE gm.group_id = ?
    ORDER BY gm.created_at ASC
");
$stmt->execute([$groupId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read (optional)
$pdo->prepare("
    UPDATE group_messages 
    SET status = 'read' 
    WHERE group_id = ? AND NOT (sender_id = ? AND sender_type = ?) AND status = 'sent'
")->execute([$groupId, $userId, $userType]);

echo json_encode($messages);
?>