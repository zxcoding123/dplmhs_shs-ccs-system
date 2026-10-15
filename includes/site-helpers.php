<?php
/**
 * Shared helpers for the public website (homepage, news, articles, gallery).
 * All paths are relative to the project root, where the public pages live.
 */

const AU_DEFAULT_POST_IMAGE = 'external/img/4-Pillars-III-COLOR-MODIFIED-2048x1365-1.jpg';
const AU_POST_CATEGORIES = ['Announcement', 'News', 'Event', 'Academic', 'Other'];

function au_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** URL of a post's featured image, falling back to a campus photo. */
function au_post_image(array $post)
{
    $file = $post['featured_image'] ?? '';
    if ($file !== '' && is_file(__DIR__ . '/../uploads/posts/' . $file)) {
        return 'uploads/posts/' . rawurlencode($file);
    }
    return AU_DEFAULT_POST_IMAGE;
}

/** Plain-text excerpt of a post's HTML content. */
function au_post_excerpt(array $post, $length = 160)
{
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($post['content'] ?? ''), ENT_QUOTES, 'UTF-8')));
    if (mb_strlen($text) > $length) {
        $text = rtrim(mb_substr($text, 0, $length)) . '…';
    }
    return $text;
}

function au_post_date(array $post, $format = 'F j, Y')
{
    return date($format, strtotime($post['created_at']));
}

/** Rough reading time in minutes. */
function au_reading_time(array $post)
{
    $words = str_word_count(strip_tags($post['content'] ?? ''));
    return max(1, (int) ceil($words / 200));
}

function au_category_class($category)
{
    $map = [
        'News' => 'au-badge-news',
        'Event' => 'au-badge-event',
        'Academic' => 'au-badge-academic',
        'Other' => 'au-badge-other',
    ];
    return $map[$category] ?? '';
}

function au_article_url(array $post)
{
    return 'article.php?id=' . (int) $post['id'];
}

/**
 * Collect gallery images.
 *  - Published photos from Admin > Gallery Management (gallery_images table)
 *  - Featured images of published posts (album "News & Events")
 *  - Built-in campus photos (album "Campus")
 */
function au_gallery_images(?PDO $pdo = null)
{
    $images = [];
    $seen = [];

    $add = function ($src, $title, $album, $caption = '') use (&$images, &$seen) {
        $key = strtolower(basename(rawurldecode($src)));
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $images[] = ['src' => $src, 'title' => $title, 'album' => $album, 'caption' => (string) $caption];
    };

    if ($pdo) {
        require_once __DIR__ . '/gallery-store.php';
        try {
            au_gallery_ensure_table($pdo);
            $stmt = $pdo->query("SELECT file_name, title, album, caption FROM gallery_images WHERE status = 'Published' ORDER BY created_at DESC, id DESC");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $photo) {
                if (is_file(AU_GALLERY_DIR . $photo['file_name'])) {
                    $add(AU_GALLERY_URL . rawurlencode($photo['file_name']), $photo['title'], $photo['album'], $photo['caption']);
                }
            }
        } catch (PDOException $e) {
            // Fall through to post images and campus photos.
        }

        try {
            $stmt = $pdo->query("SELECT id, title, featured_image FROM posts WHERE status = 'Published' AND featured_image IS NOT NULL AND featured_image <> '' ORDER BY created_at DESC LIMIT 24");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post) {
                if (is_file(__DIR__ . '/../uploads/posts/' . $post['featured_image'])) {
                    $add('uploads/posts/' . rawurlencode($post['featured_image']), $post['title'], 'News & Events');
                }
            }
        } catch (PDOException $e) {
            // Gallery still renders from files if the posts table is unavailable.
        }
    }

    $add('external/img/Kanto-TDRI-DMOC-4.webp', 'Aurelius University Campus', 'Campus');
    $add('external/img/4-Pillars-III-COLOR-MODIFIED-2048x1365-1.jpg', 'The Four Pillars of Aurelius', 'Campus');

    return $images;
}
