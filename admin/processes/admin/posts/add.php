<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('../../server/conn.php');



$author_id = $_SESSION['admin_id'] ?? null;

if (!$author_id) {
    die("You're not logged in as an admin.");
}

// Sanitize POST data
$title    = trim($_POST['title'] ?? '');
$content  = trim($_POST['content'] ?? '');
$category = trim($_POST['category'] ?? '');
$status   = trim($_POST['status'] ?? 'Draft');

// Validate
if (empty($title) || empty($content) || empty($category)) {
    die('Missing required fields.');
}

// Handle image upload
$uploadPath = '../../../../uploads/posts/';
$uploadedImage = null;

if (!empty($_FILES['featured_image']['name'])) {
    $image = $_FILES['featured_image'];
    $imageName = basename($image['name']);
    $targetFile = $uploadPath . time() . '_' . $imageName;
    $imageToBeUplaoded =  time() . '_' . $imageName;

    if (move_uploaded_file($image['tmp_name'], $targetFile)) {
        $uploadedImage = str_replace('../../../', '', $targetFile); // Save relative path
    } else {
        die('Image upload failed.');
    }
}

// Insert into database (PDO example)
try {
 $stmt = $pdo->prepare("INSERT INTO posts (title, content, category, status, featured_image, author_id, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->execute([
    $title,
    $content,
    $category,
    $status,
    $imageToBeUplaoded,
    $author_id
]);


   $_SESSION['STATUS'] = "POST_ADDITION_SUCCESS";
        header("Location: ../../../post_management.php");
    exit;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
