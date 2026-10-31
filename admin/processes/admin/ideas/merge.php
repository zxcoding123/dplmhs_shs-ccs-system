<?php
/**
 * Merge a duplicate idea into another: votes move across (someone who voted for
 * both keeps a single vote), then the duplicate is deleted.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/feature-requests.php';

$back = '../../../ideas_management.php';
$from = (int) ($_POST['id'] ?? 0);
$into = (int) ($_POST['into'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $from <= 0 || $into <= 0 || $from === $into || !ideas_csrf_valid()) {
    header("Location: $back");
    exit();
}

try {
    ideas_ensure($pdo);
    $pdo->beginTransaction();
    $pdo->prepare('INSERT IGNORE INTO feature_request_votes (request_id, voter_role, voter_id, created_at)
        SELECT ?, voter_role, voter_id, created_at FROM feature_request_votes WHERE request_id = ?')->execute([$into, $from]);
    $pdo->prepare('DELETE FROM feature_requests WHERE id = ?')->execute([$from]);
    $pdo->prepare('UPDATE feature_requests SET vote_count = (SELECT COUNT(*) FROM feature_request_votes WHERE request_id = ?) WHERE id = ?')
        ->execute([$into, $into]);
    $pdo->commit();
    $_SESSION['STATUS'] = 'ADMIN_IDEA_MERGE_SUCCESS';
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Idea merge failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_IDEA_ERROR';
}

header("Location: $back");
exit();
