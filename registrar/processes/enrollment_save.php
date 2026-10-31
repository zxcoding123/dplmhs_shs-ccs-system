<?php
/** Add or update a student's enrollment record for one school year + term. */
require __DIR__ . '/../includes/bootstrap.php';

$studentRowId = (int) ($_POST['student_row_id'] ?? 0);
$back = ($_POST['return'] ?? '') === 'enrollment'
    ? '../enrollment.php?' . http_build_query(['sy' => $_POST['school_year'] ?? '', 'term' => $_POST['term'] ?? ''])
    : '../student.php?id=' . $studentRowId;
registrar_require_post($back);

$stmt = $pdo->prepare("SELECT CONCAT_WS(' ', first_name, last_name) FROM students WHERE id = ?");
$stmt->execute([$studentRowId]);
$name = $stmt->fetchColumn();
if (!$name) {
    registrar_redirect($back, ['Pick a student', 'Choose the student this record is for.', 'warning']);
}

[$record, $error] = enrollment_validate($_POST);
if ($error) {
    registrar_redirect($back, ['Check the details', $error, 'warning']);
}

try {
    $pdo->beginTransaction();
    enrollment_save($pdo, $studentRowId, $record, 'registrar', $registrarName);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Saving enrollment record failed: ' . $e->getMessage());
    registrar_redirect($back, ['Error', 'The record could not be saved. Please try again.', 'error']);
}

$what = enrollment_statuses()[$record['status']] . ' · ' . $record['strand'] . ' ' . $record['grade_level']
    . ($record['section_code'] ? ' · ' . $record['section_code'] : '');
registrar_redirect($back, ['Record saved', $name . ' — S.Y. ' . $record['school_year'] . ', ' . $record['term'] . ': ' . $what . '.', 'success']);
