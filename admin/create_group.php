<?php
require 'processes/server/conn.php';

session_start();
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if (!$userId || !$userType) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$members = json_decode($_POST['members'] ?? '[]', true);

if (empty($name)) {
    echo json_encode(['error' => 'Group name is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Create the group
    $stmt = $pdo->prepare("
        INSERT INTO groups (name, description, created_by_id, created_by_type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $userId, $userType]);
    $groupId = $pdo->lastInsertId();
    
    // Add creator as member
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id, user_type)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$groupId, $userId, $userType]);
    
    // Add other members
    foreach ($members as $member) {
        // Skip if already added (creator)
        if ($member['id'] == $userId && $member['type'] == $userType) continue;
        
        $stmt->execute([$groupId, $member['id'], $member['type']]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'group_id' => $groupId]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>