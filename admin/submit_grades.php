<?php
/**
 * Admin reviews a class's submitted grades (buttons on lecture_grades.php / lab_grades.php):
 *  accept  locks them, return sends them back to the teacher, unlock reopens accepted grades.
 * The registrar does the same from registrar/grades.php; both go through grades_review().
 */
session_start();
require_once __DIR__ . '/includes/guard.php';
require __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/grading.php';

$back = $_SERVER['HTTP_REFERER'] ?? 'class_management.php';
$classId = (int) ($_POST['id'] ?? 0);
$action = ['accept' => 'accept', 'reject' => 'return', 'return' => 'return', 'unlock' => 'unlock'][$_POST['value'] ?? ''] ?? null;
$reason = trim($_POST['reason'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$classId || !$action) {
    header("Location: $back");
    exit;
}
if ($action !== 'accept' && $reason === '') {
    $_SESSION['STATUS'] = 'GRADES_REASON_REQUIRED';
    header("Location: $back");
    exit;
}

$stmt = $pdo->prepare("SELECT fullName FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$name = $stmt->fetchColumn() ?: 'Administrator';

try {
    $changed = grades_review($pdo, $classId, $action, 'admin', $name, $reason);
} catch (PDOException $e) {
    error_log('Grade review failed: ' . $e->getMessage());
    $changed = 0;
}

$_SESSION['STATUS'] = $changed === 0
    ? 'GRADES_NOTHING_TO_REVIEW'
    : ['accept' => 'GRADES_ACCEPTED', 'return' => 'GRADES_REJECTED', 'unlock' => 'GRADES_UNLOCKED'][$action];
header("Location: $back");
exit;
