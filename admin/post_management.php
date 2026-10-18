<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';

$categories = ['Announcement', 'News', 'Event', 'Academic', 'Other'];

$posts = $pdo->query("SELECT p.*, CONCAT(a.first_name, ' ', a.last_name) AS author_name
    FROM posts p
    LEFT JOIN admin a ON p.author_id = a.id
    ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$publishedCount = count(array_filter($posts, fn($p) => $p['status'] === 'Published'));
$draftCount = count($posts) - $publishedCount;
$thisMonth = count(array_filter($posts, fn($p) => date('Y-m', strtotime($p['created_at'])) === date('Y-m')));
$usedCategories = array_values(array_unique(array_column($posts, 'category')));

function post_excerpt(string $html, int $length = 110): string
{
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>News &amp; Posts | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .post-title { font-weight: 700; color: var(--ui-navy); }
        .post-excerpt { display: block; max-width: 420px; margin-top: 0.15rem; font-size: 0.8rem; color: var(--ui-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .post-thumb { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; flex-shrink: 0; background: var(--ui-soft); }
        .post-thumb.is-empty { display: inline-flex; align-items: center; justify-content: center; color: var(--ui-muted); }
        .post-view-image { width: 100%; max-height: 320px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem; }
        .post-view-body { max-height: 55vh; overflow-y: auto; line-height: 1.65; }
        .post-view-body img { max-width: 100%; height: auto; }
        .ck-editor__editable { min-height: 220px; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php') ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System</span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <?php admin_page_header('News & Posts', 'Announcements, news and events published on the school website', 'bi-newspaper',
                        '<a href="../news.php" target="_blank" class="btn btn-ui-ghost"><i class="bi bi-box-arrow-up-right"></i> View on website</a>
                         <button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="bi bi-plus-lg"></i> New post</button>'); ?>

                    <?php admin_stat_tiles([
                        ['label' => 'All posts', 'value' => count($posts), 'icon' => 'bi-collection', 'filter' => ''],
                        ['label' => 'Published', 'value' => $publishedCount, 'icon' => 'bi-broadcast', 'tone' => 'ok', 'filter' => 'Published', 'filter_col' => 'Status'],
                        ['label' => 'Drafts', 'value' => $draftCount, 'icon' => 'bi-pencil-square', 'tone' => 'muted', 'filter' => 'Draft', 'filter_col' => 'Status'],
                        ['label' => 'This month', 'value' => $thisMonth, 'icon' => 'bi-calendar-event'],
                    ]); ?>

                    <section class="ui-panel">
                        <?php if ($posts): ?>
                            <div class="ui-panel-head">
                                <div>
                                    <h2>All posts</h2>
                                    <p>Click a tile above to filter by status. Press <kbd>/</kbd> to search.</p>
                                </div>
                                <div class="ui-filters">
                                    <select class="form-select form-select-sm" id="categoryFilter" aria-label="Filter by category">
                                        <option value="">All categories</option>
                                        <?php foreach ($usedCategories as $category): ?>
                                            <option value="<?= admin_e($category) ?>"><?= admin_e($category) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <table id="postsTable" class="display">
                                <thead>
                                    <tr>
                                        <th>Post</th>
                                        <th>Category</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $row):
                                        $isPublished = $row['status'] === 'Published'; ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($row['featured_image']): ?>
                                                        <img class="post-thumb" src="../uploads/posts/<?= admin_e(rawurlencode($row['featured_image'])) ?>" alt="" loading="lazy">
                                                    <?php else: ?>
                                                        <span class="post-thumb is-empty"><i class="bi bi-image"></i></span>
                                                    <?php endif; ?>
                                                    <div class="min-w-0">
                                                        <span class="post-title"><?= admin_e($row['title']) ?></span>
                                                        <span class="post-excerpt"><?= admin_e(post_excerpt($row['content'])) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="ui-tag"><?= admin_e($row['category']) ?></span></td>
                                            <td><?= admin_e($row['author_name'] ?: '—') ?></td>
                                            <td><span class="ui-pill <?= $isPublished ? 'is-ok' : '' ?>"><?= admin_e($row['status']) ?></span></td>
                                            <td data-order="<?= strtotime($row['created_at']) ?>"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                            <td data-order="<?= $row['updated_at'] ? strtotime($row['updated_at']) : 0 ?>"><?= $row['updated_at'] ? date('M j, Y', strtotime($row['updated_at'])) : '—' ?></td>
                                            <td>
                                                <div class="ui-acts">
                                                    <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#viewModal<?= (int) $row['id'] ?>" title="Preview" aria-label="Preview"><i class="bi bi-eye"></i></button>
                                                    <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editModal<?= (int) $row['id'] ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" class="ui-act <?= $isPublished ? 'is-warn' : 'is-ok' ?>" onclick="changeStatus(<?= (int) $row['id'] ?>, '<?= $isPublished ? 'Draft' : 'Published' ?>')"
                                                        title="<?= $isPublished ? 'Unpublish (move to drafts)' : 'Publish now' ?>" aria-label="<?= $isPublished ? 'Unpublish' : 'Publish' ?>">
                                                        <i class="bi <?= $isPublished ? 'bi-eye-slash' : 'bi-broadcast' ?>"></i>
                                                    </button>
                                                    <button type="button" class="ui-act is-danger" onclick="deletePost(<?= (int) $row['id'] ?>, <?= admin_e(json_encode($row['title'])) ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="ui-empty">
                                <i class="bi bi-newspaper"></i>
                                <h3>No posts yet</h3>
                                <p>Write the first announcement or news story for the school website.</p>
                                <button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#addPostModal"><i class="bi bi-plus-lg"></i> New post</button>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- Create post -->
    <div class="modal fade" id="addPostModal" tabindex="-1" aria-labelledby="addPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="processes/admin/posts/add.php" enctype="multipart/form-data" data-editor-form>
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-pencil-square"></i></span>
                    <h1 class="modal-title" id="addPostModalLabel">New post</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="postTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="postTitle" name="title" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="editor" class="form-label">Content</label>
                        <textarea name="content" id="editor" data-editor></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="postCategory" class="form-label">Category</label>
                            <select class="form-select" name="category" id="postCategory" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category ?>"><?= $category ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="postStatus" class="form-label">Visibility</label>
                            <select class="form-select" name="status" id="postStatus">
                                <option value="Draft">Save as draft</option>
                                <option value="Published">Publish immediately</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="postImage" class="form-label">Featured image <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="file" class="form-control" id="postImage" name="featured_image" accept="image/*" data-preview="#postImagePreview">
                        <img id="postImagePreview" class="img-thumbnail mt-2 d-none" style="max-height: 160px" alt="Selected image preview">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create post</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($posts as $row):
        $id = (int) $row['id'];
        $createdAt = date('M j, Y g:i A', strtotime($row['created_at']));
        $updatedAt = $row['updated_at'] ? date('M j, Y g:i A', strtotime($row['updated_at'])) : 'Not updated'; ?>

        <!-- Edit post -->
        <div class="modal fade" id="editModal<?= $id ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="processes/admin/posts/edit.php?id=<?= $id ?>" enctype="multipart/form-data" data-editor-form>
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                        <h1 class="modal-title" id="editModalLabel<?= $id ?>">Edit post</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editTitle<?= $id ?>" class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTitle<?= $id ?>" name="title" maxlength="255" value="<?= admin_e($row['title']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="editor-<?= $id ?>" class="form-label">Content</label>
                            <textarea id="editor-<?= $id ?>" name="content" data-editor><?= admin_e($row['content']) ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCategory<?= $id ?>" class="form-label">Category</label>
                                <select class="form-select" name="category" id="editCategory<?= $id ?>" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category ?>" <?= $row['category'] === $category ? 'selected' : '' ?>><?= $category ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editStatus<?= $id ?>" class="form-label">Visibility</label>
                                <select class="form-select" name="status" id="editStatus<?= $id ?>">
                                    <option value="Draft" <?= $row['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="Published" <?= $row['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label for="editImage<?= $id ?>" class="form-label">Featured image</label>
                            <?php if ($row['featured_image']): ?>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="../uploads/posts/<?= admin_e(rawurlencode($row['featured_image'])) ?>" class="img-thumbnail" style="max-height: 110px" alt="Current featured image">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage<?= $id ?>">
                                        <label class="form-check-label" for="removeImage<?= $id ?>">Remove current image</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="editImage<?= $id ?>" name="featured_image" accept="image/*">
                            <small class="text-muted">Leave blank to keep the current image.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview post -->
        <div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-eye"></i></span>
                        <h1 class="modal-title" id="viewModalLabel<?= $id ?>"><?= admin_e($row['title']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <span class="ui-tag"><?= admin_e($row['category']) ?></span>
                            <span class="ui-pill <?= $row['status'] === 'Published' ? 'is-ok' : '' ?>"><?= admin_e($row['status']) ?></span>
                            <small class="text-muted ms-auto">By <?= admin_e($row['author_name'] ?: 'Unknown') ?> · <?= $createdAt ?></small>
                        </div>
                        <?php if ($row['featured_image']): ?>
                            <img class="post-view-image" src="../uploads/posts/<?= admin_e(rawurlencode($row['featured_image'])) ?>" alt="">
                        <?php endif; ?>
                        <div class="post-view-body"><?= $row['content'] ?></div>
                    </div>
                    <div class="modal-footer">
                        <small class="text-muted me-auto">Last updated: <?= $updatedAt ?></small>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        $(function () {
            const table = $('#postsTable').DataTable({
                order: [[4, 'desc']],
                columnDefs: [{ targets: 6, orderable: false, searchable: false }]
            });

            $('#categoryFilter').on('change', function () {
                table.column(1).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
            });
        });

        // CKEditor is created when its modal first opens, not for every post on page load
        const editors = new Map();
        document.addEventListener('show.bs.modal', function (e) {
            const area = e.target.querySelector('textarea[data-editor]');
            if (!area || editors.has(area) || typeof ClassicEditor === 'undefined') return;
            editors.set(area, null);
            ClassicEditor.create(area, {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo']
            }).then(editor => editors.set(area, editor)).catch(console.error);
        });

        // The editor hides the textarea, so check for content ourselves
        document.querySelectorAll('form[data-editor-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const area = form.querySelector('textarea[data-editor]');
                const editor = editors.get(area);
                const html = editor ? editor.getData() : area.value;
                if (!html.replace(/<[^>]*>|&nbsp;/g, '').trim()) {
                    e.preventDefault();
                    Swal.fire('Content is empty', 'Write something in the post body before saving.', 'warning');
                    return;
                }
                if (editor) area.value = html;
            });
        });

        // Preview the chosen featured image
        document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                const img = document.querySelector(input.dataset.preview);
                if (input.files[0]) {
                    img.src = URL.createObjectURL(input.files[0]);
                    img.classList.remove('d-none');
                } else {
                    img.classList.add('d-none');
                }
            });
        });

        function changeStatus(postId, newStatus) {
            const body = new URLSearchParams({ post_id: postId, new_status: newStatus });
            fetch('processes/admin/posts/change.php', { method: 'POST', body })
                .then(() => location.reload())
                .catch(() => Swal.fire('Error!', 'The post status could not be changed. Please try again.', 'error'));
        }

        function deletePost(id, title) {
            Swal.fire({
                title: 'Delete this post?',
                html: '<b>' + $('<div>').text(title).html() + '</b> and its featured image will be removed permanently.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete post',
                customClass: { popup: 'ui-swal-danger' }
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'processes/admin/posts/delete.php?id=' + id;
            });
        }
    </script>

    <?php
    $postAlerts = [
        'POST_ADDITION_SUCCESS' => ['Post created', 'The post has been saved.', 'success'],
        'POST_DELETION_SUCCESS' => ['Deleted', 'The post has been deleted.', 'success'],
        'POST_DELETION_ERROR' => ['Error', 'The post could not be deleted. Please try again.', 'error'],
        'POST_UPDATED_SUCCESS' => ['Saved', 'The post has been updated.', 'success'],
        'POST_UPDATED_ERROR' => ['Error', 'The post could not be updated. Please try again.', 'error'],
        'POST_SWITCH_STATUS_SUCCESS' => ['Status updated', 'The post visibility has been changed.', 'success'],
        'POST_SWITCH_STATUS_ERROR' => ['Error', 'The post status could not be changed. Please try again.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $postAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $postAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php') ?>
</body>

</html>
