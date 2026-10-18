<?php
session_start();
include __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/site-helpers.php';
$pdo = Database::getConnection();

$per_page = 9;
$category = in_array($_GET['category'] ?? '', AU_POST_CATEGORIES, true) ? $_GET['category'] : '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ["status = 'Published'"];
$params = [];
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($search !== '') {
    $where[] = '(title LIKE ? OR content LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$where_sql = implode(' AND ', $where);

$count = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE $where_sql");
$count->execute($params);
$total = (int) $count->fetchColumn();
$total_pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT * FROM posts WHERE $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function au_news_url(array $overrides = [])
{
    global $category, $search;
    $query = array_filter(array_merge(['category' => $category, 'q' => $search], $overrides), function ($v) {
        return $v !== '' && $v !== null && $v !== 1;
    });
    return 'news.php' . ($query ? '?' . http_build_query($query) : '');
}

$page_title = 'News & Events';
$page_description = 'News, announcements, events and academic updates from the Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
$active_page = 'news';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">News &amp; Events</li>
            </ol>
        </nav>
        <span class="au-eyebrow">The DPLMHS Newsroom</span>
        <h1>News &amp; Events</h1>
        <p>Announcements, stories and updates from the Office of the Principal, our faculty and our students.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-5">
            <div class="au-filter-bar" role="navigation" aria-label="Filter by category">
                <a href="<?= au_e(au_news_url(['category' => '', 'page' => null])) ?>" class="<?= $category === '' ? 'active' : '' ?>">All</a>
                <?php foreach (AU_POST_CATEGORIES as $cat): ?>
                    <a href="<?= au_e(au_news_url(['category' => $cat, 'page' => null])) ?>" class="<?= $category === $cat ? 'active' : '' ?>"><?= au_e($cat) ?></a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="news.php" class="d-flex gap-2" role="search">
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="category" value="<?= au_e($category) ?>">
                <?php endif; ?>
                <label for="newsSearch" class="visually-hidden">Search news</label>
                <input type="search" id="newsSearch" name="q" class="form-control" placeholder="Search news…" value="<?= au_e($search) ?>">
                <button class="btn btn-au-navy" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <?php if ($search !== '' || $category !== ''): ?>
            <p class="au-meta mb-4">
                <?= $total ?> result<?= $total === 1 ? '' : 's' ?>
                <?= $category !== '' ? ' in <strong>' . au_e($category) . '</strong>' : '' ?>
                <?= $search !== '' ? ' for “<strong>' . au_e($search) . '</strong>”' : '' ?>
                · <a href="news.php">Clear filters</a>
            </p>
        <?php endif; ?>

        <?php if (!$posts): ?>
            <div class="au-empty">
                <i class="bi bi-newspaper"></i>
                <p class="mt-2 mb-0">No articles found. Try another category or search term.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($posts as $i => $post): ?>
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 100 ?>">
                        <article class="au-article-card">
                            <a href="<?= au_article_url($post) ?>" class="au-article-thumb" tabindex="-1" aria-hidden="true">
                                <img src="<?= au_post_image($post) ?>" alt="" loading="lazy">
                                <span class="au-badge <?= au_category_class($post['category']) ?>"><?= au_e($post['category']) ?></span>
                            </a>
                            <div class="au-article-body">
                                <span class="au-meta">
                                    <i class="bi bi-calendar3 me-1"></i> <?= au_post_date($post) ?>
                                    <span class="mx-1">·</span> <?= au_reading_time($post) ?> min read
                                </span>
                                <h3><a href="<?= au_article_url($post) ?>"><?= au_e($post['title']) ?></a></h3>
                                <p><?= au_e(au_post_excerpt($post)) ?></p>
                                <a href="<?= au_article_url($post) ?>" class="au-link-arrow">Read article <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-5" aria-label="News pages">
                    <ul class="pagination au-pagination justify-content-center">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                            <a class="page-link" href="<?= au_e(au_news_url(['page' => $page - 1])) ?>" aria-label="Previous">&laquo;</a>
                        </li>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= au_e(au_news_url(['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                            <a class="page-link" href="<?= au_e(au_news_url(['page' => $page + 1])) ?>" aria-label="Next">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
