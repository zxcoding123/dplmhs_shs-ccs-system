<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../includes/records.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.status AS account_status, u.last_login, u.date_created
    FROM students s LEFT JOIN users u ON u.id = s.user_id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    registrar_redirect('students.php', ['Not found', 'That student record no longer exists.', 'error']);
}

$records = $pdo->prepare("SELECT * FROM enrollment_records WHERE student_row_id = ? ORDER BY school_year DESC, term DESC");
$records->execute([$id]);
$records = $records->fetchAll(PDO::FETCH_ASSOC);

$advisers = $pdo->prepare("SELECT st.fullName, st.class_advising FROM students_advising sa
    JOIN staff_advising st ON st.id = sa.staff_advising_id WHERE sa.student_id = ? ORDER BY st.class_advising");
$advisers->execute([$id]);
$advisers = $advisers->fetchAll(PDO::FETCH_ASSOC);

$term = enrollment_current_term($pdo);
$currentRecord = null;
foreach ($records as $r) {
    if ($r['school_year'] === $term['school_year'] && $r['term'] === $term['term']) {
        $currentRecord = $r;
    }
}

$credentials = $_SESSION['registrar_new_credentials'] ?? null;
unset($_SESSION['registrar_new_credentials']);

$name = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
$isActive = $student['account_status'] === 'active';
$grade = shs_grade_number((string) $student['year_level']) ?? '';
$admission = enrollment_admission_types();
$gradeTerms = records_student_grades($pdo, $student);
$printYears = array_unique(array_merge(array_column($records, 'school_year'), array_column($gradeTerms, 'school_year')));
rsort($printYears);

$printMenu = '<div class="dropdown d-inline-block"><button type="button" class="btn btn-ui-ghost dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-printer"></i> Print</button><ul class="dropdown-menu dropdown-menu-end">'
    . '<li><a class="dropdown-item" target="_blank" href="print.php?doc=coe&amp;student=' . $id . '">Certificate of Enrollment</a></li>'
    . '<li><a class="dropdown-item" target="_blank" href="print.php?doc=good_moral&amp;student=' . $id . '">Certificate of Good Moral Character</a></li>'
    . '<li><hr class="dropdown-divider"></li>';
foreach ($printYears ?: [$term['school_year']] as $py) {
    $printMenu .= '<li><a class="dropdown-item" target="_blank" href="print.php?doc=sf9&amp;student=' . $id . '&amp;sy=' . admin_e($py) . '">Report card (SF9) · S.Y. ' . admin_e($py) . '</a></li>';
}
$printMenu .= '<li><a class="dropdown-item" target="_blank" href="print.php?doc=sf10&amp;student=' . $id . '">Permanent record (SF10)</a></li></ul></div>';

registrar_page_start($name);

admin_page_header($name, 'Student ID ' . $student['student_id'] . ' · ' . $student['course'] . ' ' . $student['year_level'], 'bi-person-vcard',
    '<a class="btn btn-ui-ghost" href="students.php"><i class="bi bi-arrow-left"></i> All students</a>' . $printMenu
    . '<button type="button" class="btn btn-ui-gold" ' . registrar_enroll_data([
        'student_row_id' => $id,
        'student_name'   => $name,
        'strand'         => $student['course'],
        'grade'          => $grade,
        'admission_type' => $records ? 'continuing' : 'new',
    ]) . '><i class="bi bi-clipboard-plus"></i> Add enrollment record</button>');
?>

<?php if ($credentials): ?>
    <div class="ui-callout">
        <i class="bi bi-key"></i>
        <div>
            <strong><?= !empty($credentials['reset']) ? 'Password changed.' : 'Account created.' ?> Give these sign-in details to <?= admin_e($credentials['name']) ?>:</strong><br>
            <?php if (!empty($credentials['student_id'])): ?>Student ID <code><?= admin_e($credentials['student_id']) ?></code> · <?php endif; ?>Email <code><?= admin_e($credentials['email']) ?></code> · Password <code><?= admin_e($credentials['password']) ?></code><br>
            <span class="text-muted small">This is the only time the password is shown. Ask the student to change it after signing in.</span>
        </div>
    </div>
<?php endif; ?>

<?php if (!$currentRecord): ?>
    <div class="ui-callout is-alert">
        <i class="bi bi-exclamation-circle"></i>
        <div>No enrollment record for <?= admin_e($term['label']) ?> yet. Use <strong>Add enrollment record</strong> to enroll this student.</div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <section class="ui-panel h-100">
            <div class="ui-panel-head">
                <div><h2>Details</h2></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editStudent"><i class="bi bi-pencil"></i> Edit</button>
            </div>
            <dl class="ui-dl">
                <dt>Student ID</dt><dd><?= admin_e($student['student_id']) ?></dd>
                <dt>LRN</dt><dd><?= $student['lrn'] ? admin_e($student['lrn']) : '<span class="text-muted">Not recorded</span>' ?></dd>
                <dt>Gender</dt><dd><?= admin_e($student['gender']) ?></dd>
                <dt>Strand</dt><dd><?= admin_e(shs_strand_label((string) $student['course'])) ?></dd>
                <dt>Grade</dt><dd><?= admin_e($student['year_level']) ?></dd>
                <dt>Section</dt><dd><?= admin_e($currentRecord['section_code'] ?? '—') ?></dd>
                <dt>Adviser</dt>
                <dd><?= $advisers ? admin_e(implode(', ', array_map(fn($a) => $a['fullName'] . ' (' . $a['class_advising'] . ')', $advisers))) : '<span class="text-muted">None yet</span>' ?></dd>
            </dl>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="ui-panel h-100">
            <div class="ui-panel-head"><div><h2>Account</h2></div></div>
            <dl class="ui-dl">
                <dt>Email</dt><dd><?= admin_e($student['email'] ?: '—') ?></dd>
                <dt>Status</dt><dd><span class="ui-pill <?= $isActive ? 'is-ok' : '' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></dd>
                <dt>Created</dt><dd><?= $student['date_created'] ? date('M j, Y', strtotime($student['date_created'])) : '—' ?></dd>
                <dt>Last sign-in</dt><dd><?= $student['last_login'] ? date('M j, Y g:i A', strtotime($student['last_login'])) : 'Never' ?></dd>
            </dl>
            <form method="POST" action="processes/student_status.php" data-confirm-status="<?= admin_e($name) ?>" class="mt-3">
                <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                    <i class="bi <?= $isActive ? 'bi-person-dash' : 'bi-person-check' ?>"></i> <?= $isActive ? 'Deactivate account' : 'Activate account' ?>
                </button>
            </form>
            <?php $lockedGrades = registrar_student_locked_grades($pdo, (int) $student['student_id']); ?>
            <div class="mt-3 pt-3 border-top">
                <?php if ($lockedGrades): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-trash"></i> Delete account</button>
                    <p class="text-muted small mt-2 mb-0">Can't be deleted: <?= $lockedGrades ?> accepted grade<?= $lockedGrades === 1 ? ' is an' : 's are' ?> official record<?= $lockedGrades === 1 ? '' : 's' ?>. Deactivate the account instead.</p>
                <?php else: ?>
                    <form method="POST" action="processes/student_delete.php" id="deleteStudentForm">
                        <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="confirm_student_id" id="confirmStudentId">
                        <button type="button" class="btn btn-sm btn-danger" data-delete-student="<?= admin_e($student['student_id']) ?>" data-name="<?= admin_e($name) ?>"><i class="bi bi-trash"></i> Delete account</button>
                    </form>
                    <p class="text-muted small mt-2 mb-0">Removes the login, classes, scores, attendance, messages and enrollment history for good.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="ui-panel h-100">
            <div class="ui-panel-head"><div><h2>This term</h2><p><?= admin_e($term['label']) ?></p></div></div>
            <?php if ($currentRecord): ?>
                <dl class="ui-dl">
                    <dt>Status</dt><dd><?= registrar_status_pill($currentRecord['status']) ?></dd>
                    <dt>Placement</dt><dd><?= admin_e($currentRecord['strand'] . ' ' . $currentRecord['grade_level'] . ($currentRecord['section_code'] ? ' · ' . $currentRecord['section_code'] : '')) ?></dd>
                    <dt>Admission</dt><dd><?= admin_e($admission[$currentRecord['admission_type']] ?? $currentRecord['admission_type']) ?></dd>
                    <?php if ($currentRecord['remarks'] !== ''): ?><dt>Remarks</dt><dd><?= admin_e($currentRecord['remarks']) ?></dd><?php endif; ?>
                </dl>
            <?php else: ?>
                <p class="text-muted mb-0"><?= registrar_status_pill(null) ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="ui-panel mt-3">
    <div class="ui-panel-head">
        <div>
            <h2>Enrollment history</h2>
            <p>One record per school year and term. To move a record to another term, delete it and add it again.</p>
        </div>
    </div>
    <?php if ($records): ?>
        <div class="ui-table-scroll">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>School year</th>
                        <th>Term</th>
                        <th>Placement</th>
                        <th>Admission</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Recorded by</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r):
                        $label = 'S.Y. ' . $r['school_year'] . ', ' . $r['term']; ?>
                        <tr>
                            <td><?= admin_e($r['school_year']) ?></td>
                            <td><?= admin_e($r['term']) ?></td>
                            <td><?= admin_e($r['strand'] . ' ' . $r['grade_level']) ?><?= $r['section_code'] ? '<br><span class="text-muted small">' . admin_e($r['section_code']) . '</span>' : '' ?></td>
                            <td><?= admin_e($admission[$r['admission_type']] ?? $r['admission_type']) ?></td>
                            <td><?= registrar_status_pill($r['status']) ?></td>
                            <td><?= admin_e($r['remarks'] ?: '—') ?></td>
                            <td><?= admin_e($r['recorded_by_name'] ?: '—') ?><br><span class="text-muted small"><?= date('M j, Y', strtotime($r['updated_at'])) ?></span></td>
                            <td>
                                <div class="ui-acts">
                                    <button type="button" class="ui-act" title="Edit" aria-label="Edit <?= admin_e($label) ?>" <?= registrar_enroll_data([
                                        'student_row_id' => $id,
                                        'student_name'   => $name . ' — ' . $label,
                                        'school_year'    => $r['school_year'],
                                        'term'           => $r['term'],
                                        'strand'         => $r['strand'],
                                        'grade'          => shs_grade_number($r['grade_level']),
                                        'section_code'   => $r['section_code'],
                                        'admission_type' => $r['admission_type'],
                                        'status'         => $r['status'],
                                        'remarks'        => $r['remarks'],
                                    ], true) ?>><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="ui-act is-danger" title="Delete" aria-label="Delete <?= admin_e($label) ?>" data-id="<?= (int) $r['id'] ?>" data-delete-record="<?= admin_e($label) ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-clipboard"></i>
            <h3>No enrollment records yet</h3>
            <p>Add a record for each term the student is enrolled in, including earlier terms for transferees.</p>
        </div>
    <?php endif; ?>
</section>

<section class="ui-panel mt-3">
    <div class="ui-panel-head">
        <div>
            <h2>Grades</h2>
            <p>From the classes the student joined. Only accepted (locked) grades are final and appear on the SF9 and SF10.</p>
        </div>
    </div>
    <?php if ($gradeTerms): ?>
        <?php foreach (array_reverse($gradeTerms) as $gt): ?>
            <p class="ui-section-label">S.Y. <?= admin_e($gt['school_year']) ?> · <?= admin_e($gt['term']) ?>
                <?= $gt['general_average'] !== null ? ' · General average ' . admin_e((string) (int) round($gt['general_average'])) : '' ?></p>
            <div class="ui-table-scroll">
                <table class="table align-middle">
                    <thead><tr><th>Subject</th><th class="text-end">Q1</th><th class="text-end">Q2</th><th class="text-end">Final</th><th>Remarks</th></tr></thead>
                    <tbody>
                        <?php foreach ($gt['subjects'] as $s): ?>
                            <tr>
                                <td><?= admin_e($s['subject']) ?></td>
                                <td class="text-end"><?= admin_e($s['q1']) ?></td>
                                <td class="text-end"><?= admin_e($s['q2']) ?></td>
                                <td class="text-end fw-bold <?= deped_flagged($s['final']) ? 'text-danger' : '' ?>"><?= admin_e($s['final']) ?></td>
                                <td><?= $s['official'] ? admin_e($s['remarks'] ?: '—') : '<span class="ui-pill is-warn">Not final</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-journal"></i>
            <h3>No grades yet</h3>
            <p>Grades appear once the student joins classes and teachers record them.</p>
        </div>
    <?php endif; ?>
</section>

<!-- Edit student -->
<div class="modal fade" id="editStudent" tabindex="-1" aria-labelledby="editStudentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="POST" action="processes/student_save.php">
            <div class="modal-header">
                <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                <h1 class="modal-title" id="editStudentLabel">Edit <?= admin_e($name) ?></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="editFirst" class="form-label">First name</label>
                        <input type="text" class="form-control" id="editFirst" name="first_name" value="<?= admin_e($student['first_name']) ?>" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label for="editMiddle" class="form-label">Middle name</label>
                        <input type="text" class="form-control" id="editMiddle" name="middle_name" value="<?= admin_e($student['middle_name']) ?>" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label for="editLast" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="editLast" name="last_name" value="<?= admin_e($student['last_name']) ?>" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label for="editStudentId" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="editStudentId" value="<?= admin_e($student['student_id']) ?>" disabled>
                    </div>
                    <div class="col-md-5">
                        <label for="editLrn" class="form-label">LRN</label>
                        <input type="text" class="form-control" id="editLrn" name="lrn" value="<?= admin_e($student['lrn']) ?>" inputmode="numeric" pattern="\d{12}" maxlength="12" title="12 digits">
                    </div>
                    <div class="col-md-3">
                        <label for="editGender" class="form-label">Gender</label>
                        <select class="form-select" id="editGender" name="gender" required>
                            <?php foreach (['Male', 'Female', 'Others'] as $g): ?>
                                <option value="<?= $g ?>" <?= $student['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email" value="<?= admin_e($student['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="editPassword" class="form-label">New password <span class="text-muted fw-normal">(leave blank to keep)</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editPassword" name="password" minlength="8" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" data-generate-password="#editPassword" title="Generate a password" aria-label="Generate a password"><i class="bi bi-arrow-repeat"></i></button>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">Strand, grade and section change through the enrollment record for the current term.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>

<?php
registrar_enrollment_modal('student');
registrar_page_end();
