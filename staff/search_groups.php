<?php
require 'processes/server/conn.php';

header('Content-Type: application/json');

session_start();
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if (!$userId || !$userType) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$query = $_GET['query'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Split query into individual words for better searching
    $keywords = explode(' ', $query);
    $keywordConditions = [];
    $params = [$userId, $userType];
    
    foreach ($keywords as $keyword) {
        if (strlen($keyword) >= 2) { // Ignore single characters
            $keywordConditions[] = "(g.name LIKE ? OR g.description LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
    }
    
    if (empty($keywordConditions)) {
        echo json_encode([]);
        exit;
    }
    
    $sql = "
        SELECT g.id, g.name, g.description, 
               COUNT(gm.user_id) as member_count,
               MAX(gm.joined_at) as last_activity
        FROM groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE gm.user_id = ? AND gm.user_type = ?
          AND (" . implode(' AND ', $keywordConditions) . ")
        GROUP BY g.id
        ORDER BY last_activity DESC
        LIMIT 15
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($groups);
    
} catch (PDOException $e) {
    error_log("Search groups error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>