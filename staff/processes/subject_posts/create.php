<?php
session_start();
require_once '../../server/conn.php';

if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'UNAUTHORIZED';
    $_SESSION['STATUS_MSG'] = 'You are not authorized to perform this action.';
    header('Location: ../../subject_management.php');
    exit;
}

$redirect = $_SERVER['HTTP_REFERER'] ;

try {
    $subject_id = $_POST['subject_id'] ?? null;
    $title      = trim($_POST['title'] ?? '');
    $content    = trim($_POST['content'] ?? '');
    $teacher_id = $_SESSION['teacher_id'];

    if (!$subject_id || !$title || !$content) {
        $_SESSION['STATUS'] = 'ERROR';
        $_SESSION['STATUS_MSG'] = 'All fields are required.';
        header('Location: ../../subjects.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO subject_announcements
        (subject_id, teacher_id, title, content)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$subject_id, $teacher_id, $title, $content]);

    $_SESSION['STATUS'] = 'SUCCESS';
    $_SESSION['STATUS_MSG'] = 'Announcement posted successfully.';

} catch (PDOException $e) {
    error_log('Announcement error: ' . $e->getMessage());

    $_SESSION['STATUS'] = 'ERROR';
    $_SESSION['STATUS_MSG'] = 'A database error occurred.';
}

header('Location: ' . $redirect);
exit;
