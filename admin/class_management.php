<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    $_SESSION['STATUS'] = "ADMIN_NOT_LOGGED_IN";
    header("Location: admin_login_page.php");
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
                                            <span>Class Management</span>
                                        </h5>

                                        <div class="ms-auto" aria-hidden="true">
                                            <img src="external/svgs/undraw_favorite_gb6n.svg"
                                                class=" small-picture img-fluid">
                                        </div>
                                    </div>

                                    <br>

                                    <h5 class="card-title mb-0">
                                        <div class="d-flex align-items-center">
                                            <h3>Class List</h3>
                                            <div class="ms-auto" aria-hidden="true">

                                                <button type="button" class="btn btn-csms dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bi bi-folder2-open"></i> Assign a Subject Class
                                                </button>

                                                <button type="button" class="btn btn-csms" data-bs-toggle="modal"
                                                    data-bs-target="#createClassModal">
                                                    <i class="bi bi-pencil-square"></i> Create a Class
                                                </button>

                                                <!-- Assign Subject Class button with dropdown -->


                                                <!-- Dropdown for Course, Year, and Subject Selection -->
                                                <div class="dropdown-menu" style="padding: 10px;">
                                                    <!-- Course Selection -->
                                                    <small>Available Courses</small>
                                                    <hr>
                                                    <select class="form-select" id="course-select" required>
                                                        <option selected disabled>Select a course</option>
                                                        <option value="BSIT">BSIT</option>
                                                        <option value="BSCS">BSCS</option>
                                                    </select>
                                                    <hr>

                                                    <!-- Year Level Selection -->
                                                    <small>Available Year Levels</small>
                                                    <select class="form-select" id="year-select" required disabled>
                                                        <option selected disabled>Select a year level</option>

                                                        <!-- Year Levels for BSIT -->
                                                        <optgroup id="year-bsit" label="BSIT" style="display:none;">
                                                            <option value="BSIT-1A">1A</option>
                                                            <option value="BSIT-1B">1B</option>
                                                            <option value="BSIT-2A">2A</option>
                                                            <option value="BSIT-2B">2B</option>
                                                            <option value="BSIT-3A">3A</option>
                                                            <option value="BSIT-3B">3B</option>
                                                            <option value="BSIT-4A">4A</option>
                                                            <option value="BSIT-4B">4B</option>
                                                        </optgroup>

                                                        <!-- Year Levels for BSCS -->
                                                        <optgroup id="year-bscs" label="BSCS" style="display:none;">
                                                            <option value="BSCS-1A">1A</option>
                                                            <option value="BSCS-1B">1B</option>
                                                            <option value="BSCS-2A">2A</option>
                                                            <option value="BSCS-2B">2B</option>
                                                            <option value="BSCS-3A">3A</option>
                                                            <option value="BSCS-3B">3B</option>
                                                            <option value="BSCS-4A">4A</option>
                                                            <option value="BSCS-4B">4B</option>
                                                        </optgroup>
                                                    </select>
                                                    <hr>

                                                    <!-- Subject List -->
                                                    <small>Available Subject List</small>
                                                    <select class="form-select" id="subject-select" required>
                                                        <option selected disabled>Select a subject</option>
                                                    </select>
                                                </div>

                                            <!-- Modal to Populate Data -->
<div class="modal fade" id="createClassModalDropdown" tabindex="-1" aria-labelledby="createClassModalDropdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="createClassModalDropdownLabel"><b>Create a Class</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClassForm" action="processes/admin/classes/addDropdown.php" method="POST">
                    <div class="mb-3">
                        <label for="class" class="form-label bold">Selected Class:</label>
                        <input type="text" class="form-control" id="classSelected" name="selectedClassAdd" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="adviser" class="form-label bold">Assigned Adviser:</label>
                        <input type="text" class="form-control" id="assignedAdviserDropdown" name="assignedAdviserDropdown" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="subjectName" class="form-label bold">Subject Name:</label>
                        <input type="text" class="form-control" id="selectedSubjectDropdown" name="subjectNameClassDropdown" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="subjectType" class="form-label bold">Subject Type:</label>
                        <input type="text" class="form-control" id="selectedSubjectType" name="subjectTypeClass" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="teacher" class="form-label bold">Select Teacher:</label>
                        <select class="form-select" name="teacher" required>
                            <?php
                            require 'processes/server/conn.php';
                            $sql = "SELECT id, fullName FROM staff_accounts";
                            $stmt = $pdo->query($sql);
                            if ($stmt->rowCount() > 0) {
                                echo '<option value="" selected>Select teacher below</option>'; // Value is empty to enforce validation
                                while ($teacher = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($teacher["fullName"], ENT_QUOTES, 'UTF-8') . '">' .
                                         htmlspecialchars($teacher["fullName"], ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                            } else {
                                echo '<option value="">There is no staff added yet!</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3" style="display:none">
                        <label for="semester" class="form-label bold">Select Semester:</label>
                        <select class="form-select" name="semester" id="semesterSelect" required>
                            <?php
                            $sql = "SELECT name FROM semester WHERE status = 'active' ORDER BY name";
                            $stmt = $pdo->query($sql);
                            $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if (!empty($semesters)): ?>
                                <?php foreach ($semesters as $semester): ?>
                                    <option selected value="<?php echo htmlspecialchars($semester['name']); ?>">
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No semesters available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="classDesc" class="form-label bold">Class Description:</label>
                        <textarea class="form-control" id="classDesc" name="classDesc" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="saveClassBtn">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 CDN (if not already included in your project) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addClassForm');
    const submitBtn = document.getElementById('saveClassBtn');
    const hasActiveSemester = <?php echo !empty($semesters) ? 'true' : 'false'; ?>;

    if (!hasActiveSemester) {
        submitBtn.disabled = true; // Disable the submit button

        // Show SweetAlert when the modal is opened
        $('#createClassModalDropdown').on('shown.bs.modal', function () {
            Swal.fire({
                icon: 'warning',
                title: 'No Active Semester',
                text: 'There is no active semester available. Please activate a semester before creating a class.',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#createClassModalDropdown').modal('hide'); // Close the modal after confirmation
                }
            });
        });
    }

    // Prevent form submission if no active semester (extra safety)
    form.addEventListener('submit', function (e) {
        if (!hasActiveSemester) {
            e.preventDefault(); // Prevent form submission
        }
    });
});
</script>

                                                <script>
                                                    let selectedCourse = '';
                                                    let selectedYear = '';
                                                    let selectedSubjectDropdown = '';
                                                    let subjectType = '';

                                                    // Update the year levels based on the selected course
                                                    document.getElementById('course-select').addEventListener('change',
                                                        function() {
                                                            selectedCourse = this.value;
                                                            const yearSelect = document.getElementById('year-select');
                                                            const bsitOptions = document.getElementById('year-bsit');
                                                            const bscsOptions = document.getElementById('year-bscs');

                                                            yearSelect.disabled = false;
                                                            bsitOptions.style.display = selectedCourse === 'BSIT' ?
                                                                'block' : 'none';
                                                            bscsOptions.style.display = selectedCourse === 'BSCS' ?
                                                                'block' : 'none';
                                                        });

                                                    // Fetch subjects based on the selected year
                                                    document.getElementById('year-select').addEventListener('change',
                                                        function() {
                                                            selectedYear = this.value;
                                                            const subjectSelect = document.getElementById(
                                                                'subject-select');

                                                            // Fetch available subjects based on the selected class
                                                            fetch(
                                                                    `fetch_subjects.php?class=${encodeURIComponent(selectedYear)}`)
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    subjectSelect.innerHTML =
                                                                        '<option selected disabled>Select a subject</option>';

                                                                    if (data.length > 0) {
                                                                        data.forEach(subject => {
                                                                            const option = document
                                                                                .createElement('option');
                                                                            option.value = subject.id;
                                                                            option.textContent =
                                                                                `${subject.name} (${subject.code}) [${subject.type}]`;
                                                                            subjectSelect.appendChild(
                                                                                option);
                                                                        });
                                                                    } else {
                                                                        const option = document.createElement(
                                                                            'option');
                                                                        option.textContent =
                                                                            'No subjects available for this year level';
                                                                        option.disabled = true;
                                                                        subjectSelect.appendChild(option);
                                                                    }
                                                                });
                                                        });

                                                    // Open modal automatically after selecting a subject
                                                    document.getElementById('subject-select').addEventListener('change',
                                                        function() {
                                                            const selectedSubject = this.options[this.selectedIndex]
                                                                .text; // Get selected subject text
                                                            const selectedSubjectId = this
                                                                .value; // Get the selected subject's ID

                                                            console.log(selectedSubjectId);

                                                            // Ensure selectedYear and selectedCourse are available and concatenate them
                                                            const selectedClass = `${selectedYear}`;

                                                            console.log(selectedClass);


                                                            // Fetch subject type from the selected subject
                                                            fetch(
                                                                    `getSubjectType.php?subjectId=${encodeURIComponent(selectedSubjectId)}`)
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    const subjectType = data
                                                                        .type; // Default to 'Lecture' if no subject type is returned

                                                                    // Pre-populate modal fields with selected values
                                                                    document.getElementById('classSelected').value =
                                                                        `${selectedYear}`;
                                                                    document.getElementById(
                                                                            'selectedSubjectDropdown').value =
                                                                        selectedSubject;
                                                                    document.getElementById('selectedSubjectType')
                                                                        .value = subjectType;

                                                                    console.log(subjectType);

                                                                    // Fetch assigned adviser for the selected class (using GET)
                                                                    fetch(
                                                                            `getAdviser.php?class=${encodeURIComponent(selectedClass)}`)

                                                                        .then(response => response.json())
                                                                        .then(data => {
                                                                            const adviserField = document
                                                                                .getElementById(
                                                                                    'assignedAdviserDropdown');
                                                                            if (data.fullName) {
                                                                                adviserField.textContent = data
                                                                                    .fullName;
                                                                                adviserField.value = data
                                                                                    .fullName;
                                                                            } else {
                                                                                adviserField.textContent =
                                                                                    'No adviser assigned';
                                                                                adviserField.value = "";
                                                                            }
                                                                        })
                                                                        .catch(error => console.error(
                                                                            'Error fetching adviser:', error));

                                                                    // Show modal
                                                                    new bootstrap.Modal(document.getElementById(
                                                                        'createClassModalDropdown')).show();
                                                                })
                                                                .catch(error => {
                                                                    console.error('Error fetching subject type:',
                                                                        error);

                                                                });
                                                        });


                                                    // Open modal automatically after selecting a subject
                                                    document.getElementById('subject-select').addEventListener('change',
                                                        function() {
                                                            const selectedSubject = this.options[this.selectedIndex]
                                                                .text; // Get selected subject text
                                                            const selectedSubjectId = this
                                                                .value; // Get the selected subject's ID


                                                            // Ensure selectedYear and selectedCourse are available and concatenate them
                                                            const selectedClass = `${selectedYear}`;


                                                            // Fetch subject type from the selected subject using getSubjectType.php
                                                            fetch(
                                                                    `getSubjectType.php?subjectId=${encodeURIComponent(selectedSubjectId)}`)
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    const subjectType = data.type ||
                                                                        'Lecture'; // Default to 'Lecture' if no subject type is returned

                                                                    // Pre-populate modal fields with selected values
                                                                    document.getElementById('classSelected').value =
                                                                        `${selectedYear}`;
                                                                    document.getElementById('selectedSubject')
                                                                        .value = selectedSubject;
                                                                    document.getElementById('selectedSubjectType')
                                                                        .value = subjectType; // Use subject type

                                                                    // Fetch assigned adviser for the selected class (using GET)
                                                                    fetch(
                                                                            `getAdviser.php?class=${encodeURIComponent(selectedClass)}`)
                                                                        .then(response => response.json())
                                                                        .then(data => {
                                                                            const adviserField = document
                                                                                .getElementById(
                                                                                    'assignedAdviserDropdown');

                                                                            console.log(selectedClass);

                                                                            // Handle the case when there is no adviser assigned
                                                                            if (data.fullName) {
                                                                                adviserField.textContent = data
                                                                                    .fullName;
                                                                                adviserField.value = data
                                                                                    .fullName;
                                                                            } else if (data.error) {
                                                                                adviserField.textContent =
                                                                                    'Error: ' + data.error;
                                                                                adviserField.value = "";
                                                                            } else {
                                                                                adviserField.textContent =
                                                                                    'No adviser assigned';
                                                                                adviserField.value = "";
                                                                            }
                                                                        })
                                                                        .catch(error => console.error(
                                                                            'Error fetching adviser:', error));

                                                                    // Show modal
                                                                    new bootstrap.Modal(document.getElementById(
                                                                        'createClassModalDropdown')).show();

                                                                })
                                                                .catch(error => {
                                                                    console.error('Error fetching subject type:',
                                                                        error);

                                                                });
                                                        });
                                                </script>


                                            </div>


                                        </div>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    require 'processes/server/conn.php';


                                    try {
                                        $stmt = $pdo->query("SELECT * FROM classes
WHERE status IS NOT NULL
ORDER BY CASE 
    WHEN status = 'pending' THEN 1
    WHEN status = 'accepted' THEN 2
    WHEN status = 'rejected' THEN 3
    ELSE 4
END;
");





                                        if ($stmt->rowCount() > 0) {

                                    ?>
                                            <!-- Wrap the table in a responsive container -->
                                            <div class="table-responsive">
                                                <table id="classes" class="table" style="width: 100%;">
                                                    <thead class="text-center">
                                                        <tr>
                                                            <th>Class Name</th>
                                                            <th>Adviser</th>
                                                            <th>Subject</th>
                                                            <th>Teacher</th>
                                                            <th>Semester</th>
                                                            <th>No. of Students</th>
                                                            <th>Archive Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tfoot class="text-center">
                                                        <tr>
                                                            <th>Class Name</th>
                                                            <th>Adviser</th>
                                                            <th>Subject</th>
                                                            <th>Teacher</th>
                                                            <th>Semester</th>
                                                            <th>No. of Students</th>
                                                            <th>Archive Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </tfoot>
                                                    <tbody>
                                                        <?php
                                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                            // Fetch meeting schedule details
                                                            $scheduleStmt = $pdo->prepare("SELECT meeting_days, start_time, end_time FROM subjects_schedules WHERE subject_id = :subject_id");
                                                            $scheduleStmt->bindParam(':subject_id', $row['subject_id'], PDO::PARAM_INT);
                                                            $scheduleStmt->execute();
                                                            $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

                                                            // Format schedule information
                                                            $scheduleDetails = [];
                                                            foreach ($schedules as $schedule) {
                                                                $formattedTime = date("h:i A", strtotime($schedule['start_time'])) . " - " . date("h:i A", strtotime($schedule['end_time']));
                                                                $scheduleDetails[] = "<strong>" . htmlspecialchars($schedule['meeting_days']) . "</strong>: " . $formattedTime;
                                                            }
                                                            $scheduleInfo = implode("<br>", $scheduleDetails);

                                                            // Fetch adviser
                                                            $query1 = "SELECT fullName FROM staff_advising WHERE class_advising = :class";
                                                            $stmt1 = $pdo->prepare($query1);
                                                            $stmt1->bindParam(':class', $row['name'], PDO::PARAM_STR);
                                                            $stmt1->execute();
                                                            $adviser = $stmt1->fetchColumn();
                                                            if ($adviser === false) {
                                                                $adviser = "Unknown";
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($adviser); ?></td>
                                                                <td>
                                                                    <span class="alert alert-primary" style="padding:2px"><?php echo htmlspecialchars($row['type']); ?></span><br>
                                                                    <?php echo htmlspecialchars($row['code']); ?><br>
                                                                    <?php echo htmlspecialchars($row['subject']); ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($row['teacher']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                                                <td>
                                                                    <a href="#" style="color:black !important" data-bs-toggle="modal" data-bs-target="#studentModal<?php echo $row['id'] ?>">
                                                                        <?php echo htmlspecialchars($row['studentTotal']); ?>
                                                                    </a>
                                                                </td>
                                                                <td><?php echo $row['is_archived'] == 0 ? 'Not Archived' : 'Archived'; ?></td>
                                                                <td>
                                                                    <?php if ($row['is_archived'] == 1): ?>
                                                                        <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#viewModal<?php echo $row['id']; ?>'>
                                                                            <i class='bi bi-eye'></i> View
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <?php if ($row['status'] == 'pending'): ?>
                                                                            <span><b>This class is still pending for approval:</b></span><br><br>
                                                                            <span>Request made by: <b><?php echo $row['requestor']; ?></b></span><br><br>
                                                                            <a href="processes/admin/classes/accept.php?id=<?php echo $row['id'] ?>&subject=<?php echo $row['subject'] ?>&requestor=<?php echo $row['requestor'] ?>&class=<?php echo $row['name'] ?>">
                                                                                <button class='btn btn-success'><small><i class='bi bi-check2-square'></i> Approve</small></button>
                                                                            </a>
                                                                            <a data-bs-toggle="modal" data-bs-target="#disapproveModal<?php echo $row['id']; ?>">
                                                                                <button class='btn btn-danger'><small><i class='bi bi-x-circle-fill'></i> Disapprove</small></button>
                                                                            </a>
                                                                        <?php elseif ($row['status'] == 'disapproved'): ?>
                                                                            <span>This class has been rejected which was requested by: <em><?php echo $row['requestor']; ?></em><br><br>
                                                                                <b>Reason:</b><br><?php echo $row['reason']; ?></span><br><br>
                                                                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                                                                <i class="bi bi-trash"></i> Delete
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#viewModal<?php echo $row['id']; ?>'>
                                                                                <i class='bi bi-eye'></i> View
                                                                            </button>
                                                                            <button type='button' class='btn btn-warning' data-bs-toggle='modal' data-bs-target='#editModal<?php echo $row['id']; ?>'>
                                                                                <i class='bi bi-pencil'></i> Edit
                                                                            </button>
                                                                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                                                                <i class="bi bi-trash"></i> Delete
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>

                                                            <!-- Disapprove Modal -->
                                                            <div class="modal fade" id="disapproveModal<?php echo $row['id'] ?>" tabindex="-1" aria-labelledby="disapproveModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="disapproveModalLabel">Disapprove Class</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <form id="disapproveForm" action="processes/admin/classes/reject.php?id=<?php echo $row['id'] ?>&subject=<?php echo $row['subject'] ?>&requestor=<?php echo $row['requestor'] ?>&class=<?php echo $row['name'] ?>" method="POST">
                                                                                <div class="mb-3">
                                                                                    <label for="disapproveReason" class="form-label fw-bold">Select a Reason</label>
                                                                                    <select class="form-control" id="disapproveReasonSelect<?php echo $row['id']; ?>" name="reason" required>
                                                                                        <option value="" disabled selected>Select a reason</option>
                                                                                        <option value="Incomplete information">Incomplete information</option>
                                                                                        <option value="Class does not meet criteria">Class does not meet criteria</option>
                                                                                        <option value="Duplicate class">Duplicate class</option>
                                                                                        <option value="Other">Other</option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="mb-3" id="otherReasonDiv<?php echo $row['id']; ?>" style="display: none;">
                                                                                    <label for="otherReason" class="form-label fw-bold">Other Reason</label>
                                                                                    <textarea class="form-control" id="otherReason<?php echo $row['id']; ?>" name="other_reason" rows="3"></textarea>
                                                                                </div>
                                                                                <button type="submit" class="btn btn-danger">Submit Disapproval</button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- JavaScript for each modal -->
                                                            <script>
                                                                document.getElementById('disapproveReasonSelect<?php echo $row['id']; ?>').addEventListener('change', function() {
                                                                    var otherReasonDiv = document.getElementById('otherReasonDiv<?php echo $row['id']; ?>');
                                                                    var otherReasonInput = document.getElementById('otherReason<?php echo $row['id']; ?>');
                                                                    if (this.value === 'Other') {
                                                                        otherReasonDiv.style.display = 'block';
                                                                        otherReasonInput.setAttribute('required', 'required');
                                                                    } else {
                                                                        otherReasonDiv.style.display = 'none';
                                                                        otherReasonInput.removeAttribute('required');
                                                                    }
                                                                });
                                                            </script>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Additional CSS for Responsiveness -->
                                            <style>
                                                @media (max-width: 768px) {
                                                    .table-responsive {
                                                        overflow-x: auto;
                                                    }

                                                    .table th,
                                                    .table td {
                                                        font-size: 14px;
                                                        /* Reduce font size on smaller screens */
                                                        padding: 8px;
                                                        /* Reduce padding */
                                                    }

                                                    .btn {
                                                        font-size: 12px;
                                                        /* Smaller buttons */
                                                        padding: 4px 8px;
                                                    }

                                                    .alert {
                                                        font-size: 12px;
                                                        padding: 2px 4px;
                                                    }
                                                }

                                                @media (max-width: 576px) {

                                                    .table th,
                                                    .table td {
                                                        font-size: 12px;
                                                        padding: 6px;
                                                    }

                                                    .btn {
                                                        display: block;
                                                        /* Stack buttons vertically */
                                                        width: 100%;
                                                        margin-bottom: 5px;
                                                    }
                                                }
                                            </style>

                                    <?php
                                        } else {
                                            echo "<h1 class='text-center'>No classes added, yet.</h1>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<p class='text-center'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    </main>


    </div>
    </div>

    <?php
    try {
        $stmt = $pdo->query("SELECT * FROM classes");
        $staffStmt = $pdo->query("SELECT * FROM staff_accounts");
        $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $classId = htmlspecialchars($row['id']);
            $className = htmlspecialchars($row['name']);
            $classType = htmlspecialchars($row['type']);
            $subject = htmlspecialchars($row['subject']);
            $teacher = htmlspecialchars($row['teacher']);
            $semester = htmlspecialchars($row['semester']);
            $description = htmlspecialchars($row['description']);
            $classCode = htmlspecialchars($row['classCode']);
            $dateTimeAdded = htmlspecialchars(date('Y-m-d h:i A', strtotime($row['datetime_added'])));
            $grade_checker_status = $row['grade_checker'];
            $status = $row['status'];



    ?>

            <!-- View Modal -->





            <!-- Student Modal -->
            <div class="modal fade" id="studentModal<?php echo $classId; ?>" tabindex="-1" aria-labelledby="studentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="studentModalLabel">Students in Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php
                            // Query students for the specific class
                            $studentStmt = $pdo->prepare("
                            SELECT students.student_id, students.first_name, students.middle_name, students.last_name, students.gender, students.course, students.year_level
                            FROM students_enrollments
                            JOIN students ON students_enrollments.student_id = students.student_id
                            WHERE students_enrollments.class_id = :class_id
                        ");
                            $studentStmt->execute(['class_id' => $row['id']]);
                            $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($students)) {
                                // Group students by gender
                                $groupedStudents = [
                                    'Male' => [],
                                    'Female' => []
                                ];

                                foreach ($students as $student) {
                                    $gender = htmlspecialchars($student['gender']);
                                    if (isset($groupedStudents[$gender])) {
                                        $groupedStudents[$gender][] = $student;
                                    }
                                }

                                // Display students grouped by gender
                                foreach ($groupedStudents as $gender => $studentsInGender) {
                                    if (!empty($studentsInGender)) {
                                        echo '<h5>' . htmlspecialchars($gender) . ' Students</h5>';
                                        echo '<ul class="list-group mb-3">';
                                        foreach ($studentsInGender as $student) {
                                            $fullName = $student['first_name'] . " " . $student['middle_name'] . " " . $student['last_name'];
                                            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                            echo htmlspecialchars($fullName) . ' (' .
                                                htmlspecialchars($student['course']) . ', ' .
                                                htmlspecialchars($student['year_level']) . ')';
                                            echo '</li>';
                                        }
                                        echo '</ul>';
                                    }
                                }
                            } else {
                                echo '<div class="alert alert-info" role="alert">No students are enrolled in this class.</div>';
                            }
                            ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Script -->
            <script>
                function copyText(classCode) {
                    const textElement = document.getElementById("text-to-copy-" + classCode);
                    navigator.clipboard.writeText(textElement.value).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Copied!',
                            text: 'You have successfully copied the class code.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }).catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Failed to copy the class code.'
                        });
                    });
                }
            </script>
    <?php
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>







    <?php
    $stmt = $pdo->query("SELECT * FROM classes");
    $staffStmt = $pdo->query("SELECT * FROM staff_accounts");
    $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    $staffQuery = "SELECT id, fullName FROM staff_accounts";
    $staffStmt = $pdo->query($staffQuery);
    $staffMembers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $classId = htmlspecialchars($row['id']);
        $className = htmlspecialchars($row['name']);
        $classType = htmlspecialchars($row['type']);
        $subject = htmlspecialchars($row['subject']);
        $teacher = htmlspecialchars($row['teacher']);
        $semester = htmlspecialchars($row['semester']);
        $description = htmlspecialchars($row['description']);
        $classCode = htmlspecialchars($row['classCode']);
        $dateTimeAdded = htmlspecialchars(date('Y-m-d h:i A', strtotime($row['datetime_added'])));
        $grade_checker_status = $row['grade_checker'];
        $subject_id = $row['subject_id'];
    ?>



        <div class="modal fade" id="viewModal<?php echo $classId; ?>" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                            <b>Viewing Class Content for <?php echo $className; ?></b>
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <!-- Display class details -->
                        <div class="mb-3">
                            <label class="form-label bold">Class:</label>
                            <input type="text" class="form-control" value="<?php echo $className; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Class Adviser:</label>
                            <input type="text" class="form-control" value="<?php echo $adviser; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Subject Type:</label>
                            <input type="text" class="form-control" value="<?php echo $classType; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Subject Name:</label>
                            <input type="text" class="form-control" value="<?php echo $subject; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Teacher:</label>
                            <input type="text" class="form-control" value="<?php echo $teacher; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Semester:</label>
                            <input type="text" class="form-control" value="<?php echo $semester; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Class Description:</label>
                            <textarea class="form-control" readonly><?php echo $description; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Class Code:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="text-to-copy-<?php echo $classCode; ?>"
                                    value="<?php echo $classCode; ?>" readonly>
                                <button class="btn btn-primary copy-btn" onclick="copyText('<?php echo $classCode; ?>')">
                                    <i class="bi bi-clipboard-fill"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label bold">Date and Time Added:</label>
                            <input type="text" class="form-control" value="<?php echo $dateTimeAdded; ?>" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">

                        <?php
                        // Assume the grade_checker is fetched from the database and is assigned to a variable
                        $gradeChecker = $grade_checker_status;  // Example value, replace it with actual value from database query

                        // Decide the appropriate link based on the class type
                        if ($classType == 'Laboratory') {
                            // Check if the status is 'archived'
                            if ($status == 'archived') {
                                $href = 'archived_lab_grades.php?id=' . $classId . '&subject_id=' . $subject_id;  // Link for Laboratory classes
                            } else {
                                $href = 'lab_grades.php?id=' . $classId . '&subject_id=' . $subject_id;  // Link for Laboratory classes
                            }
                        } elseif ($classType == 'Lecture') {
                            // Check if the status is 'archived'
                            if ($status == 'archived') {
                                $href = 'archived_lecture_grades.php?id=' . $classId . '&subject_id=' . $subject_id;  // Link for Lecture classes
                            } else {
                                $href = 'lecture_grades.php?id=' . $classId . '&subject_id=' . $subject_id;  // Link for Lecture classes
                            }
                        } else {
                            $href = '#';  // Default or fallback link if the class type is unexpected
                        }


                        // Check if grade_checker is 'not_available' or 'available'
                        $isGradeAvailable = ($gradeChecker == 'available');
                        ?>

                        <!-- View Grades Button -->

                        <button type="button" class="btn btn-success" <?php echo $isGradeAvailable ? '' : 'disabled'; ?>>
                            <a href="<?php echo $href; ?>" style="color:white; text-decoration:none">View Grades </a>
                        </button>


                        <!-- Close Button -->
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal<?php echo $classId; ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Edit Class Details</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClassForm<?php echo $classId; ?>" action="processes/admin/classes/update.php?id=<?php echo $classId ?>" method="POST">
                    <!-- Select Class -->
                    <div class="mb-3">
                        <label for="class" class="form-label bold">Select Class:</label>
                        <select required class="form-select" name="selectedClassAdd" id="classSelect<?php echo $classId; ?>" required>
                            <option selected disabled>Select a class</option>
                            <optgroup label="Department of Information Technology">
                                <option value="BSIT-1A" <?php echo ($className == 'BSIT-1A') ? 'selected' : ''; ?>>BSIT - 1A</option>
                                <option value="BSIT-1B" <?php echo ($className == 'BSIT-1B') ? 'selected' : ''; ?>>BSIT - 1B</option>
                                <option value="BSIT-2A" <?php echo ($className == 'BSIT-2A') ? 'selected' : ''; ?>>BSIT - 2A</option>
                                <option value="BSIT-2B" <?php echo ($className == 'BSIT-2B') ? 'selected' : ''; ?>>BSIT - 2B</option>
                                <option value="BSIT-3A" <?php echo ($className == 'BSIT-3A') ? 'selected' : ''; ?>>BSIT - 3A</option>
                                <option value="BSIT-3B" <?php echo ($className == 'BSIT-3B') ? 'selected' : ''; ?>>BSIT - 3B</option>
                                <option value="BSIT-4A" <?php echo ($className == 'BSIT-4A') ? 'selected' : ''; ?>>BSIT - 4A</option>
                                <option value="BSIT-4B" <?php echo ($className == 'BSIT-4B') ? 'selected' : ''; ?>>BSIT - 4B</option>
                            </optgroup>
                            <optgroup label="Department of Computer Science">
                                <option value="BSCS-1A" <?php echo ($className == 'BSCS-1A') ? 'selected' : ''; ?>>BSCS - 1A</option>
                                <option value="BSCS-1B" <?php echo ($className == 'BSCS-1B') ? 'selected' : ''; ?>>BSCS - 1B</option>
                                <option value="BSCS-2A" <?php echo ($className == 'BSCS-2A') ? 'selected' : ''; ?>>BSCS - 2A</option>
                                <option value="BSCS-2B" <?php echo ($className == 'BSCS-2B') ? 'selected' : ''; ?>>BSCS - 2B</option>
                                <option value="BSCS-3A" <?php echo ($className == 'BSCS-3A') ? 'selected' : ''; ?>>BSCS - 3A</option>
                                <option value="BSCS-3B" <?php echo ($className == 'BSCS-3B') ? 'selected' : ''; ?>>BSCS - 3B</option>
                                <option value="BSCS-4A" <?php echo ($className == 'BSCS-4A') ? 'selected' : ''; ?>>BSCS - 4A</option>
                                <option value="BSCS-4B" <?php echo ($className == 'BSCS-4B') ? 'selected' : ''; ?>>BSCS - 4B</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Assigned Adviser -->
                    <div class="mb-3">
                        <label for="adviser" class="form-label bold">Assigned Adviser:</label>
                        <input type="text" class="form-control" id="assignedAdviser<?php echo $classId; ?>" name="assignedAdviser" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="editSubjectName<?php echo $row['id']; ?>" class="form-label fw-bold">Subject Name</label>
                        <input readonly type="text" class="form-control" id="editSubjectName<?php echo $row['id']; ?>" name="subjectNameClass" value="<?php echo htmlspecialchars($row['subject']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="editSubjectCode<?php echo $row['id']; ?>" class="form-label fw-bold">Subject Code</label>
                        <input readonly type="text" class="form-control" id="editSubjectCode<?php echo $row['id']; ?>" name="code" value="<?php echo htmlspecialchars($row['code']); ?>" required>
                    </div>

                    <style>
                        .form-select[readonly] {
                            background-color: #f8f9fa;
                            pointer-events: none; /* Allows look but prevents click */
                        }
                    </style>

                    <div class="mb-3">
                        <label for="type" class="form-label fw-bold">Subject Type</label>
                        <select class="form-select" name="type" id="type" required readonly>
                            <option value="" disabled>Select class type below</option>
                            <option value="Lecture" <?php echo ($row['type'] === 'Lecture') ? 'selected' : ''; ?>>Lecture</option>
                            <option value="Laboratory" <?php echo ($row['type'] === 'Laboratory') ? 'selected' : ''; ?>>Laboratory</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editTeacher<?php echo $row['id']; ?>" class="form-label fw-bold">Select Teacher</label>
                        <select class="form-select" id="editTeacher<?php echo $row['id']; ?>" name="teacher">
                            <?php
                            foreach ($staffMembers as $staff) {
                                $selected = ($row['teacher'] == $staff['fullName']) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($staff['fullName']) . "\" $selected>" . htmlspecialchars($staff['fullName']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3" style="display: none;">
                        <label for="editSemester<?php echo $row['id']; ?>" class="form-label fw-bold">Select Semester</label>
                        <select class="form-select" id="editSemester<?php echo $row['id']; ?>" name="semester">
                            <?php if (!empty($semesters)): ?>
                                <?php foreach ($semesters as $semester): ?>
                                    <option selected value="<?php echo htmlspecialchars($semester['name']); ?>" <?php echo ($row['semester'] === $semester['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No semesters available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Class Description -->
                    <div class="mb-3">
                        <label for="classDesc" class="form-label bold">Class Description:</label>
                        <textarea class="form-control" id="classDesc" name="classDesc" required><?php echo htmlspecialchars($row['description']); ?></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="saveChangesBtn<?php echo $classId; ?>">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 CDN (if not already included in your project) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('addClassForm<?php echo $classId; ?>');
    const submitBtn = document.getElementById('saveChangesBtn<?php echo $classId; ?>');
    const hasActiveSemester = <?php echo !empty($semesters) ? 'true' : 'false'; ?>;

    if (!hasActiveSemester) {
        submitBtn.disabled = true; // Disable the submit button

        // Show SweetAlert when the modal is opened
        $('#editModal<?php echo $classId; ?>').on('shown.bs.modal', function () {
            Swal.fire({
                icon: 'warning',
                title: 'No Active Semester',
                text: 'There is no active semester available. Please activate a semester before editing class details.',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#editModal<?php echo $classId; ?>').modal('hide'); // Close the modal after confirmation
                }
            });
        });
    }

    // Prevent form submission if no active semester (extra safety)
    form.addEventListener('submit', function (e) {
        if (!hasActiveSemester) {
            e.preventDefault(); // Prevent form submission
        }
    });

    // Existing script for fetching adviser
    const classSelect = document.getElementById('classSelect<?php echo $classId; ?>');
    const adviserField = document.getElementById('assignedAdviser<?php echo $classId; ?>');

    function fetchAdviser(selectedClass) {
        if (!selectedClass) return;

        fetch('getAdviserPost.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'class=' + encodeURIComponent(selectedClass)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.fullName) {
                adviserField.textContent = data.fullName;
                adviserField.value = data.fullName;
            } else {
                adviserField.textContent = 'No adviser assigned';
                adviserField.value = "";
            }
        })
        .catch(error => console.error('Error fetching adviser:', error));
    }

    // Preload adviser on page load (if a class is already selected)
    if (classSelect) {
        const initialClass = classSelect.value; // Get default selected class
        fetchAdviser(initialClass); // Fetch adviser for preloaded class

        // Update adviser when user selects a new class
        classSelect.addEventListener('change', function() {
            fetchAdviser(this.value);
        });
    }
});
</script>

    <?php } ?>

















    <script src="js/app.js"></script>
    <?php
    include('processes/server/modals.php');
    ?>

<div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Create a Class</b></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClassForm" action="processes/admin/classes/add.php" method="POST">
                    <div class="mb-3">
                        <label for="class" class="form-label bold">Select Class:</label>
                        <select class="form-select" name="selectedClassAdd" id="classSelectorModal" required>
                            <option selected disabled>Select a class</option>
                            <optgroup label="Department of Information Technology">
                                <option value="BSIT-1A">BSIT - 1A</option>
                                <option value="BSIT-1B">BSIT - 1B</option>
                                <option value="BSIT-2A">BSIT - 2A</option>
                                <option value="BSIT-2B">BSIT - 2B</option>
                                <option value="BSIT-3A">BSIT - 3A</option>
                                <option value="BSIT-3B">BSIT - 3B</option>
                                <option value="BSIT-4A">BSIT - 4A</option>
                                <option value="BSIT-4B">BSIT - 4B</option>
                            </optgroup>
                            <optgroup label="Department of Computer Science">
                                <option value="BSCS-1A">BSCS - 1A</option>
                                <option value="BSCS-1B">BSCS - 1B</option>
                                <option value="BSCS-2A">BSCS - 2A</option>
                                <option value="BSCS-2B">BSCS - 2B</option>
                                <option value="BSCS-3A">BSCS - 3A</option>
                                <option value="BSCS-3B">BSCS - 3B</option>
                                <option value="BSCS-4A">BSCS - 4A</option>
                                <option value="BSCS-4B">BSCS - 4B</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="adviser" class="form-label bold">Assigned Adviser:</label>
                        <input type="text" class="form-control" id="assignedAdviserModal" name="assignedAdviser" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="subjectName" class="form-label bold">Select Subject Name: </label>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT id, name, semester, type FROM subjects ORDER BY name");
                            $stmt->execute();
                            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            echo "Error: " . $e->getMessage();
                        }
                        ?>
                        <select class="form-select" id="subjectName" name="subjectNameClass" required>
                            <?php if (!empty($subjects)): ?>
                                <option value="" selected>Select a subject</option>
                                <?php
                                // Filter unique subject names
                                $uniqueSubjects = [];
                                foreach ($subjects as $row) {
                                    if (!in_array($row['name'], $uniqueSubjects)) {
                                        $uniqueSubjects[] = $row['name'];
                                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                }
                                ?>
                            <?php else: ?>
                                <option value="" selected>No subjects added yet</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subjectType" class="form-label bold">Subject Type: </label>
                        <select class="form-select" id="subjectType" name="subjectTypeClass" required>
                            <option value="" selected>Select a type</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="teacher" class="form-label bold">Select Teacher: </label>
                        <select class="form-select" name="teacher" required>
                            <?php
                            require 'processes/server/conn.php';
                            $sql = "SELECT id, fullName FROM staff_accounts";
                            $stmt = $pdo->query($sql);
                            if ($stmt->rowCount() > 0) {
                                echo '<option value="" selected>Select teacher below</option>'; // Value is empty to enforce validation
                                while ($teacher = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($teacher["fullName"], ENT_QUOTES, 'UTF-8') . '">' .
                                         htmlspecialchars($teacher["fullName"], ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                            } else {
                                echo '<option value="">There is no staff added yet!</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3" style="display:none">
                        <label for="semester" class="form-label bold">Select Semester:</label>
                        <select class="form-select" name="semester" id="semesterSelect" required>
                            <?php
                            $sql = "SELECT name FROM semester WHERE status = 'active' ORDER BY name";
                            $stmt = $pdo->query($sql);
                            $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if (!empty($semesters)): ?>
                                <?php foreach ($semesters as $semester): ?>
                                    <option selected value="<?php echo htmlspecialchars($semester['name']); ?>">
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No semesters available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="classDesc" class="form-label bold">Class Description:</label>
                        <textarea class="form-control" id="classDesc" name="classDesc" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="saveClassBtn">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 CDN (if not already included in your project) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addClassForm');
    const submitBtn = document.getElementById('saveClassBtn');
    const hasActiveSemester = <?php echo !empty($semesters) ? 'true' : 'false'; ?>;

    if (!hasActiveSemester) {
        submitBtn.disabled = true; // Disable the submit button

        // Show SweetAlert when the modal is opened
        $('#createClassModal').on('shown.bs.modal', function () {
            Swal.fire({
                icon: 'warning',
                title: 'No Active Semester',
                text: 'There is no active semester available. Please activate a semester before creating a class.',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#createClassModal').modal('hide'); // Close the modal after confirmation
                }
            });
        });
    }

    // Prevent form submission if no active semester (extra safety)
    form.addEventListener('submit', function (e) {
        if (!hasActiveSemester) {
            e.preventDefault(); // Prevent form submission
        }
    });
});

// Existing script for subject type fetching
document.getElementById('subjectName').addEventListener('change', function() {
    const subjectName = this.value;
    const subjectTypeSelect = document.getElementById('subjectType');
    subjectTypeSelect.innerHTML = '<option value="" selected>Select a type</option>'; // Reset options

    if (subjectName) {
        // Fetch types dynamically
        const url = `get_subject_type.php?subjectName=${encodeURIComponent(subjectName)}`;

        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.types && data.types.length > 0) {
                // Populate select with the types
                data.types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    subjectTypeSelect.appendChild(option);
                });
            } else {
                // Handle case where no types are found
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No types found';
                subjectTypeSelect.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching the subject types. Please try again.');
        });
    }
});
</script>

    <script>
        document.getElementById('classSelectorModal').addEventListener('change', function() {
            const selectedClass = this.value;
            fetch('getAdviserPost.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'class=' + encodeURIComponent(selectedClass)
                })
                .then(response => response.json())
                .then(data => {
                    const adviserField = document.getElementById('assignedAdviserModal');
                    if (data.fullName) {
                        adviserField.textContent = data.fullName;
                        adviserField.value = data.fullName;
                    } else {
                        adviserField.textContent = 'No adviser assigned';
                        adviserField.value = "";
                    }
                })
                .catch(error => console.error('Error fetching adviser:', error));
        });
    </script>



    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();

            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
        $(document).ready(function() {
            // Custom sorting for the 'status' column
            $.fn.dataTable.ext.type.order['status-pre'] = function(data) {
                if (data === 'pending') {
                    return 1;
                } else if (data === 'accepted') {
                    return 2;
                } else if (data === 'rejected') {
                    return 3;
                }
                return 4;
            };

            // Initialize DataTable
            var table = $('#classes').DataTable({
                responsive: true,
                columnDefs: [{
                    type: 'status',
                    targets: 4
                }],
                order: [
                    [4, 'desc']
                ]
            });

            // Add dropdown for "Class Name" and "Semester" columns
            $('#classes tfoot th').each(function(index) {
                var title = $(this).text();

                if (title === 'Class Name') {
                    // Create dropdown for Class Name
                    $(this).html(`
                <select class="class-name-selector" >
                    <option value="">All Classes</option>
                    <option value="BSIT-1A">BSIT-1A</option>
                    <option value="BSIT-1B">BSIT-1B</option>
                    <option value="BSIT-2A">BSIT-2A</option>
                    <option value="BSIT-2B">BSIT-2B</option>
                    <option value="BSIT-3A">BSIT-3A</option>
                    <option value="BSIT-3B">BSIT-3B</option>
                    <option value="BSIT-4A">BSIT-4A</option>
                    <option value="BSCS-1A">BSCS-1A</option>
                    <option value="BSCS-2A">BSCS-2A</option>
                    <option value="BSCS-3A">BSCS-3A</option>
                    <option value="BSCS-4A">BSCS-4A</option>
                </select>
            `);
                } else if (title === 'Semester') {
                    // Create dropdown for Semester
                    $(this).html(`
                <select class="semester-selector">
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
            `);
                } else {
                    // For other columns, add text input for search
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            // Implement search functionality for text inputs and dropdowns
            table.columns().every(function() {
                var that = this;

                // Search for text input
                $('input', this.footer()).on('keyup change clear', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });

                // Search for Class Name dropdown
                $('select.class-name-selector', this.footer()).on('change', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });

                // Search for Semester dropdown
                $('select.semester-selector', this.footer()).on('change', function() {
                    if (that.search() !== this.value) {
                        that.search(this.value).draw();
                    }
                });
            });

            // Adjust the table and recalculate responsiveness
            table.columns.adjust().responsive.recalc();
        });


        document.getElementById('messageForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var messageText = document.getElementById('messageInput').value;


            if (messageText.trim() !== '') {
                var chatBody = document.getElementById('chatBody');


                var newMessage = document.createElement('div');
                newMessage.className = 'row receiver';
                newMessage.innerHTML = `
      
            <div class="col">
              <div class="message">
                 <span>${messageText}</span>
              </div>
              <i class="bi bi-person"></i>
            </div>
      `;
                chatBody.appendChild(newMessage);
                document.getElementById('messageInput').value = '';
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        });
    </script>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'processes/admin/classes/delete.php';
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'id';
                    input.value = id;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
    <!-- Include SweetAlert CDN in your HTML head section -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!--      -->

</html>

<?php
include('processes/server/alerts.php');
?>