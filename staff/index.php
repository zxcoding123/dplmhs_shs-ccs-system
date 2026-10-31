<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
	$_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
	exit;
}


include __DIR__ . '/../database/connection.php';

$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");
$stmt->execute();

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>DPLMHS-SHS | Student Management System</title>
	<?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
	<link href="<?php echo csms_asset('../admin/css/admin-ui.css', __DIR__); ?>" rel="stylesheet">
	<link href="<?php echo csms_asset('css/staff-ui.css', __DIR__); ?>" rel="stylesheet">
	<script src="<?php echo csms_asset('js/staff-ui.js', __DIR__); ?>" defer></script>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
	<link href="<?php echo csms_asset('css/dashboard.css', __DIR__); ?>" rel="stylesheet">
</head>

<style>
	table.dataTable {
		font-size: 12px;
	}
</style>

<body>
	<div class="wrapper">
		<?php
		include('sidebar.php')
		?>

		<div class="main">
			<nav class="navbar navbar-expand navbar-light navbar-bg">
				<a class="sidebar-toggle js-sidebar-toggle">
					<i class="hamburger align-self-center"></i>
				</a>
				<img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
				<span class="text-white"><b>DPLMHS</b> - Student Management System </span>
				<div class="navbar-collapse collapse">
					<?php include('top-bar.php') ?>
				</div>
			</nav>

			<?php
			// ---------- Dashboard data ----------
			$teacher_id = $_SESSION['teacher_id'];
			$teacher_name = $_SESSION['teacher_name'] ?? '';
			$today = date('l');

			$classes = [];
			$todays_classes = [];
			$average_attendance_rate = null;
			// Used by the excessive absences modal below, even when there are no classes
			$excessive_absences = [];
			$total_document_count = 0;
			$total_media_count = 0;
			$total_student_count = 0;
			$total_class_count = 0;
			$notes = [];
			$reminders = [];

			try {
				$teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
				$teacher_stmt->execute(['teacher_id' => $teacher_id]);
				$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

				if (!$teacher) {
					echo "Teacher not found.";
					exit;
				}

				$teacher_full_name = $teacher['fullName'];

				// Active classes (anything that isn't still pending)
				$classes_stmt = $pdo->prepare("
					SELECT id, subject_id, CONCAT(name, ' - ', subject) AS class_name
					FROM classes
					WHERE teacher = :teacher_name AND status != 'pending'
				");
				$classes_stmt->execute(['teacher_name' => $teacher_full_name]);
				$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

				// Today's schedule
				$subject_ids = array_values(array_filter(array_column($classes, 'subject_id')));
				if (!empty($subject_ids)) {
					$placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
					$schedules_stmt = $pdo->prepare("
						SELECT ss.meeting_days, ss.start_time, ss.end_time,
							s.name AS subject_name, s.course, s.year_level
						FROM subjects_schedules ss
						JOIN subjects s ON ss.subject_id = s.id
						WHERE ss.subject_id IN ($placeholders)
					");
					$schedules_stmt->execute($subject_ids);

					$todays_classes = array_filter($schedules_stmt->fetchAll(PDO::FETCH_ASSOC), function ($schedule) use ($today) {
						return stripos($schedule['meeting_days'], $today) !== false;
					});
					usort($todays_classes, function ($a, $b) {
						return strtotime($a['start_time']) <=> strtotime($b['start_time']);
					});
				}

				// Attendance rate and students with excessive absences
				$total_records = 0;
				$total_present = 0;

				foreach ($classes as $class) {
					$class_id = $class['id'];

					$totals_stmt = $pdo->prepare("
						SELECT COUNT(*) AS total, SUM(a.status = 'present') AS present
						FROM attendance a
						JOIN classes_meetings cm ON a.meeting_id = cm.id
						WHERE cm.class_id = :class_id
					");
					$totals_stmt->execute(['class_id' => $class_id]);
					$totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);
					$total_records += (int) $totals['total'];
					$total_present += (int) $totals['present'];

					$meetings_stmt = $pdo->prepare("SELECT COUNT(*) FROM classes_meetings WHERE class_id = :class_id");
					$meetings_stmt->execute(['class_id' => $class_id]);
					$total_meetings = (int) $meetings_stmt->fetchColumn();

					if ($total_meetings === 0) continue;

					$students_stmt = $pdo->prepare("
						SELECT s.id AS student_id, s.fullName,
							(SELECT COUNT(*)
							 FROM attendance a
							 JOIN classes_meetings cm ON a.meeting_id = cm.id
							 WHERE cm.class_id = se.class_id AND a.student_id = s.id AND a.status = 'present') AS present_count
						FROM students_enrollments se
						INNER JOIN students s ON se.student_id = s.id
						WHERE se.class_id = :class_id
					");
					$students_stmt->execute(['class_id' => $class_id]);

					foreach ($students_stmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
						$present_count = (int) $student['present_count'];
						$absence_count = $total_meetings - $present_count;
						$attendance_rate = ($present_count / $total_meetings) * 100;

						// Flag if below 75% OR has 3+ absences
						if ($attendance_rate < 75 || $absence_count >= 3) {
							$excessive_absences[] = [
								'student_name' => $student['fullName'],
								'class_name' => $class['class_name'],
								'attendance_rate' => number_format($attendance_rate, 2),
								'present_count' => $present_count,
								'absence_count' => $absence_count
							];
						}
					}
				}

				if ($total_records > 0) {
					$average_attendance_rate = ($total_present / $total_records) * 100;
				}

				// Stat tiles: every class the teacher owns
				$stats_stmt = $pdo->prepare("
					SELECT
						(SELECT COUNT(*) FROM classes WHERE teacher = :t1) AS class_count,
						(SELECT COUNT(DISTINCT se.student_id)
						 FROM students_enrollments se
						 JOIN classes c ON se.class_id = c.id
						 WHERE c.teacher = :t2) AS student_count,
						(SELECT COUNT(*)
						 FROM learning_resources lr
						 JOIN classes c ON lr.class_id = c.id
						 WHERE c.teacher = :t3 AND lr.resource_type = 'document') AS document_count,
						(SELECT COUNT(*)
						 FROM learning_resources lr
						 JOIN classes c ON lr.class_id = c.id
						 WHERE c.teacher = :t4 AND lr.resource_type IN ('video', 'audio', 'image')) AS media_count
				");
				$stats_stmt->execute(['t1' => $teacher_name, 't2' => $teacher_name, 't3' => $teacher_name, 't4' => $teacher_name]);
				$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
				$total_class_count = (int) $stats['class_count'];
				$total_student_count = (int) $stats['student_count'];
				$total_document_count = (int) $stats['document_count'];
				$total_media_count = (int) $stats['media_count'];

				$notes_stmt = $pdo->prepare("SELECT * FROM teacher_notes WHERE teacher_name = :teacher_name ORDER BY created_at DESC");
				$notes_stmt->execute(['teacher_name' => $teacher_name]);
				$notes = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);

				$reminders_stmt = $pdo->prepare("SELECT * FROM teacher_reminders WHERE teacher_name = :teacher_name ORDER BY reminder_date ASC, created_at DESC");
				$reminders_stmt->execute(['teacher_name' => $teacher_name]);
				$reminders = $reminders_stmt->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $e) {
				error_log("Error loading teacher dashboard: " . $e->getMessage());
			}

			$hour = (int) date('G');
			$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
			$name_parts = explode(' ', trim($teacher_name));
			$first_name = $name_parts[0] !== '' ? $name_parts[0] : 'Teacher';
			$now_ts = time();
			$today_date = date('Y-m-d');
			?>

			<main class="content">
				<div class="container-fluid p-0">

					<div class="fd-hero">
						<div>
							<div class="fd-eyebrow"><?= htmlspecialchars(date('l, F j, Y')) ?></div>
							<h1 class="fd-title"><?= $greeting ?>, <?= htmlspecialchars($first_name) ?></h1>
							<p class="fd-subtitle">
								<?php if (count($todays_classes) > 0): ?>
									You have <?= count($todays_classes) ?> class<?= count($todays_classes) === 1 ? '' : 'es' ?> scheduled today.
								<?php else: ?>
									No classes on your schedule today.
								<?php endif; ?>
							</p>
						</div>
						<div class="fd-quick">
							<a class="fd-btn" href="class_management.php"><i class="bi bi-people"></i> Class Management</a>
							<a class="fd-btn-ghost" href="subject_management.php"><i class="bi bi-journal-bookmark"></i> Subject Management</a>
						</div>
					</div>

					<!-- Stat tiles -->
					<div class="row g-3 mb-4">
						<div class="col-6 col-xl-3">
							<div class="card fd-stat">
								<span class="fd-stat-icon"><i class="bi bi-layers"></i></span>
								<div>
									<div class="fd-stat-value"><?= $total_class_count ?></div>
									<div class="fd-stat-label">Classes</div>
								</div>
							</div>
						</div>
						<div class="col-6 col-xl-3">
							<div class="card fd-stat">
								<span class="fd-stat-icon"><i class="bi bi-people"></i></span>
								<div>
									<div class="fd-stat-value"><?= $total_student_count ?></div>
									<div class="fd-stat-label">Students</div>
								</div>
							</div>
						</div>
						<div class="col-6 col-xl-3">
							<div class="card fd-stat">
								<span class="fd-stat-icon is-gold"><i class="bi bi-file-earmark-text"></i></span>
								<div>
									<div class="fd-stat-value"><?= $total_document_count ?></div>
									<div class="fd-stat-label">Documents</div>
								</div>
							</div>
						</div>
						<div class="col-6 col-xl-3">
							<div class="card fd-stat">
								<span class="fd-stat-icon is-gold"><i class="bi bi-camera-video"></i></span>
								<div>
									<div class="fd-stat-value"><?= $total_media_count ?></div>
									<div class="fd-stat-label">Media files</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row g-3 mb-4">
						<!-- Today's schedule -->
						<div class="col-lg-7">
							<div class="card fd-panel">
								<div class="card-header">
									<h5 class="card-title"><i class="bi bi-calendar-event"></i> Today's Schedule</h5>
									<span class="fd-count"><?= count($todays_classes) ?></span>
								</div>
								<div class="card-body">
									<?php if (count($classes) === 0): ?>
										<div class="fd-empty">
											<i class="bi bi-calendar-x"></i>
											<p>You haven't been assigned to any active classes yet.</p>
										</div>
									<?php elseif (count($todays_classes) === 0): ?>
										<div class="fd-empty">
											<i class="bi bi-cup-hot"></i>
											<p>No classes are scheduled for <?= htmlspecialchars($today) ?>.</p>
										</div>
									<?php else: ?>
										<ul class="fd-schedule">
											<?php foreach ($todays_classes as $class):
												$start_ts = strtotime($today_date . ' ' . $class['start_time']);
												$end_ts = strtotime($today_date . ' ' . $class['end_time']);
												if ($now_ts >= $end_ts) {
													$status = ['is-done', 'Done'];
												} elseif ($now_ts >= $start_ts) {
													$status = ['is-now', 'In progress'];
												} else {
													$status = ['is-upcoming', 'Upcoming'];
												}
											?>
												<li>
													<div class="fd-time">
														<strong><?= htmlspecialchars(date('g:i A', $start_ts)) ?></strong>
														<span>to <?= htmlspecialchars(date('g:i A', $end_ts)) ?></span>
													</div>
													<div>
														<div class="fd-class-name"><?= htmlspecialchars($class['subject_name']) ?></div>
														<div class="fd-class-meta"><?= htmlspecialchars($class['course']) ?> &middot; <?= htmlspecialchars($class['year_level']) ?></div>
													</div>
													<span class="fd-status <?= $status[0] ?>"><?= $status[1] ?></span>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Attendance -->
						<div class="col-lg-5">
							<div class="card fd-panel">
								<div class="card-header">
									<h5 class="card-title"><i class="bi bi-clipboard-check"></i> Attendance Summary</h5>
								</div>
								<div class="card-body">
									<div class="fd-rate">
										<?php if ($average_attendance_rate !== null): ?>
											<span class="fd-rate-value"><?= number_format($average_attendance_rate, 1) ?>%</span>
											<span class="fd-rate-label">average attendance</span>
										<?php else: ?>
											<span class="fd-rate-value">&mdash;</span>
											<span class="fd-rate-label">no attendance records yet</span>
										<?php endif; ?>
									</div>
									<div class="fd-progress">
										<span class="<?= $average_attendance_rate !== null && $average_attendance_rate < 75 ? 'is-low' : '' ?>"
											style="width: <?= $average_attendance_rate !== null ? round($average_attendance_rate, 1) : 0 ?>%"></span>
									</div>

									<div class="fd-section-label">Classes handling (<?= count($classes) ?>)</div>
									<?php if (count($classes) > 0): ?>
										<div class="fd-chips">
											<?php foreach ($classes as $class): ?>
												<span class="fd-chip"><?= htmlspecialchars($class['class_name']) ?></span>
											<?php endforeach; ?>
										</div>
									<?php else: ?>
										<p class="text-muted small">None yet.</p>
									<?php endif; ?>

									<?php if (count($excessive_absences) > 0): ?>
										<div class="fd-alert-row">
											<div>
												<strong><?= count($excessive_absences) ?> student<?= count($excessive_absences) === 1 ? '' : 's' ?> flagged</strong>
												<small>Below 75% attendance or 3+ absences</small>
											</div>
											<button type="button" class="fd-btn-danger" data-bs-toggle="modal" data-bs-target="#excessiveAbsencesModal">
												<i class="bi bi-exclamation-triangle"></i> Review
											</button>
										</div>
									<?php else: ?>
										<div class="fd-alert-row is-clear">
											<div>
												<strong>No students flagged</strong>
												<small>Nobody is below 75% attendance or at 3+ absences</small>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<div class="row g-3 mb-4">
						<!-- Notes -->
						<div class="col-lg-6">
							<div class="card fd-panel">
								<div class="card-header">
									<h5 class="card-title"><i class="bi bi-sticky"></i> Notes <span class="fd-count"><?= count($notes) ?></span></h5>
									<div class="d-flex gap-2">
										<button type="button" class="fd-btn-ghost fd-btn-sm" data-bs-toggle="modal" data-bs-target="#viewNotesModal">View all</button>
										<button type="button" class="fd-btn fd-btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal"><i class="bi bi-plus-lg"></i> Add note</button>
									</div>
								</div>
								<div class="card-body">
									<?php if (count($notes) === 0): ?>
										<div class="fd-empty">
											<i class="bi bi-journal"></i>
											<p>No notes yet. Jot down anything you want to remember.</p>
										</div>
									<?php else: ?>
										<ul class="fd-list" id="notesList">
											<?php foreach ($notes as $row): ?>
												<li id="note-<?= (int) $row['id'] ?>">
													<div class="fd-list-body">
														<div class="fd-list-title"><?= htmlspecialchars($row['note_title']) ?></div>
														<div class="fd-list-text"><?= htmlspecialchars($row['note_content']) ?></div>
													</div>
													<button type="button" class="fd-delete" title="Delete note" aria-label="Delete note"
														onclick="deleteNote(<?= (int) $row['id'] ?>)"><i class="bi bi-trash"></i></button>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Reminders -->
						<div class="col-lg-6">
							<div class="card fd-panel">
								<div class="card-header">
									<h5 class="card-title"><i class="bi bi-bell"></i> Reminders <span class="fd-count"><?= count($reminders) ?></span></h5>
									<div class="d-flex gap-2">
										<button type="button" class="fd-btn-ghost fd-btn-sm" data-bs-toggle="modal" data-bs-target="#viewRemindersModal">View all</button>
										<button type="button" class="fd-btn fd-btn-sm" data-bs-toggle="modal" data-bs-target="#addReminderModal"><i class="bi bi-plus-lg"></i> Add reminder</button>
									</div>
								</div>
								<div class="card-body">
									<?php if (count($reminders) === 0): ?>
										<div class="fd-empty">
											<i class="bi bi-bell-slash"></i>
											<p>No reminders. Add one for deadlines or upcoming tasks.</p>
										</div>
									<?php else: ?>
										<ul class="fd-list" id="remindersList">
											<?php foreach ($reminders as $row):
												$due = $row['reminder_date'];
												if ($due < $today_date) {
													$due_class = 'is-overdue';
													$due_label = 'Overdue';
												} elseif ($due === $today_date) {
													$due_class = 'is-today';
													$due_label = 'Due today';
												} else {
													$due_class = '';
													$due_label = 'Due ' . date('M j, Y', strtotime($due));
												}
											?>
												<li id="reminder-<?= (int) $row['id'] ?>">
													<div class="fd-list-body">
														<div class="fd-list-text text-body"><?= htmlspecialchars($row['reminder_content']) ?></div>
														<span class="fd-due <?= $due_class ?>"><i class="bi bi-calendar3"></i> <?= htmlspecialchars($due_label) ?></span>
													</div>
													<button type="button" class="fd-delete" title="Delete reminder" aria-label="Delete reminder"
														onclick="deleteReminder(<?= (int) $row['id'] ?>)"><i class="bi bi-trash"></i></button>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

				</div>

				<!-- Modal: students with excessive absences -->
				<div class="modal fade" id="excessiveAbsencesModal" tabindex="-1" aria-labelledby="excessiveAbsencesModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
						<div class="modal-content">
							<div class="modal-header text-white">
								<h5 class="modal-title" id="excessiveAbsencesModalLabel">Students with Excessive Absences</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<?php if (count($excessive_absences) === 0): ?>
									<p>No students with excessive absences.</p>
								<?php else: ?>
									<div class="table-responsive">
										<table class="table table-bordered align-middle">
											<thead>
												<tr>
													<th>Student Name</th>
													<th>Class</th>
													<th class="text-center">Present</th>
													<th class="text-center">Absences</th>
													<th class="text-center">Attendance Rate (%)</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($excessive_absences as $student): ?>
													<tr>
														<td><?= htmlspecialchars($student['student_name']) ?></td>
														<td><?= htmlspecialchars($student['class_name']) ?></td>
														<td class="text-center"><?= $student['present_count'] ?></td>
														<td class="text-center <?= $student['absence_count'] >= 3 ? 'text-danger fw-bold' : '' ?>"><?= $student['absence_count'] ?></td>
														<td class="text-center"><?= $student['attendance_rate'] ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Modal: all notes -->
				<div class="modal fade" id="viewNotesModal" tabindex="-1" aria-labelledby="viewNotesModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="viewNotesModalLabel">All Notes</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body" id="modalNotesContent">
								<?php if (count($notes) === 0): ?>
									<p class="text-muted mb-0">No notes yet.</p>
								<?php endif; ?>
								<?php foreach ($notes as $row): ?>
									<div class="note-item mb-3">
										<h5><?= htmlspecialchars($row['note_title']) ?></h5>
										<p class="mb-0"><?= nl2br(htmlspecialchars($row['note_content'])) ?></p>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Modal: add note -->
				<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="addNoteModalLabel">Add New Note</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<form id="addNoteForm" action="processes/teachers/notes/add.php" method="POST">
									<div class="mb-3">
										<label for="noteTitle" class="form-label">Note Title</label>
										<input type="text" class="form-control" id="noteTitle" name="note_title" required>
									</div>
									<div class="mb-3">
										<label for="noteContent" class="form-label">Note Content</label>
										<textarea class="form-control" id="noteContent" rows="3" required name="note_content"></textarea>
									</div>
									<button type="submit" class="btn btn-primary">Save Note</button>
								</form>
							</div>
						</div>
					</div>
				</div>

				<!-- Modal: all reminders -->
				<div class="modal fade" id="viewRemindersModal" tabindex="-1" aria-labelledby="viewRemindersModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="viewRemindersModalLabel">All Reminders</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body" id="modalRemindersContent">
								<?php if (count($reminders) === 0): ?>
									<p class="text-muted mb-0">No reminders yet.</p>
								<?php endif; ?>
								<?php foreach ($reminders as $row): ?>
									<div class="reminder-item mb-3">
										<p class="mb-1"><?= nl2br(htmlspecialchars($row['reminder_content'])) ?></p>
										<small class="text-muted">Due date: <?= htmlspecialchars($row['reminder_date']) ?></small><br>
										<small class="text-muted">Created at: <?= htmlspecialchars($row['created_at']) ?></small>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Modal: add reminder -->
				<div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="addReminderModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title" id="addReminderModalLabel">Add New Reminder</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<form id="addReminderForm" action="processes/teachers/reminders/add.php" method="POST">
									<div class="mb-3">
										<label for="reminderContent" class="form-label">Reminder Content</label>
										<textarea class="form-control" id="reminderContent" rows="3" required name="reminder_content"></textarea>
									</div>
									<div class="mb-3">
										<label for="reminderDate" class="form-label">Reminder Date</label>
										<input type="date" class="form-control" id="reminderDate" name="reminder_date" required>
									</div>
									<button type="submit" class="btn btn-primary">Save Reminder</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</main>


		</div>
	</div>


	<script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
	<?php
	include('processes/server/modals.php');
	?>


	<script>
		// JavaScript function to delete a note using SweetAlert2
		function deleteNote(noteId) {
			// Show confirmation dialog using SweetAlert2
			Swal.fire({
				title: 'Are you sure?',
				text: 'This note will be permanently deleted.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'Cancel',
			}).then((result) => {
				if (result.isConfirmed) {
					// Use AJAX to send the delete request
					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'delete_note.php', true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					xhr.onload = function() {
						if (xhr.status == 200) {
							// Show success message using SweetAlert2
							Swal.fire(
								'Deleted!',
								'The note has been deleted.',
								'success'
							).then(() => {
								// Reload the page after the user clicks 'OK'
								location.reload();
							});
						} else {
							// Show error message using SweetAlert2
							Swal.fire(
								'Error!',
								'An error occurred while deleting the note.',
								'error'
							);
						}
					};
					xhr.send('note_id=' + noteId);
				}

			});
		}
	</script>


	<script>
		// Function to delete a reminder
		function deleteReminder(reminderId) {
			Swal.fire({
				title: 'Are you sure?',
				text: 'This reminder will be permanently deleted.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'Cancel',
			}).then((result) => {
				if (result.isConfirmed) {
					// Use AJAX to send the delete request
					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'delete_reminder.php', true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					xhr.onload = function() {
						if (xhr.status == 200) {
							// Remove the reminder from the list
							document.getElementById('reminder-' + reminderId).remove();
							Swal.fire(
								'Deleted!',
								'The reminder has been deleted.',
								'success'
							).then(() => {
								location.reload();
							});
						} else {
							Swal.fire(
								'Error!',
								'An error occurred while deleting the reminder.',
								'error'
							);
						}
					};
					xhr.send('reminder_id=' + reminderId);
				}
			});
		}
	</script>


	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();

			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}
		setInterval(getTime, 100);
	</script>

</html>

<?php
include('processes/server/alerts.php');

// Function to show SweetAlert2 messages
function showAlert($title, $message, $icon = 'warning')
{
	echo "<script>
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '$title',
                html: '$message',
                icon: '$icon',
                confirmButtonText: 'OK'
            });
        } else {
            alert('$title: $message');
        }
    </script>";
}

// Check if teacher is logged in
if (isset($_SESSION['teacher_id'])) {
	$teacher_id = $_SESSION['teacher_id'];

	try {
		// Fetch the teacher's full name from staff_accounts
		$staffStmt = $pdo->prepare("
            SELECT fullName 
            FROM staff_accounts 
            WHERE id = :teacher_id
        ");
		$staffStmt->execute([':teacher_id' => $teacher_id]);
		$teacher = $staffStmt->fetch(PDO::FETCH_ASSOC);

		if (!$teacher) {
			echo "<p class='error'>Teacher not found in staff accounts.</p>";
			exit;
		}
		$teacherFullName = $teacher['fullName'];

		// Fetch active classes taught by the teacher (matching fullName to classes.teacher)
		$classStmt = $pdo->prepare("
            SELECT c.id, c.subject, c.code
            FROM classes c
            WHERE c.teacher = :teacher_fullname AND c.is_archived = 0
        ");
		$classStmt->execute([':teacher_fullname' => $teacherFullName]);
		$classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);

		if ($classes) {
			$absentWarnings = [];
			foreach ($classes as $class) {
				$class_id = $class['id'];
				$class_name = $class['subject'] . ' (' . $class['code'] . ')';

				// Fetch enrolled students for this class
				$enrollmentStmt = $pdo->prepare("
                    SELECT se.student_id
                    FROM students_enrollments se
                    WHERE se.class_id = :class_id
                ");
				$enrollmentStmt->execute([':class_id' => $class_id]);
				$students = $enrollmentStmt->fetchAll(PDO::FETCH_ASSOC);

				foreach ($students as $student) {
					$student_id = $student['student_id'];

					// Count absences for this student in this class
					$attendanceStmt = $pdo->prepare("
                        SELECT COUNT(*) as absent_count
                        FROM attendance
                        WHERE student_id = :student_id
                        AND class_id = :class_id
                        AND status = 'absent'
                    ");
					$attendanceStmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
					$absentCount = $attendanceStmt->fetch(PDO::FETCH_ASSOC)['absent_count'];

					if ($absentCount >= 3) {
						// Fetch student name for display
						$studentStmt = $pdo->prepare("SELECT fullName FROM students WHERE student_id = :student_id");
						$studentStmt->execute([':student_id' => $student_id]);
						$studentName = $studentStmt->fetch(PDO::FETCH_ASSOC)['fullName'] ?? 'Unknown';

						$absentWarnings[] = "Student: $studentName (ID: $student_id) has $absentCount absences in $class_name.";
					}
				}
			}

			// Show teacher warning if there are students with 3+ absences
			if (!empty($absentWarnings)) {
				$message = implode('<br>', $absentWarnings);
				showAlert('Teacher Warning: Excessive Absences', $message);
			}
		} 
	} catch (PDOException $e) {
		echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
	}
}

// Check if student is logged in (hypothetical student context)
if (isset($_SESSION['student_id'])) {
	$student_id = $_SESSION['student_id'];

	try {
		// Fetch classes the student is enrolled in
		$enrollmentStmt = $pdo->prepare("
            SELECT se.class_id, c.subject, c.code
            FROM students_enrollments se
            JOIN classes c ON c.id = se.class_id
            WHERE se.student_id = :student_id AND c.is_archived = 0
        ");
		$enrollmentStmt->execute([':student_id' => $student_id]);
		$enrolledClasses = $enrollmentStmt->fetchAll(PDO::FETCH_ASSOC);

		if ($enrolledClasses) {
			foreach ($enrolledClasses as $class) {
				$class_id = $class['class_id'];
				$class_name = $class['subject'] . ' (' . $class['code'] . ')';

				// Check absences for this student in this class
				$attendanceStmt = $pdo->prepare("
                    SELECT COUNT(*) as absent_count
                    FROM attendance
                    WHERE student_id = :student_id
                    AND class_id = :class_id
                    AND status = 'absent'
                ");
				$attendanceStmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
				$absentCount = $attendanceStmt->fetch(PDO::FETCH_ASSOC)['absent_count'];

				if ($absentCount >= 3) {
					$message = "You have $absentCount absences in $class_name. Please address this with your teacher.";
					showAlert('Student Warning: Excessive Absences', $message);
					break; // Show only one warning per page load for simplicity
				}
			}
		}
	} catch (PDOException $e) {
		echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
	}
}
?>
<!-- Floating Chat Widget -->
