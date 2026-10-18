<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';

$semesters = $pdo->query("SELECT * FROM semester ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Classes and subjects linked to each semester (by name, as the rest of the system stores it)
$classesBySemester = [];
foreach ($pdo->query("SELECT name, type, subject, teacher, semester FROM classes ORDER BY name") as $row) {
	$classesBySemester[$row['semester']][] = $row;
}
$subjectsBySemester = [];
foreach ($pdo->query("SELECT name, type, code, semester FROM subjects ORDER BY name") as $row) {
	$subjectsBySemester[$row['semester']][] = $row;
}
$archiveReasons = [];
foreach ($pdo->query("SELECT semester_id, archive_reason, archived_at FROM archived_semesters") as $row) {
	$archiveReasons[$row['semester_id']] = $row;
}

$current = admin_count($pdo, "SELECT semester FROM current_semester LIMIT 1", [], null);
$active = null;
$counts = ['active' => 0, 'inactive' => 0, 'archived' => 0];
foreach ($semesters as $s) {
	$counts[$s['status']] = ($counts[$s['status']] ?? 0) + 1;
	if ($s['status'] === 'active') $active = $s;
}

// Progress of the active semester
$progress = null;
if ($active) {
	$start = strtotime($active['start_date']);
	$end = strtotime($active['end_date']);
	$today = strtotime('today');
	$total = max(1, ($end - $start) / 86400);
	$progress = [
		'pct' => (int) max(0, min(100, round(($today - $start) / 86400 / $total * 100))),
		'state' => $today < $start ? 'upcoming' : ($today > $end ? 'ended' : 'running'),
		'days' => (int) abs(round((($today < $start ? $start : $end) - $today) / 86400)),
	];
}

$statusPill = ['active' => 'is-ok', 'inactive' => 'is-warn', 'archived' => ''];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Semesters | DPLMHS-SHS Admin Portal</title>
	<?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="css/app.css" rel="stylesheet">
	<link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
	<style>
		.term-current { display: grid; grid-template-columns: 1fr auto; gap: 1rem 1.5rem; align-items: center; }
		.term-current h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--ui-navy); }
		.term-current p { margin: 0.15rem 0 0; color: var(--ui-muted); font-size: 0.88rem; }
		.term-bar { grid-column: 1 / -1; height: 10px; border-radius: 5px; background: var(--ui-soft); overflow: hidden; }
		.term-bar span { display: block; height: 100%; border-radius: 5px; background: var(--ui-blue); }
		.term-name { font-weight: 700; color: var(--ui-navy); }
		.term-desc { display: block; max-width: 320px; font-size: 0.8rem; color: var(--ui-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		@media (max-width: 575.98px) { .term-current { grid-template-columns: 1fr; } }
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
					<?php admin_page_header('Semesters', 'The school calendar every class and subject is attached to', 'bi-calendar3',
						'<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createSemesterModal"><i class="bi bi-plus-lg"></i> New semester</button>'); ?>

					<?php if (!$active): ?>
						<div class="ui-callout is-alert">
							<i class="bi bi-exclamation-triangle-fill"></i>
							<div><b>No semester is active.</b> Teachers can't create classes and you can't add subjects until one is. Use <i class="bi bi-play-circle"></i> on a semester below to make it active.</div>
						</div>
					<?php else: ?>
						<section class="ui-panel">
							<div class="term-current">
								<div>
									<span class="ui-pill is-ok">Active semester</span>
									<h2 class="mt-2"><?= admin_e($active['name']) ?> · S.Y. <?= (int) $active['school_year'] ?>–<?= (int) $active['school_year'] + 1 ?></h2>
									<p>
										<?= date('M j, Y', strtotime($active['start_date'])) ?> – <?= date('M j, Y', strtotime($active['end_date'])) ?> ·
										<?php if ($progress['state'] === 'upcoming'): ?>starts in <?= $progress['days'] ?> days
										<?php elseif ($progress['state'] === 'ended'): ?><b class="text-danger">ended <?= $progress['days'] ?> days ago</b> — activate the next semester
										<?php else: ?><?= $progress['days'] ?> days remaining<?php endif; ?>
										<?php if ($current && $current !== $active['name']): ?> · <span class="text-danger">dashboard shows "<?= admin_e($current) ?>" as current</span><?php endif; ?>
									</p>
								</div>
								<div class="ui-stat-value"><?= $progress['pct'] ?>%</div>
								<div class="term-bar" role="progressbar" aria-valuenow="<?= $progress['pct'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Semester progress"><span style="width: <?= $progress['pct'] ?>%"></span></div>
							</div>
						</section>
					<?php endif; ?>

					<?php admin_stat_tiles([
						['label' => 'Semesters', 'value' => count($semesters), 'icon' => 'bi-calendar3', 'filter' => ''],
						['label' => 'Active', 'value' => $counts['active'], 'icon' => 'bi-play-circle', 'tone' => 'ok', 'filter' => 'Active', 'filter_col' => 'Status'],
						['label' => 'Inactive', 'value' => $counts['inactive'], 'icon' => 'bi-pause-circle', 'tone' => 'muted', 'filter' => 'Inactive', 'filter_col' => 'Status'],
						['label' => 'Archived', 'value' => $counts['archived'], 'icon' => 'bi-archive', 'tone' => 'muted', 'filter' => 'Archived', 'filter_col' => 'Status'],
					]); ?>

					<section class="ui-panel">
						<?php if ($semesters): ?>
							<div class="ui-panel-head">
								<div>
									<h2>All semesters</h2>
									<p>Only one semester can be active. Archive a finished semester to keep it for records.</p>
								</div>
							</div>
							<table id="semesters" class="display">
								<thead>
									<tr>
										<th>Semester</th>
										<th>School year</th>
										<th>Dates</th>
										<th>Classes</th>
										<th>Subjects</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($semesters as $s):
										$id = (int) $s['id'];
										$days = (int) round((strtotime($s['end_date']) - strtotime($s['start_date'])) / 86400) + 1; ?>
										<tr>
											<td>
												<span class="term-name"><?= admin_e($s['name']) ?></span>
												<span class="term-desc"><?= admin_e($s['description'] ?: 'No description') ?></span>
											</td>
											<td data-order="<?= (int) $s['school_year'] ?>"><?= (int) $s['school_year'] ?>–<?= (int) $s['school_year'] + 1 ?></td>
											<td data-order="<?= strtotime($s['start_date']) ?>">
												<?= date('M j', strtotime($s['start_date'])) ?> – <?= date('M j, Y', strtotime($s['end_date'])) ?>
												<span class="term-desc"><?= $days ?> days</span>
											</td>
											<td><?= count($classesBySemester[$s['name']] ?? []) ?></td>
											<td><?= count($subjectsBySemester[$s['name']] ?? []) ?></td>
											<td><span class="ui-pill <?= $statusPill[$s['status']] ?? '' ?>"><?= admin_e(ucfirst($s['status'])) ?></span></td>
											<td>
												<div class="ui-acts">
													<button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#viewModal<?= $id ?>" title="View" aria-label="View"><i class="bi bi-eye"></i></button>
													<?php if ($s['status'] !== 'archived'): ?>
														<button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
													<?php endif; ?>
													<?php if ($s['status'] === 'inactive'): ?>
														<button type="button" class="ui-act is-ok" onclick="activateSemester(<?= $id ?>, <?= admin_e(json_encode($s['name'])) ?>)" title="Make active" aria-label="Make active"><i class="bi bi-play-circle"></i></button>
														<button type="button" class="ui-act is-warn" onclick="openArchive(<?= $id ?>, <?= admin_e(json_encode($s['name'])) ?>)" title="Archive" aria-label="Archive"><i class="bi bi-archive"></i></button>
													<?php elseif ($s['status'] === 'active'): ?>
														<button type="button" class="ui-act is-warn" onclick="deactivateSemester(<?= $id ?>, <?= admin_e(json_encode($s['name'])) ?>)" title="Make inactive" aria-label="Make inactive"><i class="bi bi-pause-circle"></i></button>
													<?php endif; ?>
													<button type="button" class="ui-act is-danger" onclick="confirmDeletion(<?= $id ?>, <?= admin_e(json_encode($s['name'])) ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<div class="ui-empty">
								<i class="bi bi-calendar3"></i>
								<h3>No semesters yet</h3>
								<p>Create the first semester of the school year to start adding subjects and classes.</p>
								<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createSemesterModal"><i class="bi bi-plus-lg"></i> New semester</button>
							</div>
						<?php endif; ?>
					</section>
				</div>
			</main>
		</div>
	</div>

	<!-- Create semester -->
	<div class="modal fade" id="createSemesterModal" tabindex="-1" aria-labelledby="createSemesterLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form class="modal-content" action="processes/admin/semester/add.php" method="POST" data-semester-form>
				<div class="modal-header">
					<span class="ui-modal-icon"><i class="bi bi-calendar-plus"></i></span>
					<h1 class="modal-title" id="createSemesterLabel">New semester</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-7">
							<label for="semesterName" class="form-label">Semester name</label>
							<input type="text" class="form-control" id="semesterName" name="name" list="semesterNames" placeholder="e.g. 1st Semester" required>
							<datalist id="semesterNames"><option value="1st Semester"><option value="2nd Semester"><option value="Summer Term"></datalist>
						</div>
						<div class="col-md-5">
							<label for="schoolYear" class="form-label">School year starts</label>
							<input type="number" class="form-control" id="schoolYear" name="schoolYear" min="2000" max="2100" value="<?= date('Y') ?>" required data-year>
						</div>
						<div class="col-6">
							<label for="startDate" class="form-label">Start date</label>
							<input type="date" class="form-control" id="startDate" name="start_date" required data-start>
						</div>
						<div class="col-6">
							<label for="endDate" class="form-label">End date</label>
							<input type="date" class="form-control" id="endDate" name="end_date" required data-end>
						</div>
						<div class="col-12">
							<small class="text-muted" data-date-hint>Both dates must fall within the school year's starting calendar year.</small>
						</div>
						<div class="col-12">
							<label for="description" class="form-label">Description</label>
							<textarea class="form-control" id="description" name="description" rows="3" required></textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create semester</button>
				</div>
			</form>
		</div>
	</div>

	<?php foreach ($semesters as $s):
		$id = (int) $s['id'];
		$classes = $classesBySemester[$s['name']] ?? [];
		$subjects = $subjectsBySemester[$s['name']] ?? []; ?>

		<!-- View semester -->
		<div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewSemesterLabel<?= $id ?>" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<span class="ui-modal-icon"><i class="bi bi-calendar3"></i></span>
						<h1 class="modal-title" id="viewSemesterLabel<?= $id ?>"><?= admin_e($s['name']) ?> · S.Y. <?= (int) $s['school_year'] ?>–<?= (int) $s['school_year'] + 1 ?></h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<dl class="ui-dl">
							<dt>Status</dt>
							<dd><span class="ui-pill <?= $statusPill[$s['status']] ?? '' ?>"><?= admin_e(ucfirst($s['status'])) ?></span></dd>
							<?php if ($s['status'] === 'archived' && isset($archiveReasons[$id])): ?>
								<dt>Archive reason</dt>
								<dd><?= admin_e($archiveReasons[$id]['archive_reason']) ?> <span class="text-muted">· <?= date('M j, Y', strtotime($archiveReasons[$id]['archived_at'])) ?></span></dd>
							<?php endif; ?>
							<dt>Dates</dt>
							<dd><?= date('F j, Y', strtotime($s['start_date'])) ?> – <?= date('F j, Y', strtotime($s['end_date'])) ?></dd>
							<dt>Description</dt>
							<dd><?= admin_e($s['description'] ?: '—') ?></dd>
							<dt>Created</dt>
							<dd><?= date('M j, Y g:i A', strtotime($s['created_at'])) ?></dd>
							<dt>Last updated</dt>
							<dd><?= date('M j, Y g:i A', strtotime($s['updated_at'])) ?></dd>
						</dl>

						<p class="ui-section-label">Classes (<?= count($classes) ?>)</p>
						<?php if ($classes): ?>
							<ul class="ui-list">
								<?php foreach ($classes as $c): ?>
									<li><span><b><?= admin_e($c['name']) ?></b> · <?= admin_e($c['subject']) ?> <span class="ui-tag"><?= admin_e($c['type']) ?></span></span><span class="text-muted"><?= admin_e($c['teacher'] ?: 'No teacher') ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<div class="ui-list-empty">No classes in this semester.</div>
						<?php endif; ?>

						<p class="ui-section-label">Subjects (<?= count($subjects) ?>)</p>
						<?php if ($subjects): ?>
							<ul class="ui-list">
								<?php foreach ($subjects as $sub): ?>
									<li><span><b><?= admin_e($sub['code']) ?></b> · <?= admin_e($sub['name']) ?></span><span class="ui-tag"><?= admin_e($sub['type']) ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<div class="ui-list-empty">No subjects in this semester.</div>
						<?php endif; ?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<?php if ($s['status'] !== 'archived'): ?>
							<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>"><i class="bi bi-pencil"></i> Edit</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ($s['status'] !== 'archived'): ?>
			<!-- Edit semester -->
			<div class="modal fade" id="editModal<?= $id ?>" tabindex="-1" aria-labelledby="editSemesterLabel<?= $id ?>" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<form class="modal-content" action="processes/admin/semester/updateSemester.php" method="POST" data-semester-form>
						<div class="modal-header">
							<span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
							<h1 class="modal-title" id="editSemesterLabel<?= $id ?>">Edit <?= admin_e($s['name']) ?></h1>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">
							<input type="hidden" name="id" value="<?= $id ?>">
							<div class="row g-3">
								<div class="col-md-7">
									<label for="name<?= $id ?>" class="form-label">Semester name</label>
									<input type="text" class="form-control" id="name<?= $id ?>" name="name" value="<?= admin_e($s['name']) ?>" required>
								</div>
								<div class="col-md-5">
									<label for="schoolYear<?= $id ?>" class="form-label">School year starts</label>
									<input type="number" class="form-control" id="schoolYear<?= $id ?>" name="schoolYear" min="2000" max="2100" value="<?= (int) $s['school_year'] ?>" required data-year>
								</div>
								<div class="col-6">
									<label for="start<?= $id ?>" class="form-label">Start date</label>
									<input type="date" class="form-control" id="start<?= $id ?>" name="start_date" value="<?= admin_e($s['start_date']) ?>" required data-start>
								</div>
								<div class="col-6">
									<label for="end<?= $id ?>" class="form-label">End date</label>
									<input type="date" class="form-control" id="end<?= $id ?>" name="end_date" value="<?= admin_e($s['end_date']) ?>" required data-end>
								</div>
								<div class="col-12">
									<label for="description<?= $id ?>" class="form-label">Description</label>
									<textarea class="form-control" id="description<?= $id ?>" name="description" rows="3"><?= admin_e($s['description']) ?></textarea>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
						</div>
					</form>
				</div>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>

	<!-- Archive semester (shared, filled in by openArchive) -->
	<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form class="modal-content" action="processes/admin/semester/archive.php" method="POST" id="archiveForm">
				<div class="modal-header">
					<span class="ui-modal-icon"><i class="bi bi-archive"></i></span>
					<h1 class="modal-title" id="archiveLabel">Archive semester</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="id" id="archiveId">
					<input type="hidden" name="name" id="archiveName">
					<input type="hidden" name="archive_reason" id="archiveReason">
					<p>Archiving <b id="archiveTitle"></b> also archives its classes and subjects. They stay available for records and grade history.</p>
					<label for="archiveReasonSelect" class="form-label">Reason</label>
					<select class="form-select mb-3" id="archiveReasonSelect" required>
						<option value="End of academic year">End of academic year</option>
						<option value="Class discontinued">Class discontinued</option>
						<option value="Administrative decision">Administrative decision</option>
						<option value="Other">Other…</option>
					</select>
					<div class="d-none" id="archiveOtherWrap">
						<label for="archiveOther" class="form-label">Describe the reason</label>
						<textarea class="form-control" id="archiveOther" rows="2"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary"><i class="bi bi-archive"></i> Archive</button>
				</div>
			</form>
		</div>
	</div>

	<script src="js/app.js"></script>
	<script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
	<script>
		const activeSemester = <?= json_encode($active ? ['id' => (int) $active['id'], 'name' => $active['name']] : null) ?>;

		$(function () {
			$('#semesters').DataTable({
				order: [[2, 'desc']],
				columnDefs: [{ targets: 6, orderable: false, searchable: false }]
			});
		});

		// Dates: end after start, both inside the school year (the server enforces the same rule)
		document.querySelectorAll('form[data-semester-form]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				const year = form.querySelector('[data-year]').value;
				const start = form.querySelector('[data-start]').value;
				const end = form.querySelector('[data-end]').value;
				let problem = '';
				if (end <= start) problem = 'The end date must be after the start date.';
				else if (start.slice(0, 4) !== year || end.slice(0, 4) !== year) problem = 'Both dates must be in ' + year + ', the school year this semester belongs to.';
				if (problem) {
					e.preventDefault();
					Swal.fire('Check the dates', problem, 'warning');
				}
			});
		});

		function postForm(url, body) {
			return fetch(url, { method: 'POST', body: new URLSearchParams(body) }).then(r => r.json());
		}

		// Activating switches over from the current active semester in one step
		function activateSemester(id, name) {
			Swal.fire({
				title: 'Make ' + name + ' active?',
				html: activeSemester
					? '<b>' + $('<div>').text(activeSemester.name).html() + '</b> is active now and will be set to inactive. New classes and subjects will go into ' + $('<div>').text(name).html() + '.'
					: 'New classes and subjects will go into this semester, and the dashboard will show it as current.',
				icon: 'question',
				showCancelButton: true,
				reverseButtons: true,
				confirmButtonText: 'Make active'
			}).then(function (result) {
				if (!result.isConfirmed) return;
				const ready = activeSemester ? postForm('processes/admin/semester/updateStatus.php', { id: activeSemester.id, status: 'inactive' }) : Promise.resolve({ success: true });
				ready.then(function (data) {
					if (!data.success) throw new Error(data.message);
					window.location.href = 'processes/admin/semester/setActiveSemester.php?id=' + id;
				}).catch(function (error) {
					Swal.fire('Error', error.message || 'The semester could not be activated.', 'error');
				});
			});
		}

		function deactivateSemester(id, name) {
			Swal.fire({
				title: 'Make ' + name + ' inactive?',
				text: 'No semester will be active until you activate another one. Teachers won\'t be able to create classes meanwhile.',
				icon: 'warning',
				showCancelButton: true,
				reverseButtons: true,
				confirmButtonText: 'Make inactive'
			}).then(function (result) {
				if (!result.isConfirmed) return;
				postForm('processes/admin/semester/updateStatus.php', { id: id, status: 'inactive' })
					.then(function (data) {
						if (!data.success) throw new Error(data.message);
						location.reload();
					})
					.catch(function (error) { Swal.fire('Error', error.message || 'The semester could not be deactivated.', 'error'); });
			});
		}

		// Archive modal
		const archiveModal = document.getElementById('archiveModal');
		const reasonSelect = document.getElementById('archiveReasonSelect');
		const otherWrap = document.getElementById('archiveOtherWrap');
		const otherText = document.getElementById('archiveOther');

		function openArchive(id, name) {
			document.getElementById('archiveId').value = id;
			document.getElementById('archiveName').value = name;
			document.getElementById('archiveTitle').textContent = name;
			reasonSelect.value = 'End of academic year';
			reasonSelect.dispatchEvent(new Event('change'));
			bootstrap.Modal.getOrCreateInstance(archiveModal).show();
		}

		reasonSelect.addEventListener('change', function () {
			const other = reasonSelect.value === 'Other';
			otherWrap.classList.toggle('d-none', !other);
			otherText.required = other;
		});

		document.getElementById('archiveForm').addEventListener('submit', function () {
			document.getElementById('archiveReason').value = reasonSelect.value === 'Other' ? otherText.value.trim() : reasonSelect.value;
		});

		function confirmDeletion(id, name) {
			Swal.fire({
				title: 'Delete ' + name + '?',
				text: 'Its classes, subjects and their schedules will be deleted too. This cannot be undone — archive it instead to keep the records.',
				icon: 'warning',
				showCancelButton: true,
				reverseButtons: true,
				confirmButtonText: 'Delete semester',
				customClass: { popup: 'ui-swal-danger' }
			}).then(function (result) {
				if (!result.isConfirmed) return;
				postForm('processes/admin/semester/delete.php', { id: id })
					.then(function (data) {
						if (!data.success) throw new Error(data.message);
						Swal.fire('Deleted', name + ' has been deleted.', 'success').then(() => location.reload());
					})
					.catch(function (error) { Swal.fire('Error', error.message || 'The semester could not be deleted.', 'error'); });
			});
		}
	</script>

	<?php
	$semesterAlerts = [
		'SEMESTER_ACTIVATED' => ['Semester activated', 'This semester is now active and set as current.', 'success'],
		'ACTIVE_SEMESTER_CONFLICT' => ['Another semester is active', 'Deactivate "' . ($_SESSION['active_semester_name'] ?? '') . '" first, then try again.', 'error'],
		'SEMESTER_UPDATE_SUCCESS' => ['Saved', 'The semester has been updated.', 'success'],
		'SEMESTER_UPDATE_NO_CHANGES' => ['No changes', 'Nothing was changed.', 'info'],
		'SEMESTER_UPDATE_ERROR' => ['Error', 'The semester could not be updated.', 'error'],
		'INVALID_REQUEST' => ['Error', 'The request was not valid. Please try again.', 'error'],
	];
	if (isset($_SESSION['STATUS'], $semesterAlerts[$_SESSION['STATUS']])) {
		[$title, $text, $icon] = $semesterAlerts[$_SESSION['STATUS']];
		echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
		unset($_SESSION['STATUS'], $_SESSION['active_semester_name']);
	}
	if (isset($_SESSION['error'])) {
		echo '<script>Swal.fire("Error", ' . json_encode($_SESSION['error']) . ', "error");</script>';
		unset($_SESSION['error']);
	}
	?>
	<?php include('processes/server/alerts.php'); ?>
</body>

</html>
