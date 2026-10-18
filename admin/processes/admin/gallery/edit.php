<?php
session_start();
include __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/gallery-store.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

$id = (int) ($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$album = trim($_POST['album'] ?? '') ?: AU_GALLERY_DEFAULT_ALBUM;
$caption = trim($_POST['caption'] ?? '');
$status = ($_POST['status'] ?? 'Published') === 'Hidden' ? 'Hidden' : 'Published';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0 || $title === '') {
    $_SESSION['STATUS'] = 'GALLERY_UPDATE_ERROR';
    $_SESSION['GALLERY_MESSAGE'] = 'A title is required.';
    header('Location: ../../../gallery_management.php');
    exit;
}

try {
    au_gallery_ensure_table($pdo);
    $stmt = $pdo->prepare("UPDATE gallery_images SET title = ?, album = ?, caption = ?, status = ? WHERE id = ?");
    $stmt->execute([mb_substr($title, 0, 255), mb_substr($album, 0, 100), $caption ?: null, $status, $id]);
    $_SESSION['STATUS'] = 'GALLERY_UPDATE_SUCCESS';
} catch (PDOException $e) {
    $_SESSION['STATUS'] = 'GALLERY_UPDATE_ERROR';
}

header('Location: ../../../gallery_management.php');
exit;
