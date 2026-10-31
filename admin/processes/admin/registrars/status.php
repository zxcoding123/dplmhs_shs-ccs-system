<?php
/** Activate or deactivate a registrar's login. */
require __DIR__ . '/../../../../database/connection.php';
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require_once __DIR__ . '/../../../../includes/registrar.php';

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid() || $id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    registrar_flash('Request expired', 'Reload the page and try again.', 'error');
} else {
    $stmt = $pdo->prepare("UPDATE users u JOIN registrar_accounts r ON r.user_id = u.id SET u.status = ? WHERE r.id = ?");
    $stmt->execute([$status, $id]);
    if ($status === 'inactive') {
        // Sign them out everywhere: remember-me cookies would otherwise keep them in
        $pdo->prepare("DELETE t FROM remember_tokens t JOIN registrar_accounts r ON r.user_id = t.user_id WHERE r.id = ?")->execute([$id]);
    }
    registrar_flash('Status updated', $status === 'active' ? 'The registrar can sign in again.' : 'The registrar can no longer sign in.');
}

header('Location: ../../../registrar_management.php');
exit;
