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
   <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>

  

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

    .announcement-card {
        border-left: 4px solid #709775;
        margin-bottom: 15px;
    }

    .class-card {
        transition: transform 0.2s;
    }

    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
                <div id="page-content-wrapper">
                    <div class="container-fluid">
                        <!-- Welcome Section -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h1><b>Adviser Dashboard</b></h1>
                                <p>Welcome back, <?php echo $_SESSION['teacher_name']; ?>! Here's an overview of your advisory responsibilities.</p>
                            </div>
                        </div>

                        <?php
                        try {

                            $teacher_name   = $_SESSION['full_name'];

                            $advisory_stmt  = $pdo->prepare("
        SELECT id, class_advising          
        FROM staff_advising
        WHERE fullName = :teacher_name
    ");
                            $advisory_stmt->execute(['teacher_name' => $teacher_name]);
                            $advisory_classes = $advisory_stmt->fetchAll(PDO::FETCH_ASSOC);


                            /* ------------------------------------------------------------
     * 2.  Build course  →  year level  →  total students
     * ------------------------------------------------------------ */
                            $student_counts = [];
                            $yearMap = [
                                '1' => '1st Year',
                                '2' => '2nd Year',
                                '3' => '3rd Year',
                                '4' => '4th Year',
                            ];

                            foreach ($advisory_classes as $cls) {
                                $class_id  = $cls['class_advising'];   // e.g. "BSIT-1A"
                                $sa_id     = $cls['id'];               // staff_advising.id


                                // Count only students linked to this adviser-section
                                $cnt_stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM students_advising sa
        JOIN students s ON s.id = sa.student_id
        WHERE sa.staff_advising_id = :sa_id
    ");
                                $cnt_stmt->execute(['sa_id' => $sa_id]);
                                $row = $cnt_stmt->fetch(PDO::FETCH_ASSOC);

                                // Store the count per CLASS (best approach)
                                $student_counts[$class_id] = (int)$row['cnt'];
                            }
                        } catch (PDOException $e) {
                            error_log('Error fetching advisory data: ' . $e->getMessage());
                            $advisory_classes = [];
                            $student_counts   = [];
                        }
                        ?>



                        <!-- Advisory Classes Summary -->
                        <div class="row mb-4">
                            <?php if (!empty($advisory_classes)): ?>
                                <?php foreach ($advisory_classes as $class): ?>
                                    <?php
                                    $class_id = $class['class_advising']; // e.g., BSIT-1A

                                    // Extract course and year level
                                    if (preg_match('/^(?<course>[A-Z]+)-(?<year>[1-4])/', $class_id, $m)) {
                                        $course    = $m['course'];
                                        $yearLabel = $yearMap[$m['year']];
                                        $stuTotal = $student_counts[$class_id] ?? 0;
                                    } else {
                                        $stuTotal = 0;
                                        $course = $yearLabel = 'Unknown';
                                    }

                                    ?>
                                    <div class="col-md-4">
                                        <div class="card class-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">Class: <?= htmlspecialchars($class_id) ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">Course and Year: <?= $course ?> - <?= $yearLabel ?></h6>
                                                <p class="card-text">
                                                    <strong>Students:</strong> <?= $stuTotal ?>
                                                </p>
                                                <div class="d-grid gap-2">
                                                    <a href="advisory_students.php?class_id=<?= urlencode($class_id) ?>"
                                                        class="btn btn-primary">
                                                        <i class="bi bi-people-fill"></i> Manage Students
                                                    </a>
                                                    <a href="class_schedule.php?class_id=<?= urlencode($class_id) ?>"
                                                        class="btn btn-info">
                                                        <i class="bi bi-calendar-week"></i> View Schedule
                                                    </a>
                                                    <button class="btn btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#newAnnouncementModal"
                                                        data-class-id="<?= htmlspecialchars($class_id) ?>">
                                                        <i class="bi bi-megaphone"></i> Post Announcement
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        You are not currently assigned as an adviser to any active classes.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Get announcements for the teacher's advisory classes
                        $announcements = [];
                        if (!empty($advisory_classes)) {
                            $class_names = array_column($advisory_classes, 'class_advising');
                            $placeholders = implode(',', array_fill(0, count($class_names), '?'));

                            $stmt = $pdo->prepare("
        SELECT a.*, s.fullName as teacher_name 
        FROM announcements a
        JOIN staff_accounts s ON a.teacher_id = s.id
        WHERE a.class_name IN ($placeholders)
        ORDER BY a.created_at DESC
    ");
                            $stmt->execute($class_names);
                            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        }
                        ?>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3 ">
                                <div class="d-flex align-items-center">
                                    <h2 class=" "><b>Class Announcements</b><br>
                                        <p class="text-secondary fs-4"><small>Post and manage your announcements here.</small></p>
                                    </h2>

                                    <div class="ms-auto" aria-hidden="true">
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                                            <i class="bi bi-file-earmark-plus-fill"></i> New Announcement
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="announcementsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Title</th>
                                                <th>Posted By</th>
                                                <th>Date Posted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($announcements as $announcement): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($announcement['class_name']) ?></td>
                                                    <td><?= htmlspecialchars($announcement['title']) ?></td>
                                                    <td><?= htmlspecialchars($announcement['teacher_name']) ?></td>
                                                    <td><?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm view-announcement"
                                                            data-title="<?= htmlspecialchars($announcement['title']) ?>"
                                                            data-content="<?= htmlspecialchars($announcement['content']) ?>"
                                                            data-class="<?= htmlspecialchars($announcement['class_name']) ?>"
                                                            data-teacher="<?= htmlspecialchars($announcement['teacher_name']) ?>"
                                                            data-date="<?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <?php if ($announcement['teacher_id'] == $_SESSION['teacher_id']): ?>
                                                            <button class="btn btn-danger btn-sm delete-announcement"
                                                                data-id="<?= $announcement['id'] ?>">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- New Announcement Modal -->
                        <div class="modal fade" id="newAnnouncementModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Create new Announcement</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form id="announcementForm" action="processes/teachers/posts/create.php" method="POST">
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="classSelect"><b>Class</b></label>
                                                <select class="form-control" id="classSelect" name="class_name" required>
                                                    <option value="">Select Class</option>
                                                    <?php foreach ($advisory_classes as $class): ?>
                                                        <option value="<?= htmlspecialchars($class['class_advising']) ?>">
                                                            <?= htmlspecialchars($class['class_advising']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <br>
                                            <div class="form-group">
                                                <label for="announcementTitle"><b>Title</b></label>
                                                <input type="text" class="form-control" id="announcementTitle" name="title" required>
                                            </div>
                                            <br>
                                            <div class="form-group">
                                                <label for="announcementContent"><b>Content</b></label>
                                                <textarea class="form-control" id="announcementContent" name="content" rows="5"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary" type="submit">Post Announcement</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <h2><b>Subject Schedules</b>
                                        <p class="text-secondary fs-4"><small>View subject schedules.</small></p>
                                    </h2>
                                    <div class="ms-auto" aria-hidden="true">
                                        <a href="class_schedule.php" class="btn btn-primary">
                                            <i class="bi bi-eye-fill"></i> View All Schedules</a>
                                    </div>
                                </div>

                                <?php
                                try {
                                    if (!empty($advisory_classes)) {
                                        echo '<div class="table-responsive">';
                                        echo '<table id="upcomingClassesTable" class="table table-bordered table-hover" style="width:100%">';
                                        echo '<thead><tr>';
                                        echo '<th>Class</th>';
                                        echo '<th>Subject</th>';
                                        echo '<th>Teacher</th>';
                                        echo '<th>Day</th>';
                                        echo '<th>Time</th>';
                                        echo '</tr></thead>';
                                        echo '<tbody>';
                                        foreach ($advisory_classes as $cls) {
                                            $classCode = $cls['class_advising']; // e.g. BSIT-4A
                                            $schedStmt = $pdo->prepare("
                        SELECT
                            sch.meeting_days,
                            sch.start_time,
                            sch.end_time,
                            sub.id          AS subject_id,
                            sub.name        AS subject_name,
                            sa.fullName     AS teacher_name
                        FROM classes            c
                        JOIN subjects           sub ON sub.id  = c.subject_id
                        JOIN subjects_schedules sch ON sch.subject_id = sub.id
                        JOIN staff_accounts     sa  ON sa.fullName   = c.teacher   
                        WHERE c.name = :class_name                             
                        ORDER BY
                            FIELD(sch.meeting_days,
                                'Monday','Tuesday','Wednesday',
                                'Thursday','Friday','Saturday','Sunday'),
                            sch.start_time
                        LIMIT 3;
                    ");
                                            $schedStmt->execute(['class_name' => $classCode]);
                                            $schedules = $schedStmt->fetchAll(PDO::FETCH_ASSOC);
                                            if ($schedules) {
                                                foreach ($schedules as $sch) {
                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($classCode) . '</td>';
                                                    echo '<td>' . htmlspecialchars($sch['subject_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($sch['teacher_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($sch['meeting_days']) . '</td>';
                                                    echo '<td>' . date('h:i A', strtotime($sch['start_time'])) .
                                                        ' - ' . date('h:i A', strtotime($sch['end_time'])) . '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5">No schedule found for ' . htmlspecialchars($classCode) . '</td></tr>';
                                            }
                                        }
                                        echo '</tbody></table></div>';
                                        echo '<div class="text-end mt-3">';

                                        echo '</div>';
                                    } else {
                                        echo '<p>No advisory classes to display schedules for.</p>';
                                    }
                                } catch (PDOException $e) {
                                    error_log('Error fetching schedules: ' . $e->getMessage());
                                    echo $e->getMessage();
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
   
    <!-- View Announcement Modal -->
    <div class="modal fade" id="viewAnnouncementModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAnnouncementTitle">Announcement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Class:</strong></label>
                        <p id="viewAnnouncementClass"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Posted By:</strong></label>
                        <p id="viewAnnouncementTeacher"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Posted On:</strong></label>
                        <p id="viewAnnouncementDate"></p>
                    </div>
                    <div class="form-group">
                        <label><strong>Content:</strong></label>
                        <div id="viewAnnouncementContent" class="border p-3 bg-light rounded"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>

    <?php include('processes/server/modals.php'); ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#announcementsTable').DataTable({
                responsive: true,
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    }, // Class
                    {
                        responsivePriority: 2,
                        targets: 1
                    }, // Title
                    {
                        responsivePriority: 3,
                        targets: 3
                    } // Date
                ]
            });



            // Delete announcement
            $(document).on('click', '.delete-announcement', function() {
                const announcementId = $(this).data('id');

                Swal.fire({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this announcement?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'processes/teachers/posts/delete.php',
                            method: 'POST',
                            data: {
                                id: announcementId
                            },
                            success: function(response) {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    Swal.fire('Deleted!', 'Announcement has been deleted.', 'success')
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire('Error', result.message, 'error');
                                }
                            }
                        });
                    }
                });
            });
        });

        // View announcement in modal
        $(document).on('click', '.view-announcement', function() {
            // Get all data attributes
            const title = $(this).data('title');
            const content = $(this).data('content');
            const className = $(this).data('class');
            const teacherName = $(this).data('teacher');
            const datePosted = $(this).data('date');

            // Set modal content
            $('#viewAnnouncementTitle').text(title);
            $('#viewAnnouncementClass').text(className);
            $('#viewAnnouncementTeacher').text(teacherName);
            $('#viewAnnouncementDate').text(datePosted);
            $('#viewAnnouncementContent').html(content.replace(/\n/g, '<br>'));

            // Show the modal
            $('#viewAnnouncementModal').modal('show');
        });


        $(document).ready(function() {
            // Initialize announcement modal with class ID
            $('#announcementModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var classId = button.data('class-id');
                $('#modalClassId').val(classId);
            });

            // Handle announcement form submission
            $('#announcementForm').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.status === 'success') {
                            Swal.fire('Success', result.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to post announcement', 'error');
                    }
                });
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            $('#upcomingClassesTable').DataTable({
                "pageLength": 5, // Show 5 entries by default
                "lengthMenu": [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                "order": [
                    [3, 'asc'],
                    [4, 'asc']
                ], // Sort by day then by time
                "columnDefs": [{
                        "orderable": true,
                        "targets": [0, 1, 2, 3, 4]
                    },
                    {
                        "className": "dt-center",
                        "targets": "_all"
                    }
                ],
                "responsive": true,
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search classes...",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "previous": "Previous",
                        "next": "Next"
                    }
                }
            });
        });
    </script>

    <script>
        function getTime() {
            const now = new Date();
            const newTime = now.toLocaleString();

            document.querySelector("#currentTime").textContent = "The current date and time is: " + newTime;
        }
        setInterval(getTime, 100);
    </script>

<script>
ClassicEditor
    .create(document.querySelector('#announcementContent'), {
        toolbar: [
            'bold', 'italic', 'underline',
            '|',
            'bulletedList', 'numberedList',
            '|',
            'link', 'blockQuote',
            '|',
            'undo', 'redo'
        ]
    })
    .catch(error => {
        console.error(error);
    });
</script>

</body>

</html>
<?php include('processes/server/alerts.php'); ?>