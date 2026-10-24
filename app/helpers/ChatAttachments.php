<?php
/**
 * Files and photos sent in chat (direct and group messages).
 *
 * One attachment per message, linked by (scope, message_id) where scope is
 * 'dm' (messages.id) or 'group' (group_messages.id). Files live under
 * uploads/chat/ (not web-accessible) and are served by chat/file.php, which
 * checks that the viewer is part of the conversation.
 *
 * Optimisation:
 *  - images are re-encoded (WebP, or JPEG without WebP support), capped at
 *    IMAGE_MAX_EDGE px and stripped of metadata such as GPS location; a small
 *    thumbnail is made for the chat bubble. The widget already compresses in
 *    the browser, so this is the safety net for anything that slips through.
 *  - GIFs are kept as they are so animations survive.
 *  - documents that compress well (text, CSV, old Office formats, PDFs) are
 *    stored gzipped and sent gzipped to browsers that accept it.
 */
class ChatAttachments
{
    public const MAX_BYTES = 25 * 1024 * 1024;
    public const IMAGE_MAX_EDGE = 1920;
    public const THUMB_EDGE = 480;
    private const MAX_PIXELS = 50_000_000;

    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    /** Everything else that may be sent, by extension. Served as downloads only. */
    public const FILE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'csv', 'md',
        'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'bmp',
        'mp3', 'm4a', 'wav', 'ogg', 'mp4', 'mov', 'webm',
    ];
    private const GZIP_EXTENSIONS = ['txt', 'csv', 'md', 'rtf', 'doc', 'xls', 'ppt', 'pdf', 'bmp', 'wav'];

    private static bool $ready = false;

    public static function ensureTable(PDO $pdo): void
    {
        if (self::$ready) {
            return;
        }
        self::$ready = true;
        $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_attachments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `scope` enum('dm','group') NOT NULL,
            `message_id` bigint(20) NOT NULL,
            `kind` enum('image','file') NOT NULL,
            `stored_name` varchar(120) NOT NULL,
            `thumb_name` varchar(120) DEFAULT NULL,
            `original_name` varchar(255) NOT NULL,
            `mime` varchar(120) NOT NULL,
            `size` int(11) NOT NULL,
            `original_size` int(11) NOT NULL,
            `width` int(11) DEFAULT NULL,
            `height` int(11) DEFAULT NULL,
            `gzipped` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_chat_attachment_message` (`scope`, `message_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    public static function storageDir(): string
    {
        return dirname(__DIR__, 2) . '/uploads/chat';
    }

    /**
     * Validate, optimise and save one uploaded file ($_FILES entry). Returns the
     * metadata to pass to link(); call discard() if the message isn't saved.
     */
    public static function store(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new ChatException('That file is too large (max ' . self::maxLabel() . ').', 'file_too_large');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new ChatException('The file could not be uploaded. Please try again.', 'upload_failed');
        }
        $size = (int) filesize($file['tmp_name']);
        if ($size <= 0) {
            throw new ChatException('That file is empty.', 'empty_file');
        }
        if ($size > self::MAX_BYTES) {
            throw new ChatException('That file is too large (max ' . self::maxLabel() . ').', 'file_too_large');
        }

        $name = self::cleanName((string) ($file['name'] ?? 'file'));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';

        $dir = self::storageDir() . '/' . date('Y/m');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Chat upload folder could not be created.');
        }
        $base = date('Y/m') . '/' . bin2hex(random_bytes(16));

        if (isset(self::IMAGE_TYPES[$mime])) {
            return self::storeImage($file['tmp_name'], $mime, $name, $size, $base);
        }
        if (!in_array($ext, self::FILE_EXTENSIONS, true) || in_array($mime, ['text/html', 'application/x-php', 'text/x-php', 'image/svg+xml'], true)) {
            throw new ChatException('This type of file can\'t be sent. Try a PDF, Office document, image, ZIP or media file.', 'file_type');
        }
        return self::storeFile($file['tmp_name'], $mime, $name, $ext, $size, $base);
    }

    private static function storeImage(string $tmp, string $mime, string $name, int $size, string $base): array
    {
        [$w, $h] = getimagesize($tmp) ?: [0, 0];
        if ($w <= 0 || $h <= 0 || $w * $h > self::MAX_PIXELS) {
            throw new ChatException('That image could not be read or is too large.', 'bad_image');
        }

        $dir = self::storageDir() . '/';
        $src = self::loadImage($tmp, $mime);
        $meta = ['kind' => 'image', 'thumb_name' => null, 'original_size' => $size, 'gzipped' => 0];

        if ($mime === 'image/gif' || $src === null) {
            // Keep GIFs (animation) and anything GD can't open exactly as sent.
            $stored = $base . '.' . self::IMAGE_TYPES[$mime];
            self::moveUpload($tmp, $dir . $stored);
            $meta += ['stored_name' => $stored, 'original_name' => $name, 'mime' => $mime, 'size' => $size, 'width' => $w, 'height' => $h];
        } else {
            $src = self::orient($src, $tmp, $mime);
            $full = self::fit($src, self::IMAGE_MAX_EDGE);
            [$bytes, $outMime, $outExt] = self::encode($full, 80);
            $fw = imagesx($full);
            $fh = imagesy($full);

            // Re-encoding a small, already-compressed photo can make it bigger; keep the original then.
            $keepOriginal = strlen($bytes) >= $size && $fw === $w && $fh === $h;
            if ($keepOriginal) {
                $stored = $base . '.' . self::IMAGE_TYPES[$mime];
                self::moveUpload($tmp, $dir . $stored);
                $meta += ['stored_name' => $stored, 'original_name' => $name, 'mime' => $mime, 'size' => $size];
            } else {
                $stored = $base . '.' . $outExt;
                file_put_contents($dir . $stored, $bytes);
                $meta += ['stored_name' => $stored, 'original_name' => self::withExtension($name, $outExt), 'mime' => $outMime, 'size' => strlen($bytes)];
            }
            $meta += ['width' => $fw, 'height' => $fh];
            if ($full !== $src) {
                imagedestroy($full);
            }
        }

        // Thumbnail for the chat bubble (first frame for GIFs)
        $thumbSrc = $src ?? self::loadImage($dir . $meta['stored_name'], $meta['mime']);
        if ($thumbSrc !== null && max($meta['width'], $meta['height']) > self::THUMB_EDGE && $mime !== 'image/gif') {
            $thumb = self::fit($thumbSrc, self::THUMB_EDGE);
            [$tBytes, , $tExt] = self::encode($thumb, 72);
            $meta['thumb_name'] = $base . '.thumb.' . $tExt;
            file_put_contents($dir . $meta['thumb_name'], $tBytes);
            imagedestroy($thumb);
        }
        if ($thumbSrc !== null) {
            imagedestroy($thumbSrc);
        }
        return $meta;
    }

    private static function storeFile(string $tmp, string $mime, string $name, string $ext, int $size, string $base): array
    {
        $dir = self::storageDir() . '/';
        $stored = $base . '.' . $ext;
        $gzipped = 0;
        $finalSize = $size;

        if (in_array($ext, self::GZIP_EXTENSIONS, true)) {
            $packed = gzencode((string) file_get_contents($tmp), 6);
            if ($packed !== false && strlen($packed) < $size * 0.9) {
                $stored .= '.gz';
                file_put_contents($dir . $stored, $packed);
                $gzipped = 1;
                $finalSize = strlen($packed);
            }
        }
        if (!$gzipped) {
            self::moveUpload($tmp, $dir . $stored);
        }

        return [
            'kind' => 'file', 'stored_name' => $stored, 'thumb_name' => null, 'original_name' => $name,
            'mime' => $mime, 'size' => $finalSize, 'original_size' => $size, 'width' => null, 'height' => null,
            'gzipped' => $gzipped,
        ];
    }

    public static function link(PDO $pdo, string $scope, int $messageId, array $meta): void
    {
        $stmt = $pdo->prepare("INSERT INTO chat_attachments
            (scope, message_id, kind, stored_name, thumb_name, original_name, mime, size, original_size, width, height, gzipped)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $scope, $messageId, $meta['kind'], $meta['stored_name'], $meta['thumb_name'], $meta['original_name'],
            $meta['mime'], $meta['size'], $meta['original_size'], $meta['width'], $meta['height'], $meta['gzipped'],
        ]);
    }

    /** Remove files saved by store() when the message itself couldn't be saved. */
    public static function discard(array $meta): void
    {
        foreach ([$meta['stored_name'] ?? null, $meta['thumb_name'] ?? null] as $f) {
            if ($f) {
                @unlink(self::storageDir() . '/' . $f);
            }
        }
    }

    /** message id => public attachment info, for one scope. */
    public static function forMessages(PDO $pdo, string $scope, array $messageIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $messageIds)));
        if (!$ids) {
            return [];
        }
        $stmt = $pdo->prepare("SELECT * FROM chat_attachments WHERE scope = ? AND message_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
        $stmt->execute(array_merge([$scope], $ids));
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['message_id']] = self::publicInfo($row);
        }
        return $map;
    }

    public static function publicInfo(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'kind' => $row['kind'],
            'name' => $row['original_name'],
            'mime' => $row['mime'],
            // What the viewer downloads: the optimised photo, or the file as sent (gzip is undone in transit)
            'size' => (int) ($row['kind'] === 'image' ? $row['size'] : $row['original_size']),
            'width' => $row['width'] !== null ? (int) $row['width'] : null,
            'height' => $row['height'] !== null ? (int) $row['height'] : null,
        ];
    }

    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM chat_attachments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function deleteFor(PDO $pdo, string $scope, int $messageId): void
    {
        $stmt = $pdo->prepare("SELECT * FROM chat_attachments WHERE scope = ? AND message_id = ?");
        $stmt->execute([$scope, $messageId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            self::discard($row);
            $pdo->prepare("DELETE FROM chat_attachments WHERE id = ?")->execute([$row['id']]);
        }
    }

    /** Short text for conversation lists, e.g. "📷 Photo" or "📎 Grades.xlsx". */
    public static function preview(?string $kind, ?string $name, string $caption): string
    {
        if ($kind === 'image') {
            return '📷 ' . ($caption !== '' ? $caption : 'Photo');
        }
        if ($kind === 'file') {
            return '📎 ' . ($name ?: 'File');
        }
        return $caption;
    }

    public static function maxLabel(): string
    {
        return (self::MAX_BYTES / 1024 / 1024) . ' MB';
    }

    /* ---------------- internals ---------------- */

    private static function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/u', '', basename(str_replace('\\', '/', $name)));
        $name = trim((string) $name, " .");
        return $name !== '' ? mb_substr($name, -200) : 'file';
    }

    private static function withExtension(string $name, string $ext): string
    {
        $stem = pathinfo($name, PATHINFO_FILENAME);
        return ($stem !== '' ? $stem : 'photo') . '.' . $ext;
    }

    private static function moveUpload(string $tmp, string $dest): void
    {
        if (!move_uploaded_file($tmp, $dest) && !copy($tmp, $dest)) {
            throw new RuntimeException('Chat upload could not be saved.');
        }
    }

    /** @return GdImage|resource|null */
    private static function loadImage(string $path, string $mime)
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };
        if (!$img) {
            return null;
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);
        return $img;
    }

    /** Apply the camera's EXIF rotation to JPEGs, since re-encoding drops the tag. */
    private static function orient($img, string $path, string $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $img;
        }
        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle) {
            $rotated = imagerotate($img, $angle, 0);
            if ($rotated) {
                imagedestroy($img);
                return $rotated;
            }
        }
        return $img;
    }

    private static function fit($img, int $maxEdge)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $maxEdge / max($w, $h));
        if ($scale >= 1) {
            return $img;
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $out;
    }

    /** @return array{0: string, 1: string, 2: string} bytes, mime, extension */
    private static function encode($img, int $quality): array
    {
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($img, null, $quality);
            return [ob_get_clean(), 'image/webp', 'webp'];
        }
        // No WebP: flatten transparency onto white and use JPEG
        $w = imagesx($img);
        $h = imagesy($img);
        $flat = imagecreatetruecolor($w, $h);
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
        imageinterlace($flat, true);
        imagejpeg($flat, null, $quality + 2);
        imagedestroy($flat);
        return [ob_get_clean(), 'image/jpeg', 'jpg'];
    }
}
