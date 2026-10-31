<?php
include('processes/server/alert_system.php');
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/feature-requests.php';

ideas_ensure($pdo);
$viewer = ideas_viewer();

$perPage = 15;
$filters = [
    'status' => isset(IDEAS_STATUSES[$_GET['status'] ?? '']) || ($_GET['status'] ?? '') === 'all' ? $_GET['status'] : 'active',
    'sort' => ($_GET['sort'] ?? '') === 'new' ? 'new' : 'top',
    'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
];
$total = ideas_count($pdo, $filters);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$rows = ideas_rows($pdo, $viewer, $filters, $perPage, ($page - 1) * $perPage);
$counts = ideas_status_counts($pdo);
$activeCount = array_sum(array_intersect_key($counts, array_flip(IDEAS_ACTIVE_STATUSES)));

$flash = $_SESSION['IDEAS_FLASH'] ?? null;
$old = $_SESSION['IDEAS_OLD'] ?? [];
unset($_SESSION['IDEAS_FLASH'], $_SESSION['IDEAS_OLD']);

/** Board URL with some filters changed; page resets unless given. */
function ideas_url(array $filters, array $change): string
{
    $q = array_merge($filters, ['page' => 1], $change);
    $q = array_filter($q, fn($v, $k) => !(($k === 'status' && $v === 'active') || ($k === 'sort' && $v === 'top') || ($k === 'page' && (int) $v === 1) || $v === ''), ARRAY_FILTER_USE_BOTH);
    return 'ideas.php' . ($q ? '?' . http_build_query($q) : '');
}

$tabs = ['active' => ['Open ideas', $activeCount]];
foreach (['planned', 'in_progress', 'done'] as $s) {
    $tabs[$s] = [IDEAS_STATUSES[$s][0], $counts[$s]];
}
$tabs['all'] = ['All', array_sum($counts)];
$returnQuery = http_build_query(array_filter(['status' => $filters['status'], 'sort' => $filters['sort'], 'q' => $filters['q'], 'page' => $page]));

$page_title = 'Ideas & Improvements';
$page_description = 'Suggest improvements to the Student Management System, upvote the ideas you need most and follow what is planned and built.';
$active_page = 'ideas';
include __DIR__ . '/includes/site-header.php';
?>
<style>
    .au-ideas-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 2rem; align-items: start; }
    .au-ideas-card { background: #fff; border: 1px solid var(--au-line); border-radius: 8px; padding: 1.25rem; box-shadow: var(--au-shadow); }
    .au-ideas-card h2 { font-family: var(--au-serif); font-size: 1.35rem; color: var(--au-navy); margin: 0 0 0.35rem; }
    .au-ideas-side { display: grid; gap: 1.5rem; position: sticky; top: 1.5rem; }
    .au-ideas-tabs { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem; }
    .au-ideas-tab { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; border: 1px solid var(--au-line); border-radius: 999px; background: #fff; color: var(--au-ink); font-weight: 600; font-size: 0.9rem; text-decoration: none; }
    .au-ideas-tab:hover { border-color: var(--au-gold); color: var(--au-ink); }
    .au-ideas-tab.active { background: var(--au-navy); border-color: var(--au-navy); color: #fff; }
    .au-ideas-tab small { font-size: 0.75rem; opacity: 0.75; }
    .au-ideas-tools { display: flex; gap: 0.6rem; margin-bottom: 1.25rem; }
    .au-ideas-tools .form-control { flex: 1; }
    .au-ideas-tools .form-select { width: auto; }
    .au-ideas-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.85rem; }
    .au-idea { display: flex; gap: 1rem; padding: 1rem 1.1rem; background: #fff; border: 1px solid var(--au-line); border-radius: 8px; scroll-margin-top: 1.5rem; }
    .au-idea:target { border-color: var(--au-gold); box-shadow: 0 0 0 3px rgba(194, 167, 77, 0.25); }
    .au-vote { flex: none; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 3.4rem; min-height: 3.8rem; padding: 0.35rem 0; border: 1px solid var(--au-line); border-radius: 8px; background: var(--au-paper); color: var(--au-navy); font-weight: 700; line-height: 1.1; }
    .au-vote i { font-size: 1.05rem; }
    .au-vote b { font-size: 1.05rem; }
    button.au-vote:hover { border-color: var(--au-gold); }
    button.au-vote:focus-visible { outline: 0; box-shadow: 0 0 0 3px rgba(194, 167, 77, 0.35); }
    .au-vote.is-voted { background: var(--au-navy); border-color: var(--au-navy); color: #fff; }
    .au-vote[disabled] { opacity: 0.6; cursor: default; }
    .au-idea-body { min-width: 0; flex: 1; }
    .au-idea-title { font-size: 1.05rem; font-weight: 700; color: var(--au-ink); margin: 0 0 0.3rem; overflow-wrap: anywhere; }
    .au-idea-text { color: var(--au-ink); margin: 0 0 0.5rem; white-space: pre-line; overflow-wrap: anywhere; }
    .au-idea-text.is-clamped { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .au-idea-more { padding: 0; border: 0; background: none; color: var(--au-navy-soft); font-size: 0.85rem; font-weight: 600; margin: -0.3rem 0 0.5rem; }
    .au-idea-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem 0.75rem; font-size: 0.82rem; color: var(--au-muted); }
    .au-idea-status { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.1rem 0.55rem; border-radius: 999px; font-weight: 700; font-size: 0.75rem; background: color-mix(in srgb, var(--status) 12%, transparent); color: var(--status); }
    .au-idea-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .au-idea-reply { margin-top: 0.75rem; padding: 0.7rem 0.85rem; border-left: 3px solid var(--au-gold); border-radius: 0 6px 6px 0; background: var(--au-paper); font-size: 0.92rem; }
    .au-idea-reply strong { display: block; font-size: 0.78rem; letter-spacing: 0.04em; text-transform: uppercase; color: var(--au-navy); margin-bottom: 0.2rem; }
    .au-idea-reply p { margin: 0; white-space: pre-line; overflow-wrap: anywhere; }
    .au-ideas-empty { text-align: center; padding: 2.5rem 1rem; color: var(--au-muted); }
    .au-ideas-empty i { display: block; font-size: 2rem; color: var(--au-gold); margin-bottom: 0.5rem; }
    .au-ideas-steps { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.55rem; font-size: 0.9rem; }
    .au-ideas-steps li { display: flex; gap: 0.6rem; align-items: baseline; }
    .au-ideas-steps .au-idea-status { flex: none; min-width: 6.5rem; }
    .au-ideas-steps span:last-child { color: var(--au-muted); }
    .au-ideas-pages { display: flex; justify-content: center; gap: 0.4rem; margin-top: 1.25rem; }
    .au-ideas-hint { font-size: 0.85rem; color: var(--au-muted); }
    @media (max-width: 991px) {
        .au-ideas-layout { grid-template-columns: 1fr; }
        .au-ideas-side { position: static; order: -1; }
    }
    @media (max-width: 575px) {
        .au-ideas-tools { flex-wrap: wrap; }
        .au-ideas-tools .form-select { width: 100%; }
        .au-idea { gap: 0.75rem; padding: 0.85rem; }
        .au-vote { width: 3rem; }
    }
</style>

<section class="au-page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb au-breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="help.php">Help Center</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ideas &amp; Improvements</li>
            </ol>
        </nav>
        <span class="au-eyebrow">Shape the system</span>
        <h1>Ideas &amp; Improvements</h1>
        <p>Tell us what the Student Management System needs. Upvote the ideas that matter to you, and follow them from review to release.</p>
    </div>
</section>

<section class="au-section" id="ideas">
    <div class="container">
        <div class="au-ideas-layout">
            <div>
                <nav class="au-ideas-tabs" aria-label="Filter by status">
                    <?php foreach ($tabs as $key => [$label, $n]): ?>
                        <a class="au-ideas-tab<?= $filters['status'] === $key ? ' active' : '' ?>" href="<?= au_e(ideas_url($filters, ['status' => $key])) ?>#ideas"
                            <?= $filters['status'] === $key ? 'aria-current="page"' : '' ?>><?= au_e($label) ?> <small><?= $n ?></small></a>
                    <?php endforeach; ?>
                </nav>

                <form class="au-ideas-tools" method="GET" action="ideas.php#ideas" role="search">
                    <?php if ($filters['status'] !== 'active'): ?><input type="hidden" name="status" value="<?= au_e($filters['status']) ?>"><?php endif; ?>
                    <input type="search" class="form-control" name="q" value="<?= au_e($filters['q']) ?>" placeholder="Search ideas before posting a new one" aria-label="Search ideas">
                    <select class="form-select" name="sort" aria-label="Sort" onchange="this.form.submit()">
                        <option value="top" <?= $filters['sort'] === 'top' ? 'selected' : '' ?>>Most votes</option>
                        <option value="new" <?= $filters['sort'] === 'new' ? 'selected' : '' ?>>Newest</option>
                    </select>
                    <button type="submit" class="btn btn-au-outline"><i class="bi bi-search"></i><span class="visually-hidden">Search</span></button>
                </form>

                <?php if ($rows): ?>
                    <ol class="au-ideas-list">
                        <?php foreach ($rows as $r):
                            [$statusLabel, , , $statusColor] = ideas_status($r['status']);
                            [$catLabel, $catIcon] = IDEAS_CATEGORIES[$r['category']] ?? IDEAS_CATEGORIES['other'];
                            $closed = in_array($r['status'], ['done', 'declined'], true); ?>
                            <li class="au-idea" id="idea-<?= (int) $r['id'] ?>">
                                <?php if ($viewer && !$closed): ?>
                                    <form method="POST" action="processes/ideas/vote.php" data-vote-form>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= au_e(ideas_csrf_token()) ?>">
                                        <input type="hidden" name="return" value="<?= au_e($returnQuery) ?>">
                                        <button type="submit" class="au-vote<?= $r['has_voted'] ? ' is-voted' : '' ?>" aria-pressed="<?= $r['has_voted'] ? 'true' : 'false' ?>"
                                            aria-label="<?= $r['has_voted'] ? 'Remove your vote' : 'Upvote' ?>: <?= au_e($r['title']) ?>">
                                            <i class="bi bi-caret-up-fill" aria-hidden="true"></i><b><?= (int) $r['vote_count'] ?></b>
                                        </button>
                                    </form>
                                <?php elseif (!$viewer && !$closed): ?>
                                    <a class="au-vote text-decoration-none" href="login/index.php" title="Log in to vote">
                                        <i class="bi bi-caret-up-fill" aria-hidden="true"></i><b><?= (int) $r['vote_count'] ?></b>
                                        <span class="visually-hidden">votes. Log in to vote.</span>
                                    </a>
                                <?php else: ?>
                                    <span class="au-vote" title="Voting closed">
                                        <i class="bi bi-<?= $r['status'] === 'done' ? 'check-lg' : 'dash-lg' ?>" aria-hidden="true"></i><b><?= (int) $r['vote_count'] ?></b>
                                        <span class="visually-hidden">votes</span>
                                    </span>
                                <?php endif; ?>

                                <div class="au-idea-body">
                                    <h3 class="au-idea-title"><?= au_e($r['title']) ?></h3>
                                    <?php if (($r['details'] ?? '') !== ''): ?>
                                        <p class="au-idea-text<?= mb_strlen($r['details']) > 240 || substr_count($r['details'], "\n") > 2 ? ' is-clamped' : '' ?>"><?= au_e($r['details']) ?></p>
                                    <?php endif; ?>
                                    <div class="au-idea-meta">
                                        <span class="au-idea-status" style="--status: <?= $statusColor ?>"><?= au_e($statusLabel) ?></span>
                                        <span><i class="bi <?= $catIcon ?> me-1"></i><?= au_e($catLabel) ?></span>
                                        <span><?= au_e(ideas_display_name($r)) ?> · <?= au_e(IDEAS_ROLES[$r['author_role']] ?? '') ?></span>
                                        <span><time datetime="<?= date('c', strtotime($r['created_at'])) ?>"><?= date('M j, Y', strtotime($r['created_at'])) ?></time></span>
                                    </div>
                                    <?php if (($r['admin_response'] ?? '') !== ''): ?>
                                        <div class="au-idea-reply">
                                            <strong><i class="bi bi-patch-check-fill me-1"></i>Update from the team<?= $r['responded_at'] ? ' · ' . date('M j, Y', strtotime($r['responded_at'])) : '' ?></strong>
                                            <p><?= au_e($r['admin_response']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <?php if ($pages > 1): ?>
                        <nav class="au-ideas-pages" aria-label="Pages">
                            <?php if ($page > 1): ?><a class="btn btn-sm btn-au-outline" href="<?= au_e(ideas_url($filters, ['page' => $page - 1])) ?>#ideas">‹ Previous</a><?php endif; ?>
                            <span class="btn btn-sm disabled">Page <?= $page ?> of <?= $pages ?></span>
                            <?php if ($page < $pages): ?><a class="btn btn-sm btn-au-outline" href="<?= au_e(ideas_url($filters, ['page' => $page + 1])) ?>#ideas">Next ›</a><?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="au-ideas-card au-ideas-empty">
                        <i class="bi bi-lightbulb"></i>
                        <?php if ($filters['q'] !== ''): ?>
                            No ideas match “<?= au_e($filters['q']) ?>”. <a href="#suggest">Be the first to suggest it.</a>
                        <?php elseif (array_sum($counts) === 0): ?>
                            No ideas yet. <a href="#suggest">Share the first one.</a>
                        <?php else: ?>
                            Nothing here yet. <a href="<?= au_e(ideas_url($filters, ['status' => 'all'])) ?>#ideas">See all ideas</a>.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="au-ideas-side">
                <div class="au-ideas-card" id="suggest">
                    <h2>Suggest an improvement</h2>
                    <p class="au-ideas-hint">A missing feature, something that could work better, or a problem you keep running into.</p>

                    <?php if ($flash): ?>
                        <div class="alert alert-<?= au_e($flash[0]) ?> py-2" role="alert"><?= au_e($flash[1]) ?></div>
                    <?php endif; ?>

                    <?php if ($viewer): ?>
                        <form method="POST" action="processes/ideas/submit.php">
                            <input type="hidden" name="csrf" value="<?= au_e(ideas_csrf_token()) ?>">
                            <div class="mb-3">
                                <label for="ideaTitle" class="form-label fw-semibold">Your idea</label>
                                <input type="text" class="form-control" id="ideaTitle" name="title" required minlength="8" maxlength="150"
                                    value="<?= au_e($old['title'] ?? '') ?>" placeholder="e.g. Let parents see their child's attendance">
                            </div>
                            <div class="mb-3">
                                <label for="ideaCategory" class="form-label fw-semibold">Type</label>
                                <select class="form-select" id="ideaCategory" name="category" required>
                                    <?php foreach (IDEAS_CATEGORIES as $key => [$label]): ?>
                                        <option value="<?= $key ?>" <?= ($old['category'] ?? 'feature') === $key ? 'selected' : '' ?>><?= au_e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="ideaDetails" class="form-label fw-semibold">Details <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea class="form-control" id="ideaDetails" name="details" rows="4" maxlength="3000"
                                    placeholder="What would it help you do? Who else needs it?"><?= au_e($old['details'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-au-gold w-100"><i class="bi bi-send me-1"></i> Post idea</button>
                            <p class="au-ideas-hint mt-2 mb-0">Posting as <?= au_e(ideas_display_name(['author_name' => $viewer['name'], 'author_role' => $viewer['role']])) ?> (<?= au_e(IDEAS_ROLES[$viewer['role']]) ?>). Only your first name and last initial are shown.</p>
                        </form>
                    <?php else: ?>
                        <p class="mb-3">Log in with your student or faculty account to post ideas and vote.</p>
                        <a href="login/index.php" class="btn btn-au-gold w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Log in to take part</a>
                    <?php endif; ?>
                </div>

                <div class="au-ideas-card">
                    <h2>How it works</h2>
                    <ul class="au-ideas-steps">
                        <?php foreach (IDEAS_STATUSES as [$label, $desc, , $color]): ?>
                            <li><span class="au-idea-status" style="--status: <?= $color ?>"><?= au_e($label) ?></span><span><?= au_e($desc) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="au-ideas-hint mt-3 mb-0">Ideas with the most votes are reviewed first. Need help right away? See the <a href="help.php">Help Center</a>.</p>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php
$page_scripts = <<<'JS'
<script>
(function () {
    // Vote without reloading; the form still works without JavaScript
    document.querySelectorAll('[data-vote-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button');
            if (btn.disabled) return;
            btn.disabled = true;
            var data = new FormData(form);
            data.append('ajax', '1');
            fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res.success) {
                        if (res.login) { window.location.href = 'login/index.php'; return; }
                        alert(res.message);
                        return;
                    }
                    btn.classList.toggle('is-voted', res.voted);
                    btn.setAttribute('aria-pressed', res.voted ? 'true' : 'false');
                    btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(/^[^:]+/, res.voted ? 'Remove your vote' : 'Upvote'));
                    btn.querySelector('b').textContent = res.count;
                })
                .catch(function () { form.submit(); })
                .finally(function () { btn.disabled = false; });
        });
    });

    // "Show more" for long descriptions
    document.querySelectorAll('.au-idea-text.is-clamped').forEach(function (p) {
        if (p.scrollHeight <= p.clientHeight + 2) { p.classList.remove('is-clamped'); return; }
        var more = document.createElement('button');
        more.type = 'button';
        more.className = 'au-idea-more';
        more.textContent = 'Show more';
        more.addEventListener('click', function () {
            var open = p.classList.toggle('is-clamped');
            more.textContent = open ? 'Show more' : 'Show less';
        });
        p.after(more);
    });
})();
</script>
JS;
include __DIR__ . '/includes/site-footer.php';
