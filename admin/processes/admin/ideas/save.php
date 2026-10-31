<?php
/**
 * Update an idea from Admin > Ideas & Improvements: status, public reply, visibility,
 * and tidying the title/details. responded_at changes only when the reply text does.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/feature-requests.php';

$back = '../../../ideas_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id']) || !ideas_csrf_valid()) {
    header("Location: $back");
    exit();
}

$id = (int) $_POST['id'];
$title = trim(preg_replace('/\s+/', ' ', $_POST['title'] ?? ''));
$details = trim($_POST['details'] ?? '');
$response = trim($_POST['admin_response'] ?? '');
$category = $_POST['category'] ?? '';
$status = $_POST['status'] ?? '';

if (mb_strlen($title) < 8 || mb_strlen($title) > 150 || mb_strlen($details) > 3000 || mb_strlen($response) > 3000
    || !isset(IDEAS_CATEGORIES[$category]) || !isset(IDEAS_STATUSES[$status])) {
    $_SESSION['STATUS'] = 'ADMIN_IDEA_INVALID';
    header("Location: $back");
    exit();
}

try {
    ideas_ensure($pdo);
    $stmt = $pdo->prepare('SELECT admin_response, responded_at FROM feature_requests WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['admin_response' => null, 'responded_at' => null];
    $respondedAt = $response === '' ? null
        : ($response === (string) $current['admin_response'] ? $current['responded_at'] : date('Y-m-d H:i:s'));

    $stmt = $pdo->prepare("UPDATE feature_requests SET title = :title, details = :details, category = :category,
        status = :status, is_hidden = :is_hidden, admin_response = :response, responded_at = :responded_at
        WHERE id = :id");
    $stmt->execute([
        'title' => $title,
        'details' => $details !== '' ? $details : null,
        'category' => $category,
        'status' => $status,
        'is_hidden' => !empty($_POST['is_hidden']) ? 1 : 0,
        'response' => $response !== '' ? $response : null,
        'responded_at' => $respondedAt,
        'id' => $id,
    ]);
    $_SESSION['STATUS'] = 'ADMIN_IDEA_UPDATE_SUCCESS';
} catch (PDOException $e) {
    error_log('Idea save failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_IDEA_ERROR';
}

header("Location: $back");
exit();
