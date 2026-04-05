<?php
session_start();
require_once '../../server/conn.php';

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $class_name = $_POST['class_name'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $teacher_id = $_SESSION['teacher_id'];
    $fullName = $_SESSION['teacher_name'];

    // Validate the teacher is an adviser for this class
    $check_stmt = $pdo->prepare("
        SELECT 1 FROM staff_advising 
        WHERE fullName = ? AND class_advising = ?
    ");
    $check_stmt->execute([$fullName, $class_name]);

    if ($check_stmt->fetch()) {
        $insert_stmt = $pdo->prepare("
            INSERT INTO announcements 
            (class_name, teacher_id, title, content) 
            VALUES (?, ?, ?, ?)
        ");
        $insert_stmt->execute([$class_name, $teacher_id, $title, $content]);

        $response['success'] = true;
        $response['status'] = "success";
        $response['message'] = "You have succesfully made an announcement!";
    } else {
        $response['success'] = false;
        $response['status'] = "error";
        $response['message'] = 'You are not an adviser for this class';
    }
} catch (PDOException $e) {
    error_log("Announcement error: " . $e->getMessage());
    $response['success'] = false;
    $response['status'] = "error";
    $response['message'] = 'Database error';
}

echo json_encode($response);
