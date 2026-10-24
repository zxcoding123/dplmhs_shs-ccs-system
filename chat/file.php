<?php
/**
 * Serves chat attachments to people in that conversation only.
 *
 *   GET ?id=..              photo inline / file as a download
 *   GET ?id=..&size=thumb   small preview used in the chat bubble
 *   GET ?id=..&download=1   always download, with the original file name
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../app/helpers/ChatHelper.php';

$chat = ChatHelper::fromSession($pdo);
session_write_close();

function chat_file_fail(int $status, string $text): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

if ($chat === null) {
    chat_file_fail(401, 'Please log in to view this file.');
}

$att = ChatAttachments::find($pdo, (int) ($_GET['id'] ?? 0));
if (!$att || !$chat->canAccessAttachment($att)) {
    chat_file_fail(404, 'File not found.');
}

$wantThumb = ($_GET['size'] ?? '') === 'thumb' && $att['thumb_name'];
$stored = $wantThumb ? $att['thumb_name'] : $att['stored_name'];
$path = ChatAttachments::storageDir() . '/' . $stored;
if (!is_file($path)) {
    chat_file_fail(404, 'File not found.');
}

$mime = $wantThumb ? (pathinfo($stored, PATHINFO_EXTENSION) === 'webp' ? 'image/webp' : 'image/jpeg') : $att['mime'];
$inline = $att['kind'] === 'image' && empty($_GET['download']);
$name = $att['original_name'];
$ascii = preg_replace('/[^\x20-\x7E]|["\\\\]/', '_', $name);

// Attachments never change, so browsers can keep them for a long time
$etag = '"' . $att['id'] . ($wantThumb ? 't' : '') . '"';
header('ETag: ' . $etag);
header('Cache-Control: private, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . ($inline ? $mime : ($mime ?: 'application/octet-stream')));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));

if (!$wantThumb && $att['gzipped']) {
    // Stored gzipped: pass it straight through when the browser accepts gzip
    header('Vary: Accept-Encoding');
    if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
        header('Content-Encoding: gzip');
        header('Content-Length: ' . filesize($path));
        readfile($path);
    } else {
        header('Content-Length: ' . $att['original_size']);
        readgzfile($path);
    }
    exit;
}

header('Content-Length: ' . filesize($path));
readfile($path);
