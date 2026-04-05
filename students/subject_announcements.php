<?php
session_start();
if (!isset($_SESSION['student_id'])) {
	$_SESSION['STATUS'] = "STUDENT_NOT_LOGGED_IN";
	header("Location: ../login/index.php");
	exit;
}
include('processes/server/conn.php');
$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");

$sender_id = $_SESSION['user_id'];

$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>ADDU - CCS | Student Management System</title>
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
		background-color: #121ba3;
		color: white;
	}

	.btn-csms:hover {
		border: 1px solid #1E28B6FF;
	}

	.container-bordered {
		border: 1px solid black;
		margin: 10px;
		padding: 10px;
	}

	.missed {
		color: red;
	}

	.bordered {
		border: 1px solid black;
		margin: 10px;
		padding: 10px;
	}

	.container-bordered {
		border: 1px solid black;
		margin: 10px;
		padding: 10px;
	}

	/* Base Styles (Default for Desktop and Larger Screens) */
	table.dataTable {
		font-size: 14px;
		/* Default readable font size for desktop */
		border: 1px solid black;
	}

	td,
	th {
		text-align: center;
		vertical-align: middle;
		border: 1px solid black;
		padding: 8px;
		/* Default padding for better readability */
	}



	.container-bordered,
	.bordered {
		border: 1px solid black;
		margin: 10px;
		padding: 10px;
	}

	.missed {
		color: red;
	}

	/* Media Query for Very Small Mobile Devices (≤ 300px) */
	@media screen and (max-width: 300px) {
		table.dataTable {
			font-size: 10px;
			/* Smaller font for very small screens */
			border: 1px solid black;
		}

		ul {
			padding-left: 10px;
			/* Minimal left padding for bullets on very small screens */
			line-height: 1.0;
			/* Very tight line spacing for compactness */
		}

		li {
			margin-bottom: 2px;
			/* Minimal spacing between list items on tiny screens */
			padding: 0;
			/* No padding for maximum compactness */
			font-size: 10px;
			/* Very small text for readability on tiny screens */
		}

		td,
		th {
			padding: 4px;
			/* Reduced padding for compactness */
			font-size: 10px;
			/* Smaller text for readability on tiny screens */
		}

		.container-bordered,
		.bordered {
			margin: 5px;
			/* Reduced margin for tighter layout */
			padding: 5px;
			/* Reduced padding for compactness */
		}

		.btn-csms {
			font-size: 10px;
			/* Smaller button text for mobile */
			padding: 2px 6px;
			/* Reduced padding for smaller buttons */
		}

		h1,
		h3,
		h5 {
			font-size: 12px;
			/* Reduced heading sizes for mobile */
			line-height: 1.2;
			/* Tighter line spacing for compactness */
		}

		.table-responsive {
			font-size: 10px;
			/* Ensure tables are readable on small screens */
		}

		.card,
		.accordion-item {
			margin: 5px 0;
			/* Reduced margins for cards and accordions */
			padding: 5px;
			/* Reduced padding for compactness */
		}

		.list-group-item {
			padding: 5px;
			/* Compact list items */
			font-size: 10px;
			/* Smaller text for list items */
		}



		/* Stack columns vertically for very small screens */
		.row.text-center,
		.row.d-flex.justify-content-center {
			flex-direction: column;
			align-items: center;
		}

		.col,
		.col-sm-2 {
			width: 100%;
			/* Full width for columns on very small screens */
			margin-bottom: 10px;
			/* Space between stacked columns */
		}

		/* Ensure text remains readable and wraps properly */
		p,
		li {
			font-size: 10px;
			/* Smaller text for paragraphs and lists */
			word-wrap: break-word;
			/* Prevent text overflow */
		}

		.modal-dialog {
			margin: auto;
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
		}

	}

	/* Media Query for Small Mobile/Tablet Devices (≤ 500px) */
	@media screen and (max-width: 500px) {
		table.dataTable {
			font-size: 12px;
			/* Slightly larger font for small mobile/tablet */
			border: 1px solid black;
		}

		.container-bordered ul,
		.bordered ul {
			padding-right: 0 !important;
			/* Minimal padding for very small screens */
		}

		.container-bordered li,
		.bordered li {
			margin-bottom: 2px;
			/* Minimal spacing for very small screens */
		}

		td,
		th {
			padding: 6px;
			/* Moderate padding for readability and compactness */
			font-size: 12px;
			/* Readable text size for small screens */
		}

		.container-bordered,
		.bordered {
			margin: 8px;
			/* Moderate margin for better spacing */
			padding: 8px;
			/* Moderate padding for compactness */
		}

		.btn-csms {
			font-size: 12px;
			/* Slightly larger button text for mobile */
			padding: 4px 8px;
			/* Moderate padding for buttons */
		}

		h1,
		h3,
		h5 {
			font-size: 14px;
			/* Slightly larger headings for readability */
			line-height: 1.3;
			/* Slightly more spacing for readability */
		}

		.table-responsive {
			font-size: 12px;
			/* Ensure tables are readable */
		}

		.card,
		.accordion-item {
			margin: 8px 0;
			/* Moderate margins for cards and accordions */
			padding: 8px;
			/* Moderate padding for compactness */
		}

		.list-group-item {
			padding: 8px;
			/* Moderate padding for list items */
			font-size: 12px;
			/* Readable text for list items */
		}


		/* Stack columns vertically for small screens */
		.row.text-center,
		.row.d-flex.justify-content-center {
			flex-direction: column;
			align-items: center;
		}

		.col,
		.col-sm-2 {
			width: 100%;
			/* Full width for columns on small screens */
			margin-bottom: 10px;
			/* Space between stacked columns */
		}

		/* Ensure text remains readable and wraps properly */
		p,
		li {
			font-size: 12px;
			/* Readable text for paragraphs and lists */
			word-wrap: break-word;
			/* Prevent text overflow */
		}
	}

	/* Media Query for Tablet and Desktop (≥ 501px) */
	@media screen and (min-width: 501px) {
		table.dataTable {
			font-size: 14px;
			/* Default readable font size for tablets/desktops */
			border: 1px solid black;
		}

		.modal-dialog {
			margin: 0 auto !important
				/* Center modal and add moderate margins for small screens */
		}

		.container-bordered ul,
		.bordered ul {
			padding-right: 0 !important;
			/* Minimal padding for very small screens */
		}

		.container-bordered li,
		.bordered li {
			margin-bottom: 2px;
			/* Minimal spacing for very small screens */
		}

		td,
		th {
			padding: 8px;
			/* Default padding for better readability */
			font-size: 14px;
			/* Readable text for larger screens */
		}



		.btn-csms {
			font-size: 14px;
			/* Default button text size */
			padding: 6px 12px;
			/* Default padding for buttons */
		}

		h1,
		h3,
		h5 {
			font-size: 16px;
			/* Default heading sizes for readability */
			line-height: 1.5;
			/* Default line spacing for readability */
		}

		.table-responsive {
			font-size: 14px;
			/* Ensure tables are readable on larger screens */
		}

		.card,
		.accordion-item {
			margin: 10px 0;
			/* Default margins for cards and accordions */
			padding: 10px;
			/* Default padding for readability */
		}

		.list-group-item {
			padding: 10px;
			/* Default padding for list items */
			font-size: 14px;
			/* Readable text for list items */
		}

		.modal-dialog {
			margin: 1rem;
			/* Default modal margins for larger screens */
		}

		/* Maintain column layout for larger screens */
		.row.text-center,
		.row.d-flex.justify-content-center {
			flex-direction: row;
			/* Horizontal layout on larger screens */
			justify-content: center;
			/* Center the row content */
		}

		.col,
		.col-sm-2 {
			width: auto;
			/* Allow columns to adjust based on Bootstrap grid */
			margin-bottom: 0;
			/* No extra margin between columns in row */
		}

		/* Ensure text remains readable and wraps properly */
		p,
		li {
			font-size: 14px;
			/* Default readable text for paragraphs and lists */
			word-wrap: break-word;
			/* Prevent text overflow */
		}
	}


	/* for activities section responsivenesss */

	/* Media Query for Very Small Mobile Devices (≤ 250px) */
	@media screen and (max-width: 700px) {
		/* html, body {
        font-size: 12px!important; 
    } */

		.container-fluid,
		.accordion-body,
		.subject-section,
		.card,
		.list-group-item {
			padding: 2px;
			/* Minimal padding for compactness */
			margin: 2px;
			/* Minimal margin for tight layout */
		}

		h1,
		h3,
		h5 {
			font-size: 12px;
			/* Very small heading size */
			margin-bottom: 2px;
			/* Minimal margin below headings */
			line-height: 1.0;
			/* Very tight line spacing */
		}

		.table {
			font-size: 8px;
			/* Very small table font size for readability */
			border: 1px solid #dee2e6;
		}

		.table th,
		.table td {
			padding: 2px;
			/* Minimal padding for table cells */
			font-size: 8px;
			/* Very small text for table cells */
		}

		/* Stack table layout for very small screens */
		.table-responsive {
			overflow-x: auto;
			/* Enable horizontal scrolling if needed */
			-webkit-overflow-scrolling: touch;
			/* Smooth scrolling on mobile */
		}

		.table-responsive table {
			display: block;
		}

		.table-responsive thead {
			display: none;
			/* Hide headers on very small screens */
		}

		.table-responsive tbody,
		.table-responsive tr,
		.table-responsive td {
			display: block;
			width: 100%;
		}

		.table-responsive tr {
			margin-bottom: 5px;
			background-color: #f8f9fa;
			/* Light background for each "row block" */
			padding: 5px;
			border-radius: 3px;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
			/* Subtle shadow for separation */
		}

		.table-responsive td {
			position: relative;
			padding: 2px 0 2px 40%;
			/* Space for label on the left, reduced for compactness */
			font-size: 8px;
			/* Very small text for readability */
			border-bottom: 1px solid #ddd;
			/* Separator between cells */
		}

		.table-responsive td::before {
			content: attr(data-label) ": ";
			/* Display the label before the content */
			position: absolute;
			left: 0;
			width: 35%;
			/* Reduced width for labels to fit 250px */
			padding-left: 2px;
			/* Minimal padding for labels */
			font-weight: bold;
			font-size: 8px;
			/* Very small label text */
		}

		.table-responsive td:last-child {
			border-bottom: none;
			/* Remove border from last cell in each row */
		}

		.btn {
			font-size: 8px;
			/* Very small button font size */
			padding: 2px 4px;
			/* Minimal button padding */
			width: 100%;
			/* Full-width buttons for easy tapping */
		}

		.bi {
			/* Bootstrap Icons */
			margin-right: 1px;
			/* Minimal icon spacing */
			font-size: 8px;
			/* Very small icons */
		}

		ul {
			padding-left: 8px;
			/* Minimal left padding for bullets */
			line-height: 0.9;
			/* Very tight line spacing */
		}

		li {
			margin-bottom: 2px;
			/* Minimal spacing between list items */
			padding: 0;
			/* No padding for maximum compactness */
			font-size: 8px;
			/* Very small list item font size */
		}

		.modal-dialog {
			max-width: 90%;
			/* Nearly full width for modals */
			margin: 0.1rem auto;
			/* Minimal margin for centering */
			height: 80vh;
			/* Reduced height to fit tiny screens */
		}

		.modal-content {
			padding: 2px;
			/* Minimal padding for modals */
		}

		.modal-header,
		.modal-body,
		.modal-footer {
			padding: 2px;
			/* Minimal padding for modal sections */
		}

		.modal-title {
			font-size: 14px !important;
			/* Very small modal title */
			line-height: 0.9;
			/* Very tight line spacing */
		}

		.btn-close {
			font-size: 8px;
			/* Very small close button */
		}

		.card {
			padding: 10px;
		}

		.shadow-sm {
			padding: 10px;
		}
	}
</style>

<body>
	<div class="wrapper">
		<?php
		include('sidebar.php')
		?>

		<div class="main">
			<?php
			include('topbar.php')
			?>

			<main class="content">
				<div class="container-fluid p-0">
					<div class="card shadow-sm">
						<div class="card-body p-3">
					<h1 class="h3 mb-3 d-flex align-items-center">
    <button class="btn btn-link p-0 me-2" onclick="history.back()" style="text-decoration: none;">
        <i class="bi bi-arrow-left-circle"></i>
    </button>
    <strong>Class &nbsp;</strong>Announcements
</h1>
							<br>
							<div class="container-fluid my-4">
							
								<div class="row">
									<!-- Class Announcements Column -->
									<div class="col-lg mb-3">
									
									<div id="class-announcements" class="row"></div>
<nav>
  <ul id="class-pagination" class="pagination justify-content-center mt-3"></ul>
</nav>

<!-- Optional Modal for Full Post -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modal-title" class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-content"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

									</div>
								</div>
							</div>

							<?php
							$course = $_SESSION['course'];
							$year_level = $_SESSION['year_level'];
							$student_id = $_SESSION['user_id'];

							function yearLabelToNumber(string $year): string
							{
								return match ($year) {
									'1st Year' => '1',
									'2nd Year' => '2',
									'3rd Year' => '3',
									'4th Year' => '4',
									default => 'Unknown'
								};
							}

							$year_level_mapped = yearLabelToNumber($year_level);

							$classPattern = $course . '-' . $year_level_mapped . '%';

						$sql = "
    SELECT 
        sa.id,
        sa.subject_id,
        sa.teacher_id,
        sa.title,
        sa.content,
        sa.created_at,
        t.fullName AS teacher_name,
        t.avatar AS teacher_avatar,
        s.name AS subject_name
    FROM subject_announcements sa
    JOIN classes c 
        ON sa.subject_id = c.subject_id
    JOIN subjects s
        ON s.id = sa.subject_id
    JOIN students_enrollments se 
        ON se.class_id = c.id
    JOIN staff_accounts t 
        ON sa.teacher_id = t.id
    WHERE se.student_id = :student_id
    GROUP BY sa.id
    ORDER BY sa.created_at DESC
";

							$stmt = $pdo->prepare($sql);
							$stmt->execute([
								'student_id' => $student_id
							]);

							$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

				


							?>
							<script>
								
						const postsPerPage = 3;
let currentPage = 1;
const announcementsData = <?php echo json_encode($announcements); ?>; // or subjectAnnouncements

function renderPosts(page = 1) {
  const container = document.getElementById('class-announcements');
  container.innerHTML = '';
  
  const start = (page - 1) * postsPerPage;
  const end = start + postsPerPage;
  const limitedPosts = announcementsData.slice(start, end);

  limitedPosts.forEach((post, index) => {
    const postId = `post-${start + index}`;
    const col = document.createElement('div');
    col.className = 'col-12 mb-3';

    col.innerHTML = `
  <div class="card shadow-sm announcement-post">
    <div class="card-body">
      <!-- Header -->
      <div class="d-flex align-items-center mb-2">
        <img src="../uploads/profile_pictures/${post.teacher_avatar ?? 'https://i.pravatar.cc/50?u=' + post.teacher_id}" class="rounded-circle me-3" width="48" height="48">
        <div class="flex-grow-1">
          <div class="fw-semibold">${post.teacher_name}</div>
          <div class="small text-muted">${post.subject_name ?? post.class_name} · ${new Date(post.created_at).toLocaleString()}</div>
        </div>
      </div>

      <hr>
      <!-- Title -->
      <h6 class="fw-bold mb-2">${post.title}</h6>
      <hr>

      <!-- Content Preview -->
      <div class="announcement-preview mb-2 p-2">${truncateHTML(post.content, 100)}</div>

      <!-- Actions: View Post / Comment / Share -->
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" id="view-${postId}"><i class="bi bi-eye"></i> View Post</button>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat"></i> Comment</button>
        <button class="btn btn-sm btn-outline-warning"><i class="bi bi-share"></i> Share</button>
      </div>
    </div>
  </div>
`;


    container.appendChild(col);

    // Show full post in modal
    const viewBtn = document.getElementById(`view-${postId}`);
    viewBtn.addEventListener('click', () => {
      document.getElementById('modal-title').innerHTML = post.title;
      document.getElementById('modal-content').innerHTML = post.content;
      const modal = new bootstrap.Modal(document.getElementById('announcementModal'));
      modal.show();
    });
  });

  renderPagination(page);
}


function renderPagination(page) {
  const paginationContainer = document.getElementById('class-pagination');
  paginationContainer.innerHTML = '';

  const totalPages = Math.ceil(announcementsData.length / postsPerPage);

  const prevLi = document.createElement('li');
  prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
  prevLi.innerHTML = `<a class="page-link" href="#">Previous</a>`;
  prevLi.addEventListener('click', e => {
    e.preventDefault();
    if (page > 1) {
      currentPage--;
      renderPosts(currentPage);
    }
  });
  paginationContainer.appendChild(prevLi);

  for (let i = 1; i <= totalPages; i++) {
    const li = document.createElement('li');
    li.className = `page-item ${i === page ? 'active' : ''}`;
    li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
    li.addEventListener('click', e => {
      e.preventDefault();
      currentPage = i;
      renderPosts(currentPage);
    });
    paginationContainer.appendChild(li);
  }

  const nextLi = document.createElement('li');
  nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`;
  nextLi.innerHTML = `<a class="page-link" href="#">Next</a>`;
  nextLi.addEventListener('click', e => {
    e.preventDefault();
    if (page < totalPages) {
      currentPage++;
      renderPosts(currentPage);
    }
  });
  paginationContainer.appendChild(nextLi);
}

// Use your existing truncateHTML helper from previous code
function truncateHTML(html, maxLength = 100) {
  const div = document.createElement('div');
  div.innerHTML = html;
  let length = 0;
  let done = false;

  function traverse(node) {
    if (done) return null;
    let clone;
    if (node.nodeType === Node.TEXT_NODE) {
      if (length + node.nodeValue.length > maxLength) {
        const truncatedText = node.nodeValue.slice(0, maxLength - length) + "...";
        length = maxLength;
        done = true;
        return document.createTextNode(truncatedText);
      } else {
        length += node.nodeValue.length;
        return node.cloneNode();
      }
    }
    clone = node.cloneNode(false);
    for (let child of node.childNodes) {
      const childClone = traverse(child);
      if (childClone) clone.appendChild(childClone);
    }
    return clone;
  }

  const truncatedNode = traverse(div);
  const wrapper = document.createElement('div');
  if (truncatedNode) wrapper.appendChild(truncatedNode);
  return wrapper.innerHTML;
}

// Initial render
renderPosts(currentPage);

							
							</script>






							<div class="row text-center d-flex justify-content-center">
								<h3 class="bold">Shortcut Links</h3>
								<div class="col-sm-2 container-bordered cb-hover  " data-bs-toggle="collapse"
									data-bs-target="#studentAllInfo">
									Info
								</div>
								<div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
									data-bs-target="#studentAllSubjects">
									Subjects
								</div>
								<div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
									data-bs-target="#studentAllActivities">
									Activities
								</div>

								<div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
									data-bs-target="#studentAllAttendance">
									Attendance
								</div>

								<div class="col-sm-2 container-bordered  cb-hover" data-bs-toggle="collapse"
									data-bs-target="#studentAllGrades">
									Grades
								</div>
							</div>

							<?php
							if (isset($_SESSION['student_id'])) {

								$studentId = $_SESSION['student_id'];

								$sql = "SELECT * FROM students WHERE student_id = :studentId";
								$stmt = $pdo->prepare($sql);
								$stmt->bindParam(':studentId', $studentId);
								$stmt->execute();

								// Assuming there is one row of data
								$student = $stmt->fetch(PDO::FETCH_ASSOC);

								// Fetch student data from the database
								$sql = "SELECT * FROM student_info WHERE student_id = :studentId";
								$stmt = $pdo->prepare($sql);
								$stmt->bindParam(':studentId', $studentId);
								$stmt->execute();

								// Assuming there is one row of data
								$studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

								// If no data is found, redirect or handle the error
								if (!$studentInfo) {
								}
							}
							?>


							<div class="accordion" id="shortcutLinks">
								<div class="container-fluid accordion-collapse collapse bordered" id="studentAllInfo"
									data-bs-parent="#shortcutLinks">
									<div class="accordion-body">
										<h1 class="text-center bold">Personal Information</h1>
										<br>
										<p><strong>Full Name:</strong>
											<?php echo htmlspecialchars($studentInfo['full_name'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>ADDU Email:</strong>
											<?php echo htmlspecialchars($student['email'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Email:</strong>
											<?php echo htmlspecialchars($studentInfo['email'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Course & Year:</strong>
											<?php echo htmlspecialchars($studentInfo['course_year'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Address:</strong>
											<?php echo htmlspecialchars($studentInfo['address'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Phone Number:</strong>
											<?php echo htmlspecialchars($studentInfo['phone_number'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Emergency Contact:</strong>
											<?php echo htmlspecialchars($studentInfo['emergency_contact'] ?? 'No information added yet.'); ?>
										</p>
										<p><strong>Gender:</strong>
											<?php echo htmlspecialchars($studentInfo['gender'] ?? 'No information added yet.'); ?>
										</p>
									</div>

								</div>

								<div class="container-fluid accordion-collapse collapse bordered"
									id="studentAllSubjects" data-bs-parent="#shortcutLinks">
									<div class="accordion-body">
										<?php

										$studentId = $_SESSION['student_id'];

										$stmt = $pdo->prepare("SELECT class_id FROM students_enrollments WHERE student_id = ?");
										$stmt->execute([$studentId]);
										$enrolledClasses = $stmt->fetchAll(PDO::FETCH_COLUMN);

										$classes = [];

										if (!empty($enrolledClasses)) {
											// Fetch class details from the 'classes' table using class_id
											$inQuery = implode(',', array_fill(0, count($enrolledClasses), '?')); // For use in WHERE IN clause
											$stmt = $pdo->prepare("SELECT * FROM classes WHERE id IN ($inQuery)");
											$stmt->execute($enrolledClasses);
											$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
										}
										?>
										<div class="container-fluid text-center">
											<h1 class="bold">Subjects</h1>

											<div class="d-flex align-items-center">

												<button type="button" class="btn btn-primary" data-bs-toggle="modal"
													data-bs-target="#enterClassModal">
													<i class="bi bi-door-open-fill"></i> Enter Class
												</button>
												<div class=" ms-auto" aria-hidden="true">
													<form>
														<input type="text" class="form-control" id="searchClasses"
															placeholder="Search classes by name, subject, or teacher"
															oninput="filterClasses()">
													</form>
												</div>
											</div>
											<div class="row align-items-center mb-4">



											</div>

											<div class="row" id="classesContainer">
												<!-- Display the enrolled classes -->
												<?php if (!empty($classes)): ?>
													<?php foreach ($classes as $class): ?>
														<div class="col mb-4 class-item">
															<div class="card">
																<div class="card-body">
																	<h5 class="card-text class-name">
																		<span class="bold">
																			Course Year and Section:
																		</span>
																		<em>
																			<?php echo htmlspecialchars($class['name']); ?>
																		</em>
																	</h5>
																	<h5 class="card-text class-subject">
																		<span class="bold">Subject:
																		</span><em><?php echo htmlspecialchars($class['subject']); ?> (<?php echo htmlspecialchars($class['type']); ?>)</em>
																	</h5>
																	<h5 class="card-text class-teacher">
																		<span class="bold">Teacher:</span>
																		<em><?php echo htmlspecialchars($class['teacher']); ?></em>
																	</h5>
																	<h5 class="card-text class-code">
																		<span class="bold">Class Code:</span>
																		<em><?php echo htmlspecialchars($class['classCode']); ?></em>
																	</h5>
																	<br>
																	<a href="student_classes.php?class_id=<?php echo $class['id']; ?>"
																		class="btn btn-primary">
																		<i class="bi bi-door-open-fill"></i> Go to Class
																	</a>
																</div>
															</div>
														</div>
													<?php endforeach; ?>
												<?php else:
													echo "
													<div class='alert alert-warning'>
													<p class='text-muted text-center'>No classes enrolled.</p>
													</div>"; ?>

												<?php endif; ?>
											</div>
										</div>

										<script>
											// JavaScript function to filter classes
											function filterClasses() {
												const searchValue = document.getElementById('searchClasses').value.toLowerCase();
												const classItems = document.querySelectorAll('#classesContainer .class-item');

												classItems.forEach(item => {
													const className = item.querySelector('.class-name').textContent.toLowerCase();
													const classSubject = item.querySelector('.class-subject').textContent.toLowerCase();
													const classTeacher = item.querySelector('.class-teacher').textContent.toLowerCase();
													const classCode = item.querySelector('.class-code').textContent.toLowerCase();

													// Check if the search value matches any relevant text in the card
													if (
														className.includes(searchValue) ||
														classSubject.includes(searchValue) ||
														classTeacher.includes(searchValue) ||
														classCode.includes(searchValue)
													) {
														item.style.display = ''; // Show the class
													} else {
														item.style.display = 'none'; // Hide the class
													}
												});
											}
										</script>

									</div>
								</div>

								<div class="modal fade" id="enterClassModal" tabindex="-1"
									aria-labelledby="enterClassModalLabel" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered">
										<div class="modal-content">
											<div class="modal-header">
												<h5 class="modal-title" id="enterClassModalLabel">Enter
													Class Code</h5>
												<button type="button" class="btn-close" data-bs-dismiss="modal"
													aria-label="Close"></button>
											</div>
											<div class="modal-body">
												<form method="POST" action="processes/students/class/enter.php">
													<div class="mb-3">
														<label for="classCode" class="form-label">Class
															Code</label>
														<input type="text" class="form-control" id="classCode"
															name="classCode" placeholder="Enter Class Code">
													</div>

											</div>
											<div class="modal-footer">

												<input type="submit" class="btn btn-csms" value="Join">
												</form>
											</div>
										</div>
									</div>
								</div>

								<div class="container-fluid accordion-collapse collapse bordered"
									id="studentAllActivities" data-bs-parent="#shortcutLinks">
									<div class="accordion-body">
										<h1 class="bold text-center mb-4">Activities List</h1>
										<div>
											<?php
											try {
												// Fetch the student's ID from the session
												$student_id = $_SESSION['student_id'];

												// Fetch all class IDs the student is enrolled in
												$stmt = $pdo->prepare("SELECT e.class_id, c.subject, c.type
														FROM students_enrollments e
														INNER JOIN classes c ON e.class_id = c.id
														WHERE e.student_id = :student_id");
												$stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
												$stmt->execute();
												$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if ($classes) {
													foreach ($classes as $class) {

														$class_id = $class['class_id'];
														$subject = htmlspecialchars($class['subject']);

											?>
														<!-- Subject Title -->
														<div class="subject-section mb-4 text-center">
															<h3 class="subject-title mb-4"><span class="bold">Subject:</span>
																<?php echo $subject; ?> (<?php echo $class['type']; ?>)
															</h3>

															<!-- Activity Table -->
															<table class="table table-striped table-hover table-bordered">
																<thead class="table-secondary">
																	<tr>
																		<th scope="col">
																			<i class="bi bi-card-text"></i> Title
																		</th>
																		<th scope="col">
																			<i class="bi bi-info-circle"></i> Description
																		</th>
																		<th scope="col">
																			<i class="bi bi-calendar-date"></i> Due Date
																		</th>
																		<th scope="col">
																			<i class="bi bi-tools"></i> Manage
																		</th>
																	</tr>

																</thead>
																<tbody>
																	<?php
																	// Fetch all activities for this class
																	$activityStmt = $pdo->prepare("SELECT title, message, due_date 
                                    FROM activities 
                                    WHERE class_id = :class_id 
                                    ORDER BY due_date ASC");
																	$activityStmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
																	$activityStmt->execute();
																	$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

																	if ($activities) {
																		foreach ($activities as $activity) {
																			$dueDate = new DateTime($activity['due_date']);
																			$formattedDueDate = $dueDate->format('F j, Y');
																	?>
																			<tr>
																				<td><strong><?php echo htmlspecialchars($activity['title']); ?></strong>
																				</td>
																				<td><?php echo htmlspecialchars($activity['message']); ?>
																				</td>
																				<td>
																					<?php echo $formattedDueDate; ?>
																				</td>
																				<td>
																					<a href="student_classes.php?class_id=<?php echo $class_id; ?>&url=activity"
																						<button class="btn btn-primary"><i
																							class="bi bi-door-open-fill"></i>Open
																						Class</button>
																					</a>
																					<!-- <button class="btn btn-success"><i
																							class="bi bi-door-open-fill"></i>View (Shortcut)
																					</button> -->
																				</td>
																			</tr>
																	<?php
																		}
																	} else {
																		echo '<tr><td colspan="4" class="text-center text-muted">No activities found for this subject.</td></tr>';
																	}
																	?>
																</tbody>
															</table>


														</div>
											<?php
													}
												} else {
													echo "
													<div class='alert alert-warning'>
													<p class='text-muted text-center'>No classes enrolled.</p>
													</div>";
												}
											} catch (PDOException $e) {
												echo "<p class='text-danger text-center'>Error: " . $e->getMessage() . "</p>";
											}
											?>
										</div>
									</div>


								</div>
								<div class="container-fluid accordion-collapse collapse bordered"
									id="studentAllAttendance" data-bs-parent="#shortcutLinks">
									<div class="accordion-body">
										<h1 class="bold text-center mb-4">Attendance</h1>
										<?php
										// Include the database connection
										require_once 'processes/server/conn.php';

										// Get the logged-in student's ID
										$studentId = $_SESSION['student_id'] ?? null;

										if ($studentId) {
											// Query to get meetings for classes the student is enrolled in
											$stmtMeetings = $pdo->prepare("
            SELECT cm.id AS meeting_id, cm.date, cm.class_id, cm.status, cm.start_time, cm.end_time, cm.type,
                   c.name AS class_name, c.subject AS subject_name, c.type as type, c.teacher AS teacher_name,
                   s.id AS semesterId
            FROM students_enrollments se
            JOIN classes_meetings cm ON se.class_id = cm.class_id
            JOIN classes c ON cm.class_id = c.id
            JOIN semester s ON c.semester = s.name
            WHERE se.student_id = :student_id
              AND cm.date = CURDATE()
         
          
        ");
											$stmtMeetings->execute([':student_id' => $studentId]);

											// Check if there are results
											if ($stmtMeetings->rowCount() > 0) {
												echo '<div class="list-group">';
												while ($row = $stmtMeetings->fetch(PDO::FETCH_ASSOC)) {
													// Create the URL for the attendance page
													$attendanceUrl = 'class_attendance_qr.php?class_id=' . urlencode($row['class_id']) .
														'&classAttendanceId=' . urlencode($row['meeting_id']) .
														'&semesterId=' . urlencode($row['semesterId']);

													echo '<div class="list-group-item border-0 mb-3 shadow-sm rounded">';
													echo '<div class="d-flex justify-content-between align-items-center">';
													echo '<div class="pe-3">';
													echo '<h5 class="mb-1 text-primary"><strong>' . htmlspecialchars($row['class_name']) .  '</strong></h5>';
													echo '<p class="mb-1"><strong>Subject:</strong> ' . htmlspecialchars($row['subject_name']) . ' (' . htmlspecialchars($row['type']) . ')</p>';

													echo '<p class="mb-1"><strong>Teacher:</strong> ' . htmlspecialchars($row['teacher_name']) . '</p>';
													echo '<p class="mb-1"><strong>Date:</strong> ' . htmlspecialchars($row['date']) . '</p>';
													echo '<p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">' . htmlspecialchars($row['status']) . '</span></p>';
													echo '<p class="mb-1"><strong>Start Time:</strong> ' . htmlspecialchars($row['start_time']) . '</p>';
													echo '<p><strong>End Time:</strong> ' . htmlspecialchars($row['end_time']) . '</p>';
													echo '</div>';
													echo '<div>';
													echo '<a href="' . htmlspecialchars($attendanceUrl) . '" class="btn btn-outline-primary btn-lg d-flex align-items-center">';
													echo '<i class="bi bi-arrow-right-circle me-2"></i> Enter';
													echo '</a>';
													echo '</div>';
													echo '</div>';
													echo '</div>';
												}
												echo '</div>';
											} else {
												echo "
												<div class='alert alert-warning'>
												<p class='text-muted text-center'>No ongoing meetings found for today</p>
												</div>";
											}
										} else {
											echo '<p>Student not logged in.</p>';
										}
										?>
									</div>
								</div>

								<div class="container-fluid accordion-collapse collapse bordered" id="studentAllGrades"
									data-bs-parent="#shortcutLinks">
									<div class="accordion-body">
										<h1 class="bold text-center mb-4">Grades</h1>
										<?php
										// Include the database connection
										require_once 'processes/server/conn.php';

										// Get the logged-in student's ID
										$studentId = $_SESSION['student_id'] ?? null;

										if ($studentId) {
											// Query to get semesters and their start/end years
											$stmtSemesters = $pdo->query("
            SELECT name AS semester_name, 
                   DATE_FORMAT(start_date, '%Y-%m-%d') AS start_year, 
                   DATE_FORMAT(end_date, '%Y-%m-%d') AS end_year
            FROM semester
        ");
											$semesters = $stmtSemesters->fetchAll(PDO::FETCH_ASSOC);

											if ($semesters) {
												echo '<div class="container-fluid mt-5">';
												foreach ($semesters as $semester) {
													$semesterName = htmlspecialchars($semester['semester_name']);
													$startYear = (new DateTime($semester['start_year']))->format('F j, Y');
													$endYear = (new DateTime($semester['end_year']))->format('F j, Y');

													// Query classes for the semester
													$stmtClasses = $pdo->prepare("
                    SELECT 
                        c.id AS class_id, 
                        c.name AS class_name, 
                        c.subject AS subject_name,
                        c.type AS type
                    FROM students_enrollments se
                    JOIN classes c ON se.class_id = c.id
                    WHERE se.student_id = :student_id AND c.semester = :semester_name
                ");
													$stmtClasses->execute([':student_id' => $studentId, ':semester_name' => $semesterName]);
													$classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

													echo '<div class="card shadow mb-4">';
													echo '<div class="card-header">';
													echo '<h4> <span class="bold">Semester:</span> ' . $semesterName . ' <br> <br> <span class="bold">School Year and Date: </span>
                (' . $startYear . ' - ' . $endYear . ')</h4>';
													echo '<br></div>';

													if (count($classes) > 0) {
														// Analyze subjects to identify which have both lab and lecture
														$subjectAnalysis = [];
														$lecOnlyClasses = [];
														$regularClasses = [];

														// First pass: Gather all subjects and their component types
														foreach ($classes as $class) {
															$subjectName = $class['subject_name'];
															$classType = strtolower($class['type'] ?? '');

															if (!isset($subjectAnalysis[$subjectName])) {
																$subjectAnalysis[$subjectName] = [
																	'has_lab' => false,
																	'has_lec' => false,
																	'classes' => []
																];
															}

															// Add this class to the subject's class list
															$subjectAnalysis[$subjectName]['classes'][] = $class;

															// Determine if this is a lab or lecture
															if (strpos($classType, 'laboratory') !== false || strpos($classType, 'lab') !== false) {
																$subjectAnalysis[$subjectName]['has_lab'] = true;
															}
															if (strpos($classType, 'lecture') !== false || strpos($classType, 'lec') !== false) {
																$subjectAnalysis[$subjectName]['has_lec'] = true;
															}
														}

														// Second pass: Categorize classes
														foreach ($subjectAnalysis as $subjectName => $info) {
															// If subject has lecture only (no lab)
															if ($info['has_lec'] && !$info['has_lab']) {
																foreach ($info['classes'] as $class) {
																	$lecOnlyClasses[] = $class;
																}
															} else {
																// All other classes (including those with both lab and lecture)
																foreach ($info['classes'] as $class) {
																	$regularClasses[] = $class;
																}
															}
														}

														// SECTION 1: Regular Classes Table (includes subjects with both lab and lecture)
														if (!empty($regularClasses)) {
															echo '<div class="card-body">';
															echo '<h5 class="card-title mb-3">Regular Classes</h5>';
															echo '<div class="table-responsive text-center">';
															echo '<table class="table table-striped table-hover table-bordered">';
															echo '<thead class="table-secondary">';
															echo '<tr>';
															echo '<th><i class="bi bi-journal"></i> Subject</th>';
															echo '<th><i class="bi bi-building"></i> Class</th>';
															echo '<th><i class="bi bi-star"></i> Midterm Grade</th>';
															echo '<th><i class="bi bi-star-fill"></i> Final Grade</th>';
															echo '<th><i class="bi bi-award"></i> Numerical Grade Rating</th>';

															echo '<th><i class="bi bi-info-circle"></i> Overall Grade <br>
	<small>(Lecture and Laboratory)</th>';
															echo '</tr>';
															echo '</thead>';
															echo '<tbody>';

															// Cache to store subject components and grades for efficiency
															$subjectComponentsCache = [];
															$subjectGradesCache = [];

															foreach ($regularClasses as $class) {
																$classId = $class['class_id'];
																$subjectName = htmlspecialchars($class['subject_name']);
																$className = htmlspecialchars($class['class_name']);
																$classType = strtolower($class['type'] ?? '');

																// Check if we've already analyzed this subject's components and grades
																if (!isset($subjectComponentsCache[$subjectName])) {
																	// Fetch all classes with the same subject to check for related Lecture/Lab classes
																	$relatedClassesStmt = $pdo->prepare("SELECT id, type FROM classes WHERE subject = ?");
																	$relatedClassesStmt->execute([$subjectName]);
																	$relatedClasses = $relatedClassesStmt->fetchAll(PDO::FETCH_ASSOC);

																	// Detect Lecture and Lab based on all related classes
																	$hasLab = false;
																	$hasLec = false;
																	$lecClassId = null;
																	$labClassId = null;

																	foreach ($relatedClasses as $relatedClass) {
																		$relatedType = strtolower($relatedClass['type'] ?? '');
																		if (strpos($relatedType, 'laboratory') !== false || strpos($relatedType, 'lab') !== false) {
																			$hasLab = true;
																			$labClassId = $relatedClass['id'];
																		}
																		if (strpos($relatedType, 'lecture') !== false || strpos($relatedType, 'lec') !== false) {
																			$hasLec = true;
																			$lecClassId = $relatedClass['id'];
																		}
																	}
																	$hasBothLabAndLec = $hasLab && $hasLec;

																	// Store the result for this subject
																	$subjectComponentsCache[$subjectName] = [
																		'has_both' => $hasBothLabAndLec,
																		'has_lab' => $hasLab,
																		'has_lec' => $hasLec,
																		'lec_class_id' => $lecClassId,
																		'lab_class_id' => $labClassId
																	];

																	// If subject has both lab and lecture, fetch grades for both
																	if ($hasBothLabAndLec) {
																		// Fetch lecture grades
																		$lecGradesStmt = $pdo->prepare("
                    SELECT overall_grade
                    FROM student_grades 
                    WHERE class_id = :class_id AND student_id = :student_id
                ");
																		$lecGradesStmt->execute([':class_id' => $lecClassId, ':student_id' => $studentId]);
																		$lecGrades = $lecGradesStmt->fetch(PDO::FETCH_ASSOC);
																		$lecOverallGrade = $lecGrades ? floatval($lecGrades['overall_grade']) : null;

																		// Fetch lab grades
																		$labGradesStmt = $pdo->prepare("
                    SELECT overall_grade
                    FROM student_grades 
                    WHERE class_id = :class_id AND student_id = :student_id
                ");
																		$labGradesStmt->execute([':class_id' => $labClassId, ':student_id' => $studentId]);
																		$labGrades = $labGradesStmt->fetch(PDO::FETCH_ASSOC);
																		$labOverallGrade = $labGrades ? floatval($labGrades['overall_grade']) : null;
																		// Calculate combined overall grade (70% Lec + 30% Lab)
																		$combinedOverallGrade = null;


																		// Calculation (from your last snippet)
																		$allowedGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 'INC', 'N/A', 0];
																		$numericAllowedGrades = array_filter($allowedGrades, 'is_numeric');
																		$combinedOverallGrade = null;

																		if ($lecOverallGrade !== null && $labOverallGrade !== null) {
																			if ($lecOverallGrade == '0' || $labOverallGrade == '0') {
																				$combinedOverallGrade = 'INC';
																			} else {
																				if (is_numeric($lecOverallGrade) && is_numeric($labOverallGrade)) {
																					$combinedOverallGrade = ($lecOverallGrade * 0.7) + ($labOverallGrade * 0.3);
																					$nearestGrade = $numericAllowedGrades[0];
																					$minDifference = abs($combinedOverallGrade - $nearestGrade);
																					foreach ($numericAllowedGrades as $grade) {
																						$difference = abs($combinedOverallGrade - $grade);
																						if ($difference < $minDifference) {
																							$minDifference = $difference;
																							$nearestGrade = $grade;
																						}
																					}
																					$combinedOverallGrade = $nearestGrade;
																				} else {
																					$combinedOverallGrade = 'N/A';
																				}
																			}
																		}

																		// Problematic display (line 1284?)
																		echo '<p><b>Grade:</b> ' . htmlspecialchars($combinedOverallGrade, 2) . '</p>';

																		// Store grades in cache
																		$subjectGradesCache[$subjectName] = [
																			'lec_grade' => $lecOverallGrade,
																			'lab_grade' => $labOverallGrade,
																			'combined_grade' => $combinedOverallGrade
																		];
																	}
																}

																// Fetch grades for the current class
																$stmtGrades = $pdo->prepare("
            SELECT midterm_grade, final_grade, overall_grade
            FROM student_grades 
            WHERE class_id = :class_id AND student_id = :student_id
        ");
																$stmtGrades->execute([':class_id' => $classId, ':student_id' => $studentId]);
																$grades = $stmtGrades->fetch(PDO::FETCH_ASSOC);

																$midtermGrade = $grades ? htmlspecialchars($grades['midterm_grade']) : 'N/A';
																$finalGrade = $grades ? htmlspecialchars($grades['final_grade']) : 'N/A';
																$overallGrade = $grades ? htmlspecialchars($grades['overall_grade']) : 'N/A';

																echo '<tr>';
																echo '<td>' . $subjectName . ' (' . htmlspecialchars($class['type']) . ')</td>';
																echo '<td>' . $className . '</td>';
																echo '<td' . ($midtermGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($midtermGrade) . '</td>';
																echo '<td' . ($finalGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($finalGrade) . '</td>';
																echo '<td' . ($overallGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($overallGrade) . '</td>';

																// Display components based on analysis
																$components = '';
																if ($subjectComponentsCache[$subjectName]['has_both']) {
																	// For classes with both lab and lecture, show combined grade if this is lecture
																	if (strpos($classType, 'lecture') !== false || strpos($classType, 'lec') !== false) {
																		$combinedGrade = $subjectGradesCache[$subjectName]['combined_grade'];
																		$components = $combinedGrade !== null ? (is_numeric($combinedGrade) ? number_format($combinedGrade, 2) : $combinedGrade) : 'N/A';
																	} else {
																		$combinedGrade = $subjectGradesCache[$subjectName]['combined_grade'];
																		$components = $combinedGrade !== null ? (is_numeric($combinedGrade) ? number_format($combinedGrade, 2) : $combinedGrade) : 'N/A';
																	}
																} elseif ($subjectComponentsCache[$subjectName]['has_lec']) {
																	$components = 'Lec';
																} elseif ($subjectComponentsCache[$subjectName]['has_lab']) {
																	$components = 'Lab';
																} else {
																	$components = htmlspecialchars($class['type']); // Fallback to original type
																}

																// Apply number_format if it's a numeric value
																$displayValue = is_numeric($components) ? number_format($components, 2) : htmlspecialchars($components);

																echo '<td' . ($displayValue === 'INC' ? ' style="color: crimson;"' : '') . '>' . $displayValue . '</td>';
																echo '</tr>';
															}

															echo '</tbody>';
															echo '</table>';
															echo '</div>'; // table-responsive
															echo '</div>'; // card-body
														}

														// SECTION 2: Lecture-Only Classes Table
														if (!empty($lecOnlyClasses)) {
															echo '<div class="card-body' . (!empty($regularClasses) ? ' border-top' : '') . '">';
															echo '<h5 class="card-title mb-3">Lecture-Only Classes</h5>';
															echo '<div class="table-responsive text-center">';
															echo '<table class="table table-striped table-hover table-bordered">';
															echo '<thead class="table-secondary">';
															echo '<tr>';
															echo '<th><i class="bi bi-journal"></i> Subject</th>';
															echo '<th><i class="bi bi-building"></i> Class</th>';
															echo '<th><i class="bi bi-star"></i> Midterm Grade</th>';
															echo '<th><i class="bi bi-star-fill"></i> Final Grade</th>';
															echo '<th><i class="bi bi-award"></i> Numerical Grade Rating</th>';
															echo '</tr>';
															echo '</thead>';
															echo '<tbody>';

															foreach ($lecOnlyClasses as $class) {
																$classId = $class['class_id'];
																$subjectName = htmlspecialchars($class['subject_name']);
																$className = htmlspecialchars($class['class_name']);

																// Fetch grades for the class
																$stmtGrades = $pdo->prepare("
                                SELECT midterm_grade, final_grade, overall_grade
                                FROM student_grades 
                                WHERE class_id = :class_id AND student_id = :student_id
                            ");
																$stmtGrades->execute([':class_id' => $classId, ':student_id' => $studentId]);
																$grades = $stmtGrades->fetch(PDO::FETCH_ASSOC);

																$midtermGrade = $grades ? htmlspecialchars($grades['midterm_grade']) : 'N/A';
																$finalGrade = $grades ? htmlspecialchars($grades['final_grade']) : 'N/A';
																$overallGrade = $grades ? htmlspecialchars($grades['overall_grade']) : 'N/A';

																echo '<tr>';
																echo '<td>' . $subjectName . ' (' . htmlspecialchars($class['type']) . ')</td>';
																echo '<td>' . $className . '</td>';
																echo '<td' . ($midtermGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($midtermGrade) . '</td>';
																echo '<td' . ($finalGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($finalGrade) . '</td>';
																echo '<td' . ($overallGrade === 'INC' ? ' style="color: crimson;"' : '') . '>' . htmlspecialchars($overallGrade) . '</td>';
																echo '</tr>';
															}

															echo '</tbody>';
															echo '</table>';
															echo '</div>'; // table-responsive
															echo '</div>'; // card-body
														}
													} else {
														echo '<div class="card-body">';
														echo '<p class="text-muted">No classes found for this semester.</p>';
														echo '</div>';
													}

													echo '</div>'; // card
												}
												echo '</div>'; // container
											} else {
												echo '<div class="alert alert-warning text-center">No semesters found.</div>';
											}
										} else {
											echo '<div class="alert alert-danger">Student not logged in.</div>';
										}
										?>
									</div>





								</div>

							</div>
			</main>


		</div>
	</div>

	<?php

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


	<script src="js/app.js"></script>
	<?php

	?>




	<script>
		function getTime() {
			const now = new Date();
			const newTime = now.toLocaleString();
			console.log(newTime);
			document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
		}
		setInterval(getTime, 100);
	</script>

</html>

<?php
include('processes/server/alerts.php');
?>