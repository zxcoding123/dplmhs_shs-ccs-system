<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/../includes/academic.php';

$strands = shs_strands();
$grades = shs_grades();
$sections = shs_section_rows();

// Who advises each section, and how many students / classes / subjects use it
$advisers = [];
foreach ($pdo->query("SELECT class_advising, fullName FROM staff_advising ORDER BY fullName") as $row) {
    $advisers[$row['class_advising']][] = $row['fullName'];
}
$studentCounts = [];
foreach ($pdo->query("SELECT st.class_advising, COUNT(DISTINCT sa.student_id) AS total
    FROM students_advising sa JOIN staff_advising st ON st.id = sa.staff_advising_id
    GROUP BY st.class_advising") as $row) {
    $studentCounts[$row['class_advising']] = (int) $row['total'];
}
$classCounts = [];
foreach ($pdo->query("SELECT name, COUNT(*) AS total FROM classes WHERE is_archived = 0 GROUP BY name") as $row) {
    $classCounts[$row['name']] = (int) $row['total'];
}
$subjectCounts = [];
foreach ($pdo->query("SELECT course, year_level, COUNT(*) AS total FROM subjects GROUP BY course, year_level") as $row) {
    $subjectCounts[$row['course'] . '-' . $row['year_level']] = (int) $row['total'];
}

// strand => grade => [sections]
$grid = [];
foreach ($sections as $s) {
    $grid[$s['strand']][$s['grade']][] = $s;
}

$withoutAdviser = count(array_filter($sections, fn($s) => empty($advisers[$s['code']])));

function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

$subtitle = $sections
    ? plural(count($sections), 'section') . ' across ' . plural(count($grid), 'strand')
        . ($withoutAdviser ? ' · ' . $withoutAdviser . ' still need an adviser' : ' · every section has an adviser')
    : 'Set up the Grade 11 and 12 sections for each strand';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sections | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Toolbar */
        html body .sec-toolbar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1rem; }
        html body .sec-search { position: relative; flex: 1 1 260px; max-width: 360px; }
        html body .sec-search > i { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--ui-muted); pointer-events: none; }
        html body .sec-search .form-control { padding-left: 2.2rem; }
        html body .sec-toggle { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.42rem 0.8rem; border: 1px solid var(--ui-line); border-radius: 999px; background: var(--ui-surface); color: var(--ui-ink); font-size: 0.85rem; font-weight: 600; cursor: pointer; }
        html body .sec-toggle:hover { border-color: var(--ui-gold); }
        html body .sec-toggle[aria-pressed="true"] { background: var(--ui-navy); border-color: var(--ui-navy); color: #fff; }
        html body .sec-toggle .sec-count { padding: 0 0.45rem; border-radius: 999px; background: var(--ui-soft); color: var(--ui-ink); font-size: 0.75rem; }
        html body .sec-toggle[aria-pressed="true"] .sec-count { background: rgba(255, 255, 255, 0.18); color: #fff; }

        /* One panel per strand, grades side by side */
        html body .sec-strand { padding: 0; overflow: hidden; }
        html body .sec-strand-head { display: flex; align-items: baseline; gap: 0.75rem; padding: 0.9rem 1.25rem; border-bottom: 1px solid var(--ui-line); }
        html body .sec-strand-head h2 { margin: 0; font-size: 1.05rem; font-weight: 800; letter-spacing: 0.02em; color: var(--ui-navy); }
        html body .sec-strand-head p { margin: 0; font-size: 0.85rem; color: var(--ui-muted); }
        html body .sec-grades { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        html body .sec-grade { padding: 0.9rem 1.25rem 1rem; }
        html body .sec-grade + .sec-grade { border-left: 1px solid var(--ui-line); }
        html body .sec-grade h3 { margin: 0 0 0.5rem; font-family: var(--au-sans, 'Source Sans 3', 'Segoe UI', sans-serif); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--ui-muted); }

        /* Section rows */
        html body .sec-list { margin: 0; padding: 0; list-style: none; }
        html body .sec-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.6rem 0.25rem; border-bottom: 1px solid #f0ede4; }
        html body .sec-item:last-child { border-bottom: 0; }
        html body .sec-mark { flex: none; display: grid; place-items: center; min-width: 2.25rem; height: 2.25rem; padding: 0 0.4rem; border-radius: 8px; background: var(--ui-soft); color: var(--ui-navy); font-weight: 800; font-size: 0.95rem; }
        html body .sec-body { flex: 1; min-width: 0; }
        html body .sec-code { font-weight: 700; color: var(--ui-ink); }
        html body .sec-room { margin-left: 0.4rem; font-size: 0.8rem; color: var(--ui-muted); }
        html body .sec-meta { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.83rem; color: var(--ui-muted); }
        html body .sec-meta.is-missing { color: var(--ui-amber); }
        html body .sec-item .ui-acts { opacity: 0.55; transition: opacity 0.15s; }
        html body .sec-item:hover .ui-acts,
        html body .sec-item:focus-within .ui-acts { opacity: 1; }
        html body .sec-item .ui-act { border-color: transparent; background: transparent; }
        html body .sec-empty { margin: 0.25rem 0 0.5rem; font-size: 0.85rem; color: var(--ui-muted); }
        html body .sec-add { display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.5rem; padding: 0.3rem 0.5rem; border: 0; border-radius: 6px; background: none; color: var(--ui-blue); font-size: 0.85rem; font-weight: 600; }
        html body .sec-add:hover { background: rgba(47, 91, 183, 0.08); }
        html body .sec-no-results { display: none; }
        html body .sec-preview { font-weight: 700; color: var(--ui-navy); }

        @media (max-width: 700px) {
            html body .sec-grade + .sec-grade { border-left: 0; border-top: 1px solid var(--ui-line); }
            html body .sec-strand-head { flex-direction: column; gap: 0.1rem; }
        }
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
                    <?php admin_page_header('Sections', $subtitle, 'bi-grid-3x3-gap',
                        '<button type="button" class="btn btn-ui-gold" data-new-section><i class="bi bi-plus-lg"></i> New section</button>'); ?>

                    <?php if ($sections): ?>
                        <div class="sec-toolbar">
                            <label class="sec-search mb-0">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" class="form-control" id="sectionSearch" placeholder="Find a section, adviser or room" aria-label="Find a section, adviser or room">
                            </label>
                            <?php if ($withoutAdviser): ?>
                                <button type="button" class="sec-toggle" id="needsAdviser" aria-pressed="false">
                                    Needs adviser <span class="sec-count"><?= $withoutAdviser ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($strands as $strand => $strandName): ?>
                        <section class="ui-panel sec-strand" data-strand-panel>
                            <header class="sec-strand-head">
                                <h2><?= $strand ?></h2>
                                <p><?= admin_e($strandName) ?></p>
                            </header>
                            <div class="sec-grades">
                                <?php foreach ($grades as $grade):
                                    $list = $grid[$strand][$grade] ?? []; ?>
                                    <div class="sec-grade">
                                        <h3><?= shs_grade_label($grade) ?></h3>
                                        <?php if ($list): ?>
                                            <ul class="sec-list">
                                                <?php foreach ($list as $s):
                                                    $id = (int) $s['id'];
                                                    $code = $s['code'];
                                                    $names = $advisers[$code] ?? [];
                                                    $students = $studentCounts[$code] ?? 0;
                                                    $classes = $classCounts[$code] ?? 0;
                                                    $inUse = $names || $classes || $students || !empty($subjectCounts[$s['strand'] . '-' . $s['grade'] . $s['name']]);
                                                    $counts = array_filter([$students ? plural($students, 'student') : '', $classes ? plural($classes, 'class', 'classes') : '']);
                                                    $search = strtolower(implode(' ', [$code, $s['room'], implode(' ', $names)])); ?>
                                                    <li class="sec-item" data-search="<?= admin_e($search) ?>" data-missing="<?= $names ? '0' : '1' ?>">
                                                        <span class="sec-mark" aria-hidden="true"><?= admin_e($s['name']) ?></span>
                                                        <div class="sec-body">
                                                            <span class="sec-code"><?= admin_e($code) ?></span>
                                                            <?php if ($s['room']): ?><span class="sec-room"><?= admin_e($s['room']) ?></span><?php endif; ?>
                                                            <?php if ($names): ?>
                                                                <span class="sec-meta"><?= admin_e(implode(', ', $names)) ?><?= $counts ? ' · ' . implode(' · ', $counts) : '' ?></span>
                                                            <?php else: ?>
                                                                <span class="sec-meta is-missing">No adviser yet<?= $counts ? ' · ' . implode(' · ', $counts) : '' ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ui-acts">
                                                            <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editSection<?= $id ?>" title="Edit <?= admin_e($code) ?>" aria-label="Edit <?= admin_e($code) ?>"><i class="bi bi-pencil"></i></button>
                                                            <button type="button" class="ui-act is-danger" onclick="deleteSection(<?= $id ?>, <?= admin_e(json_encode($code)) ?>, <?= $inUse ? 'true' : 'false' ?>)" title="Delete <?= admin_e($code) ?>" aria-label="Delete <?= admin_e($code) ?>"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="sec-empty">No <?= shs_grade_label($grade) ?> sections yet.</p>
                                        <?php endif; ?>
                                        <button type="button" class="sec-add" data-new-section data-strand="<?= $strand ?>" data-grade="<?= $grade ?>">
                                            <i class="bi bi-plus-lg"></i> Add section
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>

                    <div class="ui-panel ui-empty sec-no-results" id="noResults">
                        <i class="bi bi-search"></i>
                        <h3>No matching sections</h3>
                        <p>Try a section code like STEM-11A, an adviser's name or a room.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php
    // Shared section form fields (create + edit). $s is the section being edited, or null.
    function section_fields(?array $s, array $strands, array $grades, string $prefix): void
    {
        ?>
        <div class="row g-3">
            <div class="col-sm-7">
                <label for="<?= $prefix ?>Strand" class="form-label">Strand</label>
                <select class="form-select" id="<?= $prefix ?>Strand" name="strand" required data-code-part>
                    <option value="" disabled <?= $s ? '' : 'selected' ?>>Select a strand</option>
                    <?php foreach ($strands as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($s['strand'] ?? '') === $code ? 'selected' : '' ?>><?= $code ?> — <?= admin_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-5">
                <label for="<?= $prefix ?>Grade" class="form-label">Grade</label>
                <select class="form-select" id="<?= $prefix ?>Grade" name="grade" required data-code-part>
                    <option value="" disabled <?= $s ? '' : 'selected' ?>>Select a grade</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= ($s['grade'] ?? '') === $g ? 'selected' : '' ?>><?= shs_grade_label($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-7">
                <label for="<?= $prefix ?>Name" class="form-label">Section name</label>
                <input type="text" class="form-control" id="<?= $prefix ?>Name" name="name" value="<?= admin_e($s['name'] ?? '') ?>"
                    required maxlength="20" pattern="[A-Za-z0-9]{1,20}" placeholder="A, B or a name like Rizal" data-code-part
                    title="Letters and numbers only, no spaces" aria-describedby="<?= $prefix ?>Code">
            </div>
            <div class="col-sm-5">
                <label for="<?= $prefix ?>Room" class="form-label">Room <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" class="form-control" id="<?= $prefix ?>Room" name="room" value="<?= admin_e($s['room'] ?? '') ?>" maxlength="50" placeholder="Room 204">
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0" id="<?= $prefix ?>Code">Saved as <span class="sec-preview" data-code-preview><?= admin_e($s['code'] ?? '—') ?></span> · letters and numbers only in the name</p>
        <?php
    }
    ?>

    <!-- Create section -->
    <div class="modal fade" id="createSectionModal" tabindex="-1" aria-labelledby="createSectionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="processes/admin/sections/add.php" data-section-form>
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-plus-lg"></i></span>
                    <h1 class="modal-title" id="createSectionLabel">New section</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php section_fields(null, $strands, $grades, 'new'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create section</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($sections as $s):
        $id = (int) $s['id'];
        $used = !empty($advisers[$s['code']]) || !empty($classCounts[$s['code']]) || !empty($studentCounts[$s['code']])
            || !empty($subjectCounts[$s['strand'] . '-' . $s['grade'] . $s['name']]); ?>
        <!-- Edit section -->
        <div class="modal fade" id="editSection<?= $id ?>" tabindex="-1" aria-labelledby="editSectionLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="processes/admin/sections/edit.php" data-section-form data-original-code="<?= admin_e($s['code']) ?>" data-in-use="<?= $used ? '1' : '0' ?>">
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                        <h1 class="modal-title" id="editSectionLabel<?= $id ?>">Edit <?= admin_e($s['code']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <?php section_fields($s, $strands, $grades, 'edit' . $id); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <form id="deleteSectionForm" method="POST" action="processes/admin/sections/delete.php" hidden>
        <input type="hidden" name="id" id="deleteSectionId">
    </form>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        const existingCodes = <?= json_encode(array_column($sections, 'code')) ?>;

        // ---- Search + "needs adviser" filter ----
        const search = document.getElementById('sectionSearch');
        const needsAdviser = document.getElementById('needsAdviser');

        function applyFilters() {
            const term = (search?.value || '').trim().toLowerCase();
            const onlyMissing = needsAdviser?.getAttribute('aria-pressed') === 'true';
            const filtering = term !== '' || onlyMissing;
            let shown = 0;

            document.querySelectorAll('[data-strand-panel]').forEach(function (panel) {
                let panelShown = 0;
                panel.querySelectorAll('.sec-item').forEach(function (item) {
                    const match = (!term || item.dataset.search.includes(term)) && (!onlyMissing || item.dataset.missing === '1');
                    item.hidden = !match;
                    if (match) panelShown++;
                });
                // While filtering, hide empty-state text, add buttons, and grades/strands with no matches
                panel.querySelectorAll('.sec-empty, .sec-add').forEach(el => { el.hidden = filtering; });
                panel.querySelectorAll('.sec-grade').forEach(function (grade) {
                    grade.hidden = filtering && !grade.querySelector('.sec-item:not([hidden])');
                });
                panel.hidden = filtering && panelShown === 0;
                shown += panelShown;
            });
            document.getElementById('noResults').style.display = filtering && shown === 0 ? 'block' : 'none';
        }

        search?.addEventListener('input', applyFilters);
        needsAdviser?.addEventListener('click', function () {
            needsAdviser.setAttribute('aria-pressed', needsAdviser.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
            applyFilters();
        });

        // ---- Section form: live code preview, duplicate check, rename confirmation ----
        function sectionCode(form) {
            const strand = form.querySelector('[name=strand]').value;
            const grade = form.querySelector('[name=grade]').value;
            const name = form.querySelector('[name=name]').value.trim();
            return strand && grade && name ? strand + '-' + grade + name : '';
        }

        document.querySelectorAll('form[data-section-form]').forEach(function (form) {
            const preview = form.querySelector('[data-code-preview]');
            form.querySelectorAll('[data-code-part]').forEach(function (input) {
                input.addEventListener('input', () => { preview.textContent = sectionCode(form) || '—'; });
            });

            form.addEventListener('submit', function (e) {
                const newCode = sectionCode(form);
                const original = form.dataset.originalCode || '';
                if (newCode !== original && existingCodes.includes(newCode)) {
                    e.preventDefault();
                    Swal.fire('Section already exists', newCode + ' is already a section.', 'warning');
                    return;
                }
                // Renaming a section in use moves its advisers, classes and subjects too — confirm first
                if (original && newCode !== original && form.dataset.inUse === '1' && !form.dataset.confirmed) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Rename ' + original + ' to ' + newCode + '?',
                        text: 'Its advisers, classes, announcements and subjects will move to the new code. Students in the section follow a strand or grade change.',
                        icon: 'question',
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonText: 'Rename section'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = '1';
                            form.requestSubmit();
                        }
                    });
                }
            });
        });

        // "New section" and the per-grade "Add section" buttons share one modal;
        // the per-grade ones fill in the strand and grade so only the name is left.
        const createModalEl = document.getElementById('createSectionModal');
        const createForm = createModalEl.querySelector('form');
        document.querySelectorAll('[data-new-section]').forEach(function (button) {
            button.addEventListener('click', function () {
                createForm.reset();
                createForm.querySelector('[name=strand]').value = button.dataset.strand || '';
                createForm.querySelector('[name=grade]').value = button.dataset.grade || '';
                createForm.querySelector('[data-code-preview]').textContent = '—';
                bootstrap.Modal.getOrCreateInstance(createModalEl).show();
            });
        });
        createModalEl.addEventListener('shown.bs.modal', function () {
            const first = ['strand', 'grade', 'name'].map(n => createForm.querySelector('[name=' + n + ']')).find(el => !el.value);
            (first || createForm.querySelector('[name=name]')).focus();
        });

        function deleteSection(id, code, inUse) {
            if (inUse) {
                Swal.fire('Section in use', code + ' still has an adviser, students, classes or subjects. Move or remove those first, then delete the section.', 'info');
                return;
            }
            Swal.fire({
                title: 'Delete ' + code + '?',
                text: 'It will no longer appear in any section list.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete section',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById('deleteSectionId').value = id;
                    document.getElementById('deleteSectionForm').submit();
                }
            });
        }
    </script>

    <?php
    $sectionAlerts = [
        'ADMIN_SECTION_ADD_SUCCESS' => ['Section added', 'The section is now available for advisers and classes.', 'success'],
        'ADMIN_SECTION_ADD_ERROR' => ['Error', 'The section could not be added. Please try again.', 'error'],
        'ADMIN_SECTION_EXISTS' => ['Section already exists', 'A section with that strand, grade and name already exists.', 'error'],
        'ADMIN_SECTION_INVALID' => ['Check the details', 'Pick a strand and grade, and use only letters and numbers for the section name.', 'warning'],
        'ADMIN_SECTION_UPDATE_SUCCESS' => ['Saved', 'The section has been updated.', 'success'],
        'ADMIN_SECTION_UPDATE_ERROR' => ['Error', 'The section could not be updated. Please try again.', 'error'],
        'ADMIN_SECTION_DELETE_SUCCESS' => ['Deleted', 'The section has been deleted.', 'success'],
        'ADMIN_SECTION_DELETE_ERROR' => ['Error', 'The section could not be deleted. Please try again.', 'error'],
        'ADMIN_SECTION_IN_USE' => ['Section in use', 'Move or remove its adviser, students, classes and subjects before deleting it.', 'info'],
    ];
    if (isset($_SESSION['STATUS'], $sectionAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $sectionAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
