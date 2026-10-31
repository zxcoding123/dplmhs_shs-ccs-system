<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../includes/grading.php';

$classId = (int) ($_GET['class'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$class) {
    registrar_redirect('grades.php', ['Not found', 'That class no longer exists.', 'error']);
}

$stmt = $pdo->prepare("
    SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.gender,
           g.midterm_grade, g.final_grade, g.overall_grade, g.status
    FROM students_enrollments se
    JOIN students s ON s.student_id = se.student_id
    LEFT JOIN student_grades g ON g.class_id = se.class_id AND g.student_id = se.student_id
    WHERE se.class_id = ?
    ORDER BY s.gender DESC, s.last_name, s.first_name
");
$stmt->execute([$classId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$state = grades_class_state($pdo, $classId);
[$stateLabel, $stateTone] = grades_state_label($state);
$history = grades_history($pdo, $classId);
$finals = array_filter(array_column($rows, 'overall_grade'), 'deped_is_number');
$passed = count(array_filter($finals, fn($g) => (float) $g >= DEPED_PASSING_GRADE));
$actionLabels = ['submit' => 'Submitted', 'accept' => 'Accepted and locked', 'return' => 'Returned to teacher', 'unlock' => 'Unlocked'];

$actions = '<a class="btn btn-ui-ghost" href="grades.php"><i class="bi bi-arrow-left"></i> All classes</a>';
if ($state === 'submitted') {
    $actions .= '<button type="button" class="btn btn-outline-danger" data-review="return"><i class="bi bi-arrow-return-left"></i> Return to teacher</button>'
        . '<button type="button" class="btn btn-ui-gold" data-review="accept"><i class="bi bi-lock"></i> Accept and lock</button>';
} elseif ($state === 'locked') {
    $actions .= '<button type="button" class="btn btn-outline-danger" data-review="unlock"><i class="bi bi-unlock"></i> Unlock for corrections</button>';
}

registrar_page_start($class['subject'] . ' · ' . $class['name']);
admin_page_header($class['subject'] . ' · ' . $class['name'], $class['teacher'] . ' · ' . $class['semester'] . ' · ' . $class['type'], 'bi-journal-check', $actions);

admin_stat_tiles([
    ['label' => 'Status', 'value' => $stateLabel, 'icon' => $state === 'locked' ? 'bi-lock' : 'bi-journal', 'tone' => $state === 'submitted' ? 'alert' : ($state === 'locked' ? 'ok' : 'muted')],
    ['label' => 'Students', 'value' => count($rows), 'icon' => 'bi-people'],
    ['label' => 'Passed', 'value' => $passed, 'icon' => 'bi-check2-circle', 'tone' => 'ok'],
    ['label' => 'Failed', 'value' => count($finals) - $passed, 'icon' => 'bi-x-circle', 'tone' => count($finals) - $passed ? 'alert' : 'muted'],
    ['label' => 'No final grade', 'value' => count($rows) - count($finals), 'icon' => 'bi-question-circle', 'tone' => 'muted'],
]);
?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>Grades</h2>
            <p>DepEd quarterly grades (60–100). The final grade is the average of both quarters; 75 passes.</p>
        </div>
    </div>
    <?php if ($rows): ?>
        <div class="ui-table-scroll">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Learner</th>
                        <th class="text-end">1st Quarter</th>
                        <th class="text-end">2nd Quarter</th>
                        <th class="text-end">Final grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $final = deped_display($r['overall_grade']); ?>
                        <tr>
                            <td><a class="person-name" href="student.php?id=<?= (int) $r['id'] ?>"><?= admin_e($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']) ?></a><span class="person-meta">ID <?= admin_e($r['student_id']) ?> · <?= admin_e($r['gender']) ?></span></td>
                            <?php foreach ([$r['midterm_grade'], $r['final_grade']] as $q): ?>
                                <td class="text-end <?= deped_flagged($q) ? 'text-danger' : '' ?>"><?= admin_e(deped_display($q)) ?></td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold <?= deped_flagged($final) ? 'text-danger' : '' ?>"><?= admin_e($final) ?></td>
                            <td><?= admin_e(deped_remarks($final) ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-people"></i>
            <h3>No students in this class</h3>
            <p>Learners join with the class code from their own portal.</p>
        </div>
    <?php endif; ?>
</section>

<section class="ui-panel">
    <div class="ui-panel-head"><div><h2>Review history</h2></div></div>
    <?php if ($history): ?>
        <ul class="ui-list">
            <?php foreach ($history as $h): ?>
                <li>
                    <span><strong><?= admin_e($actionLabels[$h['action']] ?? $h['action']) ?></strong> by <?= admin_e($h['actor_name']) ?> <span class="text-muted">(<?= admin_e($h['actor_type']) ?>)</span><?= $h['reason'] !== '' ? '<br><span class="text-muted small">' . admin_e($h['reason']) . '</span>' : '' ?></span>
                    <span class="text-muted small"><?= date('M j, Y g:i A', strtotime($h['created_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="ui-list-empty">Nothing submitted or reviewed yet.</div>
    <?php endif; ?>
</section>

<form id="reviewForm" method="POST" action="processes/grade_review.php" hidden>
    <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
    <input type="hidden" name="class_id" value="<?= $classId ?>">
    <input type="hidden" name="action" id="reviewAction">
    <input type="hidden" name="reason" id="reviewReason">
</form>

<?php ob_start(); ?>
<script>
    document.querySelectorAll('[data-review]').forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.dataset.review;
            const form = document.getElementById('reviewForm');
            document.getElementById('reviewAction').value = action;
            const options = action === 'accept'
                ? { title: 'Accept and lock these grades?', text: 'They become final. The teacher can no longer change them unless they are unlocked.', icon: 'question', confirmButtonText: 'Accept and lock' }
                : {
                    title: action === 'unlock' ? 'Unlock these grades?' : 'Return these grades to the teacher?',
                    input: 'textarea', inputLabel: 'Reason (sent to the teacher)',
                    inputValidator: v => !v.trim() ? 'Please give a reason.' : undefined,
                    confirmButtonText: action === 'unlock' ? 'Unlock' : 'Return'
                };
            Swal.fire(Object.assign({ showCancelButton: true, reverseButtons: true }, options)).then(function (result) {
                if (!result.isConfirmed) return;
                document.getElementById('reviewReason').value = result.value === true ? '' : (result.value || '');
                form.submit();
            });
        });
    });
</script>
<?php registrar_page_end(ob_get_clean()); ?>
