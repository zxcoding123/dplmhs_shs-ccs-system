<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
	$_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
}


include('processes/server/conn.php');

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
	<title>AdNU - CCS | Student Management System</title>
	<link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>


</head>

<style>
	table.dataTable {
		font-size: 12px;
	}

	td {
		text-align: center;
		vertical-align: middle;
		border-bottom: 1px solid black;
	}

	.btn-csms {
		background-color: #10177a;
		color: white;
		border: 1px solid white;
	}

	.btn-csms:hover {
		border: 1px solid #10177a;
		background-color: white;
		color: #10177a;
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
				<img src="external/img/ADNU_Logo.png" class="logo-small">
				<span class="text-white"><b>AdNU</b> - Student Management System </span>
				<div class="navbar-collapse collapse">
					<?php include('top-bar.php') ?>
				</div>
			</nav>

			<main class="content">
				<div class="container-fluid p-0">

					<div class="row mb-4">

						<?php
						$today = date('l'); // Get the current day of the week (e.g., "Monday")
						echo '<h1 class="h3 mb-3"><b>Classes for </b>' . htmlspecialchars($today) . '</h1>';
						?>

						<div class="col">
							<div class="card">


								<?php
								// Ensure teacher is logged in
								if (!isset($_SESSION['teacher_id'])) {
									echo "Teacher is not logged in.";
								}

								try {
									global $pdo;

									// Get the teacher's full name
									$teacher_id = $_SESSION['teacher_id'];
									$teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
									$teacher_stmt->execute(['teacher_id' => $teacher_id]);
									$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

									if (!$teacher) {
										echo "Teacher not found.";
										exit;
									}

									$teacher_name = $teacher['fullName'];

									// Fetch all subject_ids for the teacher, excluding classes with status 'pending'
									$classes_stmt = $pdo->prepare("SELECT subject_id, name, status FROM classes WHERE teacher = :teacher_name AND status != 'pending'");
									$classes_stmt->execute(['teacher_name' => $teacher_name]);
									$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

									// If no classes are assigned or they are all 'pending'
									if (!$classes) {
										echo '<div class="card-body">';
										echo '<h5 class="card-title">Today’s Classes for</h5>';
										echo '<p>No active classes are assigned for today.</p>';  // Classes are either not assigned or pending
										echo '</div>';
									} else {
										$subject_ids = array_column($classes, 'subject_id');
										if (!empty($subject_ids)) {
											$placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
											$schedules_stmt = $pdo->prepare("
                        SELECT 
                            ss.subject_id, 
                            ss.meeting_days, 
                            ss.start_time, 
                            ss.end_time, 
                            s.name AS subject_name, 
							s.course as course,
							s.year_level as year_level
                        FROM 
                            subjects_schedules ss
                        JOIN 
                            subjects s 
                        ON 
                            ss.subject_id = s.id
                        WHERE 
                            ss.subject_id IN ($placeholders)
                    ");
											$schedules_stmt->execute($subject_ids);
											$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

											// Filter schedules for today's classes
											$today = date('l'); // Get the current day of the week (e.g., "Monday")
											$todays_classes = array_filter($schedules, function ($schedule) use ($today) {
												return stripos($schedule['meeting_days'], $today) !== false;
											});

											// Display today's classes

											echo '<div class="card-body">';
											echo '<h5 class="card-title">Today’s Classes for ' . $today . ' </h5>';

											if (!empty($todays_classes)) {
												echo '<ul>';
												foreach ($todays_classes as $class) {
													echo '<li><b>'
														. htmlspecialchars($class['subject_name']) . ' </b>@ ' // Use the subject_name
														. htmlspecialchars(date("g:i A", strtotime($class['start_time'])))
														. ' - '
														. htmlspecialchars(date("g:i A", strtotime($class['end_time'])))
														.  ' - <b>(' .
														htmlspecialchars($class['course']) . ' - ' .  htmlspecialchars($class['year_level'])
														.  ')</b>' .
														'</li>';
												}
												echo '</ul>';
											} else {
												// No active classes for today
												echo '<p>No classes are scheduled for today.</p>';
											}

											echo '</div>';
										}
									}
								} catch (PDOException $e) {
									error_log("Error fetching today's classes: " . $e->getMessage());
									echo "An error occurred while fetching today's classes.";
								}
								?>
							</div>

						</div>
						<div class="col">
							<div class="card">
								<?php
								// Ensure teacher is logged in
								if (!isset($_SESSION['teacher_id'])) {
									echo "Teacher is not logged in.";
									exit;
								}

								try {
									// Get teacher name
									$teacher_id = $_SESSION['teacher_id'];
									$teacher_stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
									$teacher_stmt->execute(['teacher_id' => $teacher_id]);
									$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

									if (!$teacher) {
										echo "Teacher not found.";
										exit;
									}

									$teacher_name = $teacher['fullName'];

									// Fetch classes handled by teacher
									$classes_stmt = $pdo->prepare("
      SELECT 
    id, 
    CONCAT(name, ' - ', subject) AS class_name, 
    status 
FROM classes 
WHERE teacher = :teacher_name AND status != 'pending';
    ");
									$classes_stmt->execute(['teacher_name' => $teacher_name]);
									$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

									$classes_handling = count($classes);

									echo '<div class="card-body">';
									echo '<h5 class="card-title">Attendance Summary</h5>';

									if ($classes_handling === 0) {
										echo '<p>You haven\'t been assigned to any active classes yet!</p>';
										echo '</div>';
										exit;
									}

									// Display list of classes handled
									echo '<p><b>Classes Handling (' . $classes_handling . '):</b></p>';
									echo '<ul>';
									foreach ($classes as $class) {
										echo '<li>' . htmlspecialchars($class['class_name']) . '</li>';
									}
									echo '</ul>';

									// Initialize attendance totals
									$total_classes = 0;
									$total_present = 0;

									// Loop through classes to calculate attendance
									foreach ($classes as $class) {
										$class_id = $class['id'];

										$meetings_stmt = $pdo->prepare("SELECT id FROM classes_meetings WHERE class_id = :class_id");
										$meetings_stmt->execute(['class_id' => $class_id]);
										$meetings = $meetings_stmt->fetchAll(PDO::FETCH_ASSOC);

										foreach ($meetings as $meeting) {
											$meeting_id = $meeting['id'];

											$attendance_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE meeting_id = :meeting_id");
											$attendance_stmt->execute(['meeting_id' => $meeting_id]);
											$total_attendance = $attendance_stmt->fetchColumn();

											$present_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE meeting_id = :meeting_id AND status = 'present'");
											$present_stmt->execute(['meeting_id' => $meeting_id]);
											$total_present_entries = $present_stmt->fetchColumn();

											$total_classes += $total_attendance;
											$total_present += $total_present_entries;
										}
									}

									if ($total_classes > 0) {
										$average_attendance_rate = ($total_present / $total_classes) * 100;
										echo '<p><b>Average Attendance Rate:</b> ' . number_format($average_attendance_rate, 2) . '%</p>';
									} else {
										echo '<p>No attendance records found.</p>';
									}

									// Fetch students with excessive absences (<75%) or 3+ absences
									$excessive_absences = [];

									foreach ($classes as $class) {
										$class_id = $class['id'];

										// Get enrolled students + full names
										$students_stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.fullName 
        FROM students_enrollments se
        INNER JOIN students s ON se.student_id = s.id
        WHERE se.class_id = :class_id
    ");
										$students_stmt->execute(['class_id' => $class_id]);
										$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

										// Total meetings for this class
										$meetings_stmt = $pdo->prepare("
        SELECT id FROM classes_meetings WHERE class_id = :class_id
    ");
										$meetings_stmt->execute(['class_id' => $class_id]);
										$total_meetings = $meetings_stmt->rowCount();

										if ($total_meetings === 0) continue;

										foreach ($students as $student) {

											$student_id = $student['student_id'];

											// Total presents for this student
											$present_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM attendance 
            WHERE meeting_id IN (
                SELECT id FROM classes_meetings WHERE class_id = :class_id
            )
            AND student_id = :student_id
            AND status = 'present'
        ");
											$present_stmt->execute(['class_id' => $class_id, 'student_id' => $student_id]);
											$total_present = $present_stmt->fetchColumn();

											// Compute absences
											$total_absences = $total_meetings - $total_present;

											// Attendance %
											$attendance_rate = ($total_present / $total_meetings) * 100;

											// Flag if below 75% OR has 3+ absences
											if ($attendance_rate < 75 || $total_absences >= 3) {
												$excessive_absences[] = [
													'student_name' => $student['fullName'],
													'class_name' => $class['class_name'],
													'attendance_rate' => number_format($attendance_rate, 2),
													'present_count' => $total_present,
													'absence_count' => $total_absences
												];
											}
										}
									}
									echo '</div>';
								} catch (PDOException $e) {
									error_log("Error fetching attendance summary: " . $e->getMessage());
									echo $e;
								}
								?>
								<!-- Button to trigger modal -->
								<button type="button" class="btn btn-danger mx-auto"
									style="width: 50%; margin-bottom: 20px;"
									data-bs-toggle="modal" data-bs-target="#excessiveAbsencesModal">
									View Students with Absences
								</button>

								<!-- Modal -->
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
													<table class="table table-bordered">
														<thead>
															<tr>
																<th>Student Name</th>
																<th>Class</th>
																<th>Present</th>
																<th>Absences</th>
																<th>Attendance Rate (%)</th>
															</tr>
														</thead>

														<tbody>
															<?php foreach ($excessive_absences as $student): ?>
																<tr>
																	<td><?= htmlspecialchars($student['student_name']) ?></td>
																	<td><?= htmlspecialchars($student['class_name']) ?></td>
																	<td><?= $student['present_count'] ?></td>
																	<td class="<?= $student['absence_count'] >= 3 ? 'text-danger fw-bold' : '' ?>">
																		<?= $student['absence_count'] ?>
																	</td>
																	<td><?= $student['attendance_rate'] ?></td>
																</tr>

															<?php endforeach; ?>
														</tbody>
													</table>
												<?php endif; ?>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
											</div>
										</div>
									</div>
								</div>

							</div>
						</div>


					</div>


					<h1 class="h3 mb-3"><strong>Analytics</strong> Dashboard</h1>


					<?php
					$teacher_name = $_SESSION['teacher_name'];
					$total_document_count = 0; // Initialize the total document count

					try {
						require_once 'processes/server/conn2.php'; // Ensure the path is correct
						$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

						// Set the PDO error mode to exception
						$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

						// SQL query to get the class IDs where the teacher matches
						$sql = "SELECT id FROM classes WHERE teacher = :teacher_name";
						$stmt = $conn->prepare($sql);
						$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
						$stmt->execute();

						// Check if any rows are returned
						if ($stmt->rowCount() > 0) {
							// Fetch all the class IDs as an associative array
							$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

							// Loop through the class IDs and get document count for each
							foreach ($results as $row) {
								$class_id = $row['id'];

								// SQL query to count documents in learning_resources for the specific class_id
								$sql_count = "SELECT COUNT(*) AS document_count 
                          FROM learning_resources 
                          WHERE class_id = :class_id 
                          AND resource_type = 'document'";

								// Prepare the count query
								$stmt_count = $conn->prepare($sql_count);
								$stmt_count->bindParam(':class_id', $class_id, PDO::PARAM_INT);
								$stmt_count->execute();

								// Fetch the result
								$count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);

								// Accumulate the document count
								$total_document_count += $count_result['document_count'];
							}
						} else {
						}
					} catch (PDOException $e) {
						echo "Error: " . $e->getMessage();
					}

					$conn = null;
					?>

					<div class="row">
						<div class="col d-flex">
							<div class="w-100">
								<div class="row">
									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Documents</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="align-middle" data-feather="file-text"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3"><?php echo $total_document_count; ?>
												</h1>
												<div class="mb-0">
													<span class="text-muted">Total Documents</span>
												</div>
											</div>
										</div>

										<?php
										$count_result_video = 0; // Initialize the total media count
										$teacher_name = $_SESSION['teacher_name'];

										try {
											// Create a new PDO instance (with error handling)
											$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

											// Set the PDO error mode to exception
											$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

											// SQL query to get the class ids where the teacher matches
											$sql = "SELECT id FROM classes WHERE teacher = :teacher_name";

											// Prepare the SQL statement
											$stmt = $conn->prepare($sql);

											// Bind the teacher_name parameter to the query
											$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);

											// Execute the query
											$stmt->execute();

											// Check if any rows are returned
											if ($stmt->rowCount() > 0) {
												// Fetch all the class ids as an associative array
												$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

												// Loop through the class ids and get the document count (videos) and student count for each
												foreach ($results as $row) {
													$class_id = $row['id'];

													// SQL query to count videos in learning_resources for the current class_id
													$sql_count_video = "SELECT COUNT(*) AS media_count 
                               FROM learning_resources 
                               WHERE class_id = :class_id 
                               AND resource_type IN ('video', 'audio', 'image')";

													// Prepare the count query for videos
													$stmt_count_video = $conn->prepare($sql_count_video);

													// Bind the class_id parameter to the query
													$stmt_count_video->bindParam(':class_id', $class_id, PDO::PARAM_INT);

													// Execute the query to get the document count for videos
													$stmt_count_video->execute();

													// Fetch the result for video count
													$count_result = $stmt_count_video->fetch(PDO::FETCH_ASSOC);

													// Accumulate the media count
													$count_result_video += $count_result['media_count'];

													// SQL query to count students in students_enrollments for the current class_id
													$sql_count_students = "SELECT COUNT(*) AS student_count 
                                  FROM students_enrollments 
                                  WHERE class_id = :class_id";

													// Prepare the count query for students
													$stmt_count_students = $conn->prepare($sql_count_students);

													// Bind the class_id parameter to the query
													$stmt_count_students->bindParam(':class_id', $class_id, PDO::PARAM_INT);

													// Execute the query to get the student count
													$stmt_count_students->execute();

													// Fetch the result for student count
													$count_result_students = $stmt_count_students->fetch(PDO::FETCH_ASSOC);
												}
											} else {
											}
										} catch (PDOException $e) {
											// Catch and display any error
											echo "Error: " . $e->getMessage();
										}

										// Close the connection
										$conn = null;
										?>

										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">No. of Students</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="align-middle" data-feather="users"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3">

													<?php echo !empty($count_result_students['student_count']) ? $count_result_students['student_count'] : 0; ?>

												</h1>
												<div class="mb-0">
													<span class="text-muted">Active Students</span>
												</div>
											</div>
										</div>
									</div>


									<?php
									$teacher_name = $_SESSION['teacher_name'];

									try {
										// Create a new PDO instance (assuming $servername, $dbname, $username, $password are defined)
										$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

										// Set the PDO error mode to exception
										$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

										// SQL query to get the class IDs where the teacher matches
										$sql = "SELECT id FROM classes WHERE teacher = :teacher_name";
										$stmt = $conn->prepare($sql);
										$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
										$stmt->execute();

										// Check if any rows are returned
										if ($stmt->rowCount() > 0) {
											// Fetch all the class IDs as an associative array
											$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

											// Loop through the class IDs and get count for video, audio, and image resources
											foreach ($results as $row) {
												$class_id = $row['id'];

												// SQL query to count video, audio, and image resources for the current class_id
												$sql_count = "SELECT COUNT(*) AS media_count 
                          FROM learning_resources 
                          WHERE class_id = :class_id 
                          AND resource_type IN ('video', 'audio', 'image')";

												// Prepare the count query
												$stmt_count = $conn->prepare($sql_count);
												$stmt_count->bindParam(':class_id', $class_id, PDO::PARAM_INT);
												$stmt_count->execute();

												// Fetch the result
												$count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
												$media_count = $count_result['media_count'];



												// Display the class ID and media count

											}
										} else {
										}
									} catch (PDOException $e) {
										echo "Error: " . $e->getMessage();
									}

									$conn = null;
									?>




									<div class="col-sm-6">
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">Media</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="align-middle" data-feather="video"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3">
													<?php echo !empty($count_result_video) ? $count_result_video : 0; ?>

												</h1>
												<div class="mb-0">
													<span class="text-muted">Total Media</span>
												</div>
											</div>
										</div>

										<?php
										$teacher_name = $_SESSION['teacher_name'];
										try {
											// Create a new PDO instance (with error handling)
											$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

											// Set the PDO error mode to exception
											$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

											// SQL query to count the number of classes where the teacher matches
											$sql_count_classes = "SELECT COUNT(*) AS class_count FROM classes WHERE teacher = :teacher_name";

											// Prepare the SQL statement
											$stmt_count_classes = $conn->prepare($sql_count_classes);

											// Bind the teacher_name parameter to the query
											$stmt_count_classes->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);

											// Execute the query
											$stmt_count_classes->execute();

											// Fetch the result
											$count_result_classes = $stmt_count_classes->fetch(PDO::FETCH_ASSOC);
										} catch (PDOException $e) {
											// Catch and display any error
											echo "Error: " . $e->getMessage();
										}

										// Close the connection
										$conn = null;
										?>
										<div class="card">
											<div class="card-body">
												<div class="row">
													<div class="col mt-0">
														<h5 class="card-title">No. of Classes</h5>
													</div>
													<div class="col-auto">
														<div class="stat text-primary">
															<i class="align-middle" data-feather="layers"></i>
														</div>
													</div>
												</div>
												<h1 class="mt-1 mb-3"><?php echo $count_result_classes['class_count'] ?>
												</h1>
												<div class="mb-0">
													<span class="text-muted">Active Classes</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- Activities Section with Activities per Subject -->
						<div class="row">
							<div class="col">
								<div class="card flex-fill w-100">
									<div class="card-header">


										<div class="d-flex align-items-center">
											<h5 class="card-title mb-0">Notes</h5>
											<div class=" ms-auto" aria-hidden="true">
												<button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal"
													data-bs-target="#viewNotesModal">View Notes</button>

												<button style="margin-right: 10px"
													class="btn btn-primary btn-sm float-end ms-2" data-bs-toggle="modal"
													data-bs-target="#addNoteModal">Add New
													Note</button>

											</div>
										</div>
										<!-- Button to trigger modal for viewing all notes -->

									</div>
									<div class="card-body">
										<!-- List of notes -->
										<ul id="notesList">
											<?php
											// Fetch teacher's notes from the database
											$teacher_name = $_SESSION['teacher_name'];

											// SQL query to get notes for the teacher
											$sql = "SELECT * FROM teacher_notes WHERE teacher_name = :teacher_name ORDER BY created_at DESC";
											$stmt = $pdo->prepare($sql);
											$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
											$stmt->execute();

											// Fetch and display each note
											while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
												echo '<li style="margin-bottom: 5px" id="note-' . $row['id'] . '">' . htmlspecialchars($row['note_title']) . ' - ' . htmlspecialchars($row['note_content']) . ' 
                <i style="color:red" class="bi bi-trash-fill" onclick="deleteNote(' . $row['id'] . ')"></i></li>';
											}
											?>
										</ul>
									</div>
								</div>

								<!-- Modal for Viewing All Notes -->
								<div class="modal fade" id="viewNotesModal" tabindex="-1"
									aria-labelledby="viewNotesModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-lg modal-dialog-centered">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="viewNotesModalLabel">All Notes</h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal"
													aria-label="Close"></button>
											</div>
											<div class="modal-body" id="modalNotesContent">
												<!-- Notes will be displayed here dynamically -->
												<?php
												// Fetch teacher's notes from the database again to show in the modal
												$stmt->execute(); // Reuse the previous query to get all notes
												while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
													echo '<div class="note-item">';
													echo '<h5>' . htmlspecialchars($row['note_title']) . '</h5>';
													echo '<p>' . nl2br(htmlspecialchars($row['note_content'])) . '</p>';
													echo '</div>';
												}
												?>
											</div>
										</div>
									</div>
								</div>

							</div>


							<!-- Modal for adding a new note -->
							<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel"
								aria-hidden="true">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="addNoteModalLabel">Add New Note</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal"
												aria-label="Close"></button>
										</div>
										<div class="modal-body">
											<form id="addNoteForm" action="processes/teachers/notes/add.php"
												method="POST">
												<div class="mb-3">
													<label for="noteTitle" class="form-label">Note Title</label>
													<input type="text" class="form-control" id="noteTitle"
														name="note_title" required>
												</div>
												<div class="mb-3">
													<label for="noteContent" class="form-label">Note Content</label>
													<textarea class="form-control" id="noteContent" rows="3" required
														name="note_content"></textarea>
												</div>
												<button type="submit" class="btn btn-primary">Save Note</button>
											</form>
										</div>
									</div>
								</div>
							</div>

							<div class="col">
								<div class="card flex-fill w-100">
									<div class="card-header">
										<div class="d-flex align-items-center">
											<h5 class="card-title mb-0">Reminders</h5>
											<div class="ms-auto">
												<button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal"
													data-bs-target="#viewRemindersModal">View All Reminders</button>
												<button style="margin-right: 10px"
													class="btn btn-primary btn-sm float-end ms-2" data-bs-toggle="modal"
													data-bs-target="#addReminderModal">Add New Reminder</button>
											</div>
										</div>
									</div>
									<div class="card-body">
										<!-- List of reminders -->
										<ul id="remindersList">
											<?php
											$sql = "SELECT * FROM teacher_reminders WHERE teacher_name = :teacher_name ORDER BY reminder_date ASC, created_at DESC";
											$stmt = $pdo->prepare($sql);
											$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
											$stmt->execute();

											while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
												echo '<li style="margin-bottom: 5px" id="reminder-' . $row['id'] . '">'
													. htmlspecialchars($row['reminder_content'])
													. ' (Due: ' . htmlspecialchars($row['reminder_date']) . ')'
													. ' <i style="color:red" class="bi bi-trash-fill" onclick="deleteReminder(' . $row['id'] . ')"></i></li>';
											}
											?>
										</ul>

									</div>
								</div>

								<!-- Modal for Viewing All Reminders -->
								<div class="modal fade" id="viewRemindersModal" tabindex="-1"
									aria-labelledby="viewRemindersModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-lg modal-dialog-centered">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="viewRemindersModalLabel">All Reminders</h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal"
													aria-label="Close"></button>
											</div>
											<div class="modal-body" id="modalRemindersContent">
												<!-- Reminders will be displayed here dynamically -->
												<?php
												// Fetch teacher's reminders from the database again to show in the modal
												$sql = "SELECT reminder_content, reminder_date, created_at FROM teacher_reminders WHERE teacher_name = :teacher_name ORDER BY reminder_date ASC";
												$stmt = $pdo->prepare($sql);
												$stmt->bindParam(':teacher_name', $teacher_name, PDO::PARAM_STR);
												$stmt->execute();

												while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
													echo '<div class="reminder-item mb-3">';
													echo '<p>' . nl2br(htmlspecialchars($row['reminder_content'])) . '</p>';
													echo '<small class="text-muted">Due date: ' . htmlspecialchars($row['reminder_date']) . '</small><br>';
													echo '<small class="text-muted">Created at: ' . htmlspecialchars($row['created_at']) . '</small>';
													echo '</div>';
												}
												?>
											</div>
										</div>
									</div>
								</div>

								<!-- Modal for Adding a New Reminder -->
								<div class="modal fade" id="addReminderModal" tabindex="-1"
									aria-labelledby="addReminderModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="addReminderModalLabel">Add New Reminder</h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal"
													aria-label="Close"></button>
											</div>
											<div class="modal-body">
												<form id="addReminderForm" action="processes/teachers/reminders/add.php"
													method="POST">
													<div class="mb-3">
														<label for="reminderContent" class="form-label">Reminder
															Content</label>
														<textarea class="form-control" id="reminderContent" rows="3"
															required name="reminder_content"></textarea>
													</div>
													<div class="mb-3">
														<label for="reminderDate" class="form-label">Reminder
															Date</label>
														<input type="date" class="form-control" id="reminderDate"
															name="reminder_date" required>
													</div>
													<button type="submit" class="btn btn-primary">Save Reminder</button>
												</form>

											</div>
										</div>
									</div>
								</div>
							</div>








							<!-- Other sections like Latest Projects or Monthly Sales can be modified similarly if needed -->
						</div>
			</main>
			</main>


		</div>
	</div>


	<script src="js/app.js"></script>
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
		} else {
			echo "<p>No active classes found for this teacher.</p>";
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