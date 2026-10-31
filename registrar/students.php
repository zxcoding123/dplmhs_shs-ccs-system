<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$term = enrollment_current_term($pdo);

$stmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.lrn, s.first_name, s.middle_name, s.last_name, s.gender, s.course, s.year_level,
           u.email, u.status AS account_status,
           er.status AS term_status, er.section_code
    FROM students s
    LEFT JOIN users u ON u.id = s.user_id
    LEFT JOIN enrollment_records er ON er.student_row_id = s.id AND er.school_year = :sy AND er.term = :term
    ORDER BY s.last_name, s.first_name
");
$stmt->execute(['sy' => $term['school_year'], 'term' => $term['term']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$enrolled = count(array_filter($students, fn($s) => $s['term_status'] === 'enrolled'));
$notEnrolled = count(array_filter($students, fn($s) => !$s['term_status']));
$missingLrn = count(array_filter($students, fn($s) => !$s['lrn']));
$inactive = count(array_filter($students, fn($s) => $s['account_status'] !== 'active'));

// Refill the form after a failed attempt
$form = $_SESSION['registrar_student_form'] ?? [];
unset($_SESSION['registrar_student_form']);
$openForm = !empty($form);
$tempPassword = $form['password'] ?? registrar_temp_password();

registrar_page_start('Students');

admin_page_header('Students', count($students) . ' student records · ' . $term['label'], 'bi-people',
    '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createStudent"><i class="bi bi-person-plus"></i> New student</button>');

admin_stat_tiles([
    ['label' => 'All students', 'value' => count($students), 'icon' => 'bi-people', 'filter' => ''],
    ['label' => 'Enrolled this term', 'value' => $enrolled, 'icon' => 'bi-clipboard-check', 'tone' => 'ok', 'filter' => 'Enrolled', 'filter_col' => 'This term'],
    ['label' => 'Not yet enrolled', 'value' => $notEnrolled, 'icon' => 'bi-hourglass-split', 'tone' => $notEnrolled ? 'alert' : 'muted', 'filter' => 'Not enrolled', 'filter_col' => 'This term', 'hint' => 'No record for this term yet'],
    ['label' => 'Missing LRN', 'value' => $missingLrn, 'icon' => 'bi-123', 'tone' => $missingLrn ? 'alert' : 'muted', 'filter' => 'No LRN', 'filter_col' => 'LRN'],
    ['label' => 'Inactive accounts', 'value' => $inactive, 'icon' => 'bi-person-dash', 'tone' => 'muted', 'filter' => 'Inactive', 'filter_col' => 'Account'],
]);
?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>Student master list</h2>
            <p>Open a student to edit their details, reset their password or update their enrollment history.</p>
        </div>
    </div>
    <?php if ($students): ?>
        <div class="ui-filters">
            <select class="form-select" data-column-filter="Strand" aria-label="Filter by strand">
                <option value="">All strands</option>
                <?php foreach (shs_strands() as $code => $label): ?><option value="<?= $code ?>"><?= $code ?></option><?php endforeach; ?>
            </select>
            <select class="form-select" data-column-filter="Grade" aria-label="Filter by grade">
                <option value="">All grades</option>
                <?php foreach (shs_grade_levels() as $g): ?><option value="<?= $g ?>"><?= $g ?></option><?php endforeach; ?>
            </select>
        </div>
        <table id="studentTable" class="display">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>LRN</th>
                    <th>Strand</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>This term</th>
                    <th>Account</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s):
                    $id = (int) $s['id'];
                    $name = trim($s['last_name'] . ', ' . $s['first_name'] . ' ' . $s['middle_name']);
                    $initials = strtoupper(mb_substr($s['first_name'], 0, 1) . mb_substr($s['last_name'], 0, 1)); ?>
                    <tr>
                        <td data-order="<?= admin_e($name) ?>">
                            <div class="person">
                                <span class="person-avatar"><?= admin_e($initials ?: '?') ?></span>
                                <div>
                                    <a class="person-name" href="student.php?id=<?= $id ?>"><?= admin_e($name) ?></a>
                                    <span class="person-meta">ID <?= admin_e($s['student_id']) ?> · <?= admin_e($s['email'] ?: 'no login') ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= $s['lrn'] ? admin_e($s['lrn']) : '<span class="text-muted">No LRN</span>' ?></td>
                        <td><?= admin_e($s['course']) ?></td>
                        <td><?= admin_e($s['year_level']) ?></td>
                        <td><?= admin_e($s['section_code'] ?: '—') ?></td>
                        <td><?= registrar_status_pill($s['term_status']) ?></td>
                        <td><span class="ui-pill <?= $s['account_status'] === 'active' ? 'is-ok' : '' ?>"><?= $s['account_status'] === 'active' ? 'Active' : 'Inactive' ?></span></td>
                        <td>
                            <div class="ui-acts">
                                <a class="ui-act" href="student.php?id=<?= $id ?>" title="Open record" aria-label="Open <?= admin_e($name) ?>"><i class="bi bi-folder2-open"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-people"></i>
            <h3>No students yet</h3>
            <p>Add the first student with <strong>New student</strong>. Advisers can also add students to their own section.</p>
        </div>
    <?php endif; ?>
</section>

<!-- Create student -->
<div class="modal fade" id="createStudent" tabindex="-1" aria-labelledby="createStudentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="POST" action="processes/student_save.php">
            <div class="modal-header">
                <span class="ui-modal-icon"><i class="bi bi-person-plus"></i></span>
                <h1 class="modal-title" id="createStudentLabel">New student</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="newFirst" class="form-label">First name</label>
                        <input type="text" class="form-control" id="newFirst" name="first_name" value="<?= admin_e($form['first_name'] ?? '') ?>" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label for="newMiddle" class="form-label">Middle name</label>
                        <input type="text" class="form-control" id="newMiddle" name="middle_name" value="<?= admin_e($form['middle_name'] ?? '') ?>" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label for="newLast" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="newLast" name="last_name" value="<?= admin_e($form['last_name'] ?? '') ?>" maxlength="100" required>
                    </div>
                    <div class="col-md-4">
                        <label for="newStudentId" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="newStudentId" name="student_id" value="<?= admin_e($form['student_id'] ?? '') ?>" inputmode="numeric" pattern="\d{1,9}" maxlength="9" required title="Up to 9 digits">
                        <small class="text-muted">Can't be changed later.</small>
                    </div>
                    <div class="col-md-5">
                        <label for="newLrn" class="form-label">LRN <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="newLrn" name="lrn" value="<?= admin_e($form['lrn'] ?? '') ?>" inputmode="numeric" pattern="\d{12}" maxlength="12" title="12 digits">
                    </div>
                    <div class="col-md-3">
                        <label for="newGender" class="form-label">Gender</label>
                        <select class="form-select" id="newGender" name="gender" required>
                            <?php foreach (['Male', 'Female', 'Others'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($form['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="newEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="newEmail" name="email" value="<?= admin_e($form['email'] ?? '') ?>" autocomplete="off" required>
                    </div>
                    <div class="col-md-6">
                        <label for="newPassword" class="form-label">Temporary password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newPassword" name="password" value="<?= admin_e($tempPassword) ?>" minlength="8" autocomplete="off" required>
                            <button type="button" class="btn btn-outline-secondary" data-generate-password="#newPassword" title="Generate another" aria-label="Generate another password"><i class="bi bi-arrow-repeat"></i></button>
                        </div>
                        <small class="text-muted">Shown once after saving so you can hand it to the student.</small>
                    </div>
                </div>

                <p class="ui-section-label">Placement</p>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label for="newStrand" class="form-label">Strand</label>
                        <select class="form-select" id="newStrand" name="strand" required>
                            <option value="">Select</option>
                            <?php foreach (shs_strands() as $code => $label): ?>
                                <option value="<?= $code ?>" title="<?= admin_e($label) ?>" <?= ($form['strand'] ?? '') === $code ? 'selected' : '' ?>><?= $code ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label for="newGrade" class="form-label">Grade</label>
                        <select class="form-select" id="newGrade" name="grade" required>
                            <option value="">Select</option>
                            <?php foreach (shs_grades() as $g): ?>
                                <option value="<?= $g ?>" <?= ($form['grade'] ?? '') === $g ? 'selected' : '' ?>><?= shs_grade_label($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label for="newSection" class="form-label">Section</label>
                        <select class="form-select" id="newSection" name="section_code" data-section-select>
                            <option value="">No section yet</option>
                            <?php foreach (shs_section_rows() as $sec): ?>
                                <option value="<?= admin_e($sec['code']) ?>" data-strand="<?= admin_e($sec['strand']) ?>" data-grade="<?= admin_e($sec['grade']) ?>" <?= ($form['section_code'] ?? '') === $sec['code'] ? 'selected' : '' ?>><?= admin_e($sec['code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="newAdmission" class="form-label">Admission</label>
                        <select class="form-select" id="newAdmission" name="admission_type">
                            <?php foreach (enrollment_admission_types() as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($form['admission_type'] ?? 'new') === $value ? 'selected' : '' ?>><?= admin_e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="newEnroll" name="enroll_now" value="1" <?= !$form || !empty($form['enroll_now']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="newEnroll">Enroll for <?= admin_e($term['label']) ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create student</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
    $(function () {
        const table = $('#studentTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [{ targets: 7, orderable: false, searchable: false }]
        });

        // Strand / grade dropdowns match their column exactly
        document.querySelectorAll('[data-column-filter]').forEach(function (select) {
            const index = table.columns().header().toArray().findIndex(th => th.textContent.trim() === select.dataset.columnFilter);
            select.addEventListener('change', function () {
                const value = $.fn.dataTable.util.escapeRegex(select.value);
                table.column(index).search(value ? '^' + value + '$' : '', true, false).draw();
            });
        });
        // Stat tiles reset every column search, so reset the dropdowns with them
        document.querySelectorAll('[data-ui-filter]').forEach(function (tile) {
            tile.addEventListener('click', function () {
                document.querySelectorAll('[data-column-filter]').forEach(select => { select.value = ''; });
            });
        });

        <?php if ($openForm): ?>
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createStudent')).show();
        <?php endif; ?>
    });
</script>
<?php registrar_page_end(ob_get_clean()); ?>
