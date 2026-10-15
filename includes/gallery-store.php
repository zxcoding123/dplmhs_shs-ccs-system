<?php
/**
 * Gallery storage shared by the admin Gallery Management screen and the public gallery.
 * Image files live in uploads/gallery/; metadata lives in the gallery_images table,
 * which is created on first use (see also database/gallery_images.sql).
 */

const AU_GALLERY_DIR = __DIR__ . '/../uploads/gallery/';
const AU_GALLERY_URL = 'uploads/gallery/';
const AU_GALLERY_MAX_BYTES = 8 * 1024 * 1024;
const AU_GALLERY_DEFAULT_ALBUM = 'Campus Life';
const AU_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

function au_gallery_ensure_table(PDO $pdo)
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        file_name VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        album VARCHAR(100) NOT NULL DEFAULT 'Campus Life',
        caption TEXT NULL,
        status ENUM('Published','Hidden') NOT NULL DEFAULT 'Published',
        uploaded_by INT(11) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_gallery_status (status),
        KEY idx_gallery_album (album)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $done = true;
}

/**
 * Validate one uploaded image (an entry from $_FILES) and move it into $target_dir.
 * Only real images of an allowed type are accepted; the stored name is random so
 * the original filename can never be executed or overwrite another file.
 *
 * @return array ['file' => stored name] on success, ['error' => message] on failure
 */
function au_store_uploaded_image(array $file, $target_dir, $prefix = 'img_')
{
    $name = $file['name'] ?? 'file';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $limit = in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        return ['error' => $name . ($limit ? ' is too large.' : ' could not be uploaded.')];
    }
    if ($file['size'] > AU_GALLERY_MAX_BYTES) {
        return ['error' => $name . ' is larger than 8 MB.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset(AU_IMAGE_TYPES[$info['mime']])) {
        return ['error' => $name . ' is not a JPG, PNG, GIF or WebP image.'];
    }
    if (!is_dir($target_dir) && !mkdir($target_dir, 0775, true)) {
        return ['error' => 'The upload folder could not be created.'];
    }
    $stored = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . AU_IMAGE_TYPES[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], rtrim($target_dir, '/\\') . '/' . $stored)) {
        return ['error' => $name . ' could not be saved.'];
    }
    return ['file' => $stored];
}

/** Turn the multi-file $_FILES['field'] structure into a list of single-file arrays. */
function au_normalize_files(array $field)
{
    if (!is_array($field['name'])) {
        return [$field];
    }
    $files = [];
    foreach ($field['name'] as $i => $name) {
        if ($name === '' && $field['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => $name,
            'type' => $field['type'][$i],
            'tmp_name' => $field['tmp_name'][$i],
            'error' => $field['error'][$i],
            'size' => $field['size'][$i],
        ];
    }
    return $files;
}

/** "2026-hackathon_finals.jpg" -> "2026 Hackathon Finals" */
function au_title_from_filename($name)
{
    $name = pathinfo($name, PATHINFO_FILENAME);
    $name = preg_replace('/^\d{9,}_/', '', $name);
    return ucwords(trim(preg_replace('/[_\-]+/', ' ', $name))) ?: 'Untitled Photo';
}

function au_gallery_albums(PDO $pdo)
{
    au_gallery_ensure_table($pdo);
    return $pdo->query("SELECT DISTINCT album FROM gallery_images ORDER BY album")->fetchAll(PDO::FETCH_COLUMN);
}

function au_gallery_delete_file($file_name)
{
    $path = AU_GALLERY_DIR . basename($file_name);
    if ($file_name !== '' && is_file($path)) {
        @unlink($path);
    }
}
