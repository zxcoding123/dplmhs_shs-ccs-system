<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include('../../server/conn.php');

// Get POST data
$post_id = $_POST['post_id'] ?? 0;
$new_status = $_POST['new_status'] ?? '';

// Validate inputs
if (empty($post_id) || !is_numeric($post_id)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

if (!in_array($new_status, ['Published', 'Draft'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Update post status
    $stmt = $pdo->prepare("UPDATE posts SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $post_id]);

    if ($stmt->rowCount() > 0) {

        $_SESSION['STATUS'] = "POST_SWITCH_STATUS_SUCCESS";;
    } else {
        $_SESSION['STATUS'] = "POST_SWITCH_STATUS_ERROR";
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $_SESSION['STATUS'] = "POST_SWITCH_STATUS_ERROR";
}
