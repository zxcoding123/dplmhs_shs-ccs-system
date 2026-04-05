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

	<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>



</head>

<style>
	table.dataTable {
		font-size: 12px;
	}

	td {
		text-align: center;
		vertical-align: middle;

	}

	.btn-csms {
		background-color: #10177a;
		color: white;
	}

	.btn-csms:hover {
		border: 1px solid #10177a;
		color: #10177a;
	}
</style>

<style>
	.responsive-table {
		width: 100%;
		border-collapse: collapse;
		margin: 0 0 1em 0;
	}

	/* Ensure table header and footer cells are centered where needed */
	.responsive-table th.text-center,
	.responsive-table td.text-center {
		text-align: center;
	}

	/* Style for table headers */
	.responsive-table th {
		background-color: #f5f5f5;
		font-weight: bold;
		padding: 8px;
		border: 1px solid #ddd;
	}

	/* Style for table cells */
	.responsive-table td {
		padding: 8px;
		border: 1px solid #ddd;
	}

	/* Container for responsive behavior */
	.table-wrapper {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
		margin-bottom: 1em;
	}

	/* Minimum width to ensure all content is visible */
	.responsive-table {
		min-width: 800px;
		/* Adjust based on your content */
	}

	/* Button spacing */
	.responsive-table td .btn {
		margin: 2px;
	}

	/* Media query for smaller screens */
	@media screen and (max-width: 768px) {

		.responsive-table th,
		.responsive-table td {
			font-size: 14px;
		}

		.responsive-table td .btn {
			padding: 4px 8px;
			font-size: 12px;
		}

		/* Stack buttons vertically on very small screens */
		@media screen and (max-width: 480px) {
			.responsive-table td .btn {
				display: block;
				width: 100%;
				margin: 2px 0;
			}
		}
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
											<span>Teacher Management</span>
										</h5>

										<div class="ms-auto" aria-hidden="true">
											<img src="external/svgs/undraw_favorite_gb6n.svg"
												class=" small-picture img-fluid">
										</div>
									</div>

									<br>

									<h5 class="card-title mb-0">
										<div class="d-flex align-items-center">
											<h3>Teacher List</h3>
											<div class="ms-auto" aria-hidden="true">
												<button type="button" class="btn btn-csms" data-bs-toggle="modal"
													data-bs-target="#addTeacherModal"><i
														class="bi bi-pencil-square"></i>
													Add a Teacher</button>
											</div>
										</div>
									</h5>
								</div>
								<div class="card-body">
									<?php
									// Query to select staff details along with their class advisings
									$sql = "
    SELECT sa.id, sa.first_name, sa.middle_name, sa.last_name, sa.email, sa.department, sa.gender, sa.phone_number, sa.status, 
           GROUP_CONCAT(sa_ad.class_advising ORDER BY sa_ad.class_advising SEPARATOR ', ') AS class_advising
    FROM staff_accounts sa
    LEFT JOIN staff_advising sa_ad ON sa.fullName = sa_ad.fullName
    GROUP BY sa.id
";
									$stmt = $pdo->query($sql);

									if ($stmt->rowCount() > 0) {
										echo '
										<div class="table-wrapper">
										<table id="teachers" class="responsive" style="width: 100%;">
        <thead>
            <tr>
               <th>First Name</th>
               <th>Middle Name</th>
               <th>Last Name</th>
               <th class="text-center">Email</th>
               <th class="text-center">Department</th>
               <th class="text-center">Gender</th>
               <th class="text-center">Phone Number</th>
               <th class="text-center">Class Advising</th>
               <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tfoot class="text-center">
            <tr>
               <th>First Name</th>
               <th>Middle Name</th>
               <th>Last Name</th>
               <th>Email</th>
               <th>Department</th>
               <th>Gender</th>
               <th>Phone Number</th>
               <th>Class Advising</th>
               <th>Actions</th>
            </tr>
        </tfoot>
        <tbody>';

										while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
											// Set text and class for activate/deactivate button
											$statusText = $row['status'] == 'Active' ? 'Deactivate' : 'Activate';
											$statusBtnClass = $row['status'] == 'Active' ? 'btn-danger' : 'btn-success';

											// Check if class advising is available, otherwise set to "None"
											$classAdvising = !empty($row['class_advising']) ? $row['class_advising'] : 'None';

											echo '<tr>
            <td>' . htmlspecialchars($row['first_name']) . '</td>
            <td>' . htmlspecialchars($row['middle_name']) . '</td>
            <td>' . htmlspecialchars($row['last_name']) . '</td>
            <td>' . htmlspecialchars($row['email']) . '</td>
            <td>' . htmlspecialchars($row['department']) . '</td>
            <td>' . htmlspecialchars($row['gender']) . '</td>
            <td>' . htmlspecialchars($row['phone_number']) . '</td>
            <td>' . htmlspecialchars($classAdvising) . '</td>
            <td>
                <button type="button" data-bs-toggle="modal" data-bs-target="#viewModal' . $row['id'] . '" class="btn btn-primary">
                    <i class="bi bi-eye"></i> View
                </button>
                <button type="button" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id'] . '" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button type="button" onclick="deleteModal(' . $row['id'] . ')" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Delete
                </button>
                <button type="button" onclick="changeStatus(' . $row['id'] . ', \'' . $statusText . '\')" class="btn ' . $statusBtnClass . '">
                    <i class="bi bi-activity"></i> ' . $statusText . '
                </button>
            </td>
        </tr>';
										}

										echo '</tbody></table>';
									} else {
										echo '<h1 class="text-center">No teachers added, yet.</h1>';
									}
									echo "</div>";
									?>


									<script>
										function changeStatus(userId, currentStatus) {
											const newStatus = currentStatus === 'Activate' ? 'Active' : 'Inactive'; // Adjusting here: you are toggling "Activate/Deactivate"

											console.log(`Changing status for user ${userId} to ${newStatus}`);

											var xhr = new XMLHttpRequest();
											xhr.open('POST', 'change_status.php', true);
											xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

											xhr.onload = function() {
												if (xhr.status === 200) {
													console.log("Status update response:", xhr.responseText);
													location.reload(); // Reload the page to show the updated status
												} else {
													console.error("Error updating status:", xhr.status, xhr.statusText);
												}
											};

											xhr.onerror = function() {
												console.error("Request failed");
											};

											xhr.send('user_id=' + userId + '&new_status=' + newStatus);
										}
									</script>
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
	</div>
	</div>
	</main>
	</div>
	</div>

	<script src="js/app.js"></script>

	<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="exampleModalLabel"><b>Add a new Teacher</b></h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form method="POST" action="processes/admin/staff/add.php">
						<div class="mb-3">
							<label for="first_name" class="form-label bold">First Name</label>
							<input type="text" class="form-control" id="first_name" name="firstName">
						</div>
						<div class="mb-3">
							<label for="middle_name" class="form-label bold">Middle Name</label>
							<input type="text" class="form-control" id="middle_name" name="middleName">
						</div>
						<div class="mb-3">
							<label for="last_name" class="form-label bold">Last Name</label>
							<input type="text" class="form-control" id="last_name" name="lastName">
						</div>
						<div class="mb-3">
							<label for="email" class="form-label bold">Email</label>
							<input type="email" class="form-control" id="email" name="email">
						</div>
						<div class="mb-3">
							<label for="department" class="form-label bold">Department</label>
							<select class="form-control" name="department" id="department-1" required>
								<option value="None">None</option>
								<option value="Department of Information Technology">Department of Information
									Technology</option>
								<option value="Department of Computer Science">Department of Computer Science</option>
							</select>
						</div>

						<div class="mb-3">
							<label for="class" class="form-label bold">Class Advisory (Multi-selectable)</label>
							<select class="form-select" name="class[]" id="class-1" multiple disabled>
								<option value="" selected>None</option>
								<!-- Options will be dynamically populated -->
							</select>
						</div>

						<script>
							document.addEventListener('DOMContentLoaded', function() {
								const departmentSelect1 = document.getElementById('department-1');
								const classSelect1 = document.getElementById('class-1');

								// Define class options based on the department
								const classes = {
									"Department of Information Technology": [
										"BSIT-1A", "BSIT-1B", "BSIT-2A", "BSIT-2B",
										"BSIT-3A", "BSIT-3B", "BSIT-4A", "BSIT-4B"
									],
									"Department of Computer Science": [
										"BSCS-1A", "BSCS-1B", "BSCS-2A", "BSCS-2B",
										"BSCS-3A", "BSCS-3B", "BSCS-4A", "BSCS-4B"
									]
								};

								// Function to update class options based on selected department
								function updateClassOptions(department) {
									// Clear the current options
									classSelect1.innerHTML = '<option value="" disabled selected>None</option>';

									// Enable the class select dropdown
									classSelect1.disabled = false;

									// If a valid department is selected, update the class options
									if (classes[department]) {
										classes[department].forEach(function(classOption) {
											const option = document.createElement('option');
											option.value = classOption;
											option.textContent = classOption;
											classSelect1.appendChild(option);
										});
									} else {
										// If no department is selected, disable the class select
										classSelect1.disabled = true;
									}
								}

								// Event listener for when the department changes
								departmentSelect1.addEventListener('change', function() {
									const selectedDepartment = departmentSelect1.value;
									updateClassOptions(selectedDepartment);
								});

								// Initialize class options based on the initially selected department (if any)
								updateClassOptions(departmentSelect1.value);
							});
						</script>



						<div class="mb-3">
							<label for="password" class="form-label bold">Password</label>
							<input type="password" class="form-control" id="password" name="password"
								style="width: 100%; display: inline !important;">
							<span
								style="position: absolute; display: inline; right: 5%; margin-top: 5px !important; vertical-align: middle">
								<i class="bi bi-eye-slash-fill" id="togglerPassword"></i>
							</span>
						</div>
						<div class="mb-3">
							<label for="confirm_password" class="form-label bold">Confirm Password</label>
							<input type="password" class="form-control" id="confirm_password" name="confirm_password"
								style="width: 100%; display: inline !important;">
							<span
								style="position: absolute; display: inline; right: 5%; margin-top: 5px !important; vertical-align: middle">
								<i class="bi bi-eye-slash-fill" id="togglerConfirmPassword"></i>
							</span>
						</div>


						<div class="mb-3">
							<label for="gender" class="form-label bold">Gender</label>
							<select class="form-control" name="gender" id="gender">
								<option value="Male">Male</option>
								<option value="Female">Female</option>
							</select>
						</div>

						<div class="mb-3">
							<label for="phone_number" class="form-label bold">Phone Number</label>
							<input type="tel" class="form-control" id="phone_number" name="phone_number" value="+63"
								pattern="^\+63\d{10}$" maxlength="13" placeholder="+63xxxxxxxxxx" required>

						</div>
				</div>
				<div class="modal-footer">
					<input type="submit" class="btn btn-primary" value="Update">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<?php
	include('processes/server/modals.php');
	?>

	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();
			console.log(newTime);
			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}
		setInterval(getTime, 100);
		$(document).ready(function() {
			var table = $('#teachers').DataTable({
				responsive: true,
			});

			// Loop through each footer element
			$('#teachers tfoot th').each(function(index) {
				var title = $(this).text(); // Get column title

				// Decide which column should have a dropdown
				if (title === 'Department' || title === 'Class') {
					// Example: Dropdown for 'Status' or 'Role' columns
					var options = '';
					if (title === 'Department') {
						options = `
					<select class="selector">
						<option value="">None</option>
						<option value="Department of Information Technology">Department of Information Technology</option>
						<option value="Department of Computer Science">Department of Computer Science</option>
					</select>
				`;
					} else if (title === 'Class') {
						options = `
				  <select class="selector">
	<option value="">All</option>
	<option value="Teacher">Teacher</option>
	<option value="Admin">Admin</option>
	<!-- BSIT Classes -->
	<option value="BSIT-1A">BSIT-1A</option>
	<option value="BSIT-1B">BSIT-1B</option>
	<option value="BSIT-2A">BSIT-2A</option>
	<option value="BSIT-2B">BSIT-2B</option>
	<option value="BSIT-3A">BSIT-3A</option>
	<option value="BSIT-3B">BSIT-3B</option>
	<option value="BSIT-4A">BSIT-4A</option>
		<option value="BSIT-4B">BSIT-4B</option>
	<!-- BSCS Classes -->
	<option value="BSCS-1A">BSCS-1A</option>
		<option value="BSCS-1B">BSCS-1B</option>
	<option value="BSCS-2A">BSCS-2A</option>
		<option value="BSCS-2B">BSCS-2B</option>
	<option value="BSCS-3A">BSCS-3A</option>
		<option value="BSCS-3B">BSCS-3B</option>
	<option value="BSCS-4A">BSCS-4A</option>
	   <option value="BSCS-4B">BSCS-4B</option>
</select>
				`;
					}
					$(this).html(options); // Replace footer cell with a dropdown
				} else {
					// For other columns, use text input
					$(this).html('<input type="text" placeholder="Search ' + title + '" />');
				}
			});

			// Apply search logic
			table.columns().every(function() {
				var that = this;

				// Search for text input
				$('input', this.footer()).on('keyup change clear', function() {
					if (that.search() !== this.value) {
						that.search(this.value).draw();
					}
				});

				// Search for dropdown
				$('select', this.footer()).on('change', function() {
					if (that.search() !== this.value) {
						that.search(this.value).draw();
					}
				});
			});


		});
	</script>


	<?php
	$sql = "SELECT * FROM staff_accounts";
	$stmt = $pdo->query($sql);

	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		// Pre-select the department, gender, and phone number if they exist in the record
		$selectedDepartment = $row['department'];
		$selectedGender = $row['gender'];
		$selectedPhoneNumber = $row['phone_number'];

		// Construct the teacher's full name
		$fullName = $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'];

		// Fetch all unique class advising options
		$all_classes_stmt = $pdo->prepare("SELECT DISTINCT class_advising FROM staff_advising");
		$all_classes_stmt->execute();
		$all_classes = $all_classes_stmt->fetchAll(PDO::FETCH_COLUMN);

		// Fetch the classes this teacher advises
		$teacher_classes_stmt = $pdo->prepare("SELECT class_advising FROM staff_advising WHERE fullName = :fullName");
		$teacher_classes_stmt->execute([':fullName' => $fullName]);
		$teacher_classes = $teacher_classes_stmt->fetchAll(PDO::FETCH_COLUMN);

		echo '
    <div class="modal fade" id="editModal' . $row['id'] . '" tabindex="-1" aria-labelledby="exampleModalLabel' . $row['id'] . '" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel' . $row['id'] . '"><b>Editing Teacher: ' . htmlspecialchars($row['fullName']) . '</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="processes/admin/staff/edit.php?id=' . $row['id'] . '">
                        <div class="mb-3">
                            <label for="first_name' . $row['id'] . '" class="form-label bold">First Name</label>
                            <input type="text" class="form-control" id="first_name' . $row['id'] . '" name="first_name" value="' . htmlspecialchars($row['first_name']) . '">
                        </div>
                        <div class="mb-3">
                            <label for="middle_name' . $row['id'] . '" class="form-label bold">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name' . $row['id'] . '" name="middle_name" value="' . htmlspecialchars($row['middle_name']) . '">
                        </div>
                        <div class="mb-3">
                            <label for="last_name' . $row['id'] . '" class="form-label bold">Last Name</label>
                            <input type="text" class="form-control" id="last_name' . $row['id'] . '" name="last_name" value="' . htmlspecialchars($row['last_name']) . '">
                        </div>
                        <div class="mb-3">
                            <label for="email' . $row['id'] . '" class="form-label bold">Email</label>
                            <input type="email" class="form-control" id="email' . $row['id'] . '" name="email" value="' . htmlspecialchars($row['email']) . '">
                        </div>
                        <div class="mb-3">
                            <label for="department' . $row['id'] . '" class="form-label bold">Department</label>
                            <select class="form-control" name="department" id="department-' . $row['id'] . '" required>
                                <option>Select a department</option>
                                <option value="Department of Information Technology"' . ($selectedDepartment == 'Department of Information Technology' ? ' selected' : '') . '>Department of Information Technology</option>
                                <option value="Department of Computer Science"' . ($selectedDepartment == 'Department of Computer Science' ? ' selected' : '') . '>Department of Computer Science</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="class' . $row['id'] . '" class="form-label bold">Class Advisory (Multi-selectable)</label>
                            <select class="form-control" name="class[]" id="class-' . $row['id'] . '" multiple>
                                <optgroup label="Past Selected Classes">
                                    ';
		foreach ($teacher_classes as $class) {
			echo '<option style="background-color:lightgrey"value="' . htmlspecialchars($class) . '" selected>' . htmlspecialchars($class) . '</option>';
		}
		echo '
                                </optgroup>
                                <optgroup label="Department-Specific Classes" id="dept-classes-' . $row['id'] . '">
                                    <!-- Populated by JavaScript -->
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3 position-relative">
                            <label for="password' . $row['id'] . '" class="form-label bold">Password</label>
                            <input type="password" class="form-control" id="password' . $row['id'] . '" name="password">
                            <i class="bi bi-eye-slash-fill toggler-icon" id="togglerPassword' . $row['id'] . '" style="position: absolute; top: 70%; right: 10px; cursor: pointer; transform: translateY(-50%);"></i>
                        </div>
                        <div class="mb-3 position-relative">
                            <label for="confirm_password' . $row['id'] . '" class="form-label bold">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password' . $row['id'] . '" name="confirm_password">
                            <i class="bi bi-eye-slash-fill toggler-icon" id="togglerConfirmPassword' . $row['id'] . '" style="position: absolute; top: 70%; right: 10px; cursor: pointer; transform: translateY(-50%);"></i>
                        </div>
                        <div class="mb-3">
                            <label for="gender' . $row['id'] . '" class="form-label bold">Gender</label>
                            <select class="form-control" name="gender" id="gender-' . $row['id'] . '">
                                <option value="Male" ' . ($selectedGender == 'Male' ? 'selected' : '') . '>Male</option>
                                <option value="Female" ' . ($selectedGender == 'Female' ? 'selected' : '') . '>Female</option>
                                <option value="Other" ' . ($selectedGender == 'Other' ? 'selected' : '') . '>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone_number' . $row['id'] . '" class="form-label bold">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number' . $row['id'] . '" name="phone_number" value="' . $selectedPhoneNumber . '" 
                                pattern="^\+63\d{10}$" maxlength="13" placeholder="+63xxxxxxxxxx" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';

		// Password toggler JavaScript
		echo "
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const togglerPassword = document.querySelector('#togglerPassword" . $row['id'] . "');
            const password = document.querySelector('#password" . $row['id'] . "');
            togglerPassword.addEventListener('click', () => {
                password.type = (password.type === 'password') ? 'text' : 'password';
            });

            const togglerConfirmPassword = document.querySelector('#togglerConfirmPassword" . $row['id'] . "');
            const confirmPassword = document.querySelector('#confirm_password" . $row['id'] . "');
            togglerConfirmPassword.addEventListener('click', () => {
                confirmPassword.type = (confirmPassword.type === 'password') ? 'text' : 'password';
            });
        });
        </script>
    ";

		// Department-class linkage JavaScript with optgroup separation
		echo '
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const departmentSelect = document.getElementById("department-' . $row['id'] . '");
        const classSelect = document.getElementById("class-' . $row['id'] . '");
        const deptClassesGroup = document.getElementById("dept-classes-' . $row['id'] . '");

        const classes = {
            "Department of Information Technology": [
                "BSIT-1A", "BSIT-1B", "BSIT-2A", "BSIT-2B",
                "BSIT-3A", "BSIT-3B", "BSIT-4A", "BSIT-4B"
            ],
            "Department of Computer Science": [
                "BSCS-1A", "BSCS-1B", "BSCS-2A", "BSCS-2B",
                "BSCS-3A", "BSCS-3B", "BSCS-4A", "BSCS-4B"
            ]
        };

        const teacherClasses = ' . json_encode($teacher_classes) . ';

        function updateClassOptions(department) {
            deptClassesGroup.innerHTML = ""; // Clear department-specific options

            if (classes[department]) {
                classes[department].forEach(function (classOption) {
                    // Skip if already in past selected classes to avoid duplicates
                    if (!teacherClasses.includes(classOption)) {
                        const option = document.createElement("option");
                        option.value = classOption;
                        option.textContent = classOption;
                        deptClassesGroup.appendChild(option);
                    }
                });
            }

            // Ensure past selected classes remain selected
            Array.from(classSelect.options).forEach(function (option) {
                if (teacherClasses.includes(option.value)) {
                    option.selected = true;
                }
            });
        }

        // Initialize class options based on current department
        updateClassOptions(departmentSelect.value);

        // Update class options when department changes
        departmentSelect.addEventListener("change", function () {
            updateClassOptions(this.value);
        });
    });
    </script>';
	}
	?><?php
		$sql = "SELECT * FROM staff_accounts";
		$stmt = $pdo->query($sql);

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// Pre-select fields
			$selectedDepartment = $row['department'];
			$selectedGender = $row['gender'];
			$selectedPhoneNumber = $row['phone_number'];

			// Construct full name
			$fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);

			// Fetch the classes this teacher advises
			$teacher_classes_stmt = $pdo->prepare("SELECT class_advising FROM staff_advising WHERE fullName = :fullName");
			$teacher_classes_stmt->execute([':fullName' => $fullName]);
			$teacher_classes = $teacher_classes_stmt->fetchAll(PDO::FETCH_COLUMN);

			echo '
    <div class="modal fade" id="viewModal' . $row['id'] . '" tabindex="-1" aria-labelledby="exampleModalLabel' . $row['id'] . '" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel' . $row['id'] . '"><b>Viewing Teacher: ' . htmlspecialchars($fullName) . '</b></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label bold">First Name</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($row['first_name']) . '" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label bold">Middle Name</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($row['middle_name']) . '" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label bold">Last Name</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($row['last_name']) . '" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label bold">Email</label>
                        <input type="email" class="form-control" value="' . htmlspecialchars($row['email']) . '" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label bold">Department</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($selectedDepartment) . '" readonly>
                    </div>
                    <div class="mb-3">
                    <label class="form-label bold">Class Advisory</label>';

			if (empty($teacher_classes)) {
				echo '<p class="text-muted"><em>No assigned classes</em></p>';
			} else {
				echo '<div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <tbody>
                                        <tr>';
				foreach ($teacher_classes as $index => $class) {
					echo '<td style="background-color:white !important">' . htmlspecialchars($class) . '</td>';
					if (($index + 1) % 3 == 0) {
						echo '</tr t"><tr>';
					}
				}
				echo '                </tr>
                                    </tbody>
                                </table>
                              </div>';
			}

			echo '
                </div>
                    <div class="mb-3">
                        <label class="form-label bold">Gender</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($selectedGender) . '" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label bold">Phone Number</label>
                        <input type="text" class="form-control" value="' . htmlspecialchars($selectedPhoneNumber) . '" readonly>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>';
		}
		?>

	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();
			console.log(newTime);
			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}

		setInterval(getTime, 100);

		document.addEventListener('DOMContentLoaded', function() {
			const semesterSelect = document.getElementById('semester');
			const availableClassesSelect = document.getElementById('availableClasses');
			const assignedClassesContainer = document.getElementById('assignedClassesContainer');
			const assignedClassesBody = document.getElementById('assignedClassesBody');

			const classes = {
				"First Semester": ["BSIT-1A", "BSIT-1B", "BSIT-2A"],
				"Second Semester": ["BSIT-3A", "BSIT-3B", "BSIT-4A"]
			};

			// Update available classes based on selected semester
			semesterSelect.addEventListener('change', function() {
				const selectedSemester = semesterSelect.value;

				// Clear previous options
				availableClassesSelect.innerHTML = '<option selected disabled>Select a Class</option>';

				// Add new options based on selected semester
				if (classes[selectedSemester]) {
					classes[selectedSemester].forEach(function(className) {
						const option = document.createElement('option');
						option.value = className;
						option.textContent = className;
						availableClassesSelect.appendChild(option);
					});
				}

				checkSelections();
			});

			// Show/hide assigned classes section based on selections
			function checkSelections() {
				if (semesterSelect.value !== 'Select a Semester' && availableClassesSelect.value !== 'Select a Class') {
					// Filter and show only relevant rows
					const selectedSemester = semesterSelect.value;
					const selectedClass = availableClassesSelect.value;
					let hasRelevantClasses = false;

					[...assignedClassesBody.getElementsByTagName('tr')].forEach(row => {
						if (row.dataset.semester === selectedSemester && row.dataset.class === selectedClass) {
							row.style.display = '';
							hasRelevantClasses = true;
						} else {
							row.style.display = 'none';
						}
					});

					if (hasRelevantClasses) {
						assignedClassesContainer.style.display = 'block';
					} else {
						assignedClassesContainer.style.display = 'none';
					}
				} else {
					assignedClassesContainer.style.display = 'none';
				}
			}

			// Remove class
			document.querySelectorAll('.remove-btn').forEach(button => {
				button.addEventListener('click', function() {
					const row = this.closest('tr');
					row.parentNode.removeChild(row);
					checkSelections();
				});
			});

			// Also check the selections when the class dropdown changes
			availableClassesSelect.addEventListener('change', checkSelections);
		});

		function hidePasswordsOnLoad() {
			var passwordCells = document.querySelectorAll('span[data-password]');
			passwordCells.forEach(function(passwordCell) {
				var password = passwordCell.getAttribute('data-password');
				passwordCell.textContent = '*'.repeat(password.length);
				passwordCell.setAttribute('data-hidden', 'true');
			});
		}

		function togglePassword(passwordId, button) {
			var passwordCell = document.getElementById(passwordId);
			var isHidden = passwordCell.getAttribute('data-hidden') === 'true';

			if (isHidden) {
				passwordCell.textContent = passwordCell.getAttribute('data-password');
				button.textContent = "Hide";
				passwordCell.setAttribute('data-hidden', 'false');
			} else {
				passwordCell.textContent = '*'.repeat(passwordCell.getAttribute('data-password').length);
				button.textContent = "Show";
				passwordCell.setAttribute('data-hidden', 'true');
			}
		}

		// Hide passwords when the page loads
		window.onload = hidePasswordsOnLoad;

		const togglerPassword = document
			.querySelector('#togglerPassword');
		const password = document.querySelector('#password');
		togglerPassword.addEventListener('click', () => {
			const type = password
				.getAttribute('type') === 'password' ?
				'text' : 'password';
			password.setAttribute('type', type);
			togglerPassword.classList.toggle('bi-eye');
		});

		const togglerConfirmPassword = document.querySelector('#togglerConfirmPassword');
		const confirmPassword = document.querySelector('#confirm_password');
		togglerConfirmPassword.addEventListener('click', () => {
			const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
			confirmPassword.setAttribute('type', type);
			togglerConfirmPassword.classList.toggle('bi-eye');
			togglerConfirmPassword.classList.toggle('bi-eye-slash');
		});


		function deleteModal(id) {
			Swal.fire({
				title: "Are you sure?",
				text: "You won't be able to revert this!",
				icon: "warning",
				showCancelButton: true,
				confirmButtonColor: "#3085d6",
				cancelButtonColor: "#d33",
				confirmButtonText: "Yes, delete it!"
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "processes/admin/staff/delete.php?id=" + id;
				}
			});
		}
	</script>

	<script>
		document.getElementById('messageForm').addEventListener('submit', function(event) {
			event.preventDefault(); // Prevent form submission

			// Get the message from the textarea
			var messageText = document.getElementById('messageInput').value;

			// If the message is not empty, append it to the chat
			if (messageText.trim() !== '') {
				var chatBody = document.getElementById('chatBody');

				// Create the new message element
				var newMessage = document.createElement('div');
				newMessage.className = 'row receiver'; // Set it as a sender message
				newMessage.innerHTML = `
	  
			<div class="col">
			  <div class="message">
				 <span>${messageText}</span>
			  </div>
			  <i class="bi bi-person"></i>
			</div>
	  
	  
	  `;

				// Append the new message to the chat body
				chatBody.appendChild(newMessage);

				// Clear the textarea
				document.getElementById('messageInput').value = '';

				// Scroll to the bottom of the chat
				chatBody.scrollTop = chatBody.scrollHeight;

			}
		});
	</script>

</html>

<?php include('processes/server/alerts.php') ?>