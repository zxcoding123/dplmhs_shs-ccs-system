<?php
/** Permanently delete a registrar account. Enrollment records and reviews they made keep their name as text. */
require __DIR__ . '/../../../../database/connection.php';
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require_once __DIR__ . '/../../../../includes/registrar.php';

$id = (int) ($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid() || $id <= 0) {
    registrar_flash('Request expired', 'Reload the page and try again.', 'error');
} else {
    $stmt = $pdo->prepare("SELECT user_id, fullName FROM registrar_accounts WHERE id = ?");
    $stmt->execute([$id]);
    $registrar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registrar) {
        registrar_flash('Not found', 'That registrar account was already deleted.', 'info');
    } else {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$registrar['user_id']]);
        $pdo->prepare("DELETE FROM registrar_accounts WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'registrar'")->execute([$registrar['user_id']]);
        $pdo->commit();
        registrar_flash('Account deleted', $registrar['fullName'] . "'s registrar account was deleted.");
    }
}

header('Location: ../../../registrar_management.php');
exit;
