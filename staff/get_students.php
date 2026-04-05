<?php
session_start();
require_once('processes/server/conn.php');

// Check if user is logged in as teacher/adviser
if (!isset($_SESSION['teacher_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

// Validate class_id parameter
if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    die(json_encode(['error' => 'Invalid class ID']));
}

$classId = $_GET['class_id'];
$teacherId = $_SESSION['teacher_id'];

try {
    // Verify the teacher is the adviser for this class
    $verifyStmt = $pdo->prepare("
        SELECT id FROM classes 
        WHERE id = :class_id AND adviser_id = :teacher_id
    ");
    $verifyStmt->execute(['class_id' => $classId, 'teacher_id' => $teacherId]);
    
    if ($verifyStmt->rowCount() === 0) {
        die(json_encode(['error' => 'You are not the adviser for this class']));
    }

    // Get basic class info
    $classStmt = $pdo->prepare("
        SELECT classCode, name, status 
        FROM classes 
        WHERE id = :class_id
    ");
    $classStmt->execute(['class_id' => $classId]);
    $class = $classStmt->fetch(PDO::FETCH_ASSOC);

    // Get all students in this class
    $studentsStmt = $pdo->prepare("
        SELECT 
            s.id,
            s.student_id as student_number,
            s.firstName,
            s.middleName,
            s.lastName,
            s.email,
            s.phone,
            sc.status as class_status,
            (SELECT COUNT(*) FROM attendance a 
             JOIN classes_meetings cm ON a.meeting_id = cm.id 
             WHERE a.student_id = s.id AND cm.class_id = :class_id AND a.status = 'present') as present_count,
            (SELECT COUNT(*) FROM attendance a 
             JOIN classes_meetings cm ON a.meeting_id = cm.id 
             WHERE a.student_id = s.id AND cm.class_id = :class_id) as total_meetings
        FROM students s
        JOIN student_classes sc ON s.id = sc.student_id
        WHERE sc.class_id = :class_id
        ORDER BY s.lastName, s.firstName
    ");
    $studentsStmt->execute(['class_id' => $classId]);
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent notes for these students
    $notesStmt = $pdo->prepare("
        SELECT 
            an.student_id,
            an.note,
            an.created_at,
            sa.fullName as adviser_name
        FROM advisory_notes an
        JOIN staff_accounts sa ON an.created_by = sa.id
        WHERE an.class_id = :class_id
        AND an.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY an.created_at DESC
    ");
    $notesStmt->execute(['class_id' => $classId]);
    $notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize notes by student
    $studentNotes = [];
    foreach ($notes as $note) {
        $studentNotes[$note['student_id']][] = $note;
    }

    // Prepare response data
    $response = [
        'class' => $class,
        'students' => [],
        'stats' => [
            'total' => count($students),
            'active' => 0,
            'dropped' => 0,
            'transferred' => 0
        ]
    ];

    foreach ($students as $student) {
        // Calculate attendance percentage
        $attendancePercent = $student['total_meetings'] > 0 
            ? round(($student['present_count'] / $student['total_meetings']) * 100) 
            : 0;

        // Count statuses
        $response['stats'][$student['class_status']]++;

        $response['students'][] = [
            'id' => $student['id'],
            'student_number' => $student['student_number'],
            'name' => $student['lastName'] . ', ' . $student['firstName'] . ' ' . substr($student['middleName'], 0, 1) . '.',
            'email' => $student['email'],
            'phone' => $student['phone'],
            'status' => $student['class_status'],
            'attendance' => $attendancePercent,
            'notes' => $studentNotes[$student['id']] ?? []
        ];
    }

    // Return HTML response for the modal
    header('Content-Type: text/html');
    ?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h4><?= htmlspecialchars($class['classCode']) ?> - <?= htmlspecialchars($class['name']) ?></h4>
                <p class="mb-0">Status: <span class="badge bg-<?= $class['status'] == 'active' ? 'success' : 'warning' ?>">
                    <?= htmlspecialchars(ucfirst($class['status'])) ?>
                </span></p>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary" onclick="exportStudentList(<?= $classId ?>)">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-plus-circle"></i> Add Student
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-3">
                        <h6 class="card-title">Total Students</h6>
                        <h4 class="mb-0"><?= $response['stats']['total'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <h6 class="card-title">Active</h6>
                        <h4 class="mb-0"><?= $response['stats']['active'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body p-3">
                        <h6 class="card-title">Dropped</h6>
                        <h4 class="mb-0"><?= $response['stats']['dropped'] ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body p-3">
                        <h6 class="card-title">Transferred</h6>
                        <h4 class="mb-0"><?= $response['stats']['transferred'] ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Attendance</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($response['students'] as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_number']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $student['status'] == 'active' ? 'success' : ($student['status'] == 'dropped' ? 'danger' : 'warning') ?>">
                                <?= htmlspecialchars(ucfirst($student['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?= $student['attendance'] < 75 ? 'bg-danger' : 'bg-success' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $student['attendance'] ?>%" 
                                     aria-valuenow="<?= $student['attendance'] ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?= $student['attendance'] ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-envelope"></i>
                            </a>
                            <a href="tel:<?= htmlspecialchars($student['phone']) ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-telephone"></i>
                            </a>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="viewStudentDetails(<?= $student['id'] ?>, <?= $classId ?>)">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addNoteModal" 
                                           onclick="setStudentForNote(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')">
                                            <i class="bi bi-journal-text"></i> Add Note
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="changeStudentStatus(<?= $student['id'] ?>, <?= $classId ?>)">
                                            <i class="bi bi-pencil-square"></i> Change Status
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($student['notes'])): ?>
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="accordion" id="notesAccordion<?= $student['id'] ?>">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-1" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#notesCollapse<?= $student['id'] ?>">
                                            <small>Recent Notes (<?= count($student['notes']) ?>)</small>
                                        </button>
                                    </h2>
                                    <div id="notesCollapse<?= $student['id'] ?>" class="accordion-collapse collapse" 
                                         data-bs-parent="#notesAccordion<?= $student['id'] ?>">
                                        <div class="accordion-body p-2">
                                            <?php foreach ($student['notes'] as $note): ?>
                                            <div class="card mb-2">
                                                <div class="card-body p-2">
                                                    <p class="card-text mb-1"><?= htmlspecialchars($note['note']) ?></p>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y h:i A', strtotime($note['created_at'])) ?> by 
                                                        <?= htmlspecialchars($note['adviser_name']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Student Modal (empty, will be populated via another AJAX call) -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Student to Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="addStudentModalBody">
                    <!-- Content loaded via AJAX -->
                    <p>Loading student search form...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Note for <span id="studentNoteName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addNoteForm" onsubmit="submitNote(event)">
                    <div class="modal-body">
                        <input type="hidden" id="noteStudentId" name="student_id">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <div class="mb-3">
                            <label for="noteText" class="form-label">Note</label>
                            <textarea class="form-control" id="noteText" name="note" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set student for note modal
        function setStudentForNote(studentId, studentName) {
            $('#studentNoteName').text(studentName);
            $('#noteStudentId').val(studentId);
        }

        // Submit note via AJAX
        function submitNote(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('addNoteForm'));
            
            $.ajax({
                url: 'processes/adviser/add_note.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', 'Note added successfully', 'success');
                        $('#addNoteModal').modal('hide');
                        $('#addNoteForm')[0].reset();
                        // Reload student list
                        loadStudentManagement(<?= $classId ?>);
                    } else {
                        Swal.fire('Error', response.message || 'Failed to add note', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while adding the note', 'error');
                }
            });
        }

        // Export student list
        function exportStudentList(classId) {
            window.location.href = `processes/adviser/export_students.php?class_id=${classId}`;
        }

        // View student details
        function viewStudentDetails(studentId, classId) {
            // Implement this function to show student details
            console.log(`View details for student ${studentId} in class ${classId}`);
        }

        // Change student status
        function changeStudentStatus(studentId, classId) {
            // Implement this function to change student status
            console.log(`Change status for student ${studentId} in class ${classId}`);
        }

        // Initialize the add student modal content when shown
        $('#addStudentModal').on('show.bs.modal', function() {
            $.ajax({
                url: 'processes/adviser/student_search_form.php',
                type: 'GET',
                data: { class_id: <?= $classId ?> },
                success: function(response) {
                    $('#addStudentModalBody').html(response);
                },
                error: function() {
                    $('#addStudentModalBody').html('<div class="alert alert-danger">Failed to load student search form</div>');
                }
            });
        });
    </script>
    <?php
} catch (PDOException $e) {
    error_log("Database error in get_students.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading student data. Please try again.</div>';
}