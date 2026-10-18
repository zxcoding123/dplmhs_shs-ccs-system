<?php
/**
 * Admin-only gate for endpoints under admin/.
 *
 *   session_start();
 *   require_once __DIR__ . '/../../../includes/guard.php';
 *
 * Browser navigations are sent to the login page; fetch/XHR/form posts get a
 * 401 JSON response, so AJAX callers fail cleanly instead of following a redirect.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    $isNavigation = ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '') === 'navigate'
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false;

    if ($isNavigation) {
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $base = ($docRoot !== '' && stripos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';

        $_SESSION['STATUS'] = 'ADMIN_NOT_LOGGED_IN';
        header('Location: ' . $base . '/login/index.php');
    } else {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized', 'message' => 'Please sign in as an administrator.']);
    }
    exit;
}
