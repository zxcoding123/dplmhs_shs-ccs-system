<?php
/** Move a document request along (pending → processing → ready → released, or declined) and tell the student. */
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/records.php';

$back = '../requests.php';
registrar_require_post($back);

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$note = mb_substr(trim($_POST['registrar_note'] ?? ''), 0, 500);
$statuses = records_request_statuses();

if (!isset($statuses[$status])) {
    registrar_redirect($back, ['Error', 'Pick a status.', 'error']);
}
if ($status === 'declined' && $note === '') {
    registrar_redirect($back, ['Reason needed', 'Tell the student why the request was declined.', 'warning']);
}

$stmt = $pdo->prepare("SELECT r.*, s.student_id FROM document_requests r JOIN students s ON s.id = r.student_row_id WHERE r.id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$request) {
    registrar_redirect($back, ['Not found', 'That request no longer exists.', 'error']);
}

$pdo->beginTransaction();
$pdo->prepare("UPDATE document_requests SET status = ?, registrar_note = ?, handled_by = ? WHERE id = ?")
    ->execute([$status, $note, $registrarName, $id]);
if ($status !== $request['status']) {
    $document = records_document_types()[$request['document']];
    $texts = [
        'pending'    => "Your request for a $document is back in the queue.",
        'processing' => "The registrar is preparing your $document.",
        'ready'      => "Your $document is ready for pick-up at the registrar's office.",
        'released'   => "Your $document has been released.",
        'declined'   => "Your request for a $document was declined.",
    ];
    records_notify_student($pdo, (int) $request['student_id'], 'Document request: ' . $statuses[$status][0], $texts[$status] . ($note !== '' ? ' ' . $note : ''));
}
$pdo->commit();

registrar_redirect($back, ['Request updated', 'Request #' . $id . ' is now ' . strtolower($statuses[$status][0]) . '. The student has been notified.', 'success']);
