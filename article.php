<?php
session_start();
include __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/site-helpers.php';
$pdo = Database::getConnection();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, CONCAT(a.first_name, ' ', a.last_name) AS author_name
                       FROM posts p
                       LEFT JOIN admin a ON p.author_id = a.id
                       WHERE p.id = ? AND p.status = 'Published'");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
}

$related = [];
if ($post) {
    $rel = $pdo->prepare("SELECT * FROM posts WHERE status = 'Published' AND id <> ?
                          ORDER BY (category = ?) DESC, created_at DESC LIMIT 3");
    $rel->execute([$post['id'], $post['category']]);
    $related = $rel->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = $post ? $post['title'] : 'Article not found';
$page_description = $post ? au_post_excerpt($post, 155) : null;
$active_page = 'news';
include __DIR__ . '/includes/site-header.php';
?>

<?php if (!$post): ?>
    <section class="au-section">
        <div class="container">
            <div class="au-empty">
                <i class="bi bi-file-earmark-x"></i>
                <h1 class="h3 mt-3">Article not found</h1>
                <p>The article you are looking for may have been moved or is no longer available.</p>
                <a href="news.php" class="btn btn-au-navy">Back to News &amp; Events</a>
            </div>
        </div>
    </section>
<?php else: ?>
    <article>
        <section class="au-section pb-0">
            <div class="container">
                <header class="au-article-header">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="news.php">News &amp; Events</a></li>
                            <li class="breadcrumb-item"><a href="news.php?category=<?= urlencode($post['category']) ?>"><?= au_e($post['category']) ?></a></li>
                        </ol>
                    </nav>
                    <span class="au-badge <?= au_category_class($post['category']) ?>"><?= au_e($post['category']) ?></span>
                    <h1><?= au_e($post['title']) ?></h1>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 border-bottom">
                        <span class="au-meta">
                            <i class="bi bi-person me-1"></i> <?= au_e($post['author_name'] ?: 'Office of the Principal') ?>
                            <span class="mx-2">·</span>
                            <i class="bi bi-calendar3 me-1"></i> <?= au_post_date($post) ?>
                            <span class="mx-2">·</span>
                            <i class="bi bi-clock me-1"></i> <?= au_reading_time($post) ?> min read
                        </span>
                        <div class="au-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=" class="js-share" data-network="facebook" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="bi bi-facebook"></i></a>
                            <a href="https://twitter.com/intent/tweet?url=" class="js-share" data-network="x" target="_blank" rel="noopener" aria-label="Share on X"><i class="bi bi-twitter-x"></i></a>
                            <button type="button" id="copyLink" aria-label="Copy link"><i class="bi bi-link-45deg"></i></button>
                        </div>
                    </div>
                </header>

                <?php if (!empty($post['featured_image'])): ?>
                    <figure class="au-article-cover">
                        <img src="<?= au_post_image($post) ?>" alt="<?= au_e($post['title']) ?>">
                    </figure>
                <?php endif; ?>
            </div>
        </section>

        <section class="au-section pt-5">
            <div class="container">
                <div class="au-article-content">
                    <?= $post['content'] /* Trusted HTML authored by admins in Post Management */ ?>
                </div>
                <div class="au-article-content mt-5 pt-4 border-top">
                    <a href="news.php" class="au-link-arrow"><i class="bi bi-arrow-left"></i> Back to all news</a>
                </div>
            </div>
        </section>
    </article>

    <?php if ($related): ?>
        <section class="au-section au-section-paper">
            <div class="container">
                <div class="au-section-head">
                    <div>
                        <span class="au-eyebrow">Keep Reading</span>
                        <h2 class="au-section-title mb-0">Related stories</h2>
                    </div>
                    <a href="news.php" class="au-link-arrow">View all news <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="row g-4">
                    <?php foreach ($related as $item): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="au-article-card">
                                <a href="<?= au_article_url($item) ?>" class="au-article-thumb" tabindex="-1" aria-hidden="true">
                                    <img src="<?= au_post_image($item) ?>" alt="" loading="lazy">
                                    <span class="au-badge <?= au_category_class($item['category']) ?>"><?= au_e($item['category']) ?></span>
                                </a>
                                <div class="au-article-body">
                                    <span class="au-meta"><i class="bi bi-calendar3 me-1"></i> <?= au_post_date($item) ?></span>
                                    <h3><a href="<?= au_article_url($item) ?>"><?= au_e($item['title']) ?></a></h3>
                                    <p><?= au_e(au_post_excerpt($item, 120)) ?></p>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php
$page_scripts = <<<'HTML'
<script>
    document.querySelectorAll('.js-share').forEach(function (link) {
        link.href += encodeURIComponent(window.location.href);
    });
    var copyBtn = document.getElementById('copyLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(window.location.href).then(function () {
                copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(function () { copyBtn.innerHTML = '<i class="bi bi-link-45deg"></i>'; }, 2000);
            });
        });
    }
</script>
HTML;
include __DIR__ . '/includes/site-footer.php';
?>
