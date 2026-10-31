<?php
/**
 * Shared notification bell — the same dropdown for admin, staff and students.
 *
 * Include it inside a portal's <ul class="navbar-nav"> (admin/top-bar.php,
 * staff/top-bar.php, students/topbar.php). Renders nothing if nobody is
 * logged in. All data comes from notifications/api.php (backed by
 * app/helpers/NotificationHelper.php); reading and deleting happen in place,
 * without a page reload.
 *
 * Public hooks (see notifications/notifications.js):
 *   [data-csms-notify-badge]                  receives the unread count
 *   [data-bs-target="#notificationsModal"]    opens the full list
 *   window.CSMSNotify.refresh() / .openAll()
 */

if (defined('CSMS_NOTIFY_BELL_RENDERED') || session_status() !== PHP_SESSION_ACTIVE) {
    return;
}

require_once __DIR__ . '/../app/helpers/NotificationHelper.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type']) || !NotificationHelper::isValidRole((string) $_SESSION['user_type'])) {
    return;
}

define('CSMS_NOTIFY_BELL_RENDERED', true);

// Relative path from the current page back to the project root (same as the chat widget)
$csmsNotifyRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$csmsNotifyScriptDir = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
$csmsNotifyBase = '';
if (strpos($csmsNotifyScriptDir, $csmsNotifyRoot) === 0) {
    $csmsNotifyDepth = count(array_filter(explode('/', substr($csmsNotifyScriptDir, strlen($csmsNotifyRoot)))));
    $csmsNotifyBase = str_repeat('../', $csmsNotifyDepth);
}
$csmsNotifyVersion = max(
    @filemtime(__DIR__ . '/../notifications/notifications.js') ?: 0,
    @filemtime(__DIR__ . '/../notifications/notifications.css') ?: 0
) ?: time();

// Server-side first paint of the badge, so it doesn't pop in after load
global $pdo;
$csmsNotifyUnread = 0;
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $csmsNotifyUnread = NotificationHelper::fromSession($pdo)->unreadCount();
    } catch (Throwable $e) {
        error_log('Notification bell: ' . $e->getMessage());
    }
}
?>
<li class="nav-item dropdown csms-notify" id="csmsNotify"
    data-api="<?php echo htmlspecialchars($csmsNotifyBase . 'notifications/api.php'); ?>"
    data-base="<?php echo htmlspecialchars($csmsNotifyBase); ?>">
    <link rel="stylesheet" href="<?php echo $csmsNotifyBase; ?>notifications/notifications.css?v=<?php echo $csmsNotifyVersion; ?>">
    <a class="nav-icon dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
       data-bs-auto-close="outside" data-bs-display="static" aria-expanded="false" aria-label="Notifications">
        <div class="position-relative">
            <i class="align-middle" data-feather="bell"></i>
            <span class="indicator" data-csms-notify-badge<?php echo $csmsNotifyUnread > 0 ? '' : ' style="display:none"'; ?>><?php echo $csmsNotifyUnread > 99 ? '99+' : $csmsNotifyUnread; ?></span>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end cn-menu" data-el="menu"></div>
    <script src="<?php echo $csmsNotifyBase; ?>notifications/notifications.js?v=<?php echo $csmsNotifyVersion; ?>" defer></script>
</li>
