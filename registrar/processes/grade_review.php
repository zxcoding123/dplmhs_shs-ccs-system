<?php
/** Accept (lock), return or unlock a class's grades. Same rules as the admin's review. */
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/grading.php';

$classId = (int) ($_POST['class_id'] ?? 0);
$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$back = '../grade_sheet.php?class=' . $classId;
registrar_require_post($back);

if (!in_array($action, ['accept', 'return', 'unlock'], true)) {
    registrar_redirect($back, ['Error', 'That request was not valid.', 'error']);
}
if ($action !== 'accept' && $reason === '') {
    registrar_redirect($back, ['Reason needed', 'Give a reason when returning or unlocking grades.', 'warning']);
}

try {
    $changed = grades_review($pdo, $classId, $action, 'registrar', $registrarName, $reason);
} catch (PDOException $e) {
    error_log('Grade review failed: ' . $e->getMessage());
    registrar_redirect($back, ['Error', 'The grades could not be updated. Please try again.', 'error']);
}

if ($changed === 0) {
    registrar_redirect($back, ['Nothing changed', 'These grades are no longer in a state that allows that. Someone may have reviewed them already.', 'info']);
}
$messages = [
    'accept' => ['Grades accepted', "$changed grades are final and locked. The teacher has been notified."],
    'return' => ['Grades returned', 'The teacher has been notified and can edit and resubmit them.'],
    'unlock' => ['Grades unlocked', 'The teacher has been notified and can correct and resubmit them.'],
];
registrar_redirect($back, [...$messages[$action], 'success']);
