<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
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
    <title>ADDU - CCS | Student Management System</title>
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
        border: 1px solid black
    }

    .btn-csms {
        background-color: #709775;
        color: white;
    }

    .btn-csms:hover {
        border: 1px solid #709775;
    }

    .grey-bg {
        background-color: grey;
        color: white;
    }

    .small-logo {
        height: 125px;
        width: 125px;
    }
</style>

<body>
    <div class="wrapper">
        <div class="main">
            <main class="content">
                <div class="d-flex align-items-center">
                    <a href="class_grades.php?class_id=<?php echo $_GET['id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>&subject_id=<?php echo $_GET['subject_id'] ?>"
                        class="d-flex align-items-center mb-3">
                        <i class="bi bi-arrow-left-circle" style="font-size: 1.5rem; margin-right: 5px;"></i>
                        <p class="m-0">Back</p>
                    </a>
                    <div class="ms-auto" aria-hidden="true">

                    </div>
                </div>






                <div id="printTable">
                    <div id="page-content-wrapper">
                        <div class="card bg-light border-0 shadow-sm"
                            style="background-color: white !important; padding: 5px;">

                            <div class="card-body">
                                <div
                                    class="container text-center d-flex align-items-center justify-content-center my-3">
                                    <div class="row w-100 d-flex align-items-center justify-content-center">
                                        <div class="col-2 d-flex justify-content-center">
                                            <img src="../external/img/ADDU_Logo-removebg-preview.png"
                                                class="img-fluid small-logo">
                                        </div>
                                        <div class="col-8 text-center">
                                            <h5 class="bold mb-1">Ateneo de Davao University</h5>
                                            <h5 class="mb-1">College of Computing Studies</h5>
                                            <h5>Zamboanga City</h5>
                                        </div>
                                        <div class="col-2 d-flex justify-content-center">
                                            <img src="../external/img/ccs_logo-removebg-preview.png"
                                                class="img-fluid small-logo">
                                        </div>
                                    </div>
                                </div>

                                <hr>



                                <?php

                                // Get the class_id from the URL parameter
                                $class_id = $_GET['id'];

                                // Prepare the SQL statement
                                $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id");

                                // Bind the class_id parameter
                                $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);

                                // Execute the query
                                $stmt->execute();

                                // Fetch the result
                                $class = $stmt->fetch();

                                if ($class) {
                                    // Extract the class details from the fetched data
                                    $adviser = $class['teacher'];
                                    $subject = $class['subject'];
                                    $year_section = $class['code']; // Assuming 'code' is for year/section
                                    $semester = $class['semester'];
                                    $school_year = date('Y', strtotime($class['datetime_added']));
                                    $type = $class['type'];
                                } else {
                                    // If no class is found, display a message
                                    echo "Class not found.";
                                    exit;
                                }

                                ?>

                                <style>
                                    table.dataTable {
                                        font-size: 12px;
                                        border: 1px solid black;
                                    }

                                    td,
                                    th {
                                        text-align: center;
                                        vertical-align: middle;
                                        border: 1px solid black;
                                        padding: 5px;
                                    }

                                    .small-logo {
                                        height: 100px;
                                        width: 100px;
                                    }

                                    /* Reinstated logo size */
                                    .no-print {
                                        display: block;
                                    }

                                    @media print {
                                        .no-print {
                                            display: none;
                                        }

                                        @page {
                                            size: landscape;
                                            margin: 0.25in;
                                        }

                                        /* Reduced margin for compactness */
                                        body {
                                            font-size: 10px;
                                            font-family: Arial, sans-serif;
                                        }

                                        /* Smaller font for print */
                                        .container-fluid {
                                            width: 100%;
                                        }

                                        table {
                                            width: 100%;
                                            border-collapse: collapse;
                                        }

                                        .text-end {
                                            text-align: right;
                                        }

                                        .print-class-details {
                                            font-size: 10px;
                                            line-height: 1.2;
                                        }

                                        /* Smaller, compact class details */
                                        img.small-logo {
                                            display: block;
                                            height: 75px;
                                            width: 75px;
                                        }

                                        /* Show logos in print, smaller size for compactness */
                                    }
                                </style>


                                <!-- HTML Structure -->
                                <div class="row mx-auto ">
                                    <div class="col">
                                        <h3><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                Adviser:</b> <span><?php echo htmlspecialchars($adviser); ?></span></h3>
                                        <h3><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                            <span><?php echo htmlspecialchars($subject); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-building" style="margin-right: 5px;"></i> Year and
                                                Section:</b> <span><?php echo htmlspecialchars($year_section); ?></span>
                                        </h3>
                                    </div>
                                    <div class="col  text-end">
                                        <h3><b><i class="bi bi-calendar3" style="margin-right: 5px;"></i> Semester:</b>
                                            <span><?php echo htmlspecialchars($semester); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-range" style="margin-right: 5px;"></i> School
                                                Year:</b> <span><?php echo htmlspecialchars($school_year); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-check" style="margin-right: 5px;"></i> Class
                                                Type
                                            </b> <span><?php echo htmlspecialchars($type); ?></span>
                                        </h3>
                                    </div>
                                </div>


                                <style>
                                    .active {
                                        color: black !important;
                                    }
                                </style>

                                <div class="modal fade" id="rubricModal" tabindex="-1" aria-labelledby="createRubricModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">

                                            <div class="modal-header">
                                                <h5 class="modal-title" id="createRubricModalLabel">Create New Rubric</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <ul class="nav nav-tabs" id="rubricTabs" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link active" id="rubric-tab" data-bs-toggle="tab" data-bs-target="#rubric" type="button" role="tab" aria-controls="rubric" aria-selected="true">
                                                            Available Rubrics
                                                        </button>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <button class="nav-link" id="percentile-tab" data-bs-toggle="tab" data-bs-target="#percentile" type="button" role="tab" aria-controls="percentile" aria-selected="false">
                                                            Rubric Percentile
                                                        </button>
                                                    </li>
                                                </ul>

                                                <div class="tab-content mt-3" id="rubricTabsContent">
                                                    <div class="tab-pane fade show active" id="rubric" role="tabpanel" aria-labelledby="rubric-tab">
                                                        <form id="createActivityForm" enctype="multipart/form-data" method="POST">


                                                            <div class="mb-3">
                                                                <label for="activityTitle" class="form-label">Rubric Title</label>
                                                                <input type="text" class="form-control" id="activityTitle" name="title" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="existingRubrics" class="form-label">Existing Rubrics</label>
                                                                <ul id="rubricList">
                                                                    <li>
                                                                        <div class="d-flex align-items-center"><strong role="status">Loading...</strong></div>
                                                                    </li>
                                                                </ul>
                                                            </div>

                                                            <input type="hidden" name="class_id" value="<?php echo $_GET['id']; ?>">
                                                            <input type="hidden" name="subject_id" value="<?php echo $_GET['subject_id']; ?>">
                                                            <input type="hidden" name="rubric_id" id="rubricId" value="">

                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary" id="submitBtn">Create</button>

                                                        </form>
                                                    </div>

                                                    <div class="tab-pane fade" id="percentile" role="tabpanel" aria-labelledby="percentile-tab">
                                                        <div class="mb-3" id="criteriaContainer">

                                                            <div class="criteria-list">
                                                                <!-- Criteria will be dynamically inserted here -->

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                            <input type="hidden" id="rubricId" name="rubric_id">


                                        </div>
                                    </div>
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const classId = '<?php echo $_GET['id']; ?>';
                                        const subjectId = '<?php echo $_GET['subject_id']; ?>';
                                        const form = document.getElementById('createActivityForm');
                                        const rubricList = document.getElementById('rubricList');
                                        const submitBtn = document.getElementById('submitBtn');
                                        const titleInput = document.getElementById('activityTitle');
                                        const rubricIdInput = document.getElementById('rubricId');

                                        // Fetch existing rubrics
                                        fetch(`processes/teachers/rubrics/add_from_rubrics.php?class_id=${classId}&subject_id=${subjectId}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                rubricList.innerHTML = '';
                                                if (data.success && data.rubrics && data.rubrics.length > 0) {
                                                    data.rubrics.forEach(rubric => {
                                                        rubricList.innerHTML += `
                        <li>
                            <div class="d-flex align-items-center">
                                <span>${rubric.title}</span>
                                <div class="ms-auto">
                                    <a href="#" class="editRubric" data-id="${rubric.id}" data-title="${rubric.title}">
                                        <i class="bi bi-pen-fill"></i>
                                    </a>
                                    <a href="#" class="deleteRubric" data-id="${rubric.id}" style="color:red">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </div>
                        </li>`;
                                                    });
                                                } else {
                                                    rubricList.innerHTML = '<li>No rubrics found.</li>';
                                                }

                                                // Edit rubric
                                                document.querySelectorAll('.editRubric').forEach(link => {
                                                    link.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        const id = this.getAttribute('data-id');
                                                        const title = this.getAttribute('data-title');
                                                        titleInput.value = title;
                                                        rubricIdInput.value = id;
                                                        submitBtn.textContent = 'Update';
                                                    });
                                                });

                                                // Delete rubric with SweetAlert2 confirmation
                                                document.querySelectorAll('.deleteRubric').forEach(link => {
                                                    link.addEventListener('click', function(e) {
                                                        e.preventDefault();
                                                        const id = this.getAttribute('data-id');

                                                        Swal.fire({
                                                            title: 'Are you sure?',
                                                            text: 'Do you really want to delete this rubric? This action cannot be undone.',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#3085d6',
                                                            cancelButtonColor: '#d33',
                                                            confirmButtonText: 'Yes, delete it!'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                fetch(`processes/teachers/rubrics/add_from_rubrics.php?delete=true&rubric_id=${id}&class_id=${classId}&subject_id=${subjectId}`, {
                                                                        method: 'GET'
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            Swal.fire({
                                                                                title: 'Deleted!',
                                                                                text: data.message,
                                                                                icon: 'success',
                                                                                confirmButtonText: 'OK'
                                                                            }).then(() => {
                                                                                location.reload(); // Refresh to update list
                                                                            });
                                                                        } else {
                                                                            Swal.fire({
                                                                                title: 'Error',
                                                                                text: data.message,
                                                                                icon: 'error',
                                                                                confirmButtonText: 'OK'
                                                                            });
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        Swal.fire({
                                                                            title: 'Error',
                                                                            text: 'Something went wrong: ' + error.message,
                                                                            icon: 'error',
                                                                            confirmButtonText: 'OK'
                                                                        });
                                                                    });
                                                            }
                                                        });
                                                    });
                                                });
                                            })
                                            .catch(error => {
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: 'Failed to load rubrics: ' + error.message,
                                                    icon: 'error',
                                                    confirmButtonText: 'OK'
                                                });
                                            });

                                        // Form submission (Create/Edit) with SweetAlert2
                                        form.addEventListener('submit', function(e) {
                                            e.preventDefault();
                                            const formData = new FormData(this);

                                            fetch('processes/teachers/rubrics/add_from_rubrics.php', {
                                                    method: 'POST',
                                                    body: formData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        Swal.fire({
                                                            title: 'Success!',
                                                            text: data.message,
                                                            icon: 'success',
                                                            confirmButtonText: 'OK'
                                                        }).then(() => {
                                                            location.reload(); // Refresh to update list
                                                        });
                                                    } else {
                                                        Swal.fire({
                                                            title: 'Error',
                                                            text: data.message,
                                                            icon: 'error',
                                                            confirmButtonText: 'OK'
                                                        });
                                                    }
                                                })
                                                .catch(error => {
                                                    Swal.fire({
                                                        title: 'Error',
                                                        text: 'Something went wrong: ' + error.message,
                                                        icon: 'error',
                                                        confirmButtonText: 'OK'
                                                    });
                                                });
                                        });
                                    });

                                    document.addEventListener('DOMContentLoaded', function() {
                                        // Assuming these are passed via URL parameters or some other method
                                        const urlParams = new URLSearchParams(window.location.search);
                                        const classId = urlParams.get('id') || '';
                                        const subjectId = urlParams.get('subject_id') || '';

                                        // Table container - you'll need to add this to your HTML
                                        const tableContainer = document.createElement('div');
                                        tableContainer.innerHTML = `
        <table class="table table-striped" id="rubricsTable">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Original Percentile</th>
                    <th>New Percentile</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="rubricsTableBody"></tbody>
        </table>
    `;
                                        document.querySelector('#percentile')?.appendChild(tableContainer);

                                        // Function to fetch and load rubrics
                                        function loadRubrics() {
                                            if (!classId || !subjectId) {
                                                showError('Missing class or subject ID');
                                                return;
                                            }

                                            fetch(`processes/teachers/rubrics/get_rubrics.php?class_id=${encodeURIComponent(classId)}&subject_id=${encodeURIComponent(subjectId)}`, {
                                                    method: 'GET',
                                                    credentials: 'same-origin'
                                                })
                                                .then(response => {
                                                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                                                    return response.json();
                                                })
                                                .then(data => {
                                                    const tbody = document.getElementById('rubricsTableBody');
                                                    if (!tbody) return;

                                                    tbody.innerHTML = '';
                                                    if (data?.success && Array.isArray(data.rubrics) && data.rubrics.length > 0) {
                                                        data.rubrics.forEach(rubric => {
                                                            const row = document.createElement('tr');
                                                            row.innerHTML = `
                        <td>${escapeHtml(rubric.title)}</td>
                        <td>${rubric.percentile || 0}%</td>
                        <td>
                            <input type="number" 
                                   class="form-control new-percentile" 
                                   data-rubric-id="${rubric.id}"
                                   min="0" 
                                   max="100" 
                                   step="1" 
                                   value="${rubric.percentile || ''}" 
                                   placeholder="%">
                        </td>
                        <td>
                            <button class="btn btn-primary save-percentile" 
                                    data-rubric-id="${rubric.id}">
                                Save
                            </button>
                        </td>
                    `;
                                                            tbody.appendChild(row);
                                                        });
                                                        attachSaveListeners();
                                                    } else {
                                                        tbody.innerHTML = '<tr><td colspan="4">No rubrics found</td></tr>';
                                                    }
                                                })
                                                .catch(error => showError('Failed to load rubrics: ' + error.message));
                                        }

                                        // HTML escape function
                                        function escapeHtml(unsafe) {
                                            return unsafe
                                                .replace(/&/g, "&amp;")
                                                .replace(/</g, "&lt;")
                                                .replace(/>/g, "&gt;")
                                                .replace(/"/g, "&quot;")
                                                .replace(/'/g, "&#039;");
                                        }

                                        // Error display function
                                        function showError(message) {
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: message,
                                                    icon: 'error',
                                                    confirmButtonText: 'OK'
                                                });
                                            } else {

                                            }
                                        }

                                        // Success display function
                                        function showSuccess(message) {
                                            if (typeof Swal !== 'undefined') {
                                                Swal.fire({
                                                    title: 'Success',
                                                    text: message,
                                                    icon: 'success',
                                                    confirmButtonText: 'OK'
                                                });
                                            } else {

                                            }
                                        }

                                        // Attach save button listeners
                                        function attachSaveListeners() {
                                            document.querySelectorAll('.save-percentile').forEach(button => {
                                                button.addEventListener('click', function() {
                                                    const rubricId = this.getAttribute('data-rubric-id');
                                                    const newPercentileInput = this.closest('tr').querySelector('.new-percentile');
                                                    const newPercentile = newPercentileInput.value;

                                                    if (!newPercentile || newPercentile < 0 || newPercentile > 100) {
                                                        showError('Please enter a valid percentile (0-100)');
                                                        return;
                                                    }

                                                    savePercentile(rubricId, newPercentile);
                                                });
                                            });
                                        }

                                        // Save percentile function
                                        function savePercentile(rubricId, percentile) {
                                            const formData = new FormData();
                                            formData.append('rubric_id', rubricId);
                                            formData.append('percentile', percentile);
                                            formData.append('class_id', classId);
                                            formData.append('subject_id', subjectId);

                                            fetch('processes/teachers/rubrics/update_percentile.php', {
                                                    method: 'POST',
                                                    body: formData,
                                                    credentials: 'same-origin'
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        showSuccess('Percentile updated successfully');
                                                        loadRubrics(); // Reload to show updated values
                                                    } else {
                                                        showError(data.message || 'Failed to update percentile');
                                                    }
                                                })
                                                .catch(error => showError('Error saving percentile: ' + error.message));
                                        }

                                        // Initial load
                                        loadRubrics();
                                    });
                                </script>

                                <hr>

                                <?php
                                require 'processes/server/conn.php';

                                try {
                                    // Get and validate class_id from GET parameter
                                    $class_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

                                    if (!$class_id) {
                                        throw new Exception("Invalid or missing class ID");
                                    }

                                    // Step 1: Get student IDs from student_enrollments
                                    $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
                                    $stmt->execute([$class_id]);
                                    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);




                                    if (!empty($studentIds)) {
                                        // Step 2: Prepare placeholders for SQL IN clause
                                        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
                                        // Step 3: Fetch ALL students (no gender filter) using correct column name 'id'
                                        $stmt = $pdo->prepare(
                                            "SELECT student_id, fullName, gender 
             FROM students 
             WHERE student_id IN ($placeholders)"
                                        );
                                        $stmt->execute($studentIds);
                                        $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);




                                        // Separate into male and female for display (optional)
                                        $male_students = array_filter($all_students, function ($student) {
                                            return strtolower($student['gender']) === 'male';
                                        });
                                        $female_students = array_filter($all_students, function ($student) {
                                            return strtolower($student['gender']) === 'female';
                                        });

                                        // Step 4: Merge male and female students
                                        $students = array_merge($male_students, $female_students);

                                        // Check for missing students
                                        $found_ids = array_column($all_students, 'student_id'); // Use 'id' to match the column name in students table
                                        $missing_ids = array_diff($studentIds, $found_ids);
                                        if (!empty($missing_ids)) {
                                        }
                                    } else {
                                    }
                                } catch (PDOException $e) {
                                    error_log("Database error: " . $e->getMessage());
                                    die("An error occurred while accessing the database. Please try again later.");
                                } catch (Exception $e) {
                                    die("Error: " . $e->getMessage());
                                }
                                ?>

                                <style>
                                    table.dataTable {
                                        font-size: 12px;
                                        border: 1px solid black;
                                        padding: 8px;
                                        text-align: center;
                                    }

                                    .table tr,
                                    th,
                                    td {
                                        border: 1px solid black !important;
                                        text-align: center;
                                    }

                                    .grader {
                                        border: none !important;
                                        text-align: center;
                                    }

                                    input,
                                    select {
                                        width: 100%;
                                        box-sizing: border-box;
                                    }

                                    input[type="number"]::-webkit-outer-spin-button,
                                    input[type="number"]::-webkit-inner-spin-button {
                                        -webkit-appearance: none;
                                        margin: 0;
                                    }

                                    .grader:focus {
                                        border: none;
                                        outline: none;
                                    }
                                </style>

                                <?php
                                // Assuming PDO connection is already established
                                $class_id = $_GET['id'] ?? null;

                                if (!$class_id) {
                                    die("Class ID is required");
                                }

                                // Function to calculate grade based on percentage
                                function calculateGrade($percentage)
                                {
                                    if ($percentage >= 0.95) return "1.00";
                                    if ($percentage >= 0.90) return "1.25";
                                    if ($percentage >= 0.85) return "1.50";
                                    if ($percentage >= 0.80) return "1.75";
                                    if ($percentage >= 0.75) return "2.00";
                                    if ($percentage >= 0.70) return "2.25";
                                    if ($percentage >= 0.65) return "2.50";
                                    if ($percentage >= 0.60) return "2.75";
                                    return "5.00"; // Failing grade
                                }

                                function roundToNearestGrade($value, $grades)
                                {
                                    return array_reduce($grades, function ($closest, $grade) use ($value) {
                                        return abs($grade - $value) < abs($closest - $value) ? $grade : $closest;
                                    }, $grades[0]);
                                }

                                // Fetch class information for the current class
                                $classStmt = $pdo->prepare("SELECT subject, type FROM classes WHERE id = ?");
                                $classStmt->execute([$class_id]);
                                $classInfo = $classStmt->fetch(PDO::FETCH_ASSOC);

                                if (!$classInfo) {
                                    die("Invalid Class ID");
                                }

                                $classSubject = $classInfo['subject'] ?? '';
                                $classType = strtolower($classInfo['type'] ?? '');

                                // Fetch all classes with the same subject to check for related Lecture/Lab classes
                                $relatedClassesStmt = $pdo->prepare("SELECT id, type FROM classes WHERE subject = ?");
                                $relatedClassesStmt->execute([$classSubject]);
                                $relatedClasses = $relatedClassesStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Detect Lecture and Lab based on all related classes
                                $hasLab = false;
                                $hasLec = false;
                                $lectureClassId = null;
                                $labClassId = null;

                                foreach ($relatedClasses as $relatedClass) {
                                    $relatedType = strtolower($relatedClass['type'] ?? '');
                                    if (strpos($relatedType, 'laboratory') !== false || strpos($relatedType, 'lab') !== false) {
                                        $hasLab = true;
                                        $labClassId = $relatedClass['id'];
                                    }
                                    if (strpos($relatedType, 'lecture') !== false || strpos($relatedType, 'lec') !== false) {
                                        $hasLec = true;
                                        $lectureClassId = $relatedClass['id'];
                                    }
                                }

                                $hasBothLabAndLec = $hasLab && $hasLec;

                                // Fetch rubrics with percentiles
                                $rubricsStmt = $pdo->prepare("SELECT DISTINCT title, percentile FROM rubrics WHERE class_id = ?");
                                $rubricsStmt->execute([$class_id]);
                                $rubrics = $rubricsStmt->fetchAll(PDO::FETCH_ASSOC);
                                $rubricTypes = array_column($rubrics, 'title');
                                $percentiles = array_column($rubrics, 'percentile', 'title');

                                // Detect rubric types that include the word 'Attendance'
                                $attendanceRubrics = preg_grep('/\bAttendance\b/', $rubricTypes);

                                // Separate the rubric types into two categories: with Attendance and without Attendance
                                $activityTypes = array_diff($rubricTypes, $attendanceRubrics);  // Rubrics that don't include Attendance
                                $attendanceTypes = $attendanceRubrics;  // Rubrics that include Attendance

                                // Ensure that attendance types are included in the activity types if needed
                                $activityTypes = array_merge($activityTypes, $attendanceTypes);

                                // Flag to check if any Attendance rubric is present
                                $hasAttendance = !empty($attendanceTypes);



                                // Fetch activities
                                $activities = [];
                                if (!empty($activityTypes)) {
                                    $placeholders = implode(',', array_fill(0, count($activityTypes), '?'));
                                    $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ? AND type IN ($placeholders)");
                                    $activitiesStmt->execute(array_merge([$class_id], $activityTypes));
                                    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                // Organize activities by type and term
                                $activitiesByType = [];
                                $activityIds = [];
                                foreach ($activities as $activity) {
                                    $activityIds[] = $activity['id'];
                                    if (!isset($activitiesByType[$activity['type']])) {
                                        $activitiesByType[$activity['type']] = [
                                            'midterm' => [],
                                            'final' => [],
                                            'max_points' => ['midterm' => 0, 'final' => 0]
                                        ];
                                    }
                                    $activitiesByType[$activity['type']][$activity['term']][] = $activity;
                                    $activitiesByType[$activity['type']]['max_points'][$activity['term']] += floatval($activity['max_points']);
                                }

                                // Fetch student submissions
                                $submissions = [];
                                if (!empty($activityIds)) {
                                    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
                                    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($placeholders)");
                                    $submissionsStmt->execute($activityIds);
                                    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                $studentScores = [];
                                foreach ($submissions as $submission) {
                                    $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
                                }

                                // Fetch students from enrollments
                                $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
                                $stmt->execute([$class_id]);
                                $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                $students = [];
                                if (!empty($studentIds)) {
                                    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
                                    $stmt = $pdo->prepare("SELECT student_id, fullName FROM students WHERE student_id IN ($placeholders) ORDER BY fullName");
                                    $stmt->execute($studentIds);
                                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                // Attendance handling
                                $totalMeetings = 0;
                                $attendanceDates = [];
                                $attendanceRecords = [];
                                if ($hasAttendance) {
                                    $stmt = $pdo->prepare("SELECT id, date FROM classes_meetings WHERE class_id = ? ORDER BY date ASC");
                                    $stmt->execute([$class_id]);
                                    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $totalMeetings = count($meetings);
                                    $attendanceDates = array_column($meetings, 'date', 'id');



                                    $attendanceStmt = $pdo->prepare("SELECT student_id, meeting_id, status FROM attendance WHERE class_id = ?");
                                    $attendanceStmt->execute([$class_id]);
                                    $attendanceRecordsRaw = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($attendanceRecordsRaw as $record) {
                                        $studentId = $record['student_id'];
                                        $meetingId = $record['meeting_id'];
                                        if (!isset($attendanceRecords[$studentId])) {
                                            $attendanceRecords[$studentId] = [];
                                        }
                                        $attendanceRecords[$studentId][$meetingId] = $record['status'];
                                    }
                                }

                                $totalPoints = [];
                                foreach ($activityTypes as $type) {
                                    $totalPoints[$type] = ['midterm' => 0, 'final' => 0]; // Always initialize both keys
                                    if (!isset($activitiesByType[$type])) {
                                        continue;
                                    }
                                    // Calculate midterm points
                                    if (!empty($activitiesByType[$type]['midterm'])) {
                                        foreach ($activitiesByType[$type]['midterm'] as $activity) {
                                            $totalPoints[$type]['midterm'] += floatval($activity['max_points']);
                                        }
                                    }
                                    // Calculate final points
                                    if (!empty($activitiesByType[$type]['final'])) {
                                        foreach ($activitiesByType[$type]['final'] as $activity) {
                                            $totalPoints[$type]['final'] += floatval($activity['max_points']);
                                        }
                                    }
                                }

                                // Define required activities and final exams
                                $requiredActivityIds = array_column($activities, 'id');
                                $finalExamActivityIds = array_column(array_filter($activities, function ($activity) {
                                    return stripos($activity['type'], 'exam') !== false && $activity['term'] === 'final';
                                }), 'id');

                                $studentGrades = [];
                                foreach ($students as $student) {
                                    $studentId = $student['student_id'];
                                    $grades = ['midterm' => 0, 'final' => 0];

                                    // Check if student has existing grades in the database
                                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                    $checkStmt->execute([':class_id' => $class_id, ':student_id' => $studentId]);
                                    $exists = $checkStmt->fetchColumn() > 0;

                                    if ($exists) {
                                        // Fetch existing grades and status
                                        $selectStmt = $pdo->prepare("SELECT midterm_grade, final_grade, overall_grade, status FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                        $selectStmt->execute([':class_id' => $class_id, ':student_id' => $studentId]);
                                        $existingRecord = $selectStmt->fetch(PDO::FETCH_ASSOC);

                                        if ($existingRecord) {
                                            $existingMidtermGrade = $existingRecord['midterm_grade'];
                                            $existingFinalGrade = $existingRecord['final_grade'];
                                            $existingStatus = $existingRecord['status'];

                                            // Handle special cases like AW, UW, and INC grades
                                            if ($existingMidtermGrade === 'AW' || $existingFinalGrade === 'AW') {
                                                $midtermGrade = $finalGrade = $overallGrade = 'AW';
                                                $status = $existingStatus;
                                            } elseif ($existingMidtermGrade === 'UW' || $existingFinalGrade === 'UW') {
                                                $midtermGrade = $finalGrade = $overallGrade = 'UW';
                                                $status = $existingStatus;
                                            } else {
                                                // Check if grade needs to be calculated or is already available
                                                if (in_array($existingStatus, ['for_approval', 'pending', 'final', 'accepted', 'saved'])) {
                                                    $midtermGrade = $existingMidtermGrade;
                                                    $finalGrade = $existingFinalGrade;
                                                    $overallGrade = $existingRecord['overall_grade'];
                                                    $status = $existingStatus;
                                                } else {
                                                    // Default grade if no activity is available
                                                    if (empty($activityTypes) || empty($activities)) {
                                                        $midtermGrade = $finalGrade = $overallGrade = "INC";
                                                        $status = "INC";
                                                    } else {
                                                        // Calculate grades based on activities
                                                        foreach ($activityTypes as $type) {
                                                            $grades['midterm'] += calculateActivityScore($studentId, $activitiesByType[$type]['midterm'], $studentScores, $totalPoints, $percentiles[$type], 'midterm');
                                                            $grades['final'] += calculateActivityScore($studentId, $activitiesByType[$type]['final'], $studentScores, $totalPoints, $percentiles[$type], 'final');
                                                        }

                                                        // Handle attendance

                                                        if ($hasAttendance && $totalMeetings > 0) {
                                                            // Calculate the attendance percentage
                                                            $attendancePercentage = calculateAttendance($studentId, $attendanceDates, $attendanceRecords, $totalMeetings);

                                                            // Loop through the $attendanceTypes to handle all attendance rubrics dynamically
                                                            foreach ($attendanceTypes as $attendanceType) {
                                                                // Check if this specific 'Attendance' type exists in the $percentiles array
                                                                if (isset($percentiles[$attendanceType])) {
                                                                    // Apply the attendance percentage to the grades based on the specific attendance type
                                                                    $grades['midterm'] += $attendancePercentage * ($percentiles[$attendanceType] / 100) / 2;
                                                                    $grades['final'] += $attendancePercentage * ($percentiles[$attendanceType] / 100) / 2;
                                                                }
                                                            }
                                                        }


                                                        // Check for missing requirements or final exam
                                                        $missingRequirements = checkMissingRequirements($studentId, $requiredActivityIds, $studentScores);
                                                        $missedFinalExam = checkMissingRequirements($studentId, $finalExamActivityIds, $studentScores);

                                                        // Calculate grades
                                                        $midtermGrade = calculateGrade($grades['midterm']);
                                                        $finalGrade = calculateGrade($grades['final']);

                                                        echo $finalGrade;
                                                        $midtermNumeric = floatval($midtermGrade);
                                                        $finalNumeric = floatval($finalGrade);

                                                        // Determine status and overall grade
                                                        if ($missingRequirements || $missedFinalExam) {
                                                            $midtermGrade = $missingRequirements ? "INC" : $midtermGrade;
                                                            $finalGrade = $missingRequirements ? "INC" : $finalGrade;
                                                            $overallGrade = "INC";
                                                            $status = "INC";
                                                        } else {
                                                            $rawAverage = ($midtermNumeric + $finalNumeric) / 2;
                                                            $validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00];
                                                            $overallGrade = number_format(roundToNearestGrade($rawAverage, $validGrades), 2);
                                                        }
                                                    }
                                                }

                                                // Check if there's a need to update the grade
                                                if ($existingMidtermGrade !== $midtermGrade || $existingFinalGrade !== $finalGrade || $existingRecord['overall_grade'] !== $overallGrade) {
                                                    $updateStmt = $pdo->prepare("UPDATE student_grades SET midterm_grade = :midterm_grade, final_grade = :final_grade, overall_grade = :overall_grade, updated_at = NOW()WHERE class_id = :class_id AND student_id = :student_id");
                                                    $updateStmt->execute([':class_id' => $class_id, ':student_id' => $studentId, ':midterm_grade' => $midtermGrade, ':final_grade' => $finalGrade, ':overall_grade' => $overallGrade]);
                                                }
                                            }

                                            // Calculate combined Lecture and Lab grade if applicable
                                            $combinedOverallGrade = $overallGrade;
                                            if ($hasBothLabAndLec) {
                                                $lecGrade = fetchGrade($lectureClassId, $studentId, $pdo);
                                                $labGrade = fetchGrade($labClassId, $studentId, $pdo);
                                                $combinedOverallGrade = calculateCombinedGrade($lecGrade, $labGrade);
                                            }

                                            $studentGrades[$studentId] = [
                                                'fullName' => $student['fullName'],
                                                'midterm' => $midtermGrade,
                                                'final' => $finalGrade,
                                                'lecGrade' => $lecGrade ?? 'INC',
                                                'labGrade' => $labGrade ?? 'INC',
                                                'gpa' => $overallGrade,
                                                'overallGrade' => $combinedOverallGrade
                                            ];
                                        }
                                    }
                                }

                                // In the calculateActivityScore function
                                function calculateActivityScore($studentId, $activities, $studentScores, $totalPoints, $percentile, $term)
                                {  // Changed $type to $term
                                    $score = 0;
                                    foreach ($activities as $activity) {
                                        $score += ($studentScores[$studentId][$activity['id']] ?? 0);
                                    }
                                    // Ensure the type is derived from activities and term exists
                                    $type = !empty($activities) ? $activities[0]['type'] : '';
                                    $totalPointsForType = isset($totalPoints[$type][$term]) && $totalPoints[$type][$term] > 0 ? $totalPoints[$type][$term] : 1;

                                    return ($score / $totalPointsForType) * ($percentile / 100);
                                }

                                function calculateAttendance($studentId, $attendanceDates, $attendanceRecords, $totalMeetings)
                                {
                                    $presentCount = 0;
                                    foreach ($attendanceDates as $meetingId => $date) {
                                        if (($attendanceRecords[$studentId][$meetingId] ?? 'absent') === 'present') {
                                            $presentCount++;
                                        }
                                    }
                                    return $presentCount / $totalMeetings;
                                }

                                function checkMissingRequirements($studentId, $requiredActivityIds, $studentScores)
                                {
                                    foreach ($requiredActivityIds as $activityId) {
                                        if (!isset($studentScores[$studentId][$activityId]) || $studentScores[$studentId][$activityId] == 0) {
                                            return true;
                                        }
                                    }
                                    return false;
                                }

                                function fetchGrade($classId, $studentId, $pdo)
                                {
                                    $stmt = $pdo->prepare("SELECT overall_grade FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                    $stmt->execute([':class_id' => $classId, ':student_id' => $studentId]);
                                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                                    return $record ? $record['overall_grade'] : 'INC';
                                }

                                function calculateCombinedGrade($lecGrade, $labGrade)
                                {
                                    if ($lecGrade === 'INC' || $labGrade === 'INC') {
                                        return 'INC';
                                    }
                                    if ($lecGrade === 'AW' || $labGrade === 'AW') {
                                        return 'AW';
                                    }
                                    if ($lecGrade === 'UW' || $labGrade === 'UW') {
                                        return 'UW';
                                    }

                                    $lecNumeric = floatval($lecGrade);
                                    $labNumeric = floatval($labGrade);
                                    $weightedAverage = ($lecNumeric * 0.6) + ($labNumeric * 0.4); // 60% Lecture, 40% Lab

                                    $validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00];
                                    return findNearestGrade($weightedAverage, $validGrades);
                                }

                                function findNearestGrade($weightedAverage, $validGrades)
                                {
                                    $nearestGrade = $validGrades[0]; // Default to the first valid grade
                                    $minDifference = abs($weightedAverage - $validGrades[0]);

                                    foreach ($validGrades as $grade) {
                                        $difference = abs($weightedAverage - $grade);
                                        if ($difference < $minDifference) {
                                            $minDifference = $difference;
                                            $nearestGrade = $grade;
                                        }
                                    }
                                    return $nearestGrade;
                                }




                                $totalColumns = 999; // Student ID and Name
                                if (!empty($activityTypes) && !empty($activitiesByType)) {
                                    foreach ($activityTypes as $type) {
                                        $midtermCount = isset($activitiesByType[$type]['midterm']) ? count($activitiesByType[$type]['midterm']) : 0;
                                        $finalCount = isset($activitiesByType[$type]['final']) ? count($activitiesByType[$type]['final']) : 0;
                                        $totalColumns += $midtermCount + $finalCount + 1; // Activities + Total
                                    }
                                }

                                $totalColumns += 3; // Midterm, Final, GPA
                                if ($hasBothLabAndLec) {
                                    $totalColumns += 2; // Add Lab and Lec grade columns
                                }
                                ?>
                                <div class="container-fluid mt-4">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th colspan="<?php echo $totalColumns; ?>">Worksheets</th>
                                                </tr>
                                                <?php if (empty($rubricTypes)): ?>
                                                    <tr>
                                                        <th colspan="<?php echo $totalColumns; ?>" class="text-center">No Rubrics Defined Yet</th>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <th class="text-center" colspan="2">Criteria</th>
                                                        <?php foreach ($activityTypes as $type): ?>
                                                            <?php
                                                            $containsAttendance = stripos($type, 'Attendance') !== false;
                                                            $activityCount = isset($activitiesByType[$type]) && !empty(array_merge($activitiesByType[$type]['midterm'], $activitiesByType[$type]['final']))
                                                                ? count(array_merge($activitiesByType[$type]['midterm'], $activitiesByType[$type]['final']))
                                                                : 0;
                                                            $colspan = $activityCount + 1 + ($containsAttendance ? $totalMeetings + 1 : 0);
                                                            ?>
                                                            <th colspan="<?php echo $colspan; ?>" class="text-center">
                                                                <?php echo htmlspecialchars($type); ?>
                                                                (<?php echo number_format($percentiles[$type] ?? 0, 2); ?>%)
                                                            </th>
                                                        <?php endforeach; ?>
                                                        <th>Midterms</th>
                                                        <th>Finals</th>
                                                        <th>Numerical Grade</th>
                                                        <?php if ($hasBothLabAndLec): ?>
                                                            <th class="text-center">Overall Grade <br><small>(Lecture and Laboratory)</small></th>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endif; ?>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($rubricTypes)): ?>
                                                    <tr>
                                                        <td colspan="<?php echo $totalColumns; ?>" class="text-center">Please define rubrics to display grading data.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <td>Student ID</td>
                                                        <td>Student Name</td>
                                                        <?php foreach ($activityTypes as $type): ?>
                                                            <?php
                                                            $typeData = $activitiesByType[$type] ?? ['midterm' => [], 'final' => [], 'max_points' => ['midterm' => 0, 'final' => 0]];
                                                            $activities = array_merge($typeData['midterm'], $typeData['final']);
                                                            foreach ($activities as $index => $activity):
                                                                $label = htmlspecialchars($type[0]) . ($index + 1) . " (" . htmlspecialchars($activity['max_points']) . " pts, " . ucfirst($activity['term']) . ")";
                                                            ?>
                                                                <td><?php echo $label; ?></td>
                                                            <?php endforeach; ?>
                                                            <td>TOTAL (<?php echo $typeData['max_points']['midterm'] + $typeData['max_points']['final']; ?> pts)</td>

                                                            <!-- Attendance Columns if Present in this Rubric -->
                                                            <?php if (stripos($type, 'Attendance') !== false): ?>
                                                                <?php foreach ($attendanceDates as $date): ?>
                                                                    <td><?php echo '<small>Attendance (' . date('M d, Y', strtotime($date)) . ')</small>'; ?></td>
                                                                <?php endforeach; ?>
                                                                <td>TOTAL (No. Days)</td>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                        <td>1-5</td>
                                                        <td>1-5</td>
                                                        <td>1-5</td>
                                                        <?php if ($hasBothLabAndLec): ?>
                                                            <td class="text-center">1-5</td>
                                                        <?php endif; ?>
                                                    </tr>

                                                    <?php foreach ($students as $student): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($student['fullName']); ?></td>
                                                            <?php foreach ($activityTypes as $type): ?>
                                                                <?php
                                                                $typeData = $activitiesByType[$type] ?? ['midterm' => [], 'final' => [], 'max_points' => ['midterm' => 0, 'final' => 0]];
                                                                $activities = array_merge($typeData['midterm'], $typeData['final']);
                                                                $typeTotal = 0;
                                                                ?>
                                                                <?php foreach ($activities as $activity): ?>
                                                                    <?php
                                                                    $score = $studentScores[$student['student_id']][$activity['id']] ?? 0;
                                                                    $typeTotal += (float)$score;
                                                                    ?>
                                                                    <td><?php echo htmlspecialchars($score); ?></td>
                                                                <?php endforeach; ?>
                                                                <td class='total-score' data-type='<?php echo $type; ?>' data-student-id='<?php echo $student['student_id']; ?>'><?php echo $typeTotal; ?></td>

                                                                <!-- Attendance Data Within the Same Rubric -->
                                                                <?php if (stripos($type, 'Attendance') !== false): ?>
                                                                    <?php
                                                                    $studentAttendance = $attendanceRecords[$student['student_id']] ?? [];
                                                                    $presentCount = 0;
                                                                    foreach ($attendanceDates as $meetingId => $date):
                                                                        $status = $studentAttendance[$meetingId] ?? 'absent';
                                                                        if ($status === 'present') $presentCount++;
                                                                    ?>
                                                                        <td><?php echo ucfirst($status); ?></td>
                                                                    <?php endforeach; ?>
                                                                    <td class="total-attendance" data-student-id="<?php echo $student['student_id']; ?>"><?php echo $presentCount; ?></td>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>

                                                            <!-- Midterm, Finals, and Numerical Grade -->
                                                            <td <?php echo in_array($studentGrades[$student['student_id']]['midterm'], ['5.00', 'INC', 'UW', 'AW']) ? 'style="color: crimson;"' : ''; ?>>
                                                                <?php echo $studentGrades[$student['student_id']]['midterm']; ?>
                                                            </td>

                                                            <td <?php echo in_array($studentGrades[$student['student_id']]['final'], ['5.00', 'INC', 'UW', 'AW']) ? 'style="color: crimson;"' : ''; ?>>
                                                                <?php echo $studentGrades[$student['student_id']]['final']; ?>
                                                            </td>

                                                            <td <?php echo in_array($studentGrades[$student['student_id']]['gpa'], ['5.00', 'INC', 'UW', 'AW']) ? 'style="color: crimson;"' : ''; ?>>
                                                                <?php echo $studentGrades[$student['student_id']]['gpa']; ?>
                                                            </td>

                                                            <?php if ($hasBothLabAndLec): ?>
                                                                <td <?php echo in_array($studentGrades[$student['student_id']]['overallGrade'], ['5.00', 'INC', 'UW', 'AW']) ? 'style="color: crimson;"' : ''; ?>>
                                                                    <?php echo  number_format($studentGrades[$student['student_id']]['overallGrade'], 2); ?>
                                                                </td>
                                                            <?php endif; ?>

                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>

                                    </div>
                                </div>
</body>


<?php if (!empty($rubricTypes)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function updateGPA(row) {
                const studentId = row.querySelector('.total-score')?.dataset.studentId;
                if (!studentId) return;

                const percentiles = <?php echo json_encode($percentiles); ?>;
                const totalPoints = <?php echo json_encode($totalPoints); ?>;
                const hasAttendance = <?php echo json_encode($hasAttendance); ?>;
                const totalMeetings = <?php echo $totalMeetings; ?> || 1;

                let totalWeightedScore = 0;
                let totalPercentage = 0;

                Object.keys(percentiles).forEach(type => {
                    const percentage = parseFloat(percentiles[type]) || 0;
                    totalPercentage += percentage;

                    if (type === 'Attendance' && hasAttendance) {
                        const attendance = parseFloat(row.querySelector('.total-attendance')?.textContent) || 0;
                        const weightedScore = (attendance / totalMeetings) * (percentage / 100);
                        totalWeightedScore += weightedScore;
                    } else {
                        const totalScore = parseFloat(row.querySelector(`.total-score[data-type="${type}"]`)?.textContent) || 0;
                        const maxPoints = totalPoints[type] || 1;
                        const weightedScore = (totalScore / maxPoints) * (percentage / 100);
                        totalWeightedScore += weightedScore;
                    }
                });

                const numericScore = totalWeightedScore * 4;
                let gpaValue;

                if (numericScore >= 3.5) gpaValue = "1.00";
                else if (numericScore >= 3.0) gpaValue = "1.25";
                else if (numericScore >= 2.5) gpaValue = "1.75";
                else if (numericScore >= 2.0) gpaValue = "2.00";
                else if (numericScore >= 1.5) gpaValue = "2.25";
                else if (numericScore >= 1.0) gpaValue = "2.75";
                else if (numericScore >= 0.5) gpaValue = "3.00";
                else gpaValue = "5.00";

                const gpaElement = row.querySelector('.gpa');
                if (gpaElement) gpaElement.textContent = gpaValue;
            }

            function calculateGPA(element) {
                const row = element.closest('tr');
                updateGPA(row);
            }

            document.querySelectorAll('tr').forEach(row => {
                if (row.querySelector('.total-score')) {
                    updateGPA(row);
                }
            });

            document.addEventListener("input", function(event) {
                if (event.target.matches(".score-input")) {
                    calculateGPA(event.target);
                }
            });
        });
    </script>


<?php endif; ?>
</div>
<div class="row">
    <button id="buttonism" onclick="printDiv('printTable')" class="btn btn-primary mb-5 p-2 w-25 m-auto">
        <i class="bi bi-printer"></i> Print
    </button>
    <button id="buttonism" type="button" class="btn btn-primary mb-5 p-2 w-25 m-auto" data-bs-toggle="modal" data-bs-target="#rubricModal">
        <i class="bi bi-pen-fill"></i> Rubrics</button>
</div>
</main>


</div>


<script src="js/app.js"></script>

<script>
    function printDiv(divId) {
        var content = document.getElementById(divId).innerHTML;
        var printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(`
        <html>
            <head>
                <title>Print Content</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">
                <style>
                    body { font-family: Arial, sans-serif; }
                    .text-center { text-align: center; }
                    .bold { font-weight: bold; }
                    .grey-bg { background-color: #f8f9fa; }
                    table, th, td { border: 1px solid black; border-collapse: collapse; padding: 1px; }
                    .grade { background: none; border: none; cursor: pointer; color: inherit; }
                    @media print {
                        @page { size: landscape; }

                        #buttonism{
                        display: none
            }

                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                ${content}
            </body>
        </html>
    `);
        printWindow.document.close();
    }
</script>
<?php
include('processes/server/modals.php');
?>




</html>

<?php
include('processes/server/alerts.php');
?>