<?php
/** A student withdraws a request the registrar hasn't started on yet. */
session_start();
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/records.php';

$back = '../../../document_requests.php';
if (!isset($_SESSION['student_id'])) {
    $_SESSION['STATUS'] = 'STUDENT_NOT_LOGGED_IN';
    header('Location: ../../../../login/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid()) {
    registrar_flash('Request expired', 'Reload the page and try again.', 'error');
    header("Location: $back");
    exit;
}

// Only the student's own, still-pending request
$stmt = $pdo->prepare("
    DELETE r FROM document_requests r JOIN students s ON s.id = r.student_row_id
    WHERE r.id = ? AND s.student_id = ? AND r.status = 'pending'
");
$stmt->execute([(int) ($_POST['id'] ?? 0), $_SESSION['student_id']]);

registrar_flash(...($stmt->rowCount()
    ? ['Request cancelled', 'The registrar will not prepare that document.', 'success']
    : ['Not cancelled', 'The registrar has already started on that request.', 'info']));
header("Location: $back");
exit;
