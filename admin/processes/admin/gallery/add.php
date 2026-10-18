<?php
session_start();
date_default_timezone_set('Asia/Manila');
include __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/gallery-store.php';

if (!isset($_SESSION['admin_id'])) {
    die("You're not logged in as an admin.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photos'])) {
    $_SESSION['STATUS'] = 'GALLERY_UPLOAD_ERROR';
    $_SESSION['GALLERY_MESSAGE'] = 'Please choose at least one photo to upload.';
    header('Location: ../../../gallery_management.php');
    exit;
}

$album = trim($_POST['album'] ?? '') ?: AU_GALLERY_DEFAULT_ALBUM;
$title = trim($_POST['title'] ?? '');
$caption = trim($_POST['caption'] ?? '');
$status = ($_POST['status'] ?? 'Published') === 'Hidden' ? 'Hidden' : 'Published';

au_gallery_ensure_table($pdo);
$insert = $pdo->prepare("INSERT INTO gallery_images (file_name, title, album, caption, status, uploaded_by)
                         VALUES (?, ?, ?, ?, ?, ?)");

$files = au_normalize_files($_FILES['photos']);
$saved = 0;
$errors = [];

foreach ($files as $i => $file) {
    $result = au_store_uploaded_image($file, AU_GALLERY_DIR, 'gallery_');
    if (isset($result['error'])) {
        $errors[] = $result['error'];
        continue;
    }

    // A typed title applies to every photo in the batch, numbered when there are several.
    if ($title === '') {
        $photo_title = au_title_from_filename($file['name']);
    } else {
        $photo_title = count($files) > 1 ? $title . ' (' . ($i + 1) . ')' : $title;
    }

    try {
        $insert->execute([$result['file'], mb_substr($photo_title, 0, 255), mb_substr($album, 0, 100), $caption ?: null, $status, $_SESSION['admin_id']]);
        $saved++;
    } catch (PDOException $e) {
        au_gallery_delete_file($result['file']);
        $errors[] = $file['name'] . ' could not be saved to the database.';
    }
}

if ($saved === 0) {
    $_SESSION['STATUS'] = 'GALLERY_UPLOAD_ERROR';
    $_SESSION['GALLERY_MESSAGE'] = $errors ? implode(' ', $errors) : 'No photos were uploaded.';
} else {
    $_SESSION['STATUS'] = 'GALLERY_UPLOAD_SUCCESS';
    $_SESSION['GALLERY_MESSAGE'] = $saved . ' photo' . ($saved === 1 ? '' : 's') . ' added to "' . $album . '".'
        . ($errors ? ' Skipped: ' . implode(' ', $errors) : '');
}

header('Location: ../../../gallery_management.php');
exit;
