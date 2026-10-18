<?php
session_start();
include __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/gallery-store.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

$id = (int) ($_POST['id'] ?? 0);
$status = ($_POST['status'] ?? '') === 'Hidden' ? 'Hidden' : 'Published';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
        throw new RuntimeException('Invalid request');
    }
    au_gallery_ensure_table($pdo);
    $stmt = $pdo->prepare("UPDATE gallery_images SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    $_SESSION['STATUS'] = 'GALLERY_STATUS_SUCCESS';
    $_SESSION['GALLERY_MESSAGE'] = $status === 'Published' ? 'The photo is now visible in the public gallery.' : 'The photo is now hidden from the public gallery.';
} catch (Exception $e) {
    $_SESSION['STATUS'] = 'GALLERY_UPDATE_ERROR';
}

header('Location: ../../../gallery_management.php');
exit;
