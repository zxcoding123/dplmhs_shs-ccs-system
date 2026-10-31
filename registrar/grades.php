<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../includes/grading.php';

// One row per class with how many of its grades are in each state
$classes = $pdo->query("
    SELECT c.id, c.name, c.subject, c.type, c.teacher, c.semester, c.is_archived,
           COUNT(se.id) AS students,
           (SELECT COUNT(*) FROM student_grades g WHERE g.class_id = c.id AND g.status = 'for_approval') AS submitted,
           (SELECT COUNT(*) FROM student_grades g WHERE g.class_id = c.id AND g.status = 'accepted') AS accepted,
           (SELECT COUNT(*) FROM student_grades g WHERE g.class_id = c.id AND g.status IN ('saved', 'returned', 'rejected')) AS draft,
           (SELECT MAX(created_at) FROM grade_reviews r WHERE r.class_id = c.id) AS last_activity
    FROM classes c
    LEFT JOIN students_enrollments se ON se.class_id = c.id
    WHERE c.status = 'accepted'
    GROUP BY c.id
    ORDER BY c.is_archived, c.name, c.subject
")->fetchAll(PDO::FETCH_ASSOC);

function sheet_state(array $c): string
{
    if ($c['accepted'] > 0) return 'locked';
    if ($c['submitted'] > 0) return 'submitted';
    if ($c['draft'] > 0) return 'draft';
    return 'none';
}

$counts = array_count_values(array_map('sheet_state', $classes));

registrar_page_start('Grade review');
admin_page_header('Grade review', 'Accept submitted grades to lock them, return them to the teacher, or unlock them for corrections', 'bi-journal-check');

admin_stat_tiles([
    ['label' => 'Waiting for review', 'value' => $counts['submitted'] ?? 0, 'icon' => 'bi-hourglass-split', 'tone' => !empty($counts['submitted']) ? 'alert' : 'muted', 'filter' => 'Waiting for review', 'filter_col' => 'Grades'],
    ['label' => 'Accepted · locked', 'value' => $counts['locked'] ?? 0, 'icon' => 'bi-lock', 'tone' => 'ok', 'filter' => 'Accepted · locked', 'filter_col' => 'Grades'],
    ['label' => 'Teacher editing', 'value' => $counts['draft'] ?? 0, 'icon' => 'bi-pencil', 'tone' => 'muted', 'filter' => 'Teacher editing', 'filter_col' => 'Grades'],
    ['label' => 'Not started', 'value' => $counts['none'] ?? 0, 'icon' => 'bi-dash-circle', 'tone' => 'muted', 'filter' => 'Not started', 'filter_col' => 'Grades'],
]);
?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>Classes</h2>
            <p>The admin can review grades too; both of you see the same state and history.</p>
        </div>
    </div>
    <?php if ($classes): ?>
        <table id="gradeTable" class="display">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Semester</th>
                    <th>Students</th>
                    <th>Grades</th>
                    <th>Last review step</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $c):
                    [$label, $tone] = grades_state_label(sheet_state($c)); ?>
                    <tr>
                        <td><?= admin_e($c['name']) ?><?= (int) $c['is_archived'] ? ' <span class="ui-tag">Archived</span>' : '' ?></td>
                        <td><?= admin_e($c['subject']) ?><span class="person-meta"><?= admin_e($c['type']) ?></span></td>
                        <td><?= admin_e($c['teacher']) ?></td>
                        <td><?= admin_e($c['semester']) ?></td>
                        <td><?= (int) $c['students'] ?></td>
                        <td><span class="ui-pill <?= $tone ?>"><?= admin_e($label) ?></span></td>
                        <td data-order="<?= $c['last_activity'] ? strtotime($c['last_activity']) : 0 ?>"><?= $c['last_activity'] ? date('M j, Y', strtotime($c['last_activity'])) : '—' ?></td>
                        <td><div class="ui-acts"><a class="ui-act" href="grade_sheet.php?class=<?= (int) $c['id'] ?>" title="Open grade sheet" aria-label="Open grades for <?= admin_e($c['subject'] . ' ' . $c['name']) ?>"><i class="bi bi-folder2-open"></i></a></div></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-journal"></i>
            <h3>No classes yet</h3>
            <p>Classes appear here once the admin approves them.</p>
        </div>
    <?php endif; ?>
</section>

<?php ob_start(); ?>
<script>
    $(function () {
        $('#gradeTable').DataTable({ order: [[5, 'desc'], [0, 'asc']], columnDefs: [{ targets: 7, orderable: false, searchable: false }] });
    });
</script>
<?php registrar_page_end(ob_get_clean()); ?>
