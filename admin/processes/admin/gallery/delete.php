<?php
session_start();
include('../../server/conn.php');
require_once __DIR__ . '/../../../../includes/gallery-store.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

// Accepts one id or a list of ids (bulk delete).
$ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? $_POST['id'] ?? [])));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$ids) {
        throw new RuntimeException('Invalid request');
    }
    au_gallery_ensure_table($pdo);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT file_name FROM gallery_images WHERE id IN ($placeholders)");
    $stmt->execute(array_values($ids));
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->prepare("DELETE FROM gallery_images WHERE id IN ($placeholders)")->execute(array_values($ids));
    foreach ($files as $file) {
        au_gallery_delete_file($file);
    }

    $_SESSION['STATUS'] = 'GALLERY_DELETE_SUCCESS';
    $_SESSION['GALLERY_MESSAGE'] = count($files) . ' photo' . (count($files) === 1 ? '' : 's') . ' deleted.';
} catch (Exception $e) {
    $_SESSION['STATUS'] = 'GALLERY_DELETE_ERROR';
}

header('Location: ../../../gallery_management.php');
exit;
