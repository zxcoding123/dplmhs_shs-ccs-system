<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$current = enrollment_current_term($pdo);
$sy = $_GET['sy'] ?? $current['school_year'];
$termName = $_GET['term'] ?? $current['term'];
if (!enrollment_valid_school_year($sy) || !in_array($termName, enrollment_terms(), true)) {
    $sy = $current['school_year'];
    $termName = $current['term'];
}
$isCurrent = $sy === $current['school_year'] && $termName === $current['term'];
$years = enrollment_school_years();
if (!in_array($sy, $years, true)) {
    $years[] = $sy;
}

$stmt = $pdo->prepare("
    SELECT er.*, s.student_id, s.first_name, s.middle_name, s.last_name
    FROM enrollment_records er
    JOIN students s ON s.id = er.student_row_id
    WHERE er.school_year = ? AND er.term = ?
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$sy, $termName]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Active students with no record for this term
$stmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.course, s.year_level
    FROM students s
    JOIN users u ON u.id = s.user_id AND u.status = 'active'
    WHERE NOT EXISTS (SELECT 1 FROM enrollment_records er WHERE er.student_row_id = s.id AND er.school_year = ? AND er.term = ?)
    ORDER BY s.last_name, s.first_name
");
$stmt->execute([$sy, $termName]);
$missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byStatus = array_count_values(array_column($records, 'status'));
$grid = [];
foreach ($records as $r) {
    if ($r['status'] === 'enrolled') {
        $grid[$r['strand']][$r['grade_level']] = ($grid[$r['strand']][$r['grade_level']] ?? 0) + 1;
    }
}

function roster_name(array $s): string
{
    return trim($s['last_name'] . ', ' . $s['first_name'] . ' ' . $s['middle_name']);
}

$picker = [];
foreach ($missing as $m) {
    $picker[$m['id']] = roster_name($m) . ' · ID ' . $m['student_id'];
}
$termLabel = 'S.Y. ' . $sy . ' · ' . $termName;
$admission = enrollment_admission_types();

registrar_page_start('Enrollment');

admin_page_header('Enrollment', $termLabel . ($isCurrent ? ' (current term)' : ''), 'bi-clipboard-check',
    $missing ? '<button type="button" class="btn btn-ui-gold" ' . registrar_enroll_data(['school_year' => $sy, 'term' => $termName]) . '><i class="bi bi-clipboard-plus"></i> Enroll student</button>' : '');
?>

<form class="ui-filters mb-3" method="GET">
    <select class="form-select" name="sy" aria-label="School year" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $sy ? 'selected' : '' ?>>S.Y. <?= $y ?><?= $y === $current['school_year'] ? ' (current)' : '' ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="term" aria-label="Term" onchange="this.form.submit()">
        <?php foreach (enrollment_terms() as $t): ?>
            <option value="<?= $t ?>" <?= $t === $termName ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (!$isCurrent): ?>
        <a class="btn btn-outline-secondary" href="enrollment.php">Back to current term</a>
    <?php endif; ?>
</form>

<?php admin_stat_tiles([
    ['label' => 'Enrolled', 'value' => $byStatus['enrolled'] ?? 0, 'icon' => 'bi-clipboard-check', 'tone' => 'ok', 'filter' => 'Enrolled', 'filter_col' => 'Status'],
    ['label' => 'Not yet enrolled', 'value' => count($missing), 'icon' => 'bi-hourglass-split', 'tone' => $missing ? 'alert' : 'muted', 'hint' => 'Active students with no record'],
    ['label' => 'Dropped', 'value' => $byStatus['dropped'] ?? 0, 'icon' => 'bi-person-x', 'tone' => 'muted', 'filter' => 'Dropped', 'filter_col' => 'Status'],
    ['label' => 'Transferred out', 'value' => $byStatus['transferred_out'] ?? 0, 'icon' => 'bi-box-arrow-right', 'tone' => 'muted', 'filter' => 'Transferred out', 'filter_col' => 'Status'],
    ['label' => 'Completed / graduated', 'value' => ($byStatus['completed'] ?? 0) + ($byStatus['graduated'] ?? 0), 'icon' => 'bi-mortarboard', 'tone' => 'muted'],
]); ?>

<?php if ($grid): ?>
    <section class="ui-panel">
        <div class="ui-panel-head"><div><h2>Enrolled by strand</h2></div></div>
        <div class="ui-table-scroll">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Strand</th>
                        <?php foreach (shs_grade_levels() as $g): ?><th class="text-end"><?= $g ?></th><?php endforeach; ?>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (shs_strands() as $code => $label):
                        if (empty($grid[$code])) continue; ?>
                        <tr>
                            <td><strong><?= $code ?></strong> <span class="text-muted small"><?= admin_e($label) ?></span></td>
                            <?php foreach (shs_grade_levels() as $g): ?><td class="text-end"><?= $grid[$code][$g] ?? 0 ?></td><?php endforeach; ?>
                            <td class="text-end fw-bold"><?= array_sum($grid[$code]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>Roster</h2>
            <p>Every enrollment record for <?= admin_e($termLabel) ?>.</p>
        </div>
    </div>
    <?php if ($records): ?>
        <table id="rosterTable" class="display">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Strand</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Admission</th>
                    <th>Status</th>
                    <th>Recorded by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r):
                    $name = roster_name($r); ?>
                    <tr>
                        <td data-order="<?= admin_e($name) ?>">
                            <a class="person-name" href="student.php?id=<?= (int) $r['student_row_id'] ?>"><?= admin_e($name) ?></a>
                            <span class="person-meta">ID <?= admin_e($r['student_id']) ?></span>
                        </td>
                        <td><?= admin_e($r['strand']) ?></td>
                        <td><?= admin_e($r['grade_level']) ?></td>
                        <td><?= admin_e($r['section_code'] ?: '—') ?></td>
                        <td><?= admin_e($admission[$r['admission_type']] ?? $r['admission_type']) ?></td>
                        <td><?= registrar_status_pill($r['status']) ?></td>
                        <td><?= admin_e($r['recorded_by_name'] ?: '—') ?></td>
                        <td>
                            <div class="ui-acts">
                                <button type="button" class="ui-act" title="Edit" aria-label="Edit record for <?= admin_e($name) ?>" <?= registrar_enroll_data([
                                    'student_row_id' => (int) $r['student_row_id'],
                                    'student_name'   => $name . ' — ' . $termLabel,
                                    'school_year'    => $r['school_year'],
                                    'term'           => $r['term'],
                                    'strand'         => $r['strand'],
                                    'grade'          => shs_grade_number($r['grade_level']),
                                    'section_code'   => $r['section_code'],
                                    'admission_type' => $r['admission_type'],
                                    'status'         => $r['status'],
                                    'remarks'        => $r['remarks'],
                                ], true) ?>><i class="bi bi-pencil"></i></button>
                                <a class="ui-act" href="student.php?id=<?= (int) $r['student_row_id'] ?>" title="Open record" aria-label="Open <?= admin_e($name) ?>"><i class="bi bi-folder2-open"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-clipboard"></i>
            <h3>No one is enrolled for this term yet</h3>
            <p><?= $missing ? 'Start with the students listed below.' : 'Add students from the Students page first.' ?></p>
        </div>
    <?php endif; ?>
</section>

<?php if ($missing): ?>
    <section class="ui-panel">
        <div class="ui-panel-head">
            <div>
                <h2>Not yet enrolled</h2>
                <p>Active student accounts with no record for this term. Their current strand and grade are filled in for you.</p>
            </div>
        </div>
        <table id="missingTable" class="display">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Strand</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($missing as $m):
                    $name = roster_name($m); ?>
                    <tr>
                        <td data-order="<?= admin_e($name) ?>">
                            <a class="person-name" href="student.php?id=<?= (int) $m['id'] ?>"><?= admin_e($name) ?></a>
                            <span class="person-meta">ID <?= admin_e($m['student_id']) ?></span>
                        </td>
                        <td><?= admin_e($m['course']) ?></td>
                        <td><?= admin_e($m['year_level']) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" <?= registrar_enroll_data([
                                'student_row_id' => (int) $m['id'],
                                'student_name'   => $name . ' — ' . $termLabel,
                                'school_year'    => $sy,
                                'term'           => $termName,
                                'strand'         => $m['course'],
                                'grade'          => shs_grade_number((string) $m['year_level']),
                            ]) ?>><i class="bi bi-clipboard-plus"></i> Enroll</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?php
registrar_enrollment_modal('enrollment', $picker);
ob_start(); ?>
<script>
    $(function () {
        $('#rosterTable').DataTable({ order: [[0, 'asc']], columnDefs: [{ targets: 7, orderable: false, searchable: false }] });
        $('#missingTable').DataTable({ order: [[0, 'asc']], pageLength: 10, columnDefs: [{ targets: 3, orderable: false, searchable: false }] });
    });
</script>
<?php registrar_page_end(ob_get_clean()); ?>
