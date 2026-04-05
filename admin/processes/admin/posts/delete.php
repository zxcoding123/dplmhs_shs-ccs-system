<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

include('../../server/conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $post_id = $_GET['id'] ?? 0;

    if (empty($post_id) || !is_numeric($post_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
        exit();
    }

    try {
        // First get the image path to delete the file
        $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $image_path = $stmt->fetchColumn();

        // Delete the post
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);

        // Delete the associated image file if it exists
        if ($image_path) {
            $file_path = "../../uploads/posts/" . $image_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $_SESSION['STATUS'] = "POST_DELETION_SUCCESS";
        header("Location: ../../../post_management.php");
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "POST_DELETION_ERROR";
        header("Location: ../../../post_management.php");
        exit();
    }
} else {
    $_SESSION['STATUS'] = "POST_DELETION_ERROR";
    header("Location: ../../../post_management.php");
    exit();
}
