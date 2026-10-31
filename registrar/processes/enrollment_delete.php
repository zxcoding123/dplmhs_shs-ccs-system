<?php
/** Remove an enrollment record entered by mistake. The student's placement is left alone. */
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT student_row_id, school_year, term FROM enrollment_records WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

$back = ($_POST['return'] ?? '') === 'enrollment'
    ? '../enrollment.php' . ($record ? '?' . http_build_query(['sy' => $record['school_year'], 'term' => $record['term']]) : '')
    : ($record ? '../student.php?id=' . $record['student_row_id'] : '../students.php');
registrar_require_post($back);

if (!$record) {
    registrar_redirect($back, ['Not found', 'That record was already removed.', 'info']);
}

$pdo->prepare("DELETE FROM enrollment_records WHERE id = ?")->execute([$id]);
registrar_redirect($back, ['Record deleted', 'S.Y. ' . $record['school_year'] . ', ' . $record['term'] . ' was removed from the history.', 'success']);
