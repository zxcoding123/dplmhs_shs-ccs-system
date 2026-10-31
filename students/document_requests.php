<?php
session_start();
if (!isset($_SESSION['student_id'])) {
	$_SESSION['STATUS'] = "STUDENT_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
	exit;
}
include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/records.php';

$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$studentRowId = (int) $stmt->fetchColumn();

$requests = $pdo->prepare("SELECT * FROM document_requests WHERE student_row_id = ? ORDER BY requested_at DESC");
$requests->execute([$studentRowId]);
$requests = $requests->fetchAll(PDO::FETCH_ASSOC);

$years = $pdo->prepare("SELECT DISTINCT school_year FROM enrollment_records WHERE student_row_id = ? ORDER BY school_year DESC");
$years->execute([$studentRowId]);
$years = $years->fetchAll(PDO::FETCH_COLUMN) ?: [enrollment_current_term($pdo)['school_year']];

$types = records_document_types();
$statuses = records_request_statuses();
$open = array_filter($requests, fn($r) => in_array($r['status'], ['pending', 'processing', 'ready'], true));
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Document Requests | DPLMHS-SHS Student Portal</title>
	<?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<style>
		.req-status { display: inline-block; padding: 0.15rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; background: #eef0f4; color: #333; }
		.req-status.is-warn { background: #fbf3dc; color: #8a6a12; }
		.req-status.is-info { background: #e8eefb; color: #2f5bb7; }
		.req-status.is-ok { background: #e6f3ea; color: #1f7a3a; }
		.req-status.is-alert { background: #fbe9ec; color: #b42338; }
		.req-note { display: block; margin-top: 0.25rem; font-size: 0.8rem; color: #5d6475; }
		.btn-csms { background-color: #121ba3; color: white; }
		.btn-csms:hover { border: 1px solid #1E28B6FF; color: white; }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php include('sidebar.php') ?>

		<div class="main">
			<?php include('topbar.php') ?>

			<main class="content">
				<div class="container-fluid p-0">
					<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
						<h1 class="h3 mb-0"><strong>Document</strong> Requests</h1>
						<button type="button" class="btn btn-csms ms-auto" data-bs-toggle="modal" data-bs-target="#requestModal" <?= count($open) >= 5 ? 'disabled title="You have 5 open requests"' : '' ?>>
							<i class="bi bi-plus-lg"></i> New request
						</button>
					</div>

					<div class="card shadow-sm">
						<div class="card-body">
							<p class="text-muted">Ask the registrar for a certificate or a copy of your records. You'll get a notification when it's ready for pick-up at the registrar's office.</p>
							<?php if ($requests): ?>
								<div class="table-responsive">
									<table class="table align-middle">
										<thead>
											<tr><th>Requested</th><th>Document</th><th>Purpose</th><th>Status</th><th></th></tr>
										</thead>
										<tbody>
											<?php foreach ($requests as $r):
												[$label, $tone] = $statuses[$r['status']]; ?>
												<tr>
													<td><?= date('M j, Y', strtotime($r['requested_at'])) ?></td>
													<td><?= $e($types[$r['document']]) ?><?= $r['school_year'] ? '<span class="req-note">S.Y. ' . $e($r['school_year']) . '</span>' : '' ?><?= $r['copies'] > 1 ? '<span class="req-note">' . (int) $r['copies'] . ' copies</span>' : '' ?></td>
													<td><?= $e($r['purpose']) ?></td>
													<td><span class="req-status <?= $tone ?>"><?= $e($label) ?></span><?= $r['registrar_note'] !== '' ? '<span class="req-note">' . $e($r['registrar_note']) . '</span>' : '' ?></td>
													<td class="text-end">
														<?php if ($r['status'] === 'pending'): ?>
															<form method="POST" action="processes/students/requests/cancel.php" class="d-inline" data-cancel>
																<input type="hidden" name="csrf" value="<?= $e(registrar_csrf_token()) ?>">
																<input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
																<button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
															</form>
														<?php endif; ?>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php else: ?>
								<div class="text-center text-muted py-4">
									<i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
									You haven't requested any documents yet.
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</main>
		</div>
	</div>

	<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form class="modal-content" method="POST" action="processes/students/requests/submit.php">
				<div class="modal-header">
					<h5 class="modal-title" id="requestModalLabel"><i class="bi bi-file-earmark-plus me-2"></i>Request a document</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="csrf" value="<?= $e(registrar_csrf_token()) ?>">
					<div class="mb-3">
						<label for="reqDocument" class="form-label">Document</label>
						<select class="form-select" id="reqDocument" name="document" required>
							<?php foreach ($types as $value => $label): ?><option value="<?= $value ?>"><?= $e($label) ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3" id="reqYearWrap" hidden>
						<label for="reqYear" class="form-label">School year</label>
						<select class="form-select" id="reqYear" name="school_year">
							<?php foreach ($years as $y): ?><option value="<?= $e($y) ?>">S.Y. <?= $e($y) ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="row g-3 mb-3">
						<div class="col-8">
							<label for="reqPurpose" class="form-label">Purpose</label>
							<select class="form-select" id="reqPurpose" name="purpose" required>
								<?php foreach (records_request_purposes() as $p): ?><option value="<?= $e($p) ?>"><?= $e($p) ?></option><?php endforeach; ?>
							</select>
						</div>
						<div class="col-4">
							<label for="reqCopies" class="form-label">Copies</label>
							<input type="number" class="form-control" id="reqCopies" name="copies" min="1" max="5" value="1" required>
						</div>
					</div>
					<label for="reqNotes" class="form-label">Notes <span class="text-muted">(optional)</span></label>
					<textarea class="form-control" id="reqNotes" name="notes" rows="2" maxlength="500" placeholder="Anything the registrar should know"></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-csms">Send request</button>
				</div>
			</form>
		</div>
	</div>

	<script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
	<script>
		const reqDocument = document.getElementById('reqDocument');
		const toggleYear = () => { document.getElementById('reqYearWrap').hidden = reqDocument.value !== 'sf9'; };
		reqDocument.addEventListener('change', toggleYear);
		toggleYear();

		document.querySelectorAll('form[data-cancel]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				if (form.dataset.confirmed) return;
				e.preventDefault();
				Swal.fire({ title: 'Cancel this request?', icon: 'question', showCancelButton: true, confirmButtonText: 'Cancel request', cancelButtonText: 'Keep it' })
					.then(r => { if (r.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); } });
			});
		});
	</script>
	<?php
	if (!empty($_SESSION['registrar_flash'])) {
		registrar_render_flash();
	}
	include('processes/server/alerts.php');
	?>
</body>

</html>
