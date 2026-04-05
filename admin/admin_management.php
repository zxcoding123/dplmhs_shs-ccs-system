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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>

    <!-- Include SweetAlert CDN in your HTML head section -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

	.card-body {
		overflow-x: auto;
		/* Allow horizontal scrolling if needed */
		width: 100%;
		/* Full container width */
		padding: 1rem;
		/* Consistent padding */
	}

	#adminTable_wrapper {
		width: 100%;
		/* Ensure DataTables wrapper fits container */
	}

	table.dataTable {
		width: 100% !important;
		/* Force table to fit container */
		table-layout: auto;
		/* Allow natural column sizing */
	}

	/* Bootstrap table styling */
	.table-striped tbody tr:nth-of-type(odd) {
		background-color: rgba(0, 0, 0, 0.05);
	}

	/* Responsive adjustments */
	@media (max-width: 768px) {
		.card-body {
			padding: 0.5rem;
			/* Reduce padding on smaller screens */
		}

		.btn-sm {
			font-size: 0.75rem;
			/* Smaller buttons on mobile */
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
											<span>Admin Management</span>
										</h5>

										<div class="ms-auto" aria-hidden="true">
											<img src="external/svgs/undraw_favorite_gb6n.svg"
												class=" small-picture img-fluid">
										</div>
									</div>

									<br>

									<h5 class="card-title mb-0">
										<div class="d-flex align-items-center">
											<h3>Admin List</h3>
											<div class="ms-auto" aria-hidden="true">
												<button type="button" class="btn btn-csms" data-bs-toggle="modal"
													data-bs-target="#createAdmin">
													<i class="bi bi-pencil-square"></i> Create an Admin Account
												</button>
											</div>

										</div>
									</h5>
								</div>
								<div class="card-body">

									<?php


									// Fetch data from the admin table
									$stmt = $pdo->query("SELECT * FROM admin");
									$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
									?>


									<table id="adminTable" class="display table "
										width="100%">
										<thead class="text-center">
											<tr>
											
												<th>Email</th>
												<th>First Name</th>
												<th>Middle Name</th>
												<th>Last Name</th>
												<th>Phone Number</th>
												<th>Gender</th>
												<th>Date Created</th>
												<th>Last Login</th>
												<th>Manage</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($admins as $admin): ?>
												<tr>
										
													<td><?php echo htmlspecialchars($admin['email']); ?></td>
													<td><?php echo htmlspecialchars($admin['first_name']); ?></td>
													<td><?php echo htmlspecialchars($admin['middle_name']); ?></td>
													<td><?php echo htmlspecialchars($admin['last_name']); ?></td>
													<td><?php echo htmlspecialchars($admin['phone_number']); ?></td>
													<td><?php echo htmlspecialchars($admin['gender']); ?></td>
													<td><?php echo (new DateTime($admin['date_created']))->format('F j, Y'); ?>
													</td>
													<td>
														<?php
														// Check if last_login is not NULL and not empty
														if ($admin['last_login'] !== NULL && !empty($admin['last_login'])) {
															echo htmlspecialchars(date('F j, Y, g:i a', strtotime($admin['last_login'])));
														} else {
															echo "No login data";
														}
														?>
													</td>



													<td>
														<button class="btn btn-warning btn-sm" data-bs-toggle="modal"
															data-bs-target="#editAdmin<?php echo $admin['id']; ?>">Edit</button>

														<?php
														// Assuming $admin['status'] contains 'Active' or 'Inactive' and is coming from the database
														$statusText = $admin['status'] == 'Active' ? 'Deactivate' : 'Activate';
														$statusBtnClass = $admin['status'] == 'Active' ? 'btn-secondary' : 'btn-success';

														echo '<button type="button" 
															class="btn ' . $statusBtnClass . ' btn-sm" 
															onclick="updateStatus(' . $admin['id'] . ', \'' . $admin['status'] . '\')">
															' . $statusText . ' Admin
														</button>';
														?>

														<button class="btn btn-danger btn-sm"
															onclick="confirmDelete(<?php echo $admin['id']; ?>)">Delete</button>
													</td>

												
												
												</tr>

												<script>
													function updateStatus(adminId, currentStatus) {
														// Toggle between Active/Inactive
														const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active'; // Switch Active -> Inactive and Inactive -> Active

														console.log(`Changing admin status for admin ${adminId} to ${newStatus}`);

														// Create the AJAX request to update status
														var xhr = new XMLHttpRequest();
														xhr.open('POST', 'update_admin_status.php', true);
														xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

														xhr.onload = function() {
															if (xhr.status === 200) {
																console.log("Admin status update response:", xhr.responseText);
																location.reload(); // Reload the page to reflect the updated status
															} else {
																console.error("Error updating status:", xhr.status, xhr.statusText);
															}
														};

														xhr.onerror = function() {
															console.error("Request failed");
														};

														xhr.send('admin_id=' + adminId + '&new_status=' + newStatus);
													}
												</script>



												<!-- Edit Modal -->
												<div class="modal fade" id="editAdmin<?php echo $admin['id']; ?>"
													tabindex="-1"
													aria-labelledby="editAdminLabel<?php echo $admin['id']; ?>"
													aria-hidden="true">
													<div class="modal-dialog">
														<div class="modal-content">
															<div class="modal-header">
																<h5 class="modal-title"
																	id="editAdminLabel<?php echo $admin['id']; ?>">Edit
																	Admin</h5>
																<button type="button" class="btn-close"
																	data-bs-dismiss="modal" aria-label="Close"></button>
															</div>
															<form action="processes/admin/account/edit.php" method="POST">
																<div class="modal-body">
																	<input type="hidden" name="id"
																		value="<?php echo $admin['id']; ?>">

																

																	<div class="mb-3">
																		<label for="email<?php echo $admin['id']; ?>"
																			class="form-label">Email</label>
																		<input type="email" class="form-control"
																			id="email<?php echo $admin['id']; ?>"
																			name="email"
																			value="<?php echo htmlspecialchars($admin['email']); ?>"
																			required>
																	</div>

																	<div class="mb-3">
																		<label for="first_name<?php echo $admin['id']; ?>"
																			class="form-label">First Name</label>
																		<input type="text" class="form-control"
																			id="first_name<?php echo $admin['id']; ?>"
																			name="first_name"
																			value="<?php echo htmlspecialchars($admin['first_name']); ?>"
																			required>
																	</div>

																	<div class="mb-3">
																		<label for="middle_name<?php echo $admin['id']; ?>"
																			class="form-label">Middle Name</label>
																		<input type="text" class="form-control"
																			id="middle_name<?php echo $admin['id']; ?>"
																			name="middle_name"
																			value="<?php echo htmlspecialchars($admin['middle_name']); ?>">
																	</div>

																	<div class="mb-3">
																		<label for="last_name<?php echo $admin['id']; ?>"
																			class="form-label">Last Name</label>
																		<input type="text" class="form-control"
																			id="last_name<?php echo $admin['id']; ?>"
																			name="last_name"
																			value="<?php echo htmlspecialchars($admin['last_name']); ?>"
																			required>
																	</div>

																	<div class="mb-3">
																		<label for="phone_number<?php echo $admin['id']; ?>"
																			class="form-label">Phone Number</label>
																		<input type="text" class="form-control"
																			id="phone_number<?php echo $admin['id']; ?>"
																			name="phone_number"
																			value="<?php echo htmlspecialchars($admin['phone_number']); ?>">
																	</div>

																	<div class="mb-3">
																		<label for="gender<?php echo $admin['id']; ?>"
																			class="form-label">Gender</label>
																		<select class="form-select"
																			id="gender<?php echo $admin['id']; ?>"
																			name="gender" required>
																			<option value="Male" <?php echo ($admin['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
																			<option value="Female" <?php echo ($admin['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
																			<option value="Other" <?php echo ($admin['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
																		</select>
																	</div>

																	<!-- Password and Confirm Password fields -->
																	<div class="mb-3">
																		<label for="password<?php echo $admin['id']; ?>"
																			class="form-label">Password (leave as blank if not to be changed)</label>
																		<input type="password" class="form-control"
																			id="password<?php echo $admin['id']; ?>"
																			name="password">
																	</div>

																	<div class="mb-3">
																		<label
																			for="confirm_password<?php echo $admin['id']; ?>"
																			class="form-label">Confirm Password</label>
																		<input type="password" class="form-control"
																			id="confirm_password<?php echo $admin['id']; ?>"
																			name="confirm_password">
																	</div>

																</div>

																<div class="modal-footer">
																	<button type="button" class="btn btn-secondary"
																		data-bs-dismiss="modal">Close</button>
																	<button type="submit" class="btn btn-primary">Save
																		changes</button>
																</div>
															</form>
														</div>
													</div>
												</div>



											<?php endforeach; ?>
										</tbody>
									</table>
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
	<div class="modal fade" id="createAdmin" tabindex="-1" aria-labelledby="createAdminLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="createAdminLabel">Create Admin Account</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form action="processes/admin/account/add.php" method="POST">
					<div class="modal-body">
					
						<div class="mb-3">
							<label for="email" class="form-label">Email</label>
							<input type="email" class="form-control" name="email" required>
						</div>
						<div class="mb-3">
							<label for="password" class="form-label">Password</label>
							<input type="password" class="form-control" name="password" required>
						</div>
						<div class="mb-3">
							<label for="first_name" class="form-label">First Name</label>
							<input type="text" class="form-control" name="first_name" required>
						</div>
						<div class="mb-3">
							<label for="middle_name" class="form-label">Middle Name</label>
							<input type="text" class="form-control" name="middle_name">
						</div>
						<div class="mb-3">
							<label for="last_name" class="form-label">Last Name</label>
							<input type="text" class="form-control" name="last_name" required>
						</div>
						<div class="mb-3">
							<label for="phone_number" class="form-label">Phone Number</label>
							<input type="text" class="form-control" name="phone_number" required value="+63">
						</div>
						<div class="mb-3">
							<label for="gender" class="form-label">Gender</label>
							<select class="form-select" name="gender" required>
								<option value="Male">Male</option>
								<option value="Female">Female</option>
								<option value="Other">Other</option>
							</select>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary">Create Admin</button>
					</div>
				</form>
			</div>
		</div>
	</div>


												</body>



	<script src="js/app.js"></script>
	<?php
	include('processes/server/modals.php');
	?>


	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();

			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}
		setInterval(getTime, 100);

		// Adjust column widths for responsive design
		table.columns.adjust().responsive.recalc();
	</script>


	<script>

$(document).ready(function () {
    $('#adminTable').DataTable({
        "paging": true,           // Enable pagination
        "searching": true,        // Enable search
        "ordering": true,         // Enable column sorting
        "info": true,             // Show info text
        "lengthChange": true,     // Enable length selection
        "autoWidth": false,       // Prevent auto width issues
        "responsive": true        // Make table responsive
    });
});
</script>

<script> 

		function confirmDelete(adminId) {
			// Here you can add code to confirm and delete the admin record
			if (confirm("Are you sure you want to delete this admin?")) {
				window.location.href = "processes/admin/deleteAdmin.php?id=" + adminId;
			}
		}
	</script>


	<script>
		function confirmDelete(adminId) {
			Swal.fire({
				title: 'Are you sure?',
				text: "You won't be able to undo this action!",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'No, cancel!',
				reverseButtons: true
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = 'processes/admin/account/delete_admin.php?id=' + adminId; // Redirect to a PHP page that handles the deletion
				} else {
					Swal.fire(
						'Cancelled',
						'The admin account was not deleted.',
						'info'
					);
				}
			});
		}
	</script>
	
	
<?php



// Handle session status messages
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == "ACCOUNT_C_SUCCESSFUL") {
        echo "
        <script>
            Swal.fire({
                title: 'Account Created Successfully!',
                text: 'You have successfully created an admin account!',
                icon: 'success'
            });
        </script>
        ";
    } elseif ($_SESSION['STATUS'] == "ADMIN_DUPLICATE_ACCOUNT") {
        echo "
        <script>
            Swal.fire({
                title: 'Account Already Exists!',
                text: 'This account already exists as a duplicate. Please create a new one!',
                icon: 'error'
            });
        </script>
        ";
    } elseif ($_SESSION['STATUS'] == "ADMIN_DELETED_SUCCESS") {
        echo "
        <script>
            Swal.fire({
                title: 'Account Deletion Successful!',
                text: 'The admin account has been successfully deleted!',
                icon: 'success'
            });
        </script>
        ";
    } elseif ($_SESSION['STATUS'] == "ADMIN_EDIT_SUCCESSFUL") {
        echo "
        <script>
            Swal.fire({
                title: 'Account Edit Successful!',
                text: 'The admin account credentials and details have been successfully edited!',
                icon: 'success'
            });
        </script>
        ";
    } elseif ($_SESSION['STATUS'] == "ADMIN_CREATE_FAILED") {
        echo "
        <script>
            Swal.fire({
                title: 'Admin Account Creation Failed!',
                text: 'There was an error while creating the admin account. Please try again.',
                icon: 'error'
            });
        </script>
        ";
    }elseif ($_SESSION['STATUS'] == "ADMIN_NEW_STATUS_SUCCESFUL") {
        echo "
        <script>
            Swal.fire({
                title: 'Admin Account Activation Edition Succesful!',
                text: 'You have succesfully edited the status of this admin account.',
                icon: 'success'
            });
        </script>
        ";
}elseif ($_SESSION['STATUS'] == "ADMIN_EDIT_SUCCESFUL") {
        echo "
        <script>
            Swal.fire({
                title: 'Admin Account Edition Succesful!',
                text: 'You have succesfully edited the contents of this admin account.',
                icon: 'success'
            });
        </script>
        ";
    }
    
    
    

    // Unset the status after processing
    unset($_SESSION['STATUS']);
}


?>


</html>

<?php

include('processes/server/alerts.php');
?>

