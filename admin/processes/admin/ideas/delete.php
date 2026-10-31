<?php
/**
 * Delete an idea; its votes go with it (ON DELETE CASCADE).
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

try {
    ideas_ensure($pdo);
    $pdo->prepare('DELETE FROM feature_requests WHERE id = ?')->execute([(int) $_POST['id']]);
    $_SESSION['STATUS'] = 'ADMIN_IDEA_DELETE_SUCCESS';
} catch (PDOException $e) {
    error_log('Idea delete failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_IDEA_ERROR';
}

header("Location: $back");
exit();
