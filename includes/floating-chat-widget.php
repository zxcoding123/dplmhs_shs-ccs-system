<?php
/**
 * Shared chat widget — the same messaging UI for admin, staff and students.
 *
 * Rendered automatically by every portal top bar (admin/top-bar.php,
 * staff/top-bar.php, students/topbar.php) and by the public site footer.
 * Safe to include more than once; it only renders the first time.
 *
 * All data comes from chat/api.php (backed by app/helpers/ChatHelper.php).
 * Any element with [data-csms-chat-open] opens the widget, and any element
 * with [data-csms-chat-badge] shows the unread count.
 */

if (defined('CSMS_CHAT_WIDGET_RENDERED')) {
    return;
}

// No session = nobody logged in (and too late to start one: headers are sent).
if (session_status() !== PHP_SESSION_ACTIVE) {
    return;
}

require_once __DIR__ . '/../app/helpers/ChatHelper.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || !ChatHelper::isValidType((string) $_SESSION['user_type'])) {
    return;
}

define('CSMS_CHAT_WIDGET_RENDERED', true);

// Relative path from the current page back to the project root, so the
// widget works from /, /admin/, /staff/, /students/, etc.
$csmsChatRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$csmsChatScriptDir = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
$csmsChatBase = '';
if (strpos($csmsChatScriptDir, $csmsChatRoot) === 0) {
    $csmsChatDepth = count(array_filter(explode('/', substr($csmsChatScriptDir, strlen($csmsChatRoot)))));
    $csmsChatBase = str_repeat('../', $csmsChatDepth);
}
$csmsChatAssetVersion = @filemtime(__DIR__ . '/../chat/chat-widget.js') ?: time();
?>
<link rel="stylesheet" href="<?php echo $csmsChatBase; ?>chat/chat-widget.css?v=<?php echo $csmsChatAssetVersion; ?>">
<div id="csmsChat" class="csms-chat"
     data-api="<?php echo htmlspecialchars($csmsChatBase . 'chat/api.php'); ?>"
     data-user-type="<?php echo htmlspecialchars($_SESSION['user_type']); ?>"></div>
<script src="<?php echo $csmsChatBase; ?>chat/chat-widget.js?v=<?php echo $csmsChatAssetVersion; ?>" defer></script>
