<?php
/**
 * Loaded first by every page and process in registrar/.
 * Signs out anyone who isn't a registrar and loads the shared helpers.
 * The portal reuses the admin portal's look (admin/css, admin/js, admin/includes/ui.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
requireRole('registrar');

require_once __DIR__ . '/../../admin/includes/ui.php';
require_once __DIR__ . '/../../includes/registrar.php';

$registrarId = (int) $_SESSION['registrar_id'];

// The session outlives the account: sign out a registrar the admin has since deleted or deactivated
$stillActive = $pdo->prepare("SELECT 1 FROM registrar_accounts r JOIN users u ON u.id = r.user_id WHERE r.id = ? AND u.status = 'active'");
$stillActive->execute([$registrarId]);
if (!$stillActive->fetchColumn()) {
    require_once __DIR__ . '/../../app/logout.php';
    csms_logout('../' . (basename(dirname($_SERVER['PHP_SELF'])) === 'processes' ? '../' : '') . 'login/index.php', 'ACCOUNT_DEACTIVATED');
}
$registrarName = $_SESSION['name'] ?? 'Registrar';

/** For processes: send the registrar back with a one-time message. */
function registrar_redirect(string $url, ?array $flash = null): void
{
    if ($flash) {
        registrar_flash(...$flash);
    }
    header('Location: ' . $url);
    exit;
}

/** For processes: POST only, with a valid form token. */
function registrar_require_post(string $back): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid()) {
        registrar_redirect($back, ['Request expired', 'Reload the page and try again.', 'error']);
    }
}
