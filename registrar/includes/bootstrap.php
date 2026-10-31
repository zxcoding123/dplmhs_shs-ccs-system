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
