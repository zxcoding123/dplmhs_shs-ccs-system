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

$gradeTotals = array_count_values(array_column($sections, 'grade'));
$withoutAdviser = count(array_filter($sections, fn($s) => empty($advisers[$s['code']])));
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sec-code { font-weight: 700; color: var(--ui-navy); }
        .sec-room { display: block; font-size: 0.8rem; color: var(--ui-muted); }
        .sec-preview { font-weight: 700; color: var(--ui-navy); }
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
                    <?php admin_page_header('Sections', 'Grade 11 and 12 sections for each strand, used for advisers, classes and subjects', 'bi-grid-3x3-gap',
                        '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createSectionModal"><i class="bi bi-plus-lg"></i> New section</button>'); ?>

                    <?php
                    $tiles = [['label' => 'Sections', 'value' => count($sections), 'icon' => 'bi-grid-3x3-gap', 'filter' => '']];
                    foreach ($grades as $g) {
                        $tiles[] = ['label' => shs_grade_label($g), 'value' => $gradeTotals[$g] ?? 0, 'icon' => 'bi-mortarboard', 'filter' => shs_grade_label($g), 'filter_col' => 'Grade'];
                    }
                    $tiles[] = ['label' => 'Without adviser', 'value' => $withoutAdviser, 'icon' => 'bi-person-x', 'tone' => $withoutAdviser ? 'alert' : 'ok', 'filter' => 'No adviser'];
                    admin_stat_tiles($tiles);
                    ?>

                    <section class="ui-panel">
                        <?php if ($sections): ?>
                            <div class="ui-filters mb-3">
                                <select class="form-select form-select-sm" data-col-filter="1" aria-label="Filter by strand">
                                    <option value="">All strands</option>
                                    <?php foreach (array_keys($strands) as $code): ?><option value="<?= $code ?>"><?= $code ?></option><?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm" data-col-filter="2" aria-label="Filter by grade">
                                    <option value="">All grades</option>
                                    <?php foreach ($grades as $g): ?><option value="<?= shs_grade_label($g) ?>"><?= shs_grade_label($g) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <table id="sections" class="display">
                                <thead>
                                    <tr>
                                        <th>Section</th>
                                        <th>Strand</th>
                                        <th>Grade</th>
                                        <th>Adviser</th>
                                        <th>Students</th>
                                        <th>Classes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $s):
                                        $id = (int) $s['id'];
                                        $code = $s['code'];
                                        $inUse = !empty($advisers[$code]) || !empty($classCounts[$code]) || !empty($subjectCounts[$s['strand'] . '-' . $s['grade'] . $s['name']]); ?>
                                        <tr>
                                            <td>
                                                <span class="sec-code"><?= admin_e($code) ?></span>
                                                <?php if ($s['room']): ?><span class="sec-room"><i class="bi bi-door-open"></i> <?= admin_e($s['room']) ?></span><?php endif; ?>
                                            </td>
                                            <td><?= admin_e($s['strand']) ?></td>
                                            <td><?= admin_e(shs_grade_label($s['grade'])) ?></td>
                                            <td>
                                                <?php if (!empty($advisers[$code])): ?>
                                                    <?= admin_e(implode(', ', $advisers[$code])) ?>
                                                <?php else: ?>
                                                    <span class="ui-pill is-alert">No adviser</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $studentCounts[$code] ?? 0 ?></td>
                                            <td><?= $classCounts[$code] ?? 0 ?></td>
                                            <td>
                                                <div class="ui-acts">
                                                    <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editSection<?= $id ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" class="ui-act is-danger" onclick="deleteSection(<?= $id ?>, <?= admin_e(json_encode($code)) ?>, <?= $inUse ? 'true' : 'false' ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="ui-empty">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <h3>No sections yet</h3>
                                <p>Add the Grade 11 and 12 sections for each strand so advisers and classes can be assigned to them.</p>
                            </div>
                        <?php endif; ?>
                    </section>
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
            <div class="col-md-6">
                <label for="<?= $prefix ?>Strand" class="form-label">Strand</label>
                <select class="form-select" id="<?= $prefix ?>Strand" name="strand" required data-code-part>
                    <option value="" disabled <?= $s ? '' : 'selected' ?>>Select a strand</option>
                    <?php foreach ($strands as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($s['strand'] ?? '') === $code ? 'selected' : '' ?>><?= $code ?> — <?= admin_e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="<?= $prefix ?>Grade" class="form-label">Grade</label>
                <select class="form-select" id="<?= $prefix ?>Grade" name="grade" required data-code-part>
                    <option value="" disabled <?= $s ? '' : 'selected' ?>>Select a grade</option>
                    <?php foreach ($grades as $g): ?>
                        <option value="<?= $g ?>" <?= ($s['grade'] ?? '') === $g ? 'selected' : '' ?>><?= shs_grade_label($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="<?= $prefix ?>Name" class="form-label">Section name</label>
                <input type="text" class="form-control" id="<?= $prefix ?>Name" name="name" value="<?= admin_e($s['name'] ?? '') ?>"
                    required maxlength="20" pattern="[A-Za-z0-9]{1,20}" placeholder="e.g. A or Rizal" data-code-part
                    title="Letters and numbers only, no spaces">
                <small class="text-muted">Letters and numbers only, no spaces.</small>
            </div>
            <div class="col-md-6">
                <label for="<?= $prefix ?>Room" class="form-label">Room <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" class="form-control" id="<?= $prefix ?>Room" name="room" value="<?= admin_e($s['room'] ?? '') ?>" maxlength="50" placeholder="e.g. Room 204">
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Section code: <span class="sec-preview" data-code-preview><?= admin_e($s['code'] ?? '—') ?></span></p>
        <?php
    }
    ?>

    <!-- Create section -->
    <div class="modal fade" id="createSectionModal" tabindex="-1" aria-labelledby="createSectionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create section</button>
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
            <div class="modal-dialog modal-dialog-centered modal-lg">
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
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
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

        $(function () {
            const table = $('#sections').DataTable({
                order: [],
                columnDefs: [{ targets: 6, orderable: false, searchable: false }]
            });
            document.querySelectorAll('[data-col-filter]').forEach(function (select) {
                select.addEventListener('change', function () {
                    const value = select.value ? '^' + $.fn.dataTable.util.escapeRegex(select.value) + '$' : '';
                    table.column(+select.dataset.colFilter).search(value, true, false).draw();
                });
            });
        });

        document.querySelectorAll('form[data-section-form]').forEach(function (form) {
            const preview = form.querySelector('[data-code-preview]');
            const code = () => {
                const strand = form.querySelector('[name=strand]').value;
                const grade = form.querySelector('[name=grade]').value;
                const name = form.querySelector('[name=name]').value.trim();
                return strand && grade && name ? strand + '-' + grade + name : '';
            };

            // Live preview of the code the section will be saved as
            form.querySelectorAll('[data-code-part]').forEach(function (input) {
                input.addEventListener('input', () => { preview.textContent = code() || '—'; });
            });

            form.addEventListener('submit', function (e) {
                const newCode = code();
                const original = form.dataset.originalCode || '';
                if (newCode !== original && existingCodes.includes(newCode)) {
                    e.preventDefault();
                    Swal.fire('Section already exists', newCode + ' is already a section.', 'warning');
                    return;
                }
                // Renaming a section in use updates its advisers, classes and subjects too — confirm first
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

        function deleteSection(id, code, inUse) {
            if (inUse) {
                Swal.fire('Section in use', code + ' still has an adviser, classes or subjects. Move or remove those first, then delete the section.', 'info');
                return;
            }
            Swal.fire({
                title: 'Delete ' + code + '?',
                text: 'This removes the section from every section list.',
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
        'ADMIN_SECTION_IN_USE' => ['Section in use', 'Move or remove its adviser, classes and subjects before deleting it.', 'info'],
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
