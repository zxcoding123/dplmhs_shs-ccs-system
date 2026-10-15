<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['STATUS'] = "ADMIN_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include('processes/server/conn.php');
require_once __DIR__ . '/../includes/gallery-store.php';

au_gallery_ensure_table($pdo);
$photos = $pdo->query("SELECT g.*, CONCAT(a.first_name, ' ', a.last_name) AS uploader
                       FROM gallery_images g
                       LEFT JOIN admin a ON g.uploaded_by = a.id
                       ORDER BY g.created_at DESC, g.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$albums = au_gallery_albums($pdo);
$published_count = count(array_filter($photos, function ($p) { return $p['status'] === 'Published'; }));

function g_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AU - CCS | Gallery Management</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .btn-csms {
            background-color: #10177a;
            color: white;
        }

        .btn-csms:hover {
            border: 1px solid #10177a;
            color: #10177a;
        }

        .gallery-stat {
            border-left: 4px solid #c2a74d;
            background: #fff;
            padding: 0.9rem 1.1rem;
            border-radius: 0.375rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .gallery-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #061E4E;
            line-height: 1;
        }

        .gallery-stat .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
        }

        .photo-card {
            position: relative;
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fff;
            transition: box-shadow 0.2s ease;
        }

        .photo-card:hover {
            box-shadow: 0 6px 18px rgba(6, 30, 78, 0.12);
        }

        .photo-card.is-selected {
            outline: 3px solid #c2a74d;
        }

        .photo-thumb {
            position: relative;
            aspect-ratio: 4 / 3;
            background: #061E4E;
            cursor: zoom-in;
        }

        .photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-card.is-hidden .photo-thumb img {
            opacity: 0.45;
        }

        .photo-thumb .badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }

        .photo-select {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
            z-index: 2;
        }

        .photo-body {
            padding: 0.75rem 0.85rem;
        }

        .photo-body h6 {
            margin: 0 0 0.15rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .photo-body small {
            color: #6c757d;
        }

        .photo-actions {
            display: flex;
            gap: 0.35rem;
            padding: 0 0.85rem 0.85rem;
        }

        .photo-actions .btn {
            flex: 1;
            font-size: 0.8rem;
            padding: 0.3rem 0.4rem;
        }

        .dropzone {
            border: 2px dashed #c2a74d;
            border-radius: 0.5rem;
            padding: 2rem 1rem;
            text-align: center;
            background: #faf8f3;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .dropzone.dragover {
            background: #f1ead2;
        }

        .dropzone i {
            font-size: 2rem;
            color: #c2a74d;
        }

        .upload-previews {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 0.5rem;
        }

        .upload-previews img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 0.375rem;
        }

        .empty-gallery {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-gallery i {
            font-size: 3rem;
            color: #c2a74d;
        }

        #previewModal img {
            max-height: 75vh;
            width: 100%;
            object-fit: contain;
        }
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
                <img src="external/img/AU_Logo.png" class="logo-small">
                <span class="text-white"><b>AU</b> - Gallery Management</span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center">
                                <h5 class="h5 mb-3"><a href="index.php" class="nav-ham-link">Home</a> /
                                    <span>Gallery Management</span>
                                </h5>
                                <div class="ms-auto" aria-hidden="true">
                                    <img src="external/svgs/undraw_favorite_gb6n.svg" class="small-picture img-fluid">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                <h3 class="mb-0">Photo Gallery</h3>
                                <div class="ms-auto d-flex gap-2">
                                    <a href="../gallery.php" target="_blank" class="btn btn-outline-secondary">
                                        <i class="bi bi-box-arrow-up-right"></i> View Public Gallery
                                    </a>
                                    <button type="button" class="btn btn-csms" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        <i class="bi bi-cloud-arrow-up"></i> Upload Photos
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-6 col-md-3">
                                    <div class="gallery-stat"><div class="value"><?= count($photos) ?></div><div class="label">Total Photos</div></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="gallery-stat"><div class="value"><?= $published_count ?></div><div class="label">Published</div></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="gallery-stat"><div class="value"><?= count($photos) - $published_count ?></div><div class="label">Hidden</div></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="gallery-stat"><div class="value"><?= count($albums) ?></div><div class="label">Albums</div></div>
                                </div>
                            </div>

                            <?php if (!$photos): ?>
                                <div class="empty-gallery">
                                    <i class="bi bi-images"></i>
                                    <h4 class="mt-3">No photos yet</h4>
                                    <p>Upload photos to show them in the public gallery and on the homepage.</p>
                                    <button type="button" class="btn btn-csms" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        <i class="bi bi-cloud-arrow-up"></i> Upload Photos
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="row g-2 align-items-center mb-3">
                                    <div class="col-md-4">
                                        <input type="search" id="filterSearch" class="form-control" placeholder="Search by title or caption…">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <select id="filterAlbum" class="form-select">
                                            <option value="">All albums</option>
                                            <?php foreach ($albums as $album): ?>
                                                <option value="<?= g_e($album) ?>"><?= g_e($album) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <select id="filterStatus" class="form-select">
                                            <option value="">Any status</option>
                                            <option value="Published">Published</option>
                                            <option value="Hidden">Hidden</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger" disabled>
                                            <i class="bi bi-trash"></i> Delete selected (<span id="selectedCount">0</span>)
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3" id="photoGrid">
                                    <?php foreach ($photos as $photo): ?>
                                        <?php
                                        $src = '../' . AU_GALLERY_URL . rawurlencode($photo['file_name']);
                                        $is_published = $photo['status'] === 'Published';
                                        ?>
                                        <div class="col-6 col-md-4 col-xl-3 photo-col"
                                            data-album="<?= g_e($photo['album']) ?>"
                                            data-status="<?= g_e($photo['status']) ?>"
                                            data-search="<?= g_e(mb_strtolower($photo['title'] . ' ' . $photo['caption'])) ?>">
                                            <div class="photo-card<?= $is_published ? '' : ' is-hidden' ?>">
                                                <div class="photo-thumb" data-preview="<?= g_e($src) ?>" data-title="<?= g_e($photo['title']) ?>">
                                                    <input type="checkbox" class="form-check-input photo-select" value="<?= (int) $photo['id'] ?>" aria-label="Select <?= g_e($photo['title']) ?>">
                                                    <img src="<?= g_e($src) ?>" alt="<?= g_e($photo['title']) ?>" loading="lazy">
                                                    <span class="badge <?= $is_published ? 'bg-success' : 'bg-secondary' ?>"><?= g_e($photo['status']) ?></span>
                                                </div>
                                                <div class="photo-body">
                                                    <h6 title="<?= g_e($photo['title']) ?>"><?= g_e($photo['title']) ?></h6>
                                                    <small><i class="bi bi-folder2"></i> <?= g_e($photo['album']) ?></small><br>
                                                    <small><i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($photo['created_at'])) ?><?= $photo['uploader'] ? ' · ' . g_e($photo['uploader']) : '' ?></small>
                                                </div>
                                                <div class="photo-actions">
                                                    <button type="button" class="btn btn-info btn-edit"
                                                        data-id="<?= (int) $photo['id'] ?>"
                                                        data-title="<?= g_e($photo['title']) ?>"
                                                        data-album="<?= g_e($photo['album']) ?>"
                                                        data-caption="<?= g_e($photo['caption']) ?>"
                                                        data-status="<?= g_e($photo['status']) ?>"
                                                        data-src="<?= g_e($src) ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <form method="POST" action="processes/admin/gallery/status.php" class="d-flex flex-fill">
                                                        <input type="hidden" name="id" value="<?= (int) $photo['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= $is_published ? 'Hidden' : 'Published' ?>">
                                                        <button type="submit" class="btn <?= $is_published ? 'btn-warning' : 'btn-success' ?> w-100" title="<?= $is_published ? 'Hide from public gallery' : 'Show in public gallery' ?>">
                                                            <i class="bi <?= $is_published ? 'bi-eye-slash' : 'bi-eye' ?>"></i> <?= $is_published ? 'Hide' : 'Publish' ?>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-delete" data-id="<?= (int) $photo['id'] ?>" data-title="<?= g_e($photo['title']) ?>" aria-label="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-center text-muted mt-4 d-none" id="noMatches">No photos match these filters.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Album suggestions shared by upload and edit forms -->
    <datalist id="albumList">
        <?php foreach (array_unique(array_merge([AU_GALLERY_DEFAULT_ALBUM], $albums)) as $album): ?>
            <option value="<?= g_e($album) ?>">
        <?php endforeach; ?>
    </datalist>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="uploadModalLabel"><b>Upload Photos</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="processes/admin/gallery/add.php" enctype="multipart/form-data" id="uploadForm">
                    <div class="modal-body">
                        <label class="dropzone d-block mb-3" id="dropzone" for="photoInput">
                            <i class="bi bi-cloud-arrow-up"></i>
                            <p class="mb-1 fw-semibold">Drag photos here or click to browse</p>
                            <small class="text-muted">JPG, PNG, GIF or WebP · up to 8 MB each · several at once</small>
                        </label>
                        <input type="file" id="photoInput" name="photos[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple required class="visually-hidden">
                        <div class="upload-previews mb-3" id="uploadPreviews"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="uploadAlbum" class="form-label bold">Album</label>
                                <input type="text" class="form-control" id="uploadAlbum" name="album" list="albumList" maxlength="100" placeholder="<?= AU_GALLERY_DEFAULT_ALBUM ?>">
                                <small class="text-muted">Pick an existing album or type a new name.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="uploadStatus" class="form-label bold">Visibility</label>
                                <select class="form-select" id="uploadStatus" name="status">
                                    <option value="Published">Publish immediately</option>
                                    <option value="Hidden">Keep hidden for now</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="uploadTitle" class="form-label bold">Title (optional)</label>
                            <input type="text" class="form-control" id="uploadTitle" name="title" maxlength="255" placeholder="Leave blank to use each file name">
                        </div>
                        <div class="mb-1">
                            <label for="uploadCaption" class="form-label bold">Caption (optional)</label>
                            <textarea class="form-control" id="uploadCaption" name="caption" rows="2" placeholder="Shown under the photo in the public gallery"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="uploadSubmit"><i class="bi bi-upload"></i> Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="editModalLabel"><b>Edit Photo</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="processes/admin/gallery/edit.php">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <img src="" id="editPreview" class="img-fluid rounded" alt="">
                            </div>
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label for="editTitle" class="form-label bold">Title</label>
                                    <input type="text" class="form-control" id="editTitle" name="title" maxlength="255" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editAlbum" class="form-label bold">Album</label>
                                    <input type="text" class="form-control" id="editAlbum" name="album" list="albumList" maxlength="100">
                                </div>
                                <div class="mb-3">
                                    <label for="editStatus" class="form-label bold">Visibility</label>
                                    <select class="form-select" id="editStatus" name="status">
                                        <option value="Published">Published</option>
                                        <option value="Hidden">Hidden</option>
                                    </select>
                                </div>
                                <div class="mb-1">
                                    <label for="editCaption" class="form-label bold">Caption</label>
                                    <textarea class="form-control" id="editCaption" name="caption" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="previewTitle"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-dark"><img src="" id="previewImage" alt=""></div>
            </div>
        </div>
    </div>

    <!-- Hidden form used by single and bulk delete -->
    <form method="POST" action="processes/admin/gallery/delete.php" id="deleteForm" class="d-none"></form>

    <script src="js/app.js"></script>
    <script>
        function getTime() {
            const el = document.querySelector("#currentTime");
            if (el) el.textContent = "The current date and time is: " + new Date().toLocaleString();
        }
        setInterval(getTime, 1000);

        // ----- Upload: drag & drop + previews -----
        (function () {
            const input = document.getElementById('photoInput');
            const zone = document.getElementById('dropzone');
            const previews = document.getElementById('uploadPreviews');

            function renderPreviews() {
                previews.innerHTML = '';
                Array.from(input.files).forEach(function (file) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.alt = file.name;
                    img.title = file.name;
                    previews.appendChild(img);
                });
                zone.querySelector('p').textContent = input.files.length
                    ? input.files.length + ' photo' + (input.files.length === 1 ? '' : 's') + ' selected — click to change'
                    : 'Drag photos here or click to browse';
            }

            input.addEventListener('change', renderPreviews);
            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('dragover'); });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('dragover'); });
            });
            zone.addEventListener('drop', function (e) {
                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    renderPreviews();
                }
            });

            document.getElementById('uploadForm').addEventListener('submit', function (e) {
                if (!input.files.length) {
                    e.preventDefault();
                    Swal.fire('No photos selected', 'Choose at least one photo to upload.', 'warning');
                    return;
                }
                const btn = document.getElementById('uploadSubmit');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading…';
            });
        })();

        // ----- Edit / preview / delete -----
        document.querySelectorAll('.btn-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('editId').value = btn.dataset.id;
                document.getElementById('editTitle').value = btn.dataset.title;
                document.getElementById('editAlbum').value = btn.dataset.album;
                document.getElementById('editCaption').value = btn.dataset.caption;
                document.getElementById('editStatus').value = btn.dataset.status;
                document.getElementById('editPreview').src = btn.dataset.src;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
            });
        });

        document.querySelectorAll('.photo-thumb').forEach(function (thumb) {
            thumb.addEventListener('click', function (e) {
                if (e.target.classList.contains('photo-select')) return;
                document.getElementById('previewImage').src = thumb.dataset.preview;
                document.getElementById('previewTitle').textContent = thumb.dataset.title;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();
            });
        });

        function submitDelete(ids) {
            const form = document.getElementById('deleteForm');
            form.innerHTML = '';
            ids.forEach(function (id) {
                const field = document.createElement('input');
                field.type = 'hidden';
                field.name = 'ids[]';
                field.value = id;
                form.appendChild(field);
            });
            form.submit();
        }

        function confirmDelete(ids, label) {
            Swal.fire({
                title: 'Delete ' + label + '?',
                text: 'The photo file will be removed permanently. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete'
            }).then(function (result) {
                if (result.isConfirmed) submitDelete(ids);
            });
        }

        document.querySelectorAll('.btn-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                confirmDelete([btn.dataset.id], '"' + btn.dataset.title + '"');
            });
        });

        // ----- Selection & bulk delete -----
        const selects = Array.from(document.querySelectorAll('.photo-select'));
        const bulkBtn = document.getElementById('bulkDeleteBtn');

        function updateSelection() {
            const chosen = selects.filter(function (c) { return c.checked; });
            selects.forEach(function (c) { c.closest('.photo-card').classList.toggle('is-selected', c.checked); });
            if (bulkBtn) {
                bulkBtn.disabled = chosen.length === 0;
                document.getElementById('selectedCount').textContent = chosen.length;
            }
            return chosen;
        }

        selects.forEach(function (c) { c.addEventListener('change', updateSelection); });
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                const chosen = updateSelection();
                confirmDelete(chosen.map(function (c) { return c.value; }), chosen.length + ' photo' + (chosen.length === 1 ? '' : 's'));
            });
        }

        // ----- Filters -----
        (function () {
            const search = document.getElementById('filterSearch');
            if (!search) return;
            const album = document.getElementById('filterAlbum');
            const status = document.getElementById('filterStatus');
            const cols = Array.from(document.querySelectorAll('.photo-col'));

            function apply() {
                const q = search.value.trim().toLowerCase();
                let shown = 0;
                cols.forEach(function (col) {
                    const visible = (!album.value || col.dataset.album === album.value)
                        && (!status.value || col.dataset.status === status.value)
                        && (!q || col.dataset.search.indexOf(q) !== -1);
                    col.classList.toggle('d-none', !visible);
                    if (!visible) {
                        const box = col.querySelector('.photo-select');
                        box.checked = false;
                    }
                    if (visible) shown++;
                });
                updateSelection();
                document.getElementById('noMatches').classList.toggle('d-none', shown > 0);
            }

            [search, album, status].forEach(function (el) { el.addEventListener('input', apply); });
        })();
    </script>

    <!-- Floating Chat Widget -->
    <?php include(__DIR__ . '/../includes/floating-chat-widget.php'); ?>

    <?php
    $gallery_alerts = [
        'GALLERY_UPLOAD_SUCCESS' => ['Uploaded!', 'success'],
        'GALLERY_UPLOAD_ERROR' => ['Upload failed', 'error'],
        'GALLERY_UPDATE_SUCCESS' => ['Saved!', 'success'],
        'GALLERY_UPDATE_ERROR' => ['Error!', 'error'],
        'GALLERY_STATUS_SUCCESS' => ['Visibility updated', 'success'],
        'GALLERY_DELETE_SUCCESS' => ['Deleted!', 'success'],
        'GALLERY_DELETE_ERROR' => ['Error!', 'error'],
    ];
    $gallery_defaults = [
        'GALLERY_UPDATE_SUCCESS' => 'The photo details have been updated.',
        'GALLERY_UPDATE_ERROR' => 'The photo could not be updated. Please try again.',
        'GALLERY_DELETE_ERROR' => 'The photo could not be deleted. Please try again.',
    ];
    if (isset($_SESSION['STATUS'], $gallery_alerts[$_SESSION['STATUS']])) {
        [$alert_title, $alert_icon] = $gallery_alerts[$_SESSION['STATUS']];
        $alert_text = $_SESSION['GALLERY_MESSAGE'] ?? ($gallery_defaults[$_SESSION['STATUS']] ?? '');
        echo '<script>document.addEventListener("DOMContentLoaded", function () { Swal.fire('
            . json_encode($alert_title) . ', ' . json_encode($alert_text) . ', ' . json_encode($alert_icon) . '); });</script>';
        unset($_SESSION['STATUS'], $_SESSION['GALLERY_MESSAGE']);
    }
    ?>
</body>

</html>
<?php include('processes/server/alerts.php') ?>
