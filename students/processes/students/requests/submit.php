<?php
/** A student asks the registrar for a document (students/document_requests.php). */
session_start();
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/records.php';

$back = '../../../document_requests.php';
if (!isset($_SESSION['student_id'])) {
    $_SESSION['STATUS'] = 'STUDENT_NOT_LOGGED_IN';
    header('Location: ../../../../login/index.php');
    exit;
}

function request_back(string $back, string $title, string $text, string $icon): void
{
    registrar_flash($title, $text, $icon);
    header("Location: $back");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid()) {
    request_back($back, 'Request expired', 'Reload the page and try again.', 'error');
}

$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$studentRowId = (int) $stmt->fetchColumn();

$document = $_POST['document'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$copies = (int) ($_POST['copies'] ?? 1);
$notes = mb_substr(trim($_POST['notes'] ?? ''), 0, 500);
$schoolYear = $document === 'sf9' ? trim($_POST['school_year'] ?? '') : null;

if (!$studentRowId || !isset(records_document_types()[$document]) || !in_array($purpose, records_request_purposes(), true) || $copies < 1 || $copies > 5) {
    request_back($back, 'Check the details', 'Pick a document, a purpose and 1 to 5 copies.', 'warning');
}
if ($schoolYear !== null && !enrollment_valid_school_year($schoolYear)) {
    request_back($back, 'Check the details', 'Pick the school year for the report card.', 'warning');
}

$open = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE student_row_id = ? AND status IN ('pending', 'processing', 'ready')");
$open->execute([$studentRowId]);
if ((int) $open->fetchColumn() >= 5) {
    request_back($back, 'Too many open requests', 'Wait until some of your requests are released before sending more.', 'info');
}

$pdo->prepare("INSERT INTO document_requests (student_row_id, document, school_year, purpose, copies, notes) VALUES (?, ?, ?, ?, ?, ?)")
    ->execute([$studentRowId, $document, $schoolYear, $purpose, $copies, $notes]);

request_back($back, 'Request sent', 'The registrar will prepare your ' . records_document_types()[$document] . '. You will be notified when it is ready.', 'success');
