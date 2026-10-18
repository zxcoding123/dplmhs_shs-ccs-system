<?php
/**
 * Shared logout for every role.
 *
 * Do NOT load auth_bootstrap.php here: constructing AuthService auto-logs the
 * user back in from the remember_me cookie, which is exactly what we are
 * trying to undo.
 */
function csms_logout(string $redirect, string $status = 'SUCCESSFUL_LOG_OUT'): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Revoke the remember-me token so the cookie can't log the user back in.
    if (!empty($_COOKIE['remember_me'])) {
        try {
            require_once __DIR__ . '/../database/connection.php';
            $stmt = Database::getConnection()->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
            $stmt->execute([hash('sha256', $_COOKIE['remember_me'])]);
        } catch (Throwable $e) {
            error_log('Logout: could not revoke remember_me token: ' . $e->getMessage());
        }
    }

    setcookie('remember_me', '', [
        'expires' => time() - 42000,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    unset($_COOKIE['remember_me']);

    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();

    // Fresh session just to carry the "logged out" alert to the login page.
    session_start();
    $_SESSION['STATUS'] = $status;

    header('Location: ' . $redirect);
    exit;
}
