<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php'; // adjust path to your file
requireRole('admin'); // ensures only admins can access
require_once __DIR__ . '/includes/ui.php';

// ---------------------------------------------------------------
// Dashboard data. Each lookup falls back to an empty value so one
// missing table never takes down the whole page.
// ---------------------------------------------------------------
function dash_value(PDO $pdo, string $sql, array $params = [], $default = 0)
{
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		$value = $stmt->fetchColumn();
		return $value === false || $value === null ? $default : $value;
	} catch (PDOException $e) {
		return $default;
	}
}

function dash_rows(PDO $pdo, string $sql, array $params = [])
{
	try {
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		return [];
	}
}

function dash_ago(string $datetime)
{
	$seconds = time() - strtotime($datetime);
	if ($seconds < 60) return 'Just now';
	if ($seconds < 3600) return floor($seconds / 60) . ' min ago';
	if ($seconds < 86400) return floor($seconds / 3600) . ' hr ago';
	if ($seconds < 86400 * 7) return floor($seconds / 86400) . ' day' . (floor($seconds / 86400) > 1 ? 's' : '') . ' ago';
	return date('M j, Y', strtotime($datetime));
}

$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Greeting
$admin_first_name = dash_value($pdo, "SELECT first_name FROM admin WHERE id = ?", [$_SESSION['admin_id'] ?? 0], 'Administrator');
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

// Learners
$totalStudents   = (int) dash_value($pdo, "SELECT COUNT(*) FROM students");
$activeStudents  = (int) dash_value($pdo, "SELECT COUNT(*) FROM students WHERE LOWER(status) = 'active'");
$pendingStudents = $totalStudents - $activeStudents;
$newAccounts     = (int) dash_value($pdo, "SELECT COUNT(*) FROM users WHERE role = 'student' AND date_created >= NOW() - INTERVAL 30 DAY");

$byStrand = dash_rows($pdo, "SELECT COALESCE(NULLIF(TRIM(course), ''), 'Unassigned') AS label, COUNT(*) AS total
	FROM students GROUP BY label ORDER BY total DESC");
$byGrade = dash_rows($pdo, "SELECT COALESCE(NULLIF(TRIM(year_level), ''), 'Unassigned') AS label, COUNT(*) AS total
	FROM students GROUP BY label ORDER BY label");
$bySex = dash_rows($pdo, "SELECT COALESCE(NULLIF(TRIM(gender), ''), 'Not specified') AS label, COUNT(*) AS total
	FROM students GROUP BY label ORDER BY total DESC");

// Faculty
$totalTeachers  = (int) dash_value($pdo, "SELECT COUNT(*) FROM staff_accounts");
$activeTeachers = (int) dash_value($pdo, "SELECT COUNT(*) FROM staff_accounts WHERE LOWER(status) = 'active'");
$totalAdvisers  = (int) dash_value($pdo, "SELECT COUNT(DISTINCT fullName) FROM staff_advising");

// Classes & subjects
$activeClasses   = (int) dash_value($pdo, "SELECT COUNT(*) FROM classes WHERE is_archived = 0 AND status = 'accepted'");
$pendingClasses  = (int) dash_value($pdo, "SELECT COUNT(*) FROM classes WHERE is_archived = 0 AND status = 'pending'");
$totalEnrolments = (int) dash_value($pdo, "SELECT COUNT(*) FROM students_enrollments");
$activeSubjects  = (int) dash_value($pdo, "SELECT COUNT(*) FROM subjects WHERE is_archived = 0");

// Website posts
$publishedPosts = (int) dash_value($pdo, "SELECT COUNT(*) FROM posts WHERE status = 'Published'");
$draftPosts     = (int) dash_value($pdo, "SELECT COUNT(*) FROM posts WHERE status = 'Draft'");
$lastPublished  = dash_value($pdo, "SELECT MAX(created_at) FROM posts WHERE status = 'Published'", [], null);

// Attendance, last 14 days
$attendanceDays = 14;
$attendanceSeries = ['present' => [], 'late' => [], 'absent' => []];
$attendanceLabels = [];
$dayKeys = [];
for ($i = $attendanceDays - 1; $i >= 0; $i--) {
	$day = date('Y-m-d', strtotime("-$i days"));
	$dayKeys[] = $day;
	$attendanceLabels[] = date('M j', strtotime($day));
}
foreach ($attendanceSeries as $status => $_) {
	$attendanceSeries[$status] = array_fill_keys($dayKeys, 0);
}
foreach (dash_rows($pdo, "SELECT date, status, COUNT(*) AS total FROM attendance
	WHERE date >= CURDATE() - INTERVAL " . ($attendanceDays - 1) . " DAY GROUP BY date, status") as $row) {
	if (isset($attendanceSeries[$row['status']][$row['date']])) {
		$attendanceSeries[$row['status']][$row['date']] = (int) $row['total'];
	}
}
$attendanceTotals = array_map('array_sum', $attendanceSeries);
$attendanceRecords = array_sum($attendanceTotals);
$attendanceRate = $attendanceRecords > 0
	? round(($attendanceTotals['present'] + $attendanceTotals['late']) / $attendanceRecords * 100)
	: null;

// Current semester (progress clamped to the semester's own dates)
$currentSemester = dash_rows($pdo, "SELECT s.name, s.start_date, s.end_date, s.school_year
	FROM current_semester cs JOIN semester s ON cs.semester = s.name LIMIT 1")[0] ?? null;
$semesterPct = 0;
$semesterStatus = 'No semester is set as current.';
if ($currentSemester) {
	$start = new DateTime($currentSemester['start_date']);
	$end   = new DateTime($currentSemester['end_date']);
	$today = new DateTime('today');
	$totalDays = max(1, $start->diff($end)->days);

	if ($today < $start) {
		$semesterStatus = 'Starts in ' . $today->diff($start)->days . ' days';
	} elseif ($today > $end) {
		$semesterPct = 100;
		$semesterStatus = 'Ended ' . $end->format('M j, Y') . ' — set the next semester';
	} else {
		$semesterPct = round($start->diff($today)->days / $totalDays * 100);
		$semesterStatus = $today->diff($end)->days . ' days remaining';
	}
}
$semesterChoices = dash_rows($pdo, "SELECT name FROM semester WHERE archived = 0 OR archived IS NULL ORDER BY start_date DESC");

// Notes, reminders and notifications
$notes = dash_rows($pdo, "SELECT * FROM admin_notes ORDER BY datetime_created DESC");
$reminders = dash_rows($pdo, "SELECT * FROM admin_reminders ORDER BY due_date ASC, datetime_created DESC");
$upcomingReminders = array_values(array_filter($reminders, fn($r) => ($r['due_date'] ?? '') >= date('Y-m-d')));
$overdueReminders = count($reminders) - count($upcomingReminders);
$unreadNotifications = (int) dash_value($pdo, "SELECT COUNT(*) FROM admin_notifications WHERE status = 'unread'");
$recentActivity = dash_rows($pdo, "SELECT title, description, date, type, status FROM admin_notifications ORDER BY date DESC LIMIT 6");

$attentionItems = [
	['icon' => 'bi-person-exclamation', 'title' => 'Learner accounts awaiting activation', 'meta' => 'Registered but not yet active', 'count' => $pendingStudents, 'link' => null],
	['icon' => 'bi-door-open', 'title' => 'Class requests to review', 'meta' => 'Submitted by teachers', 'count' => $pendingClasses, 'link' => 'class_management.php'],
	['icon' => 'bi-bell', 'title' => 'Unread notifications', 'meta' => 'Registrations and system alerts', 'count' => $unreadNotifications, 'link' => '#notificationsModal'],
	['icon' => 'bi-alarm', 'title' => 'Overdue reminders', 'meta' => 'Past their due date', 'count' => $overdueReminders, 'link' => '#remindersModal'],
];
$attentionTotal = array_sum(array_column($attentionItems, 'count'));

$sexColors = ['#2f5bb7', '#b08f2e', '#5d6475', '#9aa1ad'];
$maxGrade = max(array_merge([1], array_map('intval', array_column($byGrade, 'total'))));
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Dashboard | DPLMHS-SHS Admin Portal</title>
	<?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
	<link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
	<link href="<?= admin_asset('css/dashboard.css') ?>" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
	<!-- js/app.js bundles an older Chart.js that replaces window.Chart, so keep v4 under its own name. -->
	<script>window.DashChart = window.Chart;</script>
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
				<div class="container-fluid p-0 dash">

					<!-- Hero -->
					<section class="dash-hero">
						<div>
							<span class="dash-eyebrow">School Administration</span>
							<h1><?= $greeting ?>, <?= $e($admin_first_name) ?>.</h1>
							<p>Here's how Don Pablo Lorenzo Memorial High School – Senior High is doing today.</p>
							<div class="dash-chips">
								<span class="dash-chip"><i class="bi bi-calendar-event"></i> <?= date('l, F j, Y') ?></span>
								<?php if ($currentSemester): ?>
									<span class="dash-chip"><i class="bi bi-mortarboard"></i>
										<?= $currentSemester['school_year'] ? 'S.Y. ' . $e($currentSemester['school_year']) . ' · ' : '' ?><?= $e($currentSemester['name']) ?>
									</span>
								<?php endif; ?>
								<?php if ($attentionTotal > 0): ?>
									<span class="dash-chip"><i class="bi bi-exclamation-circle"></i> <?= $attentionTotal ?> item<?= $attentionTotal === 1 ? '' : 's' ?> need attention</span>
								<?php endif; ?>
							</div>
						</div>
						<div class="dash-hero-actions">
							<a href="post_management.php" class="btn dash-btn dash-btn-gold"><i class="bi bi-megaphone"></i> Post announcement</a>
							<a href="../index.php" target="_blank" class="btn dash-btn dash-btn-ghost"><i class="bi bi-globe2"></i> View website</a>
						</div>
					</section>

					<!-- KPIs -->
					<h2 class="dash-section-title">At a glance</h2>
					<div class="row g-3 mb-4">
						<div class="col-6 col-md-4 col-xl">
							<a href="class_management.php" class="dash-kpi">
								<div class="dash-kpi-top">
									<span class="dash-kpi-label">Learners</span>
									<span class="dash-kpi-icon"><i class="bi bi-people-fill"></i></span>
								</div>
								<div class="dash-kpi-value"><?= number_format($totalStudents) ?></div>
								<div class="dash-kpi-sub">
									<strong><?= $activeStudents ?></strong> active
									<?php if ($pendingStudents > 0): ?> · <span class="is-alert"><?= $pendingStudents ?> pending</span><?php endif; ?>
								</div>
							</a>
						</div>
						<div class="col-6 col-md-4 col-xl">
							<a href="teacher_management.php" class="dash-kpi">
								<div class="dash-kpi-top">
									<span class="dash-kpi-label">Teachers</span>
									<span class="dash-kpi-icon"><i class="bi bi-person-badge-fill"></i></span>
								</div>
								<div class="dash-kpi-value"><?= number_format($totalTeachers) ?></div>
								<div class="dash-kpi-sub"><strong><?= $activeTeachers ?></strong> active · <strong><?= $totalAdvisers ?></strong> advisers</div>
							</a>
						</div>
						<div class="col-6 col-md-4 col-xl">
							<a href="class_management.php" class="dash-kpi">
								<div class="dash-kpi-top">
									<span class="dash-kpi-label">Classes</span>
									<span class="dash-kpi-icon"><i class="bi bi-door-open-fill"></i></span>
								</div>
								<div class="dash-kpi-value"><?= number_format($activeClasses) ?></div>
								<div class="dash-kpi-sub">
									<strong><?= $totalEnrolments ?></strong> enrolments
									<?php if ($pendingClasses > 0): ?> · <span class="is-alert"><?= $pendingClasses ?> to review</span><?php endif; ?>
								</div>
							</a>
						</div>
						<div class="col-6 col-md-6 col-xl">
							<a href="subject_management.php" class="dash-kpi">
								<div class="dash-kpi-top">
									<span class="dash-kpi-label">Subjects</span>
									<span class="dash-kpi-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
								</div>
								<div class="dash-kpi-value"><?= number_format($activeSubjects) ?></div>
								<div class="dash-kpi-sub">Offered this school year</div>
							</a>
						</div>
						<div class="col-12 col-md-6 col-xl">
							<a href="post_management.php" class="dash-kpi">
								<div class="dash-kpi-top">
									<span class="dash-kpi-label">Published posts</span>
									<span class="dash-kpi-icon"><i class="bi bi-newspaper"></i></span>
								</div>
								<div class="dash-kpi-value"><?= number_format($publishedPosts) ?></div>
								<div class="dash-kpi-sub">
									<?= $draftPosts ?> draft<?= $draftPosts === 1 ? '' : 's' ?>
									<?php if ($lastPublished): ?> · last <?= $e(dash_ago($lastPublished)) ?><?php endif; ?>
								</div>
							</a>
						</div>
					</div>

					<!-- Enrolment -->
					<h2 class="dash-section-title">Enrolment</h2>
					<div class="row g-3 mb-4">
						<div class="col-lg-8">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Learners by strand</h2>
										<p>All registered learners, grouped by track or strand</p>
									</div>
									<a href="class_management.php" class="btn dash-btn dash-btn-sm">Manage</a>
								</div>
								<?php if ($byStrand): ?>
									<div class="dash-chart"><canvas id="strandChart" aria-label="Bar chart of learners by strand" role="img"></canvas></div>
									<table class="dash-sr">
										<caption>Learners by strand</caption>
										<?php foreach ($byStrand as $row): ?><tr><th><?= $e($row['label']) ?></th><td><?= (int) $row['total'] ?></td></tr><?php endforeach; ?>
									</table>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-bar-chart"></i>No learners registered yet.</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Learner profile</h2>
										<p>By grade level and sex</p>
									</div>
								</div>
								<?php if ($totalStudents > 0): ?>
									<?php foreach ($byGrade as $row): ?>
										<div class="dash-meter">
											<div class="dash-meter-row"><span><?= $e($row['label']) ?></span><span><?= (int) $row['total'] ?></span></div>
											<div class="dash-meter-track"><div class="dash-meter-fill" style="width: <?= round($row['total'] / $maxGrade * 100) ?>%"></div></div>
										</div>
									<?php endforeach; ?>

									<div class="dash-meter-row mt-4"><span>Sex</span><span><?= $totalStudents ?> total</span></div>
									<div class="dash-split" role="img" aria-label="Learners by sex">
										<?php foreach ($bySex as $i => $row): ?>
											<span style="width: <?= $row['total'] / $totalStudents * 100 ?>%; background: <?= $sexColors[$i] ?? '#9aa1ad' ?>"
												title="<?= $e($row['label']) ?>: <?= (int) $row['total'] ?>"></span>
										<?php endforeach; ?>
									</div>
									<ul class="dash-legend mt-2">
										<?php foreach ($bySex as $i => $row): ?>
											<li><span class="dash-swatch" style="background: <?= $sexColors[$i] ?? '#9aa1ad' ?>"></span>
												<?= $e($row['label']) ?> · <?= (int) $row['total'] ?> (<?= round($row['total'] / $totalStudents * 100) ?>%)</li>
										<?php endforeach; ?>
									</ul>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-person"></i>Learner details will appear once accounts are registered.</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Attendance & semester -->
					<h2 class="dash-section-title">Attendance &amp; calendar</h2>
					<div class="row g-3 mb-4">
						<div class="col-lg-8">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Daily attendance</h2>
										<p>Last <?= $attendanceDays ?> days across all classes<?= $attendanceRate !== null ? " · {$attendanceRate}% present or late" : '' ?></p>
									</div>
									<ul class="dash-legend">
										<li><span class="dash-swatch" style="background:#2f5bb7"></span>Present · <?= $attendanceTotals['present'] ?></li>
										<li><span class="dash-swatch" style="background:#b08f2e"></span>Late · <?= $attendanceTotals['late'] ?></li>
										<li><span class="dash-swatch" style="background:#c4314b"></span>Absent · <?= $attendanceTotals['absent'] ?></li>
									</ul>
								</div>
								<?php if ($attendanceRecords > 0): ?>
									<div class="dash-chart"><canvas id="attendanceChart" aria-label="Stacked bar chart of daily attendance" role="img"></canvas></div>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-calendar-check"></i>No attendance has been recorded in the last <?= $attendanceDays ?> days.</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Current semester</h2>
										<p><?= $e($semesterStatus) ?></p>
									</div>
									<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#semesterModal">Change</button>
								</div>
								<?php if ($currentSemester): ?>
									<div class="dash-ring-wrap">
										<div class="dash-ring" style="--pct: <?= $semesterPct ?>" role="img" aria-label="Semester <?= $semesterPct ?>% complete">
											<span><?= $semesterPct ?>%<small>complete</small></span>
										</div>
										<ul class="dash-facts flex-grow-1">
											<li><span>Semester</span><span><?= $e($currentSemester['name']) ?></span></li>
											<li><span>School year</span><span><?= $e($currentSemester['school_year'] ?: '—') ?></span></li>
											<li><span>Starts</span><span><?= date('M j, Y', strtotime($currentSemester['start_date'])) ?></span></li>
											<li><span>Ends</span><span><?= date('M j, Y', strtotime($currentSemester['end_date'])) ?></span></li>
										</ul>
									</div>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-calendar2-x"></i>Choose a current semester to track progress.</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Operations -->
					<h2 class="dash-section-title">Operations</h2>
					<div class="row g-3 mb-4">
						<div class="col-lg-4">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Needs attention</h2>
										<p>Items waiting on an administrator</p>
									</div>
								</div>
								<ul class="dash-list">
									<?php foreach ($attentionItems as $item):
										$isModal = $item['link'] && $item['link'][0] === '#'; ?>
										<li>
											<span class="dash-list-icon <?= $item['count'] > 0 ? 'is-alert' : 'is-ok' ?>"><i class="bi <?= $item['count'] > 0 ? $item['icon'] : 'bi-check-lg' ?>"></i></span>
											<span class="dash-list-body">
												<?php if ($item['link']): ?>
													<a class="dash-list-title" href="<?= $isModal ? '#' : $item['link'] ?>"
														<?= $isModal ? 'data-bs-toggle="modal" data-bs-target="' . $item['link'] . '"' : '' ?>><?= $item['title'] ?></a>
												<?php else: ?>
													<span class="dash-list-title"><?= $item['title'] ?></span>
												<?php endif; ?>
												<span class="dash-list-meta"><?= $item['count'] > 0 ? $item['meta'] : 'All clear' ?></span>
											</span>
											<span class="dash-count <?= $item['count'] > 0 ? 'is-alert' : '' ?>"><?= $item['count'] ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Recent activity</h2>
										<p>Latest system notifications</p>
									</div>
									<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#notificationsModal">View all</button>
								</div>
								<?php if ($recentActivity): ?>
									<ul class="dash-list">
										<?php foreach ($recentActivity as $item): ?>
											<li>
												<span class="dash-list-icon"><i class="bi <?= $item['type'] === 'student' ? 'bi-person-plus' : 'bi-info-circle' ?>"></i></span>
												<span class="dash-list-body">
													<span class="dash-list-title"><?= $e($item['title']) ?><?= $item['status'] === 'unread' ? ' <span class="dash-badge dash-badge-high">New</span>' : '' ?></span>
													<span class="dash-list-meta"><?= $e(dash_ago($item['date'])) ?></span>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-activity"></i>No recent activity.</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Quick actions</h2>
										<p>Common administrative tasks</p>
									</div>
								</div>
								<div class="dash-actions">
									<a href="teacher_management.php" class="btn dash-action"><i class="bi bi-person-badge-fill"></i> Teachers</a>
									<a href="class_management.php" class="btn dash-action"><i class="bi bi-door-open-fill"></i> Classes</a>
									<a href="subject_management.php" class="btn dash-action"><i class="bi bi-journal-bookmark-fill"></i> Subjects</a>
									<a href="semester_management.php" class="btn dash-action"><i class="bi bi-calendar-range-fill"></i> Semesters</a>
									<a href="post_management.php" class="btn dash-action"><i class="bi bi-newspaper"></i> News &amp; posts</a>
									<a href="gallery_management.php" class="btn dash-action"><i class="bi bi-images"></i> Gallery</a>
									<a href="admin_management.php" class="btn dash-action"><i class="bi bi-shield-lock-fill"></i> Admin users</a>
									<button type="button" class="btn dash-action" data-bs-toggle="modal" data-bs-target="#addReminderModal"><i class="bi bi-alarm-fill"></i> New reminder</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Notes & reminders -->
					<h2 class="dash-section-title">Your workspace</h2>
					<div class="row g-3 mb-4">
						<div class="col-lg-6">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Upcoming reminders</h2>
										<p><?= count($upcomingReminders) ?> upcoming<?= $overdueReminders > 0 ? " · {$overdueReminders} overdue" : '' ?></p>
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#remindersModal">All</button>
										<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#addReminderModal"><i class="bi bi-plus-lg"></i> Add</button>
									</div>
								</div>
								<?php if ($upcomingReminders): ?>
									<ul class="dash-list">
										<?php foreach (array_slice($upcomingReminders, 0, 4) as $reminder):
											$level = strtolower($reminder['level'] ?? 'low'); ?>
											<li>
												<span class="dash-list-icon"><i class="bi bi-alarm"></i></span>
												<span class="dash-list-body">
													<span class="dash-list-title"><?= $e($reminder['title']) ?>
														<span class="dash-badge dash-badge-<?= in_array($level, ['high', 'medium']) ? $level : 'low' ?>"><?= $e($reminder['level']) ?></span>
													</span>
													<span class="dash-list-meta">Due <?= date('M j, Y', strtotime($reminder['due_date'])) ?> · <?= $e($reminder['due_time']) ?></span>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-alarm"></i>No upcoming reminders.</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="dash-panel">
								<div class="dash-panel-head">
									<div>
										<h2>Notes</h2>
										<p><?= count($notes) ?> saved note<?= count($notes) === 1 ? '' : 's' ?></p>
									</div>
									<div class="d-flex gap-2">
										<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#notesModal">All</button>
										<button type="button" class="btn dash-btn dash-btn-sm" data-bs-toggle="modal" data-bs-target="#addNotesModal"><i class="bi bi-plus-lg"></i> Add</button>
									</div>
								</div>
								<?php if ($notes): ?>
									<ul class="dash-list">
										<?php foreach (array_slice($notes, 0, 4) as $note): ?>
											<li>
												<span class="dash-list-icon"><i class="bi bi-sticky"></i></span>
												<span class="dash-list-body">
													<span class="dash-list-title"><?= $e($note['title']) ?></span>
													<span class="dash-list-meta"><?= date('M j, Y', strtotime($note['datetime_created'])) ?></span>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else: ?>
									<div class="dash-empty"><i class="bi bi-sticky"></i>No notes yet.</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

				</div>
			</main>
		</div>
	</div>

	<!-- Notes -->
	<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="notesModalLabel">Notes</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="d-flex align-items-center mb-3">
						<strong>View Notes</strong>
						<a href="#" class="nav-ham-link ms-auto" data-bs-toggle="modal" data-bs-target="#addNotesModal">Add New Note</a>
					</div>
					<div class="accordion" id="notesAccordion">
						<?php if ($notes): ?>
							<?php foreach ($notes as $index => $note): ?>
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
											data-bs-target="#collapseNote<?= $index ?>" aria-expanded="false" aria-controls="collapseNote<?= $index ?>">
											<?= $e($note['title']) ?> (<?= date('m/d/Y', strtotime($note['datetime_created'])) ?>)
										</button>
									</h2>
									<div id="collapseNote<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#notesAccordion">
										<div class="accordion-body">
											<div class="d-flex align-items-center">
												<p class="mb-2"><?= $e($note['title']) ?>
													<a href="processes/admin/notes/delete.php?id=<?= (int) $note['id'] ?>" style="color: red !important"
														aria-label="Delete note"><i class="bi bi-trash-fill" style="color: red !important"></i></a>
												</p>
												<p class="ms-auto mb-2">Date: (<?= date('m/d/Y', strtotime($note['datetime_created'])) ?>)</p>
											</div>
											<p><em><?= $e($note['description']) ?></em></p>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p>No notes available</p>
						<?php endif; ?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="addNotesModal" tabindex="-1" aria-labelledby="addNotesModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<form class="modal-content" method="POST" action="processes/admin/notes/add.php">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="addNotesModalLabel">Add a New Note</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<label for="noteTitle" class="form-label">Title</label>
					<input type="text" class="form-control mb-3" id="noteTitle" name="title" required>
					<label for="noteDescription" class="form-label">Description</label>
					<textarea class="form-control" id="noteDescription" name="description" rows="4" required></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<input type="submit" class="btn btn-primary" value="Save note">
				</div>
			</form>
		</div>
	</div>

	<!-- Reminders -->
	<div class="modal fade" id="remindersModal" tabindex="-1" aria-labelledby="remindersModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="remindersModalLabel">Reminders</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="d-flex align-items-center mb-3">
						<strong>View Reminders</strong>
						<a href="#" class="nav-ham-link ms-auto" data-bs-toggle="modal" data-bs-target="#addReminderModal">Add New Reminder</a>
					</div>
					<div class="accordion" id="reminderAccordion">
						<?php if ($reminders): ?>
							<?php foreach ($reminders as $index => $reminder): ?>
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
											data-bs-target="#collapseReminder<?= $index ?>" aria-expanded="false">
											<span><?= $e($reminder['title']) ?><br><small>Due: <?= $e($reminder['due_date']) ?> <?= $e($reminder['due_time']) ?></small></span>
											&nbsp;&nbsp;
											<span class="badge <?= ($reminder['level'] == 'High') ? 'text-bg-danger' : (($reminder['level'] == 'Medium') ? 'text-bg-primary' : 'text-bg-secondary') ?>">
												<?= $e($reminder['level']) ?>
											</span>
										</button>
									</h2>
									<div id="collapseReminder<?= $index ?>" class="accordion-collapse collapse" data-bs-parent="#reminderAccordion">
										<div class="accordion-body">
											<p><?= $e($reminder['description']) ?>
												<a href="processes/admin/reminders/delete.php?id=<?= (int) $reminder['id'] ?>" aria-label="Delete reminder"><i class="bi bi-trash-fill" style="color:red"></i></a>
											</p>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p>No reminders available</p>
						<?php endif; ?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="addReminderModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<form class="modal-content" method="POST" action="processes/admin/reminders/add.php">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="addReminderModalLabel">Add a New Reminder</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<label for="reminderTitle" class="form-label">Title</label>
					<input type="text" class="form-control mb-3" id="reminderTitle" name="title" required>
					<div class="row g-3 mb-3">
						<div class="col-6">
							<label for="reminderDate" class="form-label">Due date</label>
							<input type="date" class="form-control" id="reminderDate" name="due_date" required>
						</div>
						<div class="col-6">
							<label for="reminderTime" class="form-label">Due time</label>
							<input type="time" class="form-control" id="reminderTime" name="due_time" required>
						</div>
					</div>
					<label for="reminderLevel" class="form-label">Priority</label>
					<select class="form-select mb-3" id="reminderLevel" name="status" required>
						<option value="" disabled selected>Select a priority</option>
						<option value="High">High</option>
						<option value="Medium">Medium</option>
						<option value="Low">Low</option>
					</select>
					<label for="reminderDescription" class="form-label">Description</label>
					<textarea class="form-control" id="reminderDescription" name="description" rows="3" required></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<input type="submit" class="btn btn-primary" value="Save reminder">
				</div>
			</form>
		</div>
	</div>

	<!-- Current semester -->
	<div class="modal fade" id="semesterModal" tabindex="-1" aria-labelledby="semesterModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<form class="modal-content" method="POST" action="processes/admin/semester/update_current_semester.php">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="semesterModalLabel">Current Semester</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<label for="semesterSelect" class="form-label">Set the current semester</label>
					<select class="form-select" id="semesterSelect" name="semester">
						<?php if ($semesterChoices): ?>
							<?php foreach ($semesterChoices as $row): ?>
								<option value="<?= $e($row['name']) ?>" <?= $currentSemester && $currentSemester['name'] === $row['name'] ? 'selected' : '' ?>><?= $e($row['name']) ?></option>
							<?php endforeach; ?>
						<?php else: ?>
							<option value="" disabled>No semesters available</option>
						<?php endif; ?>
					</select>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					<input type="submit" class="btn btn-primary" value="Save changes">
				</div>
			</form>
		</div>
	</div>

	<script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
	<script src="<?= admin_asset('js/admin-ui.js') ?>"></script>

	<script>
		(function() {
			const Chart = window.DashChart;
			if (!Chart) return;

			const ink = '#5d6475';
			const grid = 'rgba(6, 30, 78, 0.07)';
			Chart.defaults.font.family = "'Source Sans 3', 'Segoe UI', Roboto, Arial, sans-serif";
			Chart.defaults.color = ink;

			const tooltip = {
				backgroundColor: '#061E4E',
				titleColor: '#ffffff',
				bodyColor: '#ffffff',
				padding: 10,
				cornerRadius: 6,
				displayColors: true,
				boxPadding: 4
			};

			const strandEl = document.getElementById('strandChart');
			if (strandEl) {
				new Chart(strandEl, {
					type: 'bar',
					data: {
						labels: <?= json_encode(array_column($byStrand, 'label')) ?>,
						datasets: [{
							label: 'Learners',
							data: <?= json_encode(array_map('intval', array_column($byStrand, 'total'))) ?>,
							backgroundColor: '#2f5bb7',
							hoverBackgroundColor: '#061E4E',
							borderRadius: 4,
							borderSkipped: 'start',
							maxBarThickness: 28
						}]
					},
					options: {
						indexAxis: 'y',
						maintainAspectRatio: false,
						plugins: {
							legend: { display: false },
							tooltip: tooltip
						},
						scales: {
							x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid }, border: { display: false } },
							y: { grid: { display: false }, border: { display: false }, ticks: { color: '#1f2430', font: { weight: 600 } } }
						}
					}
				});
			}

			const attendanceEl = document.getElementById('attendanceChart');
			if (attendanceEl) {
				const series = [
					['Present', <?= json_encode(array_values($attendanceSeries['present'])) ?>, '#2f5bb7'],
					['Late', <?= json_encode(array_values($attendanceSeries['late'])) ?>, '#b08f2e'],
					['Absent', <?= json_encode(array_values($attendanceSeries['absent'])) ?>, '#c4314b']
				];
				new Chart(attendanceEl, {
					type: 'bar',
					data: {
						labels: <?= json_encode($attendanceLabels) ?>,
						datasets: series.map(([label, data, color]) => ({
							label,
							data,
							backgroundColor: color,
							borderColor: '#ffffff',
							borderWidth: { top: 2 },
							borderRadius: 4,
							borderSkipped: 'start',
							maxBarThickness: 26
						}))
					},
					options: {
						maintainAspectRatio: false,
						interaction: { mode: 'index', intersect: false },
						plugins: {
							legend: { display: false },
							tooltip: tooltip
						},
						scales: {
							x: { stacked: true, grid: { display: false }, border: { display: false } },
							y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid }, border: { display: false } }
						}
					}
				});
			}
		})();
	</script>

</body>

<?php include('processes/server/alerts.php'); ?>

</html>
