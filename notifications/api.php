<?php
/**
 * Notifications API — the one endpoint every portal's bell talks to.
 *
 *   GET  ?action=unread
 *   GET  ?action=list[&filter=all|unread][&limit=..][&before=..]
 *   POST action=read        id
 *   POST action=read_all
 *   POST action=delete      id
 *   POST action=delete_all
 *
 * Every response carries the fresh `unread` and `total` counts so the UI
 * can update its badge without another request.
 *
 * POST requests must carry the X-Requested-With: CSMSNotify header, which a
 * cross-site form cannot set — this is the CSRF guard.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../app/helpers/NotificationHelper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function notify_respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$notes = NotificationHelper::fromSession($pdo);
// Release the session lock so polling doesn't block the user's other page loads.
session_write_close();

if ($notes === null) {
    notify_respond(['ok' => false, 'error' => 'Please log in to see notifications.', 'code' => 'unauthenticated'], 401);
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $isPost ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');
$in = $isPost ? $_POST : $_GET;

$postActions = ['read', 'read_all', 'delete', 'delete_all'];
if (in_array($action, $postActions, true)) {
    if (!$isPost) {
        notify_respond(['ok' => false, 'error' => 'Method not allowed.', 'code' => 'method'], 405);
    }
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'CSMSNotify') {
        notify_respond(['ok' => false, 'error' => 'Invalid request.', 'code' => 'csrf'], 403);
    }
}

$int = static fn(string $key): int => max(0, (int) ($in[$key] ?? 0));
$counts = static fn(): array => ['unread' => $notes->unreadCount(), 'total' => $notes->totalCount()];

try {
    switch ($action) {
        case 'unread':
            notify_respond(['ok' => true, 'unread' => $notes->unreadCount()]);

        case 'list':
            $filter = ($in['filter'] ?? '') === 'unread' ? 'unread' : 'all';
            $page = $notes->list($filter, $int('limit') ?: 20, $int('before'));
            notify_respond(['ok' => true] + $page + $counts());

        case 'read':
            $notes->markRead($int('id'));
            notify_respond(['ok' => true] + $counts());

        case 'read_all':
            $notes->markAllRead();
            notify_respond(['ok' => true] + $counts());

        case 'delete':
            if (!$notes->delete($int('id'))) {
                notify_respond(['ok' => false, 'error' => 'Notification not found.', 'code' => 'not_found'] + $counts(), 404);
            }
            notify_respond(['ok' => true] + $counts());

        case 'delete_all':
            $notes->deleteAll();
            notify_respond(['ok' => true] + $counts());

        default:
            notify_respond(['ok' => false, 'error' => 'Unknown action.', 'code' => 'unknown_action'], 400);
    }
} catch (Throwable $e) {
    error_log('Notifications API error: ' . $e->getMessage());
    notify_respond(['ok' => false, 'error' => 'Something went wrong. Please try again.', 'code' => 'server_error'], 500);
}
