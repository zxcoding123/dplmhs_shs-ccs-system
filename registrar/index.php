<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$term = enrollment_current_term($pdo);
$params = [$term['school_year'], $term['term']];

$totalStudents = (int) admin_count($pdo, "SELECT COUNT(*) FROM students");
$enrolled = (int) admin_count($pdo, "SELECT COUNT(*) FROM enrollment_records WHERE school_year = ? AND term = ? AND status = 'enrolled'", $params);
$notEnrolled = (int) admin_count($pdo, "SELECT COUNT(*) FROM students s JOIN users u ON u.id = s.user_id AND u.status = 'active'
    WHERE NOT EXISTS (SELECT 1 FROM enrollment_records er WHERE er.student_row_id = s.id AND er.school_year = ? AND er.term = ?)", $params);
$noSection = (int) admin_count($pdo, "SELECT COUNT(*) FROM enrollment_records WHERE school_year = ? AND term = ? AND status = 'enrolled' AND section_code IS NULL", $params);
$missingLrn = (int) admin_count($pdo, "SELECT COUNT(*) FROM students WHERE lrn IS NULL OR lrn = ''");
$gradesWaiting = (int) admin_count($pdo, "SELECT COUNT(DISTINCT class_id) FROM student_grades WHERE status = 'for_approval'");
$requestsPending = (int) admin_count($pdo, "SELECT COUNT(*) FROM document_requests WHERE status = 'pending'");

$recent = $pdo->query("
    SELECT er.*, s.first_name, s.last_name
    FROM enrollment_records er JOIN students s ON s.id = er.student_row_id
    ORDER BY er.updated_at DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$hasSemester = $term['semester_id'] !== null;

registrar_page_start('Dashboard');

admin_page_header('Registrar dashboard', $term['label'], 'bi-speedometer2',
    '<a class="btn btn-ui-ghost" href="enrollment.php"><i class="bi bi-clipboard-check"></i> Enrollment</a>'
    . '<a class="btn btn-ui-gold" href="students.php"><i class="bi bi-people"></i> Students</a>');
?>

<?php if (!$hasSemester): ?>
    <div class="ui-callout">
        <i class="bi bi-info-circle"></i>
        <div>No semester is set as current in the admin portal, so the term was worked out from today's date. Ask the admin to set the current semester in <strong>Semester Management</strong>.</div>
    </div>
<?php endif; ?>

<?php admin_stat_tiles([
    ['label' => 'Student records', 'value' => $totalStudents, 'icon' => 'bi-people'],
    ['label' => 'Enrolled this term', 'value' => $enrolled, 'icon' => 'bi-clipboard-check', 'tone' => 'ok'],
    ['label' => 'Not yet enrolled', 'value' => $notEnrolled, 'icon' => 'bi-hourglass-split', 'tone' => $notEnrolled ? 'alert' : 'muted', 'hint' => 'Active accounts with no record'],
    ['label' => 'Enrolled, no section', 'value' => $noSection, 'icon' => 'bi-grid-3x3-gap', 'tone' => $noSection ? 'alert' : 'muted'],
    ['label' => 'Missing LRN', 'value' => $missingLrn, 'icon' => 'bi-123', 'tone' => $missingLrn ? 'alert' : 'muted'],
]); ?>

<?php if ($gradesWaiting || $requestsPending): ?>
    <div class="ui-callout is-alert">
        <i class="bi bi-bell"></i>
        <div>
            <?php if ($gradesWaiting): ?><a href="grades.php"><strong><?= $gradesWaiting ?></strong> class<?= $gradesWaiting === 1 ? '' : 'es' ?> waiting for grade review</a><?php endif; ?>
            <?= $gradesWaiting && $requestsPending ? ' · ' : '' ?>
            <?php if ($requestsPending): ?><a href="requests.php"><strong><?= $requestsPending ?></strong> pending document request<?= $requestsPending === 1 ? '' : 's' ?></a><?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>Recent enrollment activity</h2>
            <p>The last records added or changed, by you, other registrars or advisers.</p>
        </div>
    </div>
    <?php if ($recent): ?>
        <div class="ui-table-scroll">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Term</th>
                        <th>Placement</th>
                        <th>Status</th>
                        <th>By</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><a class="person-name" href="student.php?id=<?= (int) $r['student_row_id'] ?>"><?= admin_e($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                            <td><?= admin_e($r['school_year'] . ' · ' . $r['term']) ?></td>
                            <td><?= admin_e($r['strand'] . ' ' . $r['grade_level'] . ($r['section_code'] ? ' · ' . $r['section_code'] : '')) ?></td>
                            <td><?= registrar_status_pill($r['status']) ?></td>
                            <td><?= admin_e($r['recorded_by_name'] ?: '—') ?></td>
                            <td><?= date('M j, g:i A', strtotime($r['updated_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-clipboard"></i>
            <h3>Nothing recorded yet</h3>
            <p>Add students on the <a href="students.php">Students</a> page, then enroll them for the term on the <a href="enrollment.php">Enrollment</a> page.</p>
        </div>
    <?php endif; ?>
</section>

<?php registrar_page_end(); ?>
