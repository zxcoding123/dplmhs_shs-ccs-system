<?php
require 'processes/server/conn.php';

session_start();
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null; // This should be set during login (admin, staff, or student)

if (!$userId || !$userType) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get all groups the user is a member of
$stmt = $pdo->prepare("
    SELECT g.id, g.name, g.description, 
           COUNT(gm.user_id) as member_count,
           (SELECT message FROM group_messages 
            WHERE group_id = g.id 
            ORDER BY created_at DESC LIMIT 1) as last_message
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ? AND gm.user_type = ?
    GROUP BY g.id
");
$stmt->execute([$userId, $userType]);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($groups);
?>