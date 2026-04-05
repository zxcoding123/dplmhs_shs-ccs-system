<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
	$_SESSION['STATUS'] = "ADMIN_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
}
include('processes/server/conn.php');
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

	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css"
		rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

	<script
		src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>


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
	}

	.btn-csms:hover {
		border: 1px solid #10177a;
		color: #10177a;
	}

	.table-responsive {
		overflow-x: auto;
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
					<?php
					include('top-bar.php');
					?>
				</div>
			</nav>

			<main class="content">
				<div class="container-fluid p-0">



					<div class="row">

						<div class="col-12">
							<div class="card">

								<div class="card-header">
									<div class="d-flex align-items-center">
										<h5 class="h5 mb-3"><a href="index.php" class="nav-ham-link">Home</a> /
											<span>Semester Management</span>
										</h5>

										<div class="ms-auto" aria-hidden="true">
											<img src="external/svgs/undraw_favorite_gb6n.svg"
												class=" small-picture img-fluid">
										</div>
									</div>

									<br>

									<h5 class="card-title mb-0">
										<div class="d-flex align-items-center">
											<h3 data-bs-toggle="modal" data-bs-target="#semesterInfoModal">Semester
												List <i class="bi bi-info-circle" data-bs-toggle="tooltip"
													data-bs-placement="top" data-bs-custom-class="custom-tooltip"
													data-bs-title="Click me for more info!"></i>
											</h3>
											<div class="ms-auto" aria-hidden="true">
												<button type="button" class="btn btn-csms" data-bs-toggle="modal"
													data-bs-target="#createSemesterModal">
													<i class="bi bi-pencil-square"></i> Create a Semester
												</button>
											</div>

										</div>
									</h5>
								</div>
								<div class="card-body">
									<?php
									require 'processes/server/conn.php';

									try {
										// Query to fetch all semesters
										$stmt = $pdo->query("SELECT * FROM semester ORDER BY start_date");

										if ($stmt->rowCount() > 0) {
									?>
											<div class="table-responsive">
												<table id="semesters" class="table" style="width: 100%;">
													<thead class="text-center">
														<tr>
															<th>Semester Name</th>
															<th>Start Date</th>
															<th>End Date</th>
															<th>School Year</th>
															<th>Status</th>
															<th>Description</th>
															<th>Actions</th>
														</tr>
													</thead>
													<tfoot class="text-center">
														<tr>
															<th>Semester Name</th>
															<th>Start Date</th>
															<th>End Date</th>
															<th>School Year</th>
															<th>Status</th>
															<th>Description</th>
															<th>Actions</th>
														</tr>
													</tfoot>
													<tbody>
														<?php
														while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
														?>
															<tr>
																<td><?php echo htmlspecialchars($row['name']); ?></td>
																<td>
																	<?php
																	// Create a DateTime object from the start_date and format it
																	$startDateTime = DateTime::createFromFormat('Y-m-d', $row['start_date']);
																	echo $startDateTime ? htmlspecialchars($startDateTime->format('m/d/Y')) : 'Invalid date';
																	?>
																</td>
																<td>
																	<?php
																	// Create a DateTime object from the end_date and format it
																	$endDateTime = DateTime::createFromFormat('Y-m-d', $row['end_date']);
																	echo $endDateTime ? htmlspecialchars($endDateTime->format('m/d/Y')) : 'Invalid date';
																	?>
																</td>
																<td>
																	<?php

																	echo $row['school_year'] . " - " . $row['school_year'] + 1;
																	?>
																</td>
																<td>
																	<?php
																	switch ($row['status']) {
																		case 'active':
																			echo '<span class="btn btn-success btn-sm">Active</span>';
																			break;
																		case 'inactive':
																			echo '<span class="btn btn-warning btn-sm">Inactive</span>';
																			break;
																		case 'archived':
																			echo '<span class="btn btn-secondary btn-sm">Archived</span>';
																			break;
																		default:
																			echo '<span class="btn btn-dark btn-sm">Unknown</span>';
																	}
																	?>
																</td>
																<td><?php echo htmlspecialchars($row['description']); ?></td>
																<td>
																	<button type='button' class='btn btn-primary' data-bs-toggle='modal'
																		data-bs-target='#viewModal<?php echo $row['id']; ?>'>
																		<i class='bi bi-eye'></i> View
																	</button>
																	<!-- Delete Button -->

																	<?php if ($row['status'] == 'active' || $row['status'] == 'inactive'): ?>
																		<button type='button' class='btn btn-warning' data-bs-toggle='modal'
																			data-bs-target='#editModal<?php echo $row['id']; ?>'>
																			<i class='bi bi-pencil'></i> Edit
																		</button>


																	<?php endif; ?>
																	<?php if ($row['status'] != 'active' && $row['status'] != 'archived'): ?>
																		<div class="btn-group">
																			<button type="button" class="btn btn-success dropdown-toggle"
																				data-bs-toggle="dropdown" aria-expanded="false">
																				<i class="bi bi-archive"></i> Archive
																			</button>
																			<ul class="dropdown-menu">
																				<li><a class="dropdown-item" href="#"
																						onclick="confirmArchive(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>', 'End of academic year')">End
																						of academic year</a></li>
																				<li><a class="dropdown-item" href="#"
																						onclick="confirmArchive(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>', 'Class discontinued')">Class
																						discontinued</a></li>
																				<li><a class="dropdown-item" href="#"
																						onclick="confirmArchive(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>', 'Administrative decision')">Administrative
																						decision</a></li>
																				<li><a class="dropdown-item" href="#"
																						onclick="promptArchiveReason(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>')">Other</a>
																				</li>
																			</ul>
																		</div>
																	<?php endif; ?>

																	<div class="btn-group">
																		<button type="button" class="btn btn-danger"
																			onclick="confirmDeletion(<?php echo htmlspecialchars((int) $row['id'], ENT_QUOTES, 'UTF-8'); ?>)">
																			<i class="bi bi-trash"></i> Delete
																		</button>
																	</div>
																</td>
															</tr>

															<!-- View Semester Modal -->
															<?php
															$createdAt = new DateTime($row['created_at']);
															$updatedAt = new DateTime($row['updated_at']);
															$formattedCreatedAt = $createdAt->format('F j, Y \a\t g:i A');
															$formattedUpdatedAt = $updatedAt->format('F j, Y \a\t g:i A');

															// Fetch classes related to this semester
															$stmtClasses = $pdo->prepare("SELECT name, type, code, teacher FROM classes WHERE semester = ?");
															$stmtClasses->execute([$row['name']]);
															$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

															// Fetch subjects related to this semester
															$stmtSubjects = $pdo->prepare("SELECT name, type, code FROM subjects WHERE semester = ?");
															$stmtSubjects->execute([$row['name']]);
															$subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);
															?>

															<div class="modal fade" id="viewModal<?php echo $row['id'] ?>" tabindex="-1"
																aria-labelledby="viewModalLabel" aria-hidden="true">
																<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
																	<div class="modal-content">
																		<div class="modal-header">
																			<h5 class="modal-title" id="viewModalLabel">View Semester
																			</h5>
																			<button type="button" class="btn-close"
																				data-bs-dismiss="modal" aria-label="Close"></button>
																		</div>
																		<div class="modal-body">
																			<p>
																				<strong>Semester Status:</strong>
																				<?php
																				$status = strtolower($row['status']);
																				switch ($status) {
																					case 'active':
																						echo '<span class="btn btn-success btn-sm">Active</span>';
																						break;
																					case 'inactive':
																						echo '<span class="btn btn-warning btn-sm">Inactive</span>';
																						break;
																					case 'archived':
																						echo '<span class="btn btn-secondary btn-sm">Archived</span> <br>';
																						// Fetch archived reason
																						$stmtArchived = $pdo->prepare("SELECT archive_reason FROM archived_semesters WHERE semester_id = ?");
																						$stmtArchived->execute([$row['id']]);
																						$fetchSemesterArchived = $stmtArchived->fetch(PDO::FETCH_ASSOC);
																						if ($fetchSemesterArchived) {
																							echo '   <p>       <strong>Semester Status: </strong>';
																							echo '' . htmlspecialchars($fetchSemesterArchived['archive_reason']) . '</span>   </p>';
																						}
																						break;
																					default:
																						echo '<span class="btn btn-dark btn-sm">Unknown</span>';
																				}
																				?>
																			</p>
																			<p><strong>Semester Name:</strong>
																				<?php echo htmlspecialchars($row['name']); ?></p>
																			<p><strong>Start Date:</strong>
																				<?php echo htmlspecialchars($row['start_date']); ?></p>
																			<p><strong>End Date:</strong>
																				<?php echo htmlspecialchars($row['end_date']); ?></p>
																			<p><strong>Description:</strong>
																				<?php echo htmlspecialchars($row['description']); ?></p>
																			<p><strong>Created At:</strong>
																				<?php echo htmlspecialchars($formattedCreatedAt); ?></p>
																			<p><strong>Updated At:</strong>
																				<?php echo htmlspecialchars($formattedUpdatedAt); ?></p>

																			<!-- Display Classes -->
																			<h5><strong>Classes</strong></h5>
																			<?php if (!empty($classes)): ?>
																				<ul>
																					<?php foreach ($classes as $class): ?>
																						<li>
																							<strong>Class Name:</strong>
																							<?php echo htmlspecialchars($class['name']); ?>
																							<br><strong>Type:</strong>
																							<?php echo htmlspecialchars($class['type']); ?>
																							<br><strong>Code:</strong>
																							<?php echo htmlspecialchars($class['code']); ?>
																							<br><strong>Teacher:</strong>
																							<?php echo htmlspecialchars($class['teacher']); ?>
																						</li>
																					<?php endforeach; ?>
																				</ul>
																			<?php else: ?>
																				<p>No classes found for this semester.</p>
																			<?php endif; ?>

																			<!-- Display Subjects -->
																			<h5><strong>Subjects</strong></h5>
																			<?php if (!empty($subjects)): ?>
																				<ul>
																					<?php foreach ($subjects as $subject): ?>
																						<li>
																							<strong>Subject Name:</strong>
																							<?php echo htmlspecialchars($subject['name']); ?>
																							<br><strong>Type:</strong>
																							<?php echo htmlspecialchars($subject['type']); ?>
																							<br><strong>Code:</strong>
																							<?php echo htmlspecialchars($subject['code']); ?>
																						</li>
																					<?php endforeach; ?>
																				</ul>
																			<?php else: ?>
																				<p>No subjects found for this semester.</p>
																			<?php endif; ?>
																		</div>
																		<div class="modal-footer">
																			<button type="button" class="btn btn-secondary"
																				data-bs-dismiss="modal">Close</button>
																		</div>
																	</div>
																</div>
															</div>

															<div class="modal fade" id="editModal<?php echo $row['id'] ?>" tabindex="-1"
    aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processes/admin/semester/updateSemester.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Semester Name</label>
                        <input type="text" class="form-control" name="name"
                            value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date"
                            value="<?php echo htmlspecialchars($row['start_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date"
                            value="<?php echo htmlspecialchars($row['end_date']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="schoolYear" class="form-label">School Year</label>
                        <input type="number" class="form-control" id="schoolYear" name="schoolYear" 
                            min="2000" max="2100" maxlength="4" required
                            value="<?php echo $row['school_year'] ?>">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" 
                            rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                    <?php
                    $status = strtolower($row['status']);
                    if ($status == 'inactive') { ?>
                        <button type="button" class="btn btn-success make-active-btn" 
                            data-semester-id="<?php echo $row['id']; ?>">Make Active</button>
                    <?php } elseif ($status == 'active') { ?>
                        <button type="button" class="btn btn-warning make-inactive-btn" 
                            data-semester-id="<?php echo $row['id']; ?>">Make Inactive</button>
                    <?php } ?>

                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
														<?php
														}
														?>
													</tbody>
												</table>
										<?php
										} else {
											echo "<h1 class='text-center'>No semesters added, yet.</h1>";
										}
									} catch (PDOException $e) {
										echo "Error: " . $e->getMessage();
									}
										?>
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

	<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Make Active button
    document.querySelectorAll('.make-active-btn').forEach(button => {
        button.addEventListener('click', function() {
            const semesterId = this.getAttribute('data-semester-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to activate this semester?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, activate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('processes/admin/semester/updateStatus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + semesterId + '&status=active'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Activated!',
                                text: 'The semester has been activated successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to activate semester: ' + data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while activating the semester',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        });
    });

    // Handle Make Inactive button
    document.querySelectorAll('.make-inactive-btn').forEach(button => {
        button.addEventListener('click', function() {
            const semesterId = this.getAttribute('data-semester-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to deactivate this semester?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, deactivate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('processes/admin/semester/updateStatus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + semesterId + '&status=inactive'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deactivated!',
                                text: 'The semester has been deactivated successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to deactivate semester: ' + data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while deactivating the semester',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        });
    });
});
</script>



	<div class="modal fade" id="semesterInfoModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel">Semester Management Information</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<h5>Welcome to this simple management tool for handling semesters!</h5>
					<p>
						In terms of handling semesters, you, as an admin, must create a semester that will be utilized
						by the whole College of Computing Studies.
					</p>
					<p>You can add, edit or delete a semester here! Keep in mind that the start date must not be in
						align with the end date.</p>
					<p>Apart from that, you can archive a semester that has been finished under the status of being
						'Inactive' meaning this semester will be stored for
						archival and history viewing purposes.
					</p>
					<p>Note: You cannot add a semester under the same name and its status not being set to archive! This
						is done in order to avoid
						having the same semester name adding up to confusion!
					</p>



				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

				</div>
			</div>
		</div>
	</div>


	</div>
	</div>

	<!-- Create Semester Modal -->
	<div class="modal fade" id="createSemesterModal" tabindex="-1" aria-labelledby="createSemesterModalLabel"
		aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="createSemesterModalLabel">Create New Semester</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="createSemesterForm" action="processes/admin/semester/add.php" method="POST"
						onsubmit="return validateDates()">
						<div class="mb-3">
							<label for="semesterName" class="form-label">Semester Name</label>
							<input type="text" class="form-control" id="semesterName" name="name" required>
						</div>
						<div class="mb-3">
							<label for="startDate" class="form-label">Start Date</label>
							<input type="date" class="form-control" id="startDate" name="start_date" required>
						</div>
						<div class="mb-3">
							<label for="endDate" class="form-label">End Date</label>
							<input type="date" class="form-control" id="endDate" name="end_date" required>
						</div>
						<div class="mb-3">
							<label for="schoolYear" class="form-label">School Year</label>
							<input type="number" class="form-control" id="schoolYear" name="schoolYear" min="2000"
								max="2100" maxlength="4" required>
						</div>
						<div class="mb-3">
							<label for="description" class="form-label">Description</label>
							<textarea class="form-control" id="description" name="description" rows="3"
								required></textarea>
						</div>
						<button type="submit" class="btn btn-primary">Create Semester</button>
					</form>
				</div>


			</div>
		</div>
	</div>

	<script>
		function validateDates() {
			const schoolYearInput = document.getElementById("schoolYear").value;
			const startDateInput = document.getElementById("startDate").value;
			const endDateInput = document.getElementById("endDate").value;

			console.log("School Year Input:", schoolYearInput);
			console.log("Start Date Input:", startDateInput);
			console.log("End Date Input:", endDateInput);

			const schoolYear = parseInt(schoolYearInput, 10);
			const startDate = new Date(startDateInput);
			const endDate = new Date(endDateInput);

			if (isNaN(schoolYear)) {
				alert("Please enter a valid school year.");
				return false;
			}

			if (!startDate || !endDate || isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
				alert("Please select valid start and end dates.");
				return false;
			}

			const schoolYearStart = new Date(`${schoolYear}-01-01`);
			const schoolYearEnd = new Date(`${schoolYear}-12-31`);

			console.log("Parsed Start Date:", startDate);
			console.log("Parsed End Date:", endDate);
			console.log("School Year Range:", schoolYearStart, "to", schoolYearEnd);

			if (startDate < schoolYearStart || endDate > schoolYearEnd) {
				alert("Start and End dates must fall within the selected school year.");
				return false;
			}

			if (startDate > endDate) {
				alert("Start date must be before or equal to the end date.");
				return false;
			}

			return true;
		}
	</script>





	<script src="js/app.js"></script>
	<?php
	include('processes/server/modals.php');
	?>


	<script>
		function validateDates() {
			const startDate = new Date(document.getElementById('startDate').value);
			const endDate = new Date(document.getElementById('endDate').value);

			if (endDate <= startDate) {
				Swal.fire({
					icon: 'error',
					title: 'Invalid Date',
					text: 'End date must be after the start date.',
				});
				return false;
			}
			return true;
		}
	</script>


	<script>
		$(document).ready(function() {
			// Define custom sorting for the status column
			$.fn.dataTable.ext.type.order['status-pre'] = function(data) {
				if (data === 'active') {
					return 1;
				} else if (data === 'archived') {
					return 2;
				} else if (data === 'inactive') {
					return 3;
				}
				return 4; // For any other status
			};

			var table = $('#semesters').DataTable({
				responsive: true,
				columnDefs: [{
					type: 'status-pre',
					targets: 4 // Assuming the status is in the 5th column (0-based index)
				}],
				order: [
					[4, 'desc']
				], // Default ordering by status (5th column)
			});

			// Add search inputs to each footer cell
			$('#semesters tfoot th').each(function(index) {
				if (index === 1) { // Start Date column
					$(this).html('<input type="text" class="start-datepicker" placeholder="Start Date" />');
				} else if (index === 2) { // End Date column
					$(this).html('<input type="text" class="end-datepicker" placeholder="End Date" />');
				} else if (index === 4) { // Status column
					$(this).html(`
				<select class="status-dropdown">
					<option value="">Select Status</option>
					<option value="active">Active</option>
					<option value="archived">Archived</option>
					<option value="inactive">Inactive</option>
				</select>
			`);
				} else {
					$(this).html('<input type="text" placeholder="Search" />');
				}
			});

			// Apply search functionality for each filter
			table.columns().every(function(colIdx) {
				var column = this;
				var that = this;

				$('input[type="text"]', this.footer()).on('keyup change', function() {
					console.log("Filter Value: ", this.value);
					if (that.search() !== this.value) {
						that.search(this.value).draw();
					}
				});



				$('input[type="text"]', this.footer()).on('keyup change', function() {
					if (that.search() !== this.value) {
						that.search(this.value).draw(); // Trigger search and redraw table
					}
				});

				$('select', this.footer()).on('change', function() {
					if (that.search() !== this.value) {
						that.search(this.value).draw(); // Trigger search and redraw table
					}
				});

				// Start Date Filter
				$('input.start-datepicker', column.footer()).on('change', function() {
					column.search(this.value).draw();
				});

				// End Date Filter
				$('input.end-datepicker', column.footer()).on('change', function() {
					column.search(this.value).draw();
				});

				// School Year Filter
				$('select.school-year-dropdown', column.footer()).on('change', function() {
					column.search(this.value).draw();
				});

				// Status Filter
				$('select.status-dropdown', column.footer()).on('change', function() {
					column.search(this.value).draw();
				});
			});

			// Initialize datepickers for start and end date inputs
			$(document).on('focus', '.start-datepicker, .end-datepicker', function() {
				$(this).datepicker({
					format: 'mm/dd/yyyy',
					autoclose: true,
					todayHighlight: true
				}).datepicker('show');
			});

			// Adjust column widths for responsive design
			table.columns.adjust().responsive.recalc();
		});
	</script>


	<script>
		function confirmDeletion(id) {
			// Show SweetAlert2 confirmation dialog
			Swal.fire({
				title: 'Are you sure you want to delete this item?',
				text: "This action cannot be undone!",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'Cancel'
			}).then((result) => {
				if (result.isConfirmed) {
					// Proceed with deletion via fetch when the user clicks "Yes"
					fetch('processes/admin/semester/delete.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: `id=${id}` // Send the ID as a URL-encoded parameter
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								Swal.fire(
									'Deleted!',
									'The item has been deleted successfully.',
									'success'
								);
								// Optionally, remove the deleted item from the DOM or refresh the page
								const item = document.getElementById(`item-${id}`);
								if (item) item.remove(); // Remove item from the page immediately
								location.reload(); // Reload the page to reflect changes
							} else {
								Swal.fire(
									'Error!',
									data.message || 'An issue occurred while deleting the item.',
									'error'
								);
							}
						})
						.catch(error => {
							console.error('Error:', error);
							Swal.fire(
								'Error!',
								'Unable to delete the item. Please try again later.',
								'error'
							);
						});
				}
			});
		}
		$(document).ready(function() {
			// Handle "Make Active" button click
			$('.make-active-btn').on('click', function() {
				const semesterId = $(this).data('semester-id');

				Swal.fire({
					title: 'Are you sure?',
					text: 'This will make the selected semester active.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#28a745',
					cancelButtonColor: '#6c757d',
					confirmButtonText: 'Yes, activate it!',
					cancelButtonText: 'Cancel'
				}).then((result) => {
					if (result.isConfirmed) {
						// Directly navigate to the URL, letting the backend handle the redirect
						window.location.href = 'processes/admin/semester/setActiveSemester.php?id=' + semesterId;
					}
				});
			});

			// Handle session-based SweetAlert messages
			<?php if (isset($_SESSION['STATUS'])) { ?>
				console.log("Session STATUS: <?php echo $_SESSION['STATUS']; ?>"); // Debug log
				<?php if ($_SESSION['STATUS'] === "SEMESTER_ACTIVATED") { ?>
					Swal.fire(
						'Activated!',
						'The semester has been activated successfully.',
						'success'
					).then(() => {
						<?php unset($_SESSION['STATUS']); ?>
						location.reload(); // Refresh to reflect changes
					});
				<?php } elseif ($_SESSION['STATUS'] === "ACTIVE_SEMESTER_CONFLICT") { ?>
					Swal.fire(
						'Failed!',
						'Failed to make this semester active due to an ongoing active semester (<?php echo htmlspecialchars($_SESSION['active_semester_name']); ?>).',
						'error'
					).then(() => {
						<?php unset($_SESSION['STATUS']);
						unset($_SESSION['active_semester_name']); ?>
					});
				<?php } ?>
			<?php } ?>

			<?php if (isset($_SESSION['error'])) { ?>
				console.log("Session error: <?php echo $_SESSION['error']; ?>"); // Debug log
				Swal.fire(
					'Error!',
					'<?php echo htmlspecialchars($_SESSION['error']); ?>',
					'error'
				).then(() => {
					<?php unset($_SESSION['error']); ?>
				});
			<?php } ?>

		});
	</script>

	<script>
		function confirmArchive(semesterId, semesterName, archiveReason) {
			// Create a form dynamically and submit it via POST
			var form = document.createElement('form');
			form.method = 'POST';
			form.action = 'processes/admin/semester/archive.php'; // Your archive processing page

			// Append necessary inputs
			var idInput = document.createElement('input');
			idInput.type = 'hidden';
			idInput.name = 'id';
			idInput.value = semesterId;
			form.appendChild(idInput);

			var nameInput = document.createElement('input');
			nameInput.type = 'hidden';
			nameInput.name = 'name';
			nameInput.value = semesterName;
			form.appendChild(nameInput);

			var reasonInput = document.createElement('input');
			reasonInput.type = 'hidden';
			reasonInput.name = 'archive_reason';
			reasonInput.value = archiveReason;
			form.appendChild(reasonInput);

			// Submit the form
			document.body.appendChild(form);
			form.submit();
		}

		function promptArchiveReason(semesterId, semesterName) {
			var reason = prompt("Please enter the reason for archiving this semester:");
			if (reason) {
				confirmArchive(semesterId, semesterName, reason);
			}
		}
	</script>

	<script>
		const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
		const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
	</script>






</html>

<?php
include('processes/server/alerts.php');
?>