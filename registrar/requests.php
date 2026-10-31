<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../includes/records.php';

$requests = $pdo->query("
    SELECT r.*, s.student_id, s.first_name, s.middle_name, s.last_name, s.course, s.year_level
    FROM document_requests r JOIN students s ON s.id = r.student_row_id
    ORDER BY FIELD(r.status, 'pending', 'processing', 'ready', 'released', 'declined'), r.requested_at
")->fetchAll(PDO::FETCH_ASSOC);

$types = records_document_types();
$statuses = records_request_statuses();
$byStatus = array_count_values(array_column($requests, 'status'));

registrar_page_start('Document requests');
admin_page_header('Document requests', 'Certificates and records students asked for from their portal', 'bi-file-earmark-text');

admin_stat_tiles([
    ['label' => 'Pending', 'value' => $byStatus['pending'] ?? 0, 'icon' => 'bi-inbox', 'tone' => !empty($byStatus['pending']) ? 'alert' : 'muted', 'filter' => 'Pending', 'filter_col' => 'Status'],
    ['label' => 'Processing', 'value' => $byStatus['processing'] ?? 0, 'icon' => 'bi-gear', 'tone' => 'muted', 'filter' => 'Processing', 'filter_col' => 'Status'],
    ['label' => 'Ready for pick-up', 'value' => $byStatus['ready'] ?? 0, 'icon' => 'bi-bag-check', 'tone' => 'ok', 'filter' => 'Ready for pick-up', 'filter_col' => 'Status'],
    ['label' => 'Released', 'value' => $byStatus['released'] ?? 0, 'icon' => 'bi-check2-all', 'tone' => 'muted', 'filter' => 'Released', 'filter_col' => 'Status'],
]);
?>

<section class="ui-panel">
    <div class="ui-panel-head">
        <div>
            <h2>All requests</h2>
            <p>Print the document, then mark it ready for pick-up. The student is notified at every step.</p>
        </div>
    </div>
    <?php if ($requests): ?>
        <table id="requestTable" class="display">
            <thead>
                <tr>
                    <th>Requested</th>
                    <th>Student</th>
                    <th>Document</th>
                    <th>Purpose</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r):
                    [$label, $tone] = $statuses[$r['status']];
                    $name = $r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name'];
                    $printUrl = 'print.php?' . http_build_query(['doc' => $r['document'], 'student' => $r['student_row_id'], 'request' => $r['id'], 'sy' => $r['school_year']]); ?>
                    <tr>
                        <td data-order="<?= strtotime($r['requested_at']) ?>"><?= date('M j, Y', strtotime($r['requested_at'])) ?><span class="person-meta">#<?= (int) $r['id'] ?></span></td>
                        <td><a class="person-name" href="student.php?id=<?= (int) $r['student_row_id'] ?>"><?= admin_e($name) ?></a><span class="person-meta">ID <?= admin_e($r['student_id']) ?> · <?= admin_e($r['course'] . ' ' . $r['year_level']) ?></span></td>
                        <td><?= admin_e($types[$r['document']]) ?><?= $r['school_year'] ? '<span class="person-meta">S.Y. ' . admin_e($r['school_year']) . '</span>' : '' ?><?= $r['copies'] > 1 ? '<span class="person-meta">' . (int) $r['copies'] . ' copies</span>' : '' ?></td>
                        <td><?= admin_e($r['purpose']) ?><?= $r['notes'] !== '' ? '<span class="person-meta">' . admin_e($r['notes']) . '</span>' : '' ?></td>
                        <td><span class="ui-pill <?= $tone ?>"><?= admin_e($label) ?></span><?= $r['registrar_note'] !== '' ? '<span class="person-meta">' . admin_e($r['registrar_note']) . '</span>' : '' ?></td>
                        <td>
                            <div class="ui-acts">
                                <a class="ui-act" href="<?= admin_e($printUrl) ?>" title="Open printable document" aria-label="Print <?= admin_e($types[$r['document']]) ?> for <?= admin_e($name) ?>"><i class="bi bi-printer"></i></a>
                                <button type="button" class="ui-act" title="Update status" aria-label="Update request #<?= (int) $r['id'] ?>"
                                    data-request="<?= (int) $r['id'] ?>" data-status="<?= admin_e($r['status']) ?>" data-note="<?= admin_e($r['registrar_note']) ?>"
                                    data-label="<?= admin_e($types[$r['document']] . ' for ' . $r['first_name'] . ' ' . $r['last_name']) ?>"><i class="bi bi-pencil"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="ui-empty">
            <i class="bi bi-inbox"></i>
            <h3>No requests yet</h3>
            <p>Students request certificates and records from <strong>Document requests</strong> in their portal.</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="processes/request_update.php">
            <div class="modal-header">
                <span class="ui-modal-icon"><i class="bi bi-file-earmark-text"></i></span>
                <h1 class="modal-title" id="requestModalLabel">Update request</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= admin_e(registrar_csrf_token()) ?>">
                <input type="hidden" name="id" id="requestId">
                <p class="fw-semibold mb-3" id="requestLabel"></p>
                <label for="requestStatus" class="form-label">Status</label>
                <select class="form-select mb-3" id="requestStatus" name="status">
                    <?php foreach ($statuses as $value => [$label]): ?><option value="<?= $value ?>"><?= admin_e($label) ?></option><?php endforeach; ?>
                </select>
                <label for="requestNote" class="form-label">Note to the student <span class="text-muted fw-normal">(required when declining)</span></label>
                <input type="text" class="form-control" id="requestNote" name="registrar_note" maxlength="500" placeholder="e.g. Pick up at the registrar's office, bring your school ID">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
    $(function () {
        $('#requestTable').DataTable({ order: [], columnDefs: [{ targets: 5, orderable: false, searchable: false }] });
    });
    document.addEventListener('click', function (e) {
        const button = e.target.closest('[data-request]');
        if (!button) return;
        document.getElementById('requestId').value = button.dataset.request;
        document.getElementById('requestStatus').value = button.dataset.status;
        document.getElementById('requestNote').value = button.dataset.note;
        document.getElementById('requestLabel').textContent = button.dataset.label;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('requestModal')).show();
    });
</script>
<?php registrar_page_end(ob_get_clean()); ?>
