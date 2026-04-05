<?php
function requireRole(string $role): void
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== $role) {
        $_SESSION['STATUS'] = strtoupper($role) . "_NOT_LOGGED_IN";
        header("Location: ../../login/index.php");
        exit;
    }
}
