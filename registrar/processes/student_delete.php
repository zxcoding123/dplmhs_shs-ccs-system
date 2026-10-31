<?php
/** Permanently delete a student account (registrar/student.php). The registrar types the student ID to confirm. */
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_POST['id'] ?? 0);
$back = '../student.php?id=' . $id;
registrar_require_post($back);

$stmt = $pdo->prepare("SELECT student_id, first_name, last_name FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    registrar_redirect('../students.php', ['Not found', 'That student record no longer exists.', 'error']);
}
if (trim($_POST['confirm_student_id'] ?? '') !== (string) $student['student_id']) {
    registrar_redirect($back, ['Not deleted', 'The student ID you typed did not match.', 'warning']);
}

$name = $student['first_name'] . ' ' . $student['last_name'];
try {
    $deleted = registrar_delete_student($pdo, $id);
} catch (Throwable $e) {
    error_log('Deleting student failed: ' . $e->getMessage());
    registrar_redirect($back, ['Error', 'The account could not be deleted. Nothing was changed.', 'error']);
}
if (!$deleted) {
    registrar_redirect($back, ['Not deleted', "$name has accepted grades, which are official records. Deactivate the account instead.", 'info']);
}
registrar_redirect('../students.php', ['Account deleted', "$name (ID {$student['student_id']}) and their records were deleted.", 'success']);
