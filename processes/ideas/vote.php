<?php
/**
 * Upvote an idea, or take the vote back. The board posts ajax=1 and gets JSON;
 * without JavaScript the form posts normally and is sent back to the same view.
 */
session_start();
require __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../includes/feature-requests.php';

$ajax = !empty($_POST['ajax']);
$id = (int) ($_POST['id'] ?? 0);

function vote_done(bool $ajax, int $id, array $result): void
{
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        if (!$result['success']) {
            $_SESSION['IDEAS_FLASH'] = ['warning', $result['message']];
        }
        // Only the board's own filters survive the round trip
        parse_str((string) ($_POST['return'] ?? ''), $query);
        $query = array_intersect_key(array_filter($query, 'is_string'), array_flip(['status', 'sort', 'q', 'page']));
        header('Location: ../../ideas.php' . ($query ? '?' . http_build_query($query) : '') . ($id ? '#idea-' . $id : ''));
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
    vote_done($ajax, 0, ['success' => false, 'message' => 'Invalid request.']);
}

$viewer = ideas_viewer();
if (!$viewer) {
    vote_done($ajax, $id, ['success' => false, 'message' => 'Please log in to vote.', 'login' => true]);
}
if (!ideas_csrf_valid()) {
    vote_done($ajax, $id, ['success' => false, 'message' => 'Your session expired. Refresh the page and try again.']);
}

try {
    ideas_ensure($pdo);
    $stmt = $pdo->prepare("SELECT status FROM feature_requests WHERE id = ? AND is_hidden = 0");
    $stmt->execute([$id]);
    $status = $stmt->fetchColumn();
    if ($status === false) {
        vote_done($ajax, 0, ['success' => false, 'message' => 'That idea is no longer on the board.']);
    }
    if (in_array($status, ['done', 'declined'], true)) {
        vote_done($ajax, $id, ['success' => false, 'message' => 'Voting is closed for this idea.']);
    }
    [$voted, $count] = ideas_toggle_vote($pdo, $id, $viewer);
    vote_done($ajax, $id, ['success' => true, 'voted' => $voted, 'count' => $count]);
} catch (Throwable $e) {
    error_log('Idea vote failed: ' . $e->getMessage());
    vote_done($ajax, $id, ['success' => false, 'message' => 'Your vote could not be saved. Please try again.']);
}
