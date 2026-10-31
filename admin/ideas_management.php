<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/../includes/feature-requests.php';

ideas_ensure($pdo);

$rows = ideas_rows($pdo, null, ['status' => 'all', 'sort' => 'top', 'hidden' => true]);
$counts = ideas_status_counts($pdo, true);
$hiddenCount = count(array_filter($rows, fn($r) => $r['is_hidden']));
$votes = array_sum(array_column($rows, 'vote_count'));
$thisWeek = count(array_filter($rows, fn($r) => strtotime($r['created_at']) >= strtotime('-7 days')));
$top = current(array_filter($rows, fn($r) => !$r['is_hidden'] && in_array($r['status'], IDEAS_ACTIVE_STATUSES, true))) ?: null;

$subtitle = $counts['open']
    ? $counts['open'] . ' new idea' . ($counts['open'] === 1 ? '' : 's') . ' waiting for review'
    : 'Suggestions and upvotes from teachers, students and admins';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Ideas &amp; Improvements | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html body .idea-panel { padding: 1rem 1.25rem 1.25rem; }
        html body .idea-votes { font-weight: 800; color: var(--ui-navy); font-size: 1rem; }
        html body .idea-title { display: block; font-weight: 700; color: var(--ui-ink); }
        html body .idea-snippet { display: block; max-width: 46ch; font-size: 0.8rem; color: var(--ui-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        html body .idea-flags { display: flex; gap: 0.3rem; margin-top: 0.2rem; }
        html body .idea-top { display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem 1rem; margin-bottom: 1.25rem; border: 1px solid var(--ui-line); border-left: 4px solid var(--ui-gold); border-radius: 8px; background: var(--ui-surface); font-size: 0.9rem; }
        html body .idea-top i { color: var(--ui-gold); font-size: 1.2rem; }
        html body .idea-original { padding: 0.7rem 0.85rem; border-radius: 8px; background: var(--ui-soft); font-size: 0.85rem; white-space: pre-line; max-height: 140px; overflow: auto; }
        html body .idea-merge { padding: 0.85rem; border: 1px dashed var(--ui-line); border-radius: 8px; }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php'); ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <?php admin_page_header('Ideas & Improvements', $subtitle, 'bi-lightbulb',
                        '<a class="btn btn-outline-secondary" href="../ideas.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View public board</a>'); ?>

                    <?php admin_stat_tiles([
                        ['label' => 'New', 'value' => $counts['open'], 'icon' => 'bi-inbox', 'hint' => $thisWeek . ' posted this week', 'tone' => $counts['open'] ? 'alert' : '', 'filter' => IDEAS_STATUSES['open'][0], 'filter_col' => 'Status'],
                        ['label' => 'Planned', 'value' => $counts['planned'], 'icon' => 'bi-signpost-split', 'hint' => $counts['under_review'] . ' under review', 'filter' => IDEAS_STATUSES['planned'][0], 'filter_col' => 'Status'],
                        ['label' => 'In progress', 'value' => $counts['in_progress'], 'icon' => 'bi-hammer', 'hint' => 'Being built now', 'filter' => IDEAS_STATUSES['in_progress'][0], 'filter_col' => 'Status'],
                        ['label' => 'Done', 'value' => $counts['done'], 'icon' => 'bi-check2-circle', 'hint' => $votes . ' votes in total', 'tone' => 'ok', 'filter' => IDEAS_STATUSES['done'][0], 'filter_col' => 'Status'],
                    ]); ?>

                    <?php if ($top): ?>
                        <div class="idea-top">
                            <i class="bi bi-trophy"></i>
                            <span>Most wanted: <strong><?= admin_e($top['title']) ?></strong> · <?= (int) $top['vote_count'] ?> vote<?= (int) $top['vote_count'] === 1 ? '' : 's' ?> · <?= admin_e(ideas_status($top['status'])[0]) ?></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-edit-idea="<?= (int) $top['id'] ?>">Review</button>
                        </div>
                    <?php endif; ?>

                    <section class="ui-panel idea-panel">
                        <table id="ideasTable" class="display" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Votes</th>
                                    <th>Idea</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>From</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r):
                                    [$statusLabel, , $tone] = ideas_status($r['status']); ?>
                                    <tr>
                                        <td data-order="<?= (int) $r['vote_count'] ?>"><span class="idea-votes"><?= (int) $r['vote_count'] ?></span></td>
                                        <td>
                                            <span class="idea-title"><?= admin_e($r['title']) ?></span>
                                            <?php if (($r['details'] ?? '') !== ''): ?><span class="idea-snippet"><?= admin_e($r['details']) ?></span><?php endif; ?>
                                            <span class="idea-flags">
                                                <?php if ($r['is_hidden']): ?><span class="ui-tag"><i class="bi bi-eye-slash"></i> Hidden</span><?php endif; ?>
                                                <?php if (($r['admin_response'] ?? '') !== ''): ?><span class="ui-tag"><i class="bi bi-chat-left-text"></i> Replied</span><?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?= admin_e((IDEAS_CATEGORIES[$r['category']] ?? IDEAS_CATEGORIES['other'])[0]) ?></td>
                                        <td><span class="ui-pill <?= $tone ?>"><?= admin_e($statusLabel) ?></span></td>
                                        <td><?= admin_e($r['author_name'] ?: '—') ?><br><small class="text-muted"><?= admin_e(IDEAS_ROLES[$r['author_role']] ?? $r['author_role']) ?></small></td>
                                        <td data-order="<?= strtotime($r['created_at']) ?>"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                                        <td>
                                            <button type="button" class="ui-act" data-edit-idea="<?= (int) $r['id'] ?>" title="Review" aria-label="Review <?= admin_e($r['title']) ?>"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="ui-act is-danger" data-delete-idea="<?= (int) $r['id'] ?>" title="Delete" aria-label="Delete <?= admin_e($r['title']) ?>"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- Review idea -->
    <div class="modal fade" id="ideaModal" tabindex="-1" aria-labelledby="ideaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="processes/admin/ideas/save.php">
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-lightbulb"></i></span>
                    <h1 class="modal-title" id="ideaModalLabel">Review idea</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="ideaId">
                    <input type="hidden" name="csrf" value="<?= admin_e(ideas_csrf_token()) ?>">
                    <p class="text-muted small mb-3" id="ideaMeta"></p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="ideaTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="ideaTitle" name="title" required maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label for="ideaCategory" class="form-label">Type</label>
                            <select class="form-select" id="ideaCategory" name="category">
                                <?php foreach (IDEAS_CATEGORIES as $key => [$label]): ?>
                                    <option value="<?= $key ?>"><?= admin_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="ideaDetails" class="form-label">Details</label>
                            <textarea class="form-control" id="ideaDetails" name="details" rows="3" maxlength="3000"></textarea>
                        </div>
                        <div class="col-md-5">
                            <label for="ideaStatus" class="form-label">Status</label>
                            <select class="form-select" id="ideaStatus" name="status">
                                <?php foreach (IDEAS_STATUSES as $key => [$label, $desc]): ?>
                                    <option value="<?= $key ?>"><?= admin_e($label) ?> · <?= admin_e($desc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="ideaHidden" name="is_hidden" value="1">
                                <label class="form-check-label" for="ideaHidden">Hide from the public board (spam, off-topic, personal info)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="ideaResponse" class="form-label">Public reply <span class="text-muted fw-normal">(optional, shown under the idea)</span></label>
                            <textarea class="form-control" id="ideaResponse" name="admin_response" rows="3" maxlength="3000" placeholder="e.g. Planned for the next update. Thanks to everyone who voted!"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="idea-merge">
                                <label for="ideaMergeInto" class="form-label mb-1"><i class="bi bi-intersect"></i> Duplicate? Merge it into another idea</label>
                                <p class="text-muted small mb-2">Its votes move to the idea you pick, and this one is deleted.</p>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="ideaMergeInto">
                                        <option value="">Choose the idea to keep…</option>
                                        <?php foreach ($rows as $r): ?>
                                            <option value="<?= (int) $r['id'] ?>"><?= admin_e(mb_strimwidth($r['title'], 0, 90, '…')) ?> (<?= (int) $r['vote_count'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary text-nowrap" id="ideaMerge">Merge</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <form id="ideaActionForm" method="POST" hidden>
        <input type="hidden" name="csrf" value="<?= admin_e(ideas_csrf_token()) ?>">
        <input type="hidden" name="id" id="ideaActionId">
        <input type="hidden" name="into" id="ideaActionInto">
    </form>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        const ideas = <?= json_encode(array_column(array_map(fn($r) => [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'details' => $r['details'] ?? '',
            'category' => $r['category'],
            'status' => $r['status'],
            'admin_response' => $r['admin_response'] ?? '',
            'is_hidden' => (bool) $r['is_hidden'],
            'meta' => ($r['author_name'] ?: 'Unknown') . ' · ' . (IDEAS_ROLES[$r['author_role']] ?? $r['author_role']) . ' · posted ' . date('M j, Y g:i A', strtotime($r['created_at'])) . ' · ' . (int) $r['vote_count'] . ' vote' . ((int) $r['vote_count'] === 1 ? '' : 's'),
        ], $rows), null, 'id'), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const byId = id => document.getElementById(id);
        const modal = bootstrap.Modal.getOrCreateInstance(byId('ideaModal'));

        $('#ideasTable').DataTable({ order: [[0, 'desc']], columnDefs: [{ targets: -1, orderable: false }] });

        $(document).on('click', '[data-edit-idea]', function () {
            const idea = ideas[this.getAttribute('data-edit-idea')];
            if (!idea) return;
            byId('ideaId').value = idea.id;
            byId('ideaTitle').value = idea.title;
            byId('ideaDetails').value = idea.details;
            byId('ideaCategory').value = idea.category;
            byId('ideaStatus').value = idea.status;
            byId('ideaResponse').value = idea.admin_response;
            byId('ideaHidden').checked = idea.is_hidden;
            byId('ideaMeta').textContent = idea.meta;
            byId('ideaMergeInto').value = '';
            Array.from(byId('ideaMergeInto').options).forEach(o => { o.hidden = o.value === String(idea.id); });
            modal.show();
        });

        function runAction(action, id, into) {
            const form = byId('ideaActionForm');
            form.action = 'processes/admin/ideas/' + action + '.php';
            byId('ideaActionId').value = id;
            byId('ideaActionInto').value = into || '';
            form.submit();
        }

        $(document).on('click', '[data-delete-idea]', function () {
            const idea = ideas[this.getAttribute('data-delete-idea')];
            Swal.fire({
                title: 'Delete this idea?',
                text: '“' + idea.title + '” and its votes will be removed for good. To keep it out of sight instead, hide it.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonColor: '#b42338',
                confirmButtonText: 'Delete'
            }).then(result => { if (result.isConfirmed) runAction('delete', idea.id); });
        });

        byId('ideaMerge').addEventListener('click', function () {
            const from = ideas[byId('ideaId').value];
            const into = ideas[byId('ideaMergeInto').value];
            if (!into) { byId('ideaMergeInto').focus(); return; }
            Swal.fire({
                title: 'Merge these ideas?',
                html: 'Votes from <b></b> move to <b></b>, then the duplicate is deleted.',
                didOpen: () => {
                    const b = Swal.getHtmlContainer().querySelectorAll('b');
                    b[0].textContent = from.title;
                    b[1].textContent = into.title;
                },
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Merge'
            }).then(result => { if (result.isConfirmed) runAction('merge', from.id, into.id); });
        });
    </script>

    <?php
    $ideaAlerts = [
        'ADMIN_IDEA_UPDATE_SUCCESS' => ['Saved', 'The idea has been updated.', 'success'],
        'ADMIN_IDEA_DELETE_SUCCESS' => ['Deleted', 'The idea and its votes have been removed.', 'success'],
        'ADMIN_IDEA_MERGE_SUCCESS' => ['Merged', 'The votes were moved and the duplicate was deleted.', 'success'],
        'ADMIN_IDEA_INVALID' => ['Check the details', 'The title must be 8–150 characters, with a valid type and status.', 'warning'],
        'ADMIN_IDEA_ERROR' => ['Error', 'The change could not be saved. Please try again.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $ideaAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $ideaAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
