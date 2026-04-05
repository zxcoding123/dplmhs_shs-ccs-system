<?php
session_start();
include('processes/server/conn.php');
require '../app/helpers/auth.php'; // adjust path to your file
requireRole('admin'); // ensures only admins can access
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>AdNU - CCS | Student Management System</title>
	<link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

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
				<img src="external/img/ADNU_Logo.png" class="logo-small">
				<span class="text-white"><b>AdNU</b> - Student Management System </span>
				<div class="navbar-collapse collapse">
					<?php
					include('top-bar.php');
					?>
				</div>
			</nav>
			<main class="content">
				<div class="container-fluid p-0">
					<h1 class="h3 mb-3"><strong>Welcome to your Dashboard!</strong></h1>
					<p>Welcome back! Here's to another day of making a difference in our students' lives.</p>

					<div class="row">
						<div class="col">
							<div class="w-100">
								<div class="row">
									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<?php
												// Include the database connection
												require_once 'processes/server/conn.php';

												// Query to get the total number of students
												$stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM students");
												$result = $stmt->fetch(PDO::FETCH_ASSOC);

												// Get the total count
												$totalStudents = $result['total_students'] ?? 0;
												?>
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Total Students</h5>
													</div>

													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-person-fill"></i>
														</div>
													</div>
												</div>
												<!-- Display the total count dynamically -->
												<h1 class="mt-1 mb-3"><?php echo htmlspecialchars($totalStudents); ?>
												</h1>
											</div>

										</div>
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Total Classes</h5>
													</div>

													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-person-video3"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3"> <?php
																		// TO-DO: Implement getting number of total classes from database
																		$sql = "SELECT COUNT(*) AS total from classes";
																		$stmt = $pdo->prepare($sql);
																		$stmt->execute();
																		$result = $stmt->fetch(PDO::FETCH_ASSOC);
																		$totalClasses = $result['total'];
																		echo $totalClasses;
																		?></h1>

											</div>
										</div>
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Total Subjects</h5>
													</div>

													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-book-half"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3"> <?php
																		// TO-DO: Implement getting number of total classes from database
																		$sql = "SELECT COUNT(*) AS total from subjects";
																		$stmt = $pdo->prepare($sql);
																		$stmt->execute();
																		$result = $stmt->fetch(PDO::FETCH_ASSOC);
																		$totalSubjects = $result['total'];
																		echo $totalSubjects;
																		?></h1>

											</div>
										</div>





										<div class="col-sm">
											<div class="card">
												<div class="card-body text-center">
													<div class="row">
														<div class="col">
															<h5 class="card-title" style="margin-bottom: 20px !important">Quick Access</h5>
														</div>
													</div>
													<div class="row text-center">
														<div class="col">
															<a href="teacher_management.php" class="nav-link-shortcut d-block mb-2">
																<i class="bi bi-person-badge-fill me-1"></i>
																<span>Teachers</span>
															</a>
															<a href="subject_management.php" class="nav-link-shortcut d-block">
																<i class="bi bi-journal-bookmark-fill me-1"></i>
																<span>Subjects</span>
															</a>
														</div>
														<div class="col">
															<a href="post_management.php" class="nav-link-shortcut d-block">
																<i class="bi bi-stickies-fill me-1"></i>
																<span>Posts</span>
															</a>
														</div>
														<div class="col">
															<a href="class_management.php" class="nav-link-shortcut d-block mb-2">
																<i class="bi bi-door-open-fill me-1"></i>
																<span>Classes</span>
															</a>
															<a href="semester_management.php" class="nav-link-shortcut d-block">
																<i class="bi bi-calendar-range-fill me-1"></i>
																<span>Semester</span>
															</a>
														</div>
													</div>
												</div>
											</div>
										</div>

									</div>
									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Total Teachers</h5>
													</div>

													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-person-fill-gear"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3"> <?php
																		// TO-DO: Implement getting number of total students from database
																		$sql = "SELECT COUNT(*) AS total from staff_accounts";
																		$stmt = $pdo->prepare($sql);
																		$stmt->execute();
																		$result = $stmt->fetch(PDO::FETCH_ASSOC);
																		$totalTeachers = $result['total'];
																		echo $totalTeachers;
																		?></h5>
												</h1>

											</div>
										</div>

										<?php
										$sql = "SELECT s.name, s.start_date, s.end_date 
        FROM current_semester cs
        JOIN semester s ON cs.semester = s.name 
        LIMIT 1";

										$stmt = $pdo->query($sql);
										$currentSemester = $stmt->fetch(PDO::FETCH_ASSOC);

										if ($currentSemester) {
											$semesterName = $currentSemester['name'];
											$startDate = new DateTime($currentSemester['start_date']);
											$endDate = new DateTime($currentSemester['end_date']);
											$currentDate = new DateTime(); // Get today's date

											$totalDays = $startDate->diff($endDate)->days; // Total days of the semester
											$remainingDays = $currentDate->diff($endDate)->days; // Days left in the semester

											// Avoid division by zero if totalDays is 0
											$progressPercentage = ($totalDays > 0) ? (100 - (($remainingDays / $totalDays) * 100)) : 0;
										} else {
											// No current semester found
											$semesterName = "No Active Semester"; // Optional: Display a fallback name
											$progressPercentage = 0;
										}
										?>


										<div class="card">
											<div class="card-body">
												<div class="row" data-bs-toggle="modal" data-bs-target="#semesterModal">
													<div class="col mt-0">
														<h5 class="card-title">Current Semester</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-calendar2-range"></i>
														</div>
													</div>
												</div>


												<h1 class="mt-1 mb-3">
													<?php
													if ($currentSemester) {
														echo $semesterName;
													} else {
														echo "No current semester is set.";
													}
													?>
												</h1>

												<?php if ($currentSemester): ?>
													<div class="progress mt-3">
														<div class="progress-bar" role="progressbar"
															style="width: <?php echo $progressPercentage; ?>%;"
															aria-valuenow="<?php echo $progressPercentage; ?>"
															aria-valuemin="0" aria-valuemax="100">
															<?php echo round($progressPercentage); ?>%
														</div>
													</div>
													<p><?php echo $remainingDays; ?> days left in the semester.</p>
												<?php endif; ?>
												<?php if ($currentSemester) { ?>
													<div class="d-flex align-items-center justify-content-center">
														<canvas id="semesterProgress"></canvas>
														<script>
															var ctx = document.getElementById('semesterProgress').getContext('2d');
															var chart = new Chart(ctx, {
																type: 'pie', // Changed to 'pie' for a pie chart
																data: {
																	datasets: [{
																		data: [<?php echo $progressPercentage; ?>, <?php echo 100 - $progressPercentage; ?>],
																		backgroundColor: ['#1127A3FF', '#d3d3d3'], // Colors for progress and remaining
																		borderWidth: 2 // Border thickness for pie segments
																	}],
																	labels: ['Progress', 'Remaining'] // Labels for the data
																},
																options: {
																	responsive: false, // Adjusts the size based on the container
																	maintainAspectRatio: true, // Ensures the chart stays circular
																	tooltips: {
																		enabled: true // Enables tooltips for better user experience
																	},
																	legend: {
																		display: true, // Shows the legend
																		position: 'bottom' // Legend position at the bottom
																	}
																}
															});
														</script>
													</div>


												<?php } ?>
											</div>
										</div>

										<?php

										// Query to get the total number of students
										$stmt = $pdo->query("SELECT COUNT(*) AS total_posts FROM posts");
										$result = $stmt->fetch(PDO::FETCH_ASSOC);

										// Get the total count
										$totalPosts = $result['total_posts'] ?? 0;
										?>
										<div class="card">
											<div class="card-body">
												<div class="row mb-3">
													<div class="col ">
														<h5 class="card-title">Total Posts</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="bi bi-stickies-fill"></i>
														</div>
													</div>
												</div>

												<h1>
													<?php echo $totalPosts; ?>
												</h1>


											</div>
										</div>


									</div>


									<hr>

									<div class="row">

										<div class="col-sm">
											<div class="card">
												<div class="card-body text-center">
													<div class="row">
														<div class="col mt-0" data-bs-toggle="modal"
															data-bs-target="#notesModal">
															<h5 class="card-title">Notes</h5>
															<?php
															$notes = [];
															try {
																$stmt = $pdo->query("SELECT * FROM admin_notes ORDER BY datetime_created DESC");
																$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
															} catch (PDOException $e) {
																echo "Error fetching notes: " . $e->getMessage();
															}
															?>

															<ul>
																<?php if (!empty($notes)): ?>
																	<?php foreach ($notes as $note): ?>
																		<li><?= htmlspecialchars($note['title']) ?> </li>
																	<?php endforeach; ?>
																<?php else: ?>
																	<li>No notes available</li>
																<?php endif; ?>
															</ul>

														</div>
													</div>

												</div>
											</div>
										</div>
										<div class="col-sm">
											<div class="card">
												<div class="card-body text-center">
													<div class="row">
														<div class="col mt-0" data-bs-toggle="modal"
															data-bs-target="#remindersModal">
															<h5 class="card-title">Reminders</h5>
															<?php
															$reminders = [];
															try {
																$stmt = $pdo->query("SELECT * FROM admin_reminders ORDER BY datetime_created DESC");
																$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
															} catch (PDOException $e) {
																echo "Error fetching reminders: " . $e->getMessage();
															}
															?>


															<ul>
																<?php if (!empty($reminders)): ?>
																	<?php foreach ($reminders as $reminder): ?>
																		<li><?= htmlspecialchars($reminder['title']) ?></li>
																	<?php endforeach; ?>
																<?php else: ?>
																	<li>No reminders available</li>
																<?php endif; ?>

														</div>

													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
			</main>
		</div>
	</div>


	<?php
	include('processes/server/conn.php');
	date_default_timezone_set('Asia/Manila');

	$notes = [];
	try {
		$stmt = $pdo->query("SELECT * FROM admin_notes ORDER BY datetime_created DESC");
		$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		echo "Error fetching notes: " . $e->getMessage();
	}

	?>

	<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Notes</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="d-flex align-items-center">
						<strong role="status">View Notes</strong>
						<div class="ms-auto" aria-hidden="true">
							<a href="#" class="nav-ham-link" data-bs-toggle="modal" data-bs-target="#addNotesModal">Add
								New Note</a>
						</div>
					</div>
					<br>
					<div class="container-fluid">
						<div class="accordion" id="accordionExample">
							<?php if (!empty($notes)): ?>
								<?php foreach ($notes as $index => $note): ?>
									<div class="accordion-item">
										<h2 class="accordion-header">
											<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
												data-bs-target="#collapseNote<?= $index ?>" aria-expanded="false"
												aria-controls="collapseNote<?= $index ?>">
												<?= htmlspecialchars($note['title']) ?>
												(<?= date('m/d/Y', strtotime($note['datetime_created'])) ?>)
												&nbsp;
											</button>
										</h2>
										<div id="collapseNote<?= $index ?>" class="accordion-collapse collapse"
											data-bs-parent="#accordionExample">
											<div class="accordion-body">
												<div class="d-flex align-items-center">
													<p role="status"><?= htmlspecialchars($note['title']) ?> <a
															href="processes/admin/notes/delete.php?id=<?php echo $note['id'] ?> ?>"
															style="color: red !important"><i style="color: red !important"
																class="bi bi-trash-fill"></i></a></p>
													<div class="ms-auto" aria-hidden="true">
														<p>Date: (<?= date('m/d/Y', strtotime($note['datetime_created'])) ?>)
														</p>
													</div>
												</div>
												<p><em><?= htmlspecialchars($note['description']) ?></em></p>
												<!-- Add delete functionality -->

											</div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p>No notes available</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="addNotesModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Add a New Note</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form method="POST" action="processes/admin/notes/add.php">
						<label for="title">Title:</label>
						<input type="text" class="form-control" name="title" required>
						<label for="description">Description:</label>
						<textarea class="form-control" name="description" required></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-target="#notesModal"
						data-bs-toggle="modal">Close</button>
					<input type="submit" class="btn btn-primary" value="Submit">
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="remindersModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Reminders</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="d-flex align-items-center">
						<strong role="status">View Reminders</strong>
						<div class="ms-auto" aria-hidden="true">
							<a href class="nav-ham-link" data-bs-toggle="modal" data-bs-target="#addReminderModal">Add
								New Reminder</a>
						</div>
					</div>
					<br>
					<div class="container-fluid">
						<div class="accordion" id="reminderAccordion">
							<?php if (!empty($reminders)): ?>
								<?php foreach ($reminders as $index => $reminder): ?>
									<div class="accordion-item">
										<h2 class="accordion-header">
											<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
												data-bs-target="#collapse<?= $index ?>" aria-expanded="false">
												<p><?= htmlspecialchars($reminder['title']) ?> <br> Due at:
													<?= htmlspecialchars($reminder['due_time']) ?>
													<?= htmlspecialchars($reminder['due_date']) ?>
												</p> &nbsp; &nbsp;
												<span
													class="badge <?= ($reminder['level'] == 'High') ? 'text-bg-danger' : (($reminder['level'] == 'Medium') ? 'text-bg-primary' : 'text-bg-secondary') ?>">
													<?= htmlspecialchars($reminder['level']) ?>
												</span>
											</button>
										</h2>
										<div id="collapse<?= $index ?>" class="accordion-collapse collapse"
											data-bs-parent="#reminderAccordion">
											<div class="accordion-body">
												<p><?= htmlspecialchars($reminder['description']) ?> <a
														href="processes/admin/reminders/delete.php?id=<?php echo $reminder['id'] ?>"><i
															class="bi bi-trash-fill" style="color:red"></i></a></p>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p>No reminders available</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Add a New
						Remider</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form method="POST" action="processes/admin/reminders/add.php">
						<label for="title">Title:</label>
						<input type="text" class="form-control" name="title" required>
						<label for="due">Due Date:</label>
						<input type="date" class="form-control" name="due_date" required>
						<label for="due">Due Time:</label>
						<input type="time" class="form-control" name="due_time" required>
						<label for="status">Status:</label>
						<select class="form-control" name="status" required>
							<option>Select an option below: </option>
							<option value="High">High</option>
							<option value="Medium">Medium</option>
							<option value="Low">Low</option>
						</select>
						<label for="description">Description:</label>
						<textarea class="form-control" name="description" required></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-target="#notesModal"
						data-bs-toggle="modal">Close</button>
					<input type="submit" class="btn btn-primary" value="Submit">
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="semesterModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Current Semester</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form method="POST" action="processes/admin/semester/update_current_semester.php">
						<div class="mb">
							<label>Current Semester</label>
							<select class="form-control" name="semester">
								<?php
								$sql = "SELECT * FROM semester";
								$stmt = $pdo->query($sql);
								if ($stmt->rowCount() > 0) {
									while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
										echo "<option value='{$row['name']}'>{$row['name']}</option>";
									}
								} else {
									echo "<option value='' disabled>No semesters available</option>";
								}
								?>
							</select>
						</div>

				</div>
				<div class="modal-footer">
					<input type="submit" class="btn btn-primary" value="Save Changes">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

				</div>
				</form>
			</div>
		</div>
	</div>


	<script src="js/app.js"></script>

	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();

			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}

		setInterval(getTime, 100);
	</script>

	<script>
		document.getElementById('readAll').addEventListener('click', function() {
			// Perform an AJAX request to mark all notifications as read
			fetch('processes/admin/notifications/read_all.php', {
					method: 'POST'
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Update the notification count and UI
						document.querySelector('.indicator').innerText = '0';
						alert('All notifications marked as read.');
						// Optionally, reload notifications
						location.reload();
					} else {
						alert('Error marking notifications as read.');
					}
				})
				.catch(error => console.error('Error:', error));
		});

		document.getElementById('deleteAll').addEventListener('click', function() {
			if (confirm('Are you sure you want to delete all notifications?')) {
				// Perform an AJAX request to delete all notifications
				fetch('processes/admin//delete_all.php', {
						method: 'POST'
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							// Update the notification count and UI
							document.querySelector('.indicator').innerText = '0';
							alert('All notifications deleted.');
							// Optionally, reload notifications
							location.reload();
						} else {
							alert('Error deleting notifications.');
						}
					})
					.catch(error => console.error('Error:', error));
			}
		});
	</script>





</body>

<?php
include('processes/server/alerts.php');
?>

</html>