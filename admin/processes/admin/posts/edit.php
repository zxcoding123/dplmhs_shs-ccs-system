<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

include('../../server/conn.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_GET['id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? '';
    $status = $_POST['status'] ?? 'Draft';
    $remove_image = isset($_POST['remove_image']);

    // Validate inputs
    if (empty($title) || empty($content) || empty($category)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: edit.php?id=$post_id");
        exit();
    }

    try {
        // Handle file upload
        $image_path = null;

        // Check if we need to remove existing image
        if ($remove_image) {
            // First get current image to delete it
            $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $current_image = $stmt->fetchColumn();

            if ($current_image) {
                $file_path = "../../uploads/posts/" . $current_image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        // Handle new image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../../uploads/posts/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Get current image to delete it
            $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $current_image = $stmt->fetchColumn();

            if ($current_image) {
                $file_path = $upload_dir . $current_image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Upload new image
            $file_ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('post_') . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;

            // Validate image
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_ext), $allowed_types)) {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed";
                header("Location: edit.php?id=$post_id");
                exit();
            }

            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                $image_path = $file_name;
            }
        }

        // Prepare SQL based on whether we have a new image
        if ($remove_image) {
            $sql = "UPDATE posts SET title = ?, content = ?, category = ?, status = ?, featured_image = NULL, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $content, $category, $status, $post_id]);
        } elseif ($image_path) {
            $sql = "UPDATE posts SET title = ?, content = ?, category = ?, status = ?, featured_image = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $content, $category, $status, $image_path, $post_id]);
        } else {
            $sql = "UPDATE posts SET title = ?, content = ?, category = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $content, $category, $status, $post_id]);
        }

        $_SESSION['STATUS'] = "POST_UPDATED_SUCCESS";
        header("Location: ../../../post_management.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "POST_UPDATED_ERROR";
        header("Location: ../../../post_management.php");

        exit();
    }
} else {
    $_SESSION['STATUS'] = "POST_UPDATED_ERROR";
    header("Location: ../../../post_management.php");
    exit();
}
