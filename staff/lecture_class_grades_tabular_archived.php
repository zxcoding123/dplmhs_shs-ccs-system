<?php
require_once __DIR__ . '/../includes/grading.php';
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}
include __DIR__ . '/../database/connection.php';

// Class shown in the header, the printout and the links back
$class_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id");
$stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
$stmt->execute();
$class = $stmt->fetch();

if (!$class) {
    echo "Class not found.";
    exit;
}

$adviser = $class['teacher'];
$subject = $class['subject'];
$year_section = $class['name'];
$semester = $class['semester'];
$school_year = date('Y', strtotime($class['datetime_added']));
$type = $class['type'];
$gb_archived = (int) $class['is_archived'] === 1;
$gb_state = grades_class_state($pdo, (int) $class['id']);
$gb_back = 'class_grades.php?' . http_build_query([
    'class_id' => $class['id'],
    'semester_id' => $_GET['semester_id'] ?? '',
    'subject_id' => $_GET['subject_id'] ?? '',
]);
$gb_state_pill = [
    'none' => ['Not started', 'is-archived'],
    'draft' => ['Draft · not submitted', 'is-type'],
    'submitted' => ['Waiting for review', 'is-pending'],
    'locked' => ['Accepted · locked', 'is-active'],
][$gb_state];
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
    <link href="<?php echo csms_asset('css/gradebook.css', __DIR__); ?>" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <div class="main">
            <main class="content">
                <div class="container-fluid p-0 gb-page">

                    <div class="fd-hero">
                        <div>
                            <div class="fd-eyebrow">Gradebook &middot; <?= htmlspecialchars($type) ?></div>
                            <h1 class="fd-title"><?= htmlspecialchars($subject) ?></h1>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="fd-chip"><i class="bi bi-diagram-3 me-1"></i><?= htmlspecialchars($year_section) ?></span>
                                <span class="fd-chip"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($semester) ?> &middot; <?= htmlspecialchars($school_year) ?></span>
                                <span class="fd-chip"><i class="bi bi-person me-1"></i><?= htmlspecialchars($adviser) ?></span>
                                <span class="fd-pill <?= $gb_state_pill[1] ?>"><?= $gb_state === 'locked' ? '<i class="bi bi-lock"></i> ' : '' ?><?= $gb_state_pill[0] ?></span>
                                <?php if ($gb_archived): ?><span class="fd-pill is-archived"><i class="bi bi-archive"></i> Archived</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="fd-quick">
                            <a class="fd-btn-ghost" href="<?= htmlspecialchars($gb_back) ?>"><i class="bi bi-arrow-left"></i> Class grades</a>
                            <?php if (!$gb_archived): ?>
                                <button type="button" class="fd-btn-ghost" data-bs-toggle="modal" data-bs-target="#rubricModal"><i class="bi bi-sliders"></i> Components &amp; weights</button>
                            <?php endif; ?>
                            <button type="button" class="fd-btn" onclick="printDiv('printTable')"><i class="bi bi-printer"></i> Print</button>
                        </div>
                    </div>

                    <div class="card fd-panel mb-4">
                        <div class="card-header">
                            <div>
                                <h5 class="card-title"><i class="bi bi-table"></i> Grade Sheet</h5>
                                <p class="fd-panel-sub">Raw scores by component. Quarter grades are transmuted to the DepEd 60–100 scale; the final grade is the average of both quarters.</p>
                            </div>
                        </div>
                        <div class="card-body">

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
                    <li><div class="d-flex align-items-center"><strong role="status">Loading...</strong></div></li>
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
        <div class="border rounded p-3 mb-3 text-start" id="depedPreset">
            <label for="depedPresetSelect" class="form-label fw-bold mb-1">DepEd component weights</label>
            <p class="small text-muted mb-2">Sets Written Work, Performance Tasks and Quarterly Assessment to the DepEd weights for this subject type. Other components stay but count 0%.</p>
            <div class="d-flex gap-2">
                <select class="form-select" id="depedPresetSelect">
                    <?php foreach (deped_weight_presets() as $presetKey => $preset): ?>
                        <option value="<?= htmlspecialchars($presetKey) ?>"><?= htmlspecialchars($preset['label'] . ' (' . implode(' / ', $preset['weights']) . ')') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary text-nowrap" id="depedPresetApply" style="width:auto">Apply</button>
            </div>
        </div>
        <script>
            document.getElementById('depedPresetApply').addEventListener('click', function () {
                const body = new URLSearchParams({
                    class_id: '<?= (int) $_GET['id'] ?>',
                    subject_id: '<?= (int) $_GET['subject_id'] ?>',
                    preset: document.getElementById('depedPresetSelect').value
                });
                fetch('processes/teachers/rubrics/apply_deped.php', { method: 'POST', body: body })
                    .then(r => r.json())
                    .then(res => Swal.fire(res.success ? 'Weights applied' : 'Not applied', res.message, res.success ? 'success' : 'error')
                        .then(() => { if (res.success) location.reload(); }))
                    .catch(() => Swal.fire('Error', 'The weights could not be applied.', 'error'));
            });
        </script>

        <div class="mb-3" id="criteriaContainer">
            <table class="table table-striped" id="rubricsTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Original Percentile</th>
                        <th>New Percentile</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="rubricsTableBody">
                    <tr><td colspan="4">Loading...</td></tr>
                </tbody>
            </table>
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

    // Function to render rubric list
    function renderRubricList(rubrics) {
        rubricList.innerHTML = '';
        if (rubrics && rubrics.length > 0) {
            rubrics.forEach(rubric => {
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
            attachRubricListeners();
        } else {
            rubricList.innerHTML = '<li>No rubrics found.</li>';
        }
    }

    // Fetch and load rubric list
    function loadRubricsList() {
        fetch(`processes/teachers/rubrics/add_from_rubrics.php?class_id=${classId}&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                renderRubricList(data.success && data.rubrics ? data.rubrics : []);
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load rubrics: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }

    // Function to render percentile table
    function loadRubrics() {
        const tbody = document.getElementById('rubricsTableBody');
        if (!tbody) return;

        fetch(`processes/teachers/rubrics/get_rubrics.php?class_id=${encodeURIComponent(classId)}&subject_id=${encodeURIComponent(subjectId)}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';
                if (data?.success && Array.isArray(data.rubrics) && data.rubrics.length > 0) {
                    data.rubrics.forEach(rubric => {
                        tbody.innerHTML += `
                            <tr>
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
                            </tr>`;
                    });
                    attachSaveListeners();
                } else {
                    tbody.innerHTML = '<tr><td colspan="4">No rubrics found</td></tr>';
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load rubrics: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    }

    // Attach event listeners to rubric actions
    function attachRubricListeners() {
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
                                    loadRubricsList(); // Refresh rubric list
                                    loadRubrics();     // Refresh percentile table
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
    }

    // Form submission (Create/Edit)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const rubricId = rubricIdInput.value;

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
                    form.reset();
                    submitBtn.textContent = 'Create';
                    rubricIdInput.value = '';
                    loadRubricsList(); // Refresh rubric list
                    loadRubrics();     // Refresh percentile table
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

    // Attach save button listeners for percentile table
    function attachSaveListeners() {
        document.querySelectorAll('.save-percentile').forEach(button => {
            button.addEventListener('click', function() {
                const rubricId = this.getAttribute('data-rubric-id');
                const newPercentileInput = this.closest('tr').querySelector('.new-percentile');
                const newPercentile = newPercentileInput.value;

                if (!newPercentile || newPercentile < 0 || newPercentile > 100) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Please enter a valid percentile (0-100)',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                const formData = new FormData();
                formData.append('rubric_id', rubricId);
                formData.append('percentile', newPercentile);
                formData.append('class_id', classId);
                formData.append('subject_id', subjectId);

                fetch('processes/teachers/rubrics/update_percentile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Percentile updated successfully',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            loadRubrics(); // Refresh table after saving percentile
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to update percentile',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error saving percentile: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            });
        });
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

    // Initial load
    loadRubricsList();
    loadRubrics();
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('id') || '';
    const subjectId = urlParams.get('subject_id') || '';

    const tableContainer = document.createElement('div');
   
    document.querySelector('#percentile')?.appendChild(tableContainer);

    // Function to fetch and load rubrics for table
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
                loadRubrics(); // Refresh table
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


                                <?php
                                require __DIR__ . '/../database/connection.php';

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


                                <?php
                                // Assuming PDO connection is already established
                                $class_id = $_GET['id'] ?? null;

                                if (!$class_id) {
                                    die("Class ID is required");
                                }

                                function calculateGrade($percentage)
                                {
                                    // DepEd transmutation (includes/grading.php)
                                    return deped_quarter_grade($percentage);
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
                                            } elseif ($existingMidtermGrade === 'DRP' || $existingFinalGrade === 'DRP') {
                                                $midtermGrade = $finalGrade = $overallGrade = 'DRP';
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
                                                        $midtermGrade = $finalGrade = $overallGrade = "N/A";
                                                        $status = "N/A";
                                                    } else {
                                                        // Calculate grades based on activities only if they exist
                                                        foreach ($activityTypes as $type) {
                                                            // Check if activities exist for this type
                                                            if (!empty($activitiesByType[$type]['midterm']) || !empty($activitiesByType[$type]['final'])) {
                                                                $grades['midterm'] += !empty($activitiesByType[$type]['midterm']) ?
                                                                    calculateActivityScore($studentId, $activitiesByType[$type]['midterm'], $studentScores, $totalPoints, $percentiles[$type], 'midterm') : 0;
                                                                $grades['final'] += !empty($activitiesByType[$type]['final']) ?
                                                                    calculateActivityScore($studentId, $activitiesByType[$type]['final'], $studentScores, $totalPoints, $percentiles[$type], 'final') : 0;
                                                            }
                                                        }

                                                        // Handle attendance
                                                        if ($hasAttendance && $totalMeetings > 0) {
                                                            // Calculate the attendance percentage
                                                            $attendancePercentage = calculateAttendance($studentId, $attendanceDates, $attendanceRecords, $totalMeetings);

                                                            foreach ($attendanceTypes as $attendanceType) {
                                                                if (isset($percentiles[$attendanceType])) {
                                                                    $grades['midterm'] += $attendancePercentage * ($percentiles[$attendanceType] / 100);
                                                                    $grades['final'] += $attendancePercentage * ($percentiles[$attendanceType] / 100);
                                                                }
                                                            }
                                                        }

                                                        // Check for missing requirements or final exam
                                                        $missingRequirements = checkMissingRequirements($studentId, $requiredActivityIds, $studentScores);
                                                        $missedFinalExam = checkMissingRequirements($studentId, $finalExamActivityIds, $studentScores);

                                                        // Calculate grades only if there are activities
                                                        // DepEd: each quarter is transmuted on its own, the final grade is their average (includes/grading.php)
                                                        $midtermGrade = deped_term_has_work($activities, 'midterm') ? calculateGrade($grades['midterm']) : 'N/A';
                                                        $finalGrade = deped_term_has_work($activities, 'final') ? calculateGrade($grades['final']) : 'N/A';
                                                        $overallGrade = deped_final_grade($midtermGrade, $finalGrade);
                                                    }
                                                }

                                                // Check if there's a need to update the grade
                                                if ($existingMidtermGrade !== $midtermGrade || $existingFinalGrade !== $finalGrade || $existingRecord['overall_grade'] !== $overallGrade) {
                                                    $updateStmt = $pdo->prepare("UPDATE student_grades SET midterm_grade = :midterm_grade, final_grade = :final_grade, overall_grade = :overall_grade, updated_at = NOW() WHERE class_id = :class_id AND student_id = :student_id");
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

                                // Modified function to check missing requirements
                                function checkMissingRequirements($studentId, $requiredActivityIds, $studentScores)
                                {
                                    foreach ($requiredActivityIds as $activityId) {
                                        if (!isset($studentScores[$studentId][$activityId]) || $studentScores[$studentId][$activityId] == 0) {
                                            return true;
                                        }
                                    }
                                    return false;
                                }

                                // New function to check if submission exists but isn't graded
                                function checkUngradedStatus($studentId, $requiredActivityIds)
                                {
                                    global $pdo; // Assuming you're using PDO for database connection

                                    foreach ($requiredActivityIds as $activityId) {
                                        $stmt = $pdo->prepare("
            SELECT score, status 
            FROM activity_submissions 
            WHERE student_id = :student_id 
            AND activity_id = :activity_id
        ");
                                        $stmt->execute([
                                            ':student_id' => $studentId,
                                            ':activity_id' => $activityId
                                        ]);

                                        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

                                        // If submission exists but score is null or status indicates not graded
                                        if (
                                            $submission &&
                                            (is_null($submission['score']) ||
                                                $submission['status'] === 'submitted' ||
                                                $submission['status'] === 'pending')
                                        ) {
                                            return 'N/A';
                                        }
                                    }
                                    return 'INC'; // Default to INC if not ungraded
                                }

                                function fetchGrade($classId, $studentId, $pdo)
                                {
                                    $stmt = $pdo->prepare("SELECT overall_grade FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
                                    $stmt->execute([':class_id' => $classId, ':student_id' => $studentId]);
                                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                                    return $record ? $record['overall_grade'] : 'N/A';
                                }

                                function calculateCombinedGrade($lecGrade, $labGrade)
                                {
                                    // Check for non-numeric statuses and return them directly
                                    if ($lecGrade === 'INC' || $labGrade === 'INC') {
                                        return 'INC';
                                    }
                                    if ($lecGrade === 'AW' || $labGrade === 'AW') {
                                        return 'AW';
                                    }
                                    if ($lecGrade === 'UW' || $labGrade === 'UW') {
                                        return 'UW';
                                    }
                                    if ($lecGrade === 'N/A' || $labGrade === 'N/A') {
                                        return 'N/A';
                                    }

                                    if (is_numeric($lecGrade) || is_numeric($labGrade)) {
                                        // Convert grades to numeric values and calculate weighted average
                                        $lecNumeric = floatval($lecGrade);
                                        $labNumeric = floatval($labGrade);
                                        $weightedAverage = ($lecNumeric * 0.6) + ($labNumeric * 0.4); // 60% Lecture, 40% Lab

                                        // Define valid grades and find the nearest one
                                        $validGrades = range(60, 100); // DepEd whole-number grades
                                        return findNearestGrade($weightedAverage, $validGrades);
                                    }
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
                                <?php
                                // Quarter / final grade cell: failing grades in red, not-yet-graded muted
                                $gbGradeCell = function ($grade, $extra = '') {
                                    $grade = ($grade === null || $grade === '') ? 'N/A' : $grade;
                                    $text = is_numeric($grade) ? deped_display($grade) : (string) $grade;
                                    $flag = deped_is_number($grade)
                                        ? ($grade < DEPED_PASSING_GRADE ? ' is-fail' : '')
                                        : ($grade === 'N/A' ? ' gb-missing' : ' is-fail');
                                    return '<td class="gb-grade' . $extra . $flag . '">' . htmlspecialchars($text) . '</td>';
                                };
                                ?>
                                <div id="printTable">
                                    <div class="gb-letterhead">
                                        <img src="../external/img/dp_logo.avif" alt="">
                                        <div>
                                            <strong>Don Pablo Lorenzo Memorial High School</strong>
                                            <span>Stand-Alone Senior High School &middot; Zamboanga City</span>
                                            <span><b>Class Record</b></span>
                                        </div>
                                        <img src="../external/img/dp_logo.avif" alt="">
                                    </div>
                                    <ul class="gb-details">
                                        <li><span>Subject</span><strong><?= htmlspecialchars($class['subject']) ?></strong></li>
                                        <li><span>Section</span><strong><?= htmlspecialchars($class['name']) ?></strong></li>
                                        <li><span>Class type</span><strong><?= htmlspecialchars($class['type']) ?></strong></li>
                                        <li><span>Teacher</span><strong><?= htmlspecialchars($class['teacher']) ?></strong></li>
                                        <li><span>Semester</span><strong><?= htmlspecialchars($class['semester']) ?></strong></li>
                                        <li><span>School year</span><strong><?= htmlspecialchars($school_year) ?></strong></li>
                                    </ul>

                                    <?php if (empty($rubricTypes)): ?>
                                        <div class="fd-empty">
                                            <i class="bi bi-sliders"></i>
                                            <p>No grading components have been set up for this class yet.</p>
                                            <p class="small mt-1">Add components such as Written Work, Performance Tasks and Quarterly Assessment, then give them weights.</p>
                                            <?php if (!$gb_archived): ?>
                                                <button type="button" class="fd-btn mt-3 gb-no-print" data-bs-toggle="modal" data-bs-target="#rubricModal"><i class="bi bi-plus-lg"></i> Set up components</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="gb-scroll">
                                            <table class="gb-table">
                                                <thead>
                                                    <tr>
                                                        <th class="gb-criteria" colspan="2" style="text-align:left">Student</th>
                                                        <?php foreach ($activityTypes as $type): ?>
                                                            <?php
                                                            $containsAttendance = stripos($type, 'Attendance') !== false;
                                                            $activityCount = isset($activitiesByType[$type]) && !empty(array_merge($activitiesByType[$type]['midterm'], $activitiesByType[$type]['final']))
                                                                ? count(array_merge($activitiesByType[$type]['midterm'], $activitiesByType[$type]['final']))
                                                                : 0;
                                                            $colspan = $activityCount + 1 + ($containsAttendance ? $totalMeetings + 1 : 0);
                                                            ?>
                                                            <th colspan="<?php echo $colspan; ?>" class="gb-group-start">
                                                                <?php echo htmlspecialchars($type); ?>
                                                                <span class="gb-weight"><?php echo rtrim(rtrim(number_format($percentiles[$type] ?? 0, 2), '0'), '.'); ?>% of grade</span>
                                                            </th>
                                                        <?php endforeach; ?>
                                                        <th rowspan="2" class="gb-grade gb-group-start">1st Qtr<span class="gb-weight">60–100</span></th>
                                                        <th rowspan="2" class="gb-grade">2nd Qtr<span class="gb-weight">60–100</span></th>
                                                        <th rowspan="2" class="gb-grade">Final<span class="gb-weight">60–100</span></th>
                                                        <?php if ($hasBothLabAndLec): ?>
                                                            <th rowspan="2" class="gb-grade">Overall<span class="gb-weight">Lec 60% + Lab 40%</span></th>
                                                        <?php endif; ?>
                                                    </tr>
                                                    <tr class="gb-label">
                                                        <th class="gb-sid">ID</th>
                                                        <th class="gb-name">Name</th>
                                                        <?php foreach ($activityTypes as $type): ?>
                                                            <?php
                                                            $typeData = $activitiesByType[$type] ?? ['midterm' => [], 'final' => [], 'max_points' => ['midterm' => 0, 'final' => 0]];
                                                            $activities = array_merge($typeData['midterm'], $typeData['final']);
                                                            foreach ($activities as $index => $activity):
                                                            ?>
                                                                <th class="<?= $index === 0 ? 'gb-group-start' : '' ?>">
                                                                    <?php echo htmlspecialchars($type[0]) . ($index + 1); ?>
                                                                    <small><?php echo htmlspecialchars($activity['max_points']); ?> pts &middot; <?php echo $activity['term'] === 'final' ? 'Q2' : ($activity['term'] === 'midterm' ? 'Q1' : htmlspecialchars(deped_quarter_label($activity['term']))); ?></small>
                                                                </th>
                                                            <?php endforeach; ?>
                                                            <th class="gb-total <?= empty($activities) ? 'gb-group-start' : '' ?>">Total<small><?php echo $typeData['max_points']['midterm'] + $typeData['max_points']['final']; ?> pts</small></th>

                                                            <?php if (stripos($type, 'Attendance') !== false): ?>
                                                                <?php foreach ($attendanceDates as $date): ?>
                                                                    <th><?php echo date('M j', strtotime($date)); ?><small><?php echo date('D', strtotime($date)); ?></small></th>
                                                                <?php endforeach; ?>
                                                                <th class="gb-total">Present<small>of <?php echo $totalMeetings; ?></small></th>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($students)): ?>
                                                        <tr>
                                                            <td colspan="99" class="gb-missing py-4">No students are enrolled in this class yet.</td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <?php foreach ($students as $student):
                                                        $gbRow = $studentGrades[$student['student_id']] ?? [];
                                                    ?>
                                                        <tr>
                                                            <td class="gb-sid"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                            <td class="gb-name"><?php echo htmlspecialchars($student['fullName']); ?></td>
                                                            <?php foreach ($activityTypes as $type): ?>
                                                                <?php
                                                                $typeData = $activitiesByType[$type] ?? ['midterm' => [], 'final' => [], 'max_points' => ['midterm' => 0, 'final' => 0]];
                                                                $activities = array_merge($typeData['midterm'], $typeData['final']);
                                                                $typeTotal = 0;
                                                                ?>
                                                                <?php foreach ($activities as $index => $activity): ?>
                                                                    <?php
                                                                    $hasScore = isset($studentScores[$student['student_id']][$activity['id']]);
                                                                    $score = $studentScores[$student['student_id']][$activity['id']] ?? 0;
                                                                    $typeTotal += (float)$score;
                                                                    ?>
                                                                    <td class="<?= $index === 0 ? 'gb-group-start' : '' ?> <?= $hasScore ? '' : 'gb-missing' ?>"><?php echo $hasScore ? htmlspecialchars($score) : '&ndash;'; ?></td>
                                                                <?php endforeach; ?>
                                                                <td class="total-score gb-total <?= empty($activities) ? 'gb-group-start' : '' ?>" data-type="<?php echo htmlspecialchars($type); ?>" data-student-id="<?php echo $student['student_id']; ?>"><?php echo $typeTotal; ?></td>

                                                                <?php if (stripos($type, 'Attendance') !== false): ?>
                                                                    <?php
                                                                    $studentAttendance = $attendanceRecords[$student['student_id']] ?? [];
                                                                    $presentCount = 0;
                                                                    foreach ($attendanceDates as $meetingId => $date):
                                                                        $status = strtolower($studentAttendance[$meetingId] ?? 'absent');
                                                                        if ($status === 'present') $presentCount++;
                                                                    ?>
                                                                        <td class="<?= $status === 'absent' ? 'gb-absent' : ($status === 'late' ? 'gb-late' : '') ?>" title="<?= ucfirst($status) ?>">
                                                                            <?php echo ['present' => 'P', 'late' => 'L', 'absent' => 'A'][$status] ?? htmlspecialchars(ucfirst($status)); ?>
                                                                        </td>
                                                                    <?php endforeach; ?>
                                                                    <td class="total-attendance gb-total" data-student-id="<?php echo $student['student_id']; ?>"><?php echo $presentCount; ?></td>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>

                                                            <?= $gbGradeCell($gbRow['midterm'] ?? 'N/A', ' gb-group-start') ?>
                                                            <?= $gbGradeCell($gbRow['final'] ?? 'N/A') ?>
                                                            <?= $gbGradeCell($gbRow['gpa'] ?? 'N/A', $hasBothLabAndLec ? '' : ' is-result') ?>
                                                            <?php if ($hasBothLabAndLec): ?>
                                                                <?= $gbGradeCell($gbRow['overallGrade'] ?? 'N/A', ' is-result') ?>
                                                            <?php endif; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="gb-legend">
                                            <span><b>W1, P1 …</b> activity number within its component</span>
                                            <span><b>Q1 / Q2</b> 1st / 2nd quarter</span>
                                            <span><b>&ndash;</b> no score yet (counts as 0)</span>
                                            <?php if ($hasAttendance): ?><span><b>P / L / A</b> present / late / absent</span><?php endif; ?>
                                            <span class="is-fail" style="color:var(--au-crimson)"><b>Red</b> below <?= DEPED_PASSING_GRADE ?> or incomplete</span>
                                        </div>
                                    <?php endif; ?>
                                </div>


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
                    </div>
                </div>
            </main>
        </div>
    </div>


<script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>

<script>
    function printDiv(divId) {
        var content = document.getElementById(divId).innerHTML;
        var printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(`
        <html>
            <head>
                <title>Class Record</title>
                <base href="${location.href}">
                <link rel="stylesheet" href="css/gradebook.css">
            </head>
            <body onload="window.print(); window.close();">
                ${content}
            </body>
        </html>
    `);
        printWindow.document.close();
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modal element
        const rubricModal = document.getElementById('rubricModal');
        if (!rubricModal) return;

        // Listen for the 'hidden.bs.modal' event
        rubricModal.addEventListener('hidden.bs.modal', function() {
            // Refresh the page when the modal is closed
            window.location.reload();
        });
    });
</script>
<?php
include('processes/server/modals.php');
?>

</body>

</html>

<?php
include('processes/server/alerts.php');
?>
