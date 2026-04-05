<?php
session_start();
require_once '../../server/conn.php';


if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        // Verify the announcement belongs to the teacher
        $check_stmt = $pdo->prepare("
            DELETE FROM announcements 
            WHERE id = ? AND teacher_id = ?
        ");
        $check_stmt->execute([$_POST['id'], $_SESSION['teacher_id']]);
        
        if ($check_stmt->rowCount() > 0) {
            $response['success'] = true;
        } else {
            $response['message'] = 'Announcement not found or not authorized';
        }
    } catch (PDOException $e) {
        error_log("Delete announcement error: " . $e->getMessage());
        $response['message'] = 'Database error';
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
?>