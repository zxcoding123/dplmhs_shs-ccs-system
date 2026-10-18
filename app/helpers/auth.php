<?php
function requireRole(string $role): void
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== $role) {
        $_SESSION['STATUS'] = strtoupper($role) . "_NOT_LOGGED_IN";

        // Absolute path to <app>/login/index.php, whatever folder the caller lives in
        $root = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $base = ($docRoot !== '' && stripos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';
        header("Location: " . $base . "/login/index.php");
        exit;
    }
}
