<?php
/** Activate or deactivate a student's login. Their records stay as they are. */
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$back = '../student.php?id=' . $id;
registrar_require_post($back);

if ($id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    registrar_redirect('../students.php', ['Error', 'That request was not valid.', 'error']);
}

$stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
$stmt->execute([$id]);
$userId = (int) $stmt->fetchColumn();
if (!$userId) {
    registrar_redirect('../students.php', ['Not found', 'That student record no longer exists.', 'error']);
}

$pdo->beginTransaction();
$pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $userId]);
$pdo->prepare("UPDATE students SET status = ? WHERE id = ?")->execute([$status, $id]);
if ($status === 'inactive') {
    // Remember-me cookies would otherwise keep them signed in
    $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$userId]);
}
$pdo->commit();

registrar_redirect($back, ['Account updated', $status === 'active' ? 'The student can sign in again.' : 'The student can no longer sign in.', 'success']);
