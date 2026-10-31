<?php
/**
 * Page shell for the registrar portal.
 *
 *   registrar_page_start('Students', '<style>…</style>');
 *   …page content…
 *   registrar_page_end('<script>…</script>');
 */

require_once __DIR__ . '/../../includes/asset-version.php';

function registrar_asset(string $path): string
{
    return csms_asset($path, dirname(__DIR__));
}

/** Status pill for an enrollment record ('' = not enrolled for that term). */
function registrar_status_pill(?string $status): string
{
    $tones = ['enrolled' => 'is-ok', 'completed' => 'is-info', 'graduated' => 'is-info', 'dropped' => 'is-alert', 'transferred_out' => 'is-warn'];
    if (!$status) {
        return '<span class="ui-pill">Not enrolled</span>';
    }
    return '<span class="ui-pill ' . ($tones[$status] ?? '') . '">' . admin_e(enrollment_statuses()[$status] ?? $status) . '</span>';
}

/**
 * The enrollment record modal, filled in by registrar/js/registrar.js from the
 * [data-enroll] button that opens it. $students (row id => label) adds a student
 * picker; without it the button must supply the student.
 */
function registrar_enrollment_modal(string $return, array $students = []): void
{
    global $pdo;
    $current = enrollment_current_term($pdo);
    $years = enrollment_school_years();
    if (!in_array($current['school_year'], $years, true)) {
        array_unshift($years, $current['school_year']);
    }
    ?>
    <div class="modal fade" id="enrollmentModal" tabindex="-1" aria-labelledby="enrollmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" action="processes/enrollment_save.php" data-enrollment-form>
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-clipboard-check"></i></span>
                    <h1 class="modal-title" id="enrollmentModalLabel" data-enroll-title>Enrollment record</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                    <input type="hidden" name="return" value="<?= admin_e($return) ?>">
                    <?php if ($students): ?>
                        <div class="mb-3" data-student-picker>
                            <label for="enrollStudent" class="form-label">Student</label>
                            <select class="form-select" id="enrollStudent" name="student_row_id" required>
                                <option value="">Select a student</option>
                                <?php foreach ($students as $rowId => $label): ?>
                                    <option value="<?= (int) $rowId ?>"><?= admin_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="student_row_id" value="">
                    <?php endif; ?>
                    <p class="mb-3 fw-semibold" data-student-name hidden></p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="enrollYear" class="form-label">School year</label>
                            <select class="form-select" id="enrollYear" name="school_year" required data-term-part>
                                <?php foreach ($years as $sy): ?>
                                    <option value="<?= $sy ?>" <?= $sy === $current['school_year'] ? 'selected' : '' ?>><?= $sy ?><?= $sy === $current['school_year'] ? ' (current)' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="enrollTerm" class="form-label">Term</label>
                            <select class="form-select" id="enrollTerm" name="term" required data-term-part>
                                <?php foreach (enrollment_terms() as $t): ?>
                                    <option value="<?= $t ?>" <?= $t === $current['term'] ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label for="enrollStrand" class="form-label">Strand</label>
                            <select class="form-select" id="enrollStrand" name="strand" required>
                                <option value="">Select</option>
                                <?php foreach (shs_strands() as $code => $label): ?>
                                    <option value="<?= $code ?>" title="<?= admin_e($label) ?>"><?= $code ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label for="enrollGrade" class="form-label">Grade</label>
                            <select class="form-select" id="enrollGrade" name="grade" required>
                                <option value="">Select</option>
                                <?php foreach (shs_grades() as $g): ?>
                                    <option value="<?= $g ?>"><?= shs_grade_label($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label for="enrollSection" class="form-label">Section</label>
                            <select class="form-select" id="enrollSection" name="section_code" data-section-select>
                                <option value="">No section yet</option>
                                <?php foreach (shs_section_rows() as $s): ?>
                                    <option value="<?= admin_e($s['code']) ?>" data-strand="<?= admin_e($s['strand']) ?>" data-grade="<?= admin_e($s['grade']) ?>"><?= admin_e($s['code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="enrollAdmission" class="form-label">Admission</label>
                            <select class="form-select" id="enrollAdmission" name="admission_type" required>
                                <?php foreach (enrollment_admission_types() as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $value === 'continuing' ? 'selected' : '' ?>><?= admin_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="enrollStatus" class="form-label">Status</label>
                            <select class="form-select" id="enrollStatus" name="status" required>
                                <?php foreach (enrollment_statuses() as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= admin_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="enrollRemarks" class="form-label">Remarks <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="enrollRemarks" name="remarks" maxlength="255" placeholder="e.g. Transferred from another school, Form 137 received">
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        Records for the current term (<?= admin_e($current['label']) ?>) also update the student's strand, grade and adviser list.
                        Dropped or transferred-out students are removed from their adviser's list.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save record</button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteRecordForm" method="POST" action="processes/enrollment_delete.php" hidden>
        <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
        <input type="hidden" name="return" value="<?= admin_e($return) ?>">
        <input type="hidden" name="id" id="deleteRecordId">
    </form>
    <?php
}

/** Data for a [data-enroll] button: a blank record for a student, or an existing record to edit. */
function registrar_enroll_data(array $values, bool $locked = false): string
{
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    return 'data-enroll="' . admin_e(json_encode($values, $flags)) . '"' . ($locked ? ' data-enroll-locked' : '');
}

function registrar_page_start(string $title, string $head = ''): void
{
    global $registrarName;
    $current = basename($_SERVER['PHP_SELF']);
    $nav = [
        'Overview' => [
            ['index.php', 'bi-speedometer2', 'Dashboard'],
        ],
        'Records' => [
            ['students.php', 'bi-people', 'Students', ['student.php']],
            ['enrollment.php', 'bi-clipboard-check', 'Enrollment'],
        ],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= admin_e($title) ?> | DPLMHS-SHS Registrar Portal</title>
    <?php include __DIR__ . '/../../includes/favicon-links.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= registrar_asset('../admin/css/app.css') ?>" rel="stylesheet">
    <link href="<?= registrar_asset('../admin/css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .person { display: flex; align-items: center; gap: 0.65rem; }
        .person-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2.2rem; height: 2.2rem; border-radius: 50%; background: var(--ui-navy); color: var(--ui-gold-light); font-size: 0.8rem; font-weight: 700; }
        .person-name { display: block; font-weight: 700; color: var(--ui-navy); }
        .person-name:hover { text-decoration: underline; }
        .person-meta { display: block; font-size: 0.8rem; color: var(--ui-muted); }
    </style>
    <?= $head ?>
</head>

<body>
    <div class="wrapper">
        <nav id="sidebar" class="sidebar js-sidebar">
            <div class="sidebar-content js-simplebar">
                <a class="au-portal-brand" href="index.php">
                    <img src="../external/img/dp_logo.avif" alt="Don Pablo Lorenzo Memorial High School seal">
                    <span><strong>DPLMHS</strong><small>SHS Registrar Portal</small></span>
                </a>

                <div class="au-portal-user">
                    <span class="au-portal-avatar au-portal-avatar-placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                    <h1 class="au-portal-name"><?= admin_e($registrarName) ?></h1>
                    <span class="au-portal-role">Registrar</span>
                    <span class="au-portal-clock time" id="currentTime"></span>
                </div>

                <ul class="sidebar-nav">
                    <?php foreach ($nav as $header => $items): ?>
                        <li class="sidebar-header"><?= admin_e($header) ?></li>
                        <?php foreach ($items as $item):
                            $active = $current === $item[0] || in_array($current, $item[3] ?? [], true); ?>
                            <li class="sidebar-item <?= $active ? 'active' : '' ?>">
                                <a class="sidebar-link" href="<?= $item[0] ?>">
                                    <i class="bi <?= $item[1] ?> align-middle"></i> <span class="align-middle"><?= admin_e($item[2]) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>

                <div class="au-portal-foot">
                    <a href="../index.php" target="_blank"><i class="bi bi-globe2"></i> View public website</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
                </div>
            </div>
        </nav>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System</span>
                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <span class="text-light">Registrar</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="logout.php">Log out</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
    <?php
}

function registrar_page_end(string $scripts = ''): void
{
    ?>
                </div>
            </main>
        </div>
    </div>

    <script src="<?= registrar_asset('../admin/js/app.js') ?>"></script>
    <script src="<?= registrar_asset('../admin/js/admin-ui.js') ?>"></script>
    <script src="<?= registrar_asset('js/registrar.js') ?>"></script>
    <?= $scripts ?>
    <?php registrar_render_flash(); ?>
    <?php include __DIR__ . '/../../includes/login-success-alert.php'; ?>
</body>

</html>
    <?php
}
