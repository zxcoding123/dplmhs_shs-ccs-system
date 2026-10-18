<?php
session_start();
include __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/site-helpers.php';
$pdo = Database::getConnection();

$images = au_gallery_images($pdo);
$albums = array_values(array_unique(array_column($images, 'album')));

$page_title = 'Gallery';
$page_description = 'Photos of campus life, events and milestones at the Don Pablo Lorenzo Memorial High School – Stand-Alone Senior High School.';
$active_page = 'gallery';
include __DIR__ . '/includes/site-header.php';
?>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gallery</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Campus Life</span>
        <h1>Photo Gallery</h1>
        <p>Scenes from our classrooms, laboratories, events and celebrations across the school.</p>
    </div>
</section>

<section class="au-section">
    <div class="container">
        <?php if (!$images): ?>
            <div class="au-empty">
                <i class="bi bi-images"></i>
                <p class="mt-2 mb-0">The gallery is empty for now. Please check back soon.</p>
            </div>
        <?php else: ?>
            <?php if (count($albums) > 1): ?>
                <div class="au-filter-bar mb-4" role="group" aria-label="Filter by album">
                    <button type="button" class="active" data-album="">All Photos</button>
                    <?php foreach ($albums as $album): ?>
                        <button type="button" data-album="<?= au_e($album) ?>"><?= au_e($album) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="au-gallery-grid" id="galleryGrid">
                <?php foreach ($images as $i => $image): ?>
                    <?php $shape = $i % 7 === 0 ? ' is-wide is-tall' : ($i % 7 === 4 ? ' is-wide' : ''); ?>
                    <button type="button" class="au-gallery-item<?= $shape ?>" data-album="<?= au_e($image['album']) ?>"
                        data-src="<?= $image['src'] ?>" data-title="<?= au_e($image['title']) ?>" data-caption="<?= au_e($image['caption']) ?>">
                        <img src="<?= $image['src'] ?>" alt="<?= au_e($image['title']) ?>" loading="lazy">
                        <span class="au-gallery-caption"><small><?= au_e($image['album']) ?></small><?= au_e($image['title']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div class="modal fade au-lightbox" id="galleryLightbox" tabindex="-1" aria-label="Photo viewer" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="lightboxImage" src="" alt="">
                <button type="button" class="au-lightbox-nav au-lightbox-prev" id="lightboxPrev" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="au-lightbox-nav au-lightbox-next" id="lightboxNext" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                <p class="au-lightbox-caption" id="lightboxCaption"></p>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = <<<'HTML'
<script>
    (function () {
        var grid = document.getElementById('galleryGrid');
        if (!grid) return;

        var items = Array.prototype.slice.call(grid.querySelectorAll('.au-gallery-item'));
        var modalEl = document.getElementById('galleryLightbox');
        var modal = new bootstrap.Modal(modalEl);
        var img = document.getElementById('lightboxImage');
        var caption = document.getElementById('lightboxCaption');
        var current = 0;

        function visibleItems() {
            return items.filter(function (item) { return !item.hidden; });
        }

        function show(index) {
            var list = visibleItems();
            if (!list.length) return;
            current = (index + list.length) % list.length;
            var item = list[current];
            img.src = item.dataset.src;
            img.alt = item.dataset.title;
            caption.textContent = item.dataset.title + ' — ' + item.dataset.album + '  (' + (current + 1) + ' of ' + list.length + ')'
                + (item.dataset.caption ? '\n' + item.dataset.caption : '');
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                show(visibleItems().indexOf(item));
                modal.show();
            });
        });

        document.getElementById('lightboxPrev').addEventListener('click', function () { show(current - 1); });
        document.getElementById('lightboxNext').addEventListener('click', function () { show(current + 1); });
        modalEl.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') show(current - 1);
            if (e.key === 'ArrowRight') show(current + 1);
        });

        document.querySelectorAll('.au-filter-bar [data-album]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.au-filter-bar [data-album]').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                items.forEach(function (item) {
                    item.hidden = btn.dataset.album !== '' && item.dataset.album !== btn.dataset.album;
                });
            });
        });
    })();
</script>
HTML;
include __DIR__ . '/includes/site-footer.php';
?>
