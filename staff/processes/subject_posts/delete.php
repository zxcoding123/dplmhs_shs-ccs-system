<?php
session_start();
require_once '../../server/conn.php';

if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'UNAUTHORIZED';
    $_SESSION['STATUS_MSG'] = 'Unauthorized action.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../subject_activities.php'));
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$teacher_id = $_SESSION['teacher_id'];

if (!$id) {
    $_SESSION['STATUS'] = 'ERROR';
    $_SESSION['STATUS_MSG'] = 'Invalid announcement.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../subject_activities.php'));
    exit;
}

$stmt = $pdo->prepare("
    DELETE FROM subject_announcements
    WHERE id = ? AND teacher_id = ?
");
$stmt->execute([$id, $teacher_id]);

if ($stmt->rowCount()) {
    $_SESSION['STATUS'] = 'SUCCESS';
    $_SESSION['STATUS_MSG'] = 'Announcement deleted.';
} else {
    $_SESSION['STATUS'] = 'ERROR';
    $_SESSION['STATUS_MSG'] = 'You cannot delete this announcement.';
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../../subject_activities.php'));
exit;
