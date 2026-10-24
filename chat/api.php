<?php
/**
 * Chat API — the one endpoint every portal's chat widget talks to.
 *
 *   GET  ?action=unread
 *   GET  ?action=conversations
 *   GET  ?action=search&q=...
 *   GET  ?action=messages&partner_id=..&partner_type=..[&after=..]   (+ partner: {status, name})
 *   POST action=send            partner_id, partner_type, message [, file]
 *   POST action=delete          id
 *   GET  ?action=groups
 *   GET  ?action=group_messages&group_id=..[&after=..]
 *   GET  ?action=group_info&group_id=..
 *   POST action=group_send      group_id, message [, file]
 *   POST action=group_create    name, description, members (JSON [{id,type}])
 *   POST action=group_leave     group_id
 *
 * POST requests must carry the X-Requested-With: CSMSChat header, which a
 * cross-site form cannot set — this is the CSRF guard.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../app/helpers/ChatHelper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function chat_respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$chat = ChatHelper::fromSession($pdo);
// Release the session lock so polling doesn't block the user's other page loads.
session_write_close();

if ($chat === null) {
    chat_respond(['ok' => false, 'error' => 'Please log in to use chat.', 'code' => 'unauthenticated'], 401);
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

// A body over post_max_size arrives with $_POST and $_FILES emptied by PHP
if ($isPost && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    chat_respond(['ok' => false, 'error' => 'That file is too large (max ' . ChatAttachments::maxLabel() . ').', 'code' => 'file_too_large'], 413);
}

$action =$isPost ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');
$in = $isPost ? $_POST : $_GET;

$postActions = ['send', 'delete', 'group_send', 'group_create', 'group_leave'];
if (in_array($action, $postActions, true)) {
    if (!$isPost) {
        chat_respond(['ok' => false, 'error' => 'Method not allowed.', 'code' => 'method'], 405);
    }
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'CSMSChat') {
        chat_respond(['ok' => false, 'error' => 'Invalid request.', 'code' => 'csrf'], 403);
    }
}

$int = static fn(string $key): int => max(0, (int) ($in[$key] ?? 0));
$str = static fn(string $key): string => (string) ($in[$key] ?? '');
$file = isset($_FILES['file']) && is_array($_FILES['file']) && !is_array($_FILES['file']['name'])
    && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['file'] : null;

try {
    switch ($action) {
        case 'unread':
            chat_respond(['ok' => true, 'unread' => $chat->unreadCount()]);

        case 'conversations':
            chat_respond([
                'ok' => true,
                'conversations' => $chat->recentConversations(),
                'unread' => $chat->unreadCount(),
            ]);

        case 'search':
            chat_respond(['ok' => true, 'users' => $chat->searchUsers($str('q'))]);

        case 'messages':
            $partnerId = $int('partner_id');
            $partnerType = $str('partner_type');
            chat_respond([
                'ok' => true,
                'messages' => $chat->conversation($partnerId, $partnerType, $int('after')),
                'receipts' => $chat->readReceipts($partnerId, $partnerType),
                // Current state of the other person: active, deactivated or deleted
                'partner' => $chat->accountStatuses([[$partnerId, $partnerType]])[$partnerType . ':' . $partnerId] ?? null,
            ]);

        case 'send':
            chat_respond([
                'ok' => true,
                'message' => $chat->sendMessage($int('partner_id'), $str('partner_type'), $str('message'), $file),
            ]);

        case 'delete':
            if (!$chat->deleteMessage($int('id'))) {
                chat_respond(['ok' => false, 'error' => 'Message not found.', 'code' => 'not_found'], 404);
            }
            chat_respond(['ok' => true]);

        case 'groups':
            chat_respond(['ok' => true, 'groups' => $chat->groups()]);

        case 'group_messages':
            chat_respond(['ok' => true, 'messages' => $chat->groupMessages($int('group_id'), $int('after'))]);

        case 'group_info':
            chat_respond(['ok' => true, 'group' => $chat->groupInfo($int('group_id'))]);

        case 'group_send':
            chat_respond(['ok' => true, 'message' => $chat->sendGroupMessage($int('group_id'), $str('message'), $file)]);

        case 'group_create':
            $members = json_decode($str('members'), true);
            $groupId = $chat->createGroup($str('name'), $str('description'), is_array($members) ? $members : []);
            chat_respond(['ok' => true, 'group_id' => $groupId]);

        case 'group_leave':
            $chat->leaveGroup($int('group_id'));
            chat_respond(['ok' => true]);

        default:
            chat_respond(['ok' => false, 'error' => 'Unknown action.', 'code' => 'unknown_action'], 400);
    }
} catch (ChatException $e) {
    chat_respond(['ok' => false, 'error' => $e->getMessage(), 'code' => $e->errorCode()], 422);
} catch (Throwable $e) {
    error_log('Chat API error: ' . $e->getMessage());
    chat_respond(['ok' => false, 'error' => 'Something went wrong. Please try again.', 'code' => 'server_error'], 500);
}
