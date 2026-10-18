<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php'; // adjust path to your file
requireRole('admin'); // ensures only admins can access
require_once __DIR__ . '/includes/ui.php';

require_once __DIR__ . '/../includes/academic.php';

// Advisory sections each strand can be assigned (stored in staff_accounts.department)
$departmentClasses = shs_sections();

$teachers = $pdo->query("SELECT * FROM staff_accounts ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

// Advisory classes keyed by the stored fullName (the column staff/add.php and staff/edit.php write)
$advisory = [];
foreach ($pdo->query("SELECT fullName, class_advising FROM staff_advising ORDER BY class_advising") as $row) {
	$advisory[$row['fullName']][] = $row['class_advising'];
}

// Classes each teacher is assigned to teach
$teaching = [];
foreach ($pdo->query("SELECT teacher, COUNT(*) AS total FROM classes WHERE is_archived = 0 GROUP BY teacher") as $row) {
	$teaching[$row['teacher']] = (int) $row['total'];
}

$activeCount = count(array_filter($teachers, fn($t) => strcasecmp((string) $t['status'], 'Active') === 0));
$adviserCount = count(array_filter($teachers, fn($t) => !empty($advisory[$t['fullName']])));
$departments = array_values(array_unique(array_filter(array_column($teachers, 'department'), fn($d) => $d && $d !== 'None')));

function teacher_name(array $t): string
{
	return trim(preg_replace('/\s+/', ' ', $t['first_name'] . ' ' . $t['middle_name'] . ' ' . $t['last_name']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Advisers &amp; Teachers | DPLMHS-SHS Admin Portal</title>
	<?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
	<link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
	<style>
		.person { display: flex; align-items: center; gap: 0.65rem; }
		.person-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2.2rem; height: 2.2rem; border-radius: 50%; background: var(--ui-navy); color: var(--ui-gold-light); font-size: 0.8rem; font-weight: 700; }
		.person-name { display: block; font-weight: 700; color: var(--ui-navy); }
		.person-meta { display: block; font-size: 0.8rem; color: var(--ui-muted); }
		.tag-row { display: flex; flex-wrap: wrap; gap: 0.25rem; }
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
					<?php admin_page_header('Advisers & Teachers', 'Teaching staff, their advisory sections and account access', 'bi-person-badge',
						'<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#addTeacherModal"><i class="bi bi-person-plus"></i> Add teacher</button>'); ?>

					<?php admin_stat_tiles([
						['label' => 'Teachers', 'value' => count($teachers), 'icon' => 'bi-people', 'filter' => ''],
						['label' => 'Active', 'value' => $activeCount, 'icon' => 'bi-person-check', 'tone' => 'ok', 'filter' => 'Active', 'filter_col' => 'Status'],
						['label' => 'Inactive', 'value' => count($teachers) - $activeCount, 'icon' => 'bi-person-dash', 'tone' => 'muted', 'filter' => 'Inactive', 'filter_col' => 'Status'],
						['label' => 'Class advisers', 'value' => $adviserCount, 'icon' => 'bi-mortarboard', 'hint' => (count($teachers) - $adviserCount) . ' without an advisory'],
					]); ?>

					<section class="ui-panel">
						<?php if ($teachers): ?>
							<div class="ui-panel-head">
								<div>
									<h2>Teaching staff</h2>
									<p>Click a tile to filter by status. Press <kbd>/</kbd> to search.</p>
								</div>
								<?php if ($departments): ?>
									<div class="ui-filters">
										<select class="form-select form-select-sm" id="departmentFilter" aria-label="Filter by strand">
											<option value="">All strands</option>
											<?php foreach ($departments as $dept): ?><option value="<?= admin_e($dept) ?>"><?= admin_e($dept) ?></option><?php endforeach; ?>
										</select>
									</div>
								<?php endif; ?>
							</div>
							<table id="teachers" class="display">
								<thead>
									<tr>
										<th>Teacher</th>
										<th>Strand</th>
										<th>Advisory</th>
										<th>Classes</th>
										<th>Phone</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($teachers as $t):
										$id = (int) $t['id'];
										$isActive = strcasecmp((string) $t['status'], 'Active') === 0;
										$classes = $advisory[$t['fullName']] ?? [];
										$name = teacher_name($t);
										$initials = strtoupper(mb_substr($t['first_name'], 0, 1) . mb_substr($t['last_name'], 0, 1)); ?>
										<tr>
											<td>
												<div class="person">
													<span class="person-avatar"><?= admin_e($initials ?: '?') ?></span>
													<div>
														<span class="person-name"><?= admin_e($name) ?></span>
														<span class="person-meta"><?= admin_e($t['email']) ?></span>
													</div>
												</div>
											</td>
											<td><?= admin_e($t['department'] && $t['department'] !== 'None' ? $t['department'] : '—') ?></td>
											<td>
												<?php if ($classes): ?>
													<div class="tag-row"><?php foreach ($classes as $c): ?><span class="ui-tag"><?= admin_e($c) ?></span><?php endforeach; ?></div>
												<?php else: ?>
													<span class="text-muted">None</span>
												<?php endif; ?>
											</td>
											<td><?= $teaching[$t['fullName']] ?? 0 ?></td>
											<td><?= admin_e($t['phone_number'] ?: '—') ?></td>
											<td><span class="ui-pill <?= $isActive ? 'is-ok' : '' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
											<td>
												<div class="ui-acts">
													<button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#viewModal<?= $id ?>" title="View" aria-label="View"><i class="bi bi-eye"></i></button>
													<button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
													<button type="button" class="ui-act <?= $isActive ? 'is-warn' : 'is-ok' ?>" onclick="changeStatus(<?= $id ?>, '<?= $isActive ? 'Inactive' : 'Active' ?>', <?= admin_e(json_encode($name)) ?>)"
														title="<?= $isActive ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
														<i class="bi <?= $isActive ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
													</button>
													<button type="button" class="ui-act is-danger" onclick="deleteModal(<?= $id ?>, <?= admin_e(json_encode($name)) ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else: ?>
							<div class="ui-empty">
								<i class="bi bi-person-badge"></i>
								<h3>No teachers yet</h3>
								<p>Add teaching staff so they can sign in, advise sections and handle classes.</p>
								<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#addTeacherModal"><i class="bi bi-person-plus"></i> Add teacher</button>
							</div>
						<?php endif; ?>
					</section>
				</div>
			</main>
		</div>
	</div>

	<!-- Add teacher -->
	<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
			<form class="modal-content" method="POST" action="processes/admin/staff/add.php" data-password-form>
				<div class="modal-header">
					<span class="ui-modal-icon"><i class="bi bi-person-plus"></i></span>
					<h1 class="modal-title" id="addTeacherLabel">Add a teacher</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-4">
							<label for="first_name" class="form-label">First name</label>
							<input type="text" class="form-control" id="first_name" name="firstName" required>
						</div>
						<div class="col-md-4">
							<label for="middle_name" class="form-label">Middle name</label>
							<input type="text" class="form-control" id="middle_name" name="middleName">
						</div>
						<div class="col-md-4">
							<label for="last_name" class="form-label">Last name</label>
							<input type="text" class="form-control" id="last_name" name="lastName" required>
						</div>
						<div class="col-md-6">
							<label for="email" class="form-label">Email</label>
							<input type="email" class="form-control" id="email" name="email" autocomplete="off" required>
						</div>
						<div class="col-md-3">
							<label for="phone_number" class="form-label">Phone</label>
							<input type="tel" class="form-control" id="phone_number" name="phone_number" value="+63" pattern="^\+63\d{10}$" maxlength="13" placeholder="+639xxxxxxxxx" required>
						</div>
						<div class="col-md-3">
							<label for="gender" class="form-label">Gender</label>
							<select class="form-select" name="gender" id="gender">
								<option value="Male">Male</option>
								<option value="Female">Female</option>
							</select>
						</div>
					</div>

					<p class="ui-section-label">Strand &amp; advisory</p>
					<div class="row g-3">
						<div class="col-md-6">
							<label for="department-new" class="form-label">Strand</label>
							<select class="form-select" name="department" id="department-new" data-dept-select="#class-new" required>
								<option value="None">None</option>
								<?php foreach (array_keys($departmentClasses) as $dept): ?><option value="<?= admin_e($dept) ?>"><?= admin_e(shs_strand_label($dept)) ?></option><?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-6">
							<label for="class-new" class="form-label">Advisory sections</label>
							<select class="form-select" name="class[]" id="class-new" multiple disabled data-taken="[]"></select>
							<small class="text-muted">Hold Ctrl (⌘ on Mac) to choose several. Optional.</small>
						</div>
					</div>

					<p class="ui-section-label">Sign-in password</p>
					<div class="row g-3">
						<div class="col-md-6">
							<label for="password" class="form-label">Password</label>
							<input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required>
							<small class="text-muted">At least 8 characters.</small>
						</div>
						<div class="col-md-6">
							<label for="confirm_password" class="form-label">Confirm password</label>
							<input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required data-confirms="#password">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Add teacher</button>
				</div>
			</form>
		</div>
	</div>

	<?php foreach ($teachers as $t):
		$id = (int) $t['id'];
		$classes = $advisory[$t['fullName']] ?? [];
		$name = teacher_name($t);
		$isActive = strcasecmp((string) $t['status'], 'Active') === 0; ?>

		<!-- Edit teacher -->
		<div class="modal fade" id="editModal<?= $id ?>" tabindex="-1" aria-labelledby="editTeacherLabel<?= $id ?>" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
				<form class="modal-content" method="POST" action="processes/admin/staff/edit.php?id=<?= $id ?>" data-password-form>
					<div class="modal-header">
						<span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
						<h1 class="modal-title" id="editTeacherLabel<?= $id ?>">Edit <?= admin_e($name) ?></h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<div class="row g-3">
							<div class="col-md-4">
								<label for="first_name<?= $id ?>" class="form-label">First name</label>
								<input type="text" class="form-control" id="first_name<?= $id ?>" name="first_name" value="<?= admin_e($t['first_name']) ?>" required>
							</div>
							<div class="col-md-4">
								<label for="middle_name<?= $id ?>" class="form-label">Middle name</label>
								<input type="text" class="form-control" id="middle_name<?= $id ?>" name="middle_name" value="<?= admin_e($t['middle_name']) ?>">
							</div>
							<div class="col-md-4">
								<label for="last_name<?= $id ?>" class="form-label">Last name</label>
								<input type="text" class="form-control" id="last_name<?= $id ?>" name="last_name" value="<?= admin_e($t['last_name']) ?>" required>
							</div>
							<div class="col-md-6">
								<label for="email<?= $id ?>" class="form-label">Email</label>
								<input type="email" class="form-control" id="email<?= $id ?>" name="email" value="<?= admin_e($t['email']) ?>" required>
							</div>
							<div class="col-md-3">
								<label for="phone_number<?= $id ?>" class="form-label">Phone</label>
								<input type="tel" class="form-control" id="phone_number<?= $id ?>" name="phone_number" value="<?= admin_e($t['phone_number']) ?>" pattern="^\+63\d{10}$" maxlength="13" placeholder="+639xxxxxxxxx" required>
							</div>
							<div class="col-md-3">
								<label for="gender-<?= $id ?>" class="form-label">Gender</label>
								<select class="form-select" name="gender" id="gender-<?= $id ?>">
									<?php foreach (['Male', 'Female', 'Other'] as $g): ?>
										<option value="<?= $g ?>" <?= strcasecmp((string) $t['gender'], $g) === 0 ? 'selected' : '' ?>><?= $g ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<p class="ui-section-label">Strand &amp; advisory</p>
						<div class="row g-3">
							<div class="col-md-6">
								<label for="department-<?= $id ?>" class="form-label">Strand</label>
								<select class="form-select" name="department" id="department-<?= $id ?>" data-dept-select="#class-<?= $id ?>" required>
									<option value="None" <?= !isset($departmentClasses[$t['department']]) ? 'selected' : '' ?>>None</option>
									<?php foreach (array_keys($departmentClasses) as $dept): ?>
										<option value="<?= admin_e($dept) ?>" <?= $t['department'] === $dept ? 'selected' : '' ?>><?= admin_e(shs_strand_label($dept)) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-md-6">
								<label for="class-<?= $id ?>" class="form-label">Advisory sections</label>
								<select class="form-select" name="class[]" id="class-<?= $id ?>" multiple data-taken="<?= admin_e(json_encode($classes)) ?>"></select>
								<small class="text-muted">Currently: <?= $classes ? admin_e(implode(', ', $classes)) : 'none' ?>.</small>
							</div>
						</div>

						<p class="ui-section-label">Change password <span class="text-muted fw-normal text-lowercase">(leave blank to keep the current one)</span></p>
						<div class="row g-3">
							<div class="col-md-6">
								<label for="password<?= $id ?>" class="form-label">New password</label>
								<input type="password" class="form-control" id="password<?= $id ?>" name="password" minlength="8" autocomplete="new-password">
							</div>
							<div class="col-md-6">
								<label for="confirm_password<?= $id ?>" class="form-label">Confirm new password</label>
								<input type="password" class="form-control" id="confirm_password<?= $id ?>" name="confirm_password" autocomplete="new-password" data-confirms="#password<?= $id ?>">
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

		<!-- View teacher -->
		<div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewTeacherLabel<?= $id ?>" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<span class="ui-modal-icon"><i class="bi bi-person-vcard"></i></span>
						<h1 class="modal-title" id="viewTeacherLabel<?= $id ?>"><?= admin_e($name) ?></h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<dl class="ui-dl">
							<dt>Status</dt>
							<dd><span class="ui-pill <?= $isActive ? 'is-ok' : '' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></dd>
							<dt>Email</dt>
							<dd><?= admin_e($t['email']) ?></dd>
							<dt>Phone</dt>
							<dd><?= admin_e($t['phone_number'] ?: '—') ?></dd>
							<dt>Gender</dt>
							<dd><?= admin_e($t['gender'] ?: '—') ?></dd>
							<dt>Strand</dt>
							<dd><?= admin_e($t['department'] && $t['department'] !== 'None' ? $t['department'] : '—') ?></dd>
							<dt>Classes taught</dt>
							<dd><?= $teaching[$t['fullName']] ?? 0 ?> active class<?= ($teaching[$t['fullName']] ?? 0) === 1 ? '' : 'es' ?></dd>
							<dt>Added</dt>
							<dd><?= $t['datetime_added'] ? date('M j, Y', strtotime($t['datetime_added'])) : ($t['date_created'] ? date('M j, Y', strtotime($t['date_created'])) : '—') ?></dd>
						</dl>
						<p class="ui-section-label">Advisory sections</p>
						<?php if ($classes): ?>
							<div class="tag-row"><?php foreach ($classes as $c): ?><span class="ui-tag"><?= admin_e($c) ?></span><?php endforeach; ?></div>
						<?php else: ?>
							<div class="ui-list-empty">Not advising any section.</div>
						<?php endif; ?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $id ?>"><i class="bi bi-pencil"></i> Edit</button>
					</div>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
	<script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
	<script>
		const departmentClasses = <?= json_encode($departmentClasses) ?>;

		$(function () {
			const table = $('#teachers').DataTable({
				order: [[0, 'asc']],
				columnDefs: [{ targets: 6, orderable: false, searchable: false }]
			});
			$('#departmentFilter').on('change', function () {
				table.column(1).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
			});
		});

		// Department → advisory section options. Sections the teacher already advises stay selected,
		// even when they belong to another department.
		document.querySelectorAll('[data-dept-select]').forEach(function (deptSelect) {
			const classSelect = document.querySelector(deptSelect.dataset.deptSelect);
			const taken = JSON.parse(classSelect.dataset.taken || '[]');

			function render() {
				const keep = new Set(taken.concat(Array.from(classSelect.selectedOptions).map(o => o.value)));
				const options = (departmentClasses[deptSelect.value] || []).slice();
				keep.forEach(c => { if (!options.includes(c)) options.unshift(c); });
				classSelect.innerHTML = '';
				options.forEach(function (c) {
					const option = new Option(c, c, false, keep.has(c));
					classSelect.add(option);
				});
				classSelect.disabled = options.length === 0;
			}

			deptSelect.addEventListener('change', render);
			render();
		});

		// Password and confirmation must match
		document.querySelectorAll('form[data-password-form]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				const confirm = form.querySelector('[data-confirms]');
				const password = form.querySelector(confirm.dataset.confirms);
				if (password.value !== confirm.value) {
					e.preventDefault();
					Swal.fire('Passwords don\'t match', 'Type the same password in both fields.', 'warning');
				}
			});
		});

		function changeStatus(userId, newStatus, name) {
			Swal.fire({
				title: (newStatus === 'Active' ? 'Activate ' : 'Deactivate ') + name + '?',
				text: newStatus === 'Active' ? 'They will be able to sign in to the teacher portal again.' : 'They will no longer be able to sign in.',
				icon: 'question',
				showCancelButton: true,
				reverseButtons: true,
				confirmButtonText: newStatus === 'Active' ? 'Activate' : 'Deactivate'
			}).then(function (result) {
				if (!result.isConfirmed) return;
				fetch('change_status.php', { method: 'POST', body: new URLSearchParams({ user_id: userId, new_status: newStatus }) })
					.then(function (response) {
						if (!response.ok) throw new Error();
						location.reload();
					})
					.catch(function () { Swal.fire('Error', 'The status could not be changed. Please try again.', 'error'); });
			});
		}

		function deleteModal(id, name) {
			Swal.fire({
				title: 'Delete ' + name + '?',
				text: 'Their account and advisory assignments will be removed, and their classes left without a teacher.',
				icon: 'warning',
				showCancelButton: true,
				reverseButtons: true,
				confirmButtonText: 'Delete teacher',
				customClass: { popup: 'ui-swal-danger' }
			}).then(function (result) {
				if (result.isConfirmed) window.location.href = 'processes/admin/staff/delete.php?id=' + id;
			});
		}
	</script>

	<?php include('processes/server/alerts.php') ?>
</body>

</html>
