<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/academic.php';

// ---------- Page data ----------
$teacher_name = $_SESSION['teacher_name'] ?? $_SESSION['full_name'] ?? '';
$advisory_classes = [];
$student_counts = [];
$announcements = [];
$schedules = [];

try {
    $advisory_stmt = $pdo->prepare("
        SELECT sa.id, sa.class_advising, COUNT(stu.id) AS student_count
        FROM staff_advising sa
        LEFT JOIN students_advising sad ON sad.staff_advising_id = sa.id
        LEFT JOIN students stu ON stu.id = sad.student_id
        WHERE sa.fullName = :teacher_name
        GROUP BY sa.id, sa.class_advising
        ORDER BY sa.class_advising
    ");
    $advisory_stmt->execute(['teacher_name' => $teacher_name]);
    $advisory_classes = $advisory_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($advisory_classes as $cls) {
        $student_counts[$cls['class_advising']] = (int) $cls['student_count'];
    }

    if (!empty($advisory_classes)) {
        $class_names = array_column($advisory_classes, 'class_advising');
        $placeholders = implode(',', array_fill(0, count($class_names), '?'));

        $stmt = $pdo->prepare("
            SELECT a.*, s.fullName AS teacher_name
            FROM announcements a
            JOIN staff_accounts s ON a.teacher_id = s.id
            WHERE a.class_name IN ($placeholders)
            ORDER BY a.created_at DESC
        ");
        $stmt->execute($class_names);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sched_stmt = $pdo->prepare("
            SELECT c.name AS class_name, sch.meeting_days, sch.start_time, sch.end_time,
                sub.name AS subject_name, sa.fullName AS teacher_name
            FROM classes c
            JOIN subjects sub ON sub.id = c.subject_id
            JOIN subjects_schedules sch ON sch.subject_id = sub.id
            JOIN staff_accounts sa ON sa.fullName = c.teacher
            WHERE c.name IN ($placeholders) AND c.is_archived = 0
            ORDER BY sch.start_time, c.name
        ");
        $sched_stmt->execute($class_names);
        $schedules = $sched_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Error fetching advisory data: ' . $e->getMessage());
}

// Set by pages that send the teacher back here (e.g. advisory_students.php)
$page_error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$total_advisees = array_sum($student_counts);
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// meeting_days can list several days ("Monday, Wednesday"), so file each row under every day it names
$schedule_by_day = array_fill_keys($week_days, []);
foreach ($schedules as $sch) {
    foreach ($week_days as $day) {
        if (stripos($sch['meeting_days'], $day) !== false) {
            $schedule_by_day[$day][] = $sch;
        }
    }
}

// Weekend columns only show when something is scheduled on them
$shown_days = array_filter($week_days, function ($day) use ($schedule_by_day) {
    return !in_array($day, ['Saturday', 'Sunday'], true) || !empty($schedule_by_day[$day]);
});
$today = date('l');
$show_section = count($advisory_classes) > 1;
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
    <link href="<?php echo csms_asset('css/dashboard.css', __DIR__); ?>" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.2.1/classic/ckeditor.js"></script>
</head>

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
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white"><b>DPLMHS</b> - Student Management System </span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php') ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="fd-hero">
                        <div>
                            <div class="fd-eyebrow">Advisory</div>
                            <h1 class="fd-title">Class Management</h1>
                            <p class="fd-subtitle">Your advisory sections, their announcements and weekly schedules.</p>
                        </div>
                        <?php if (!empty($advisory_classes)): ?>
                            <div class="fd-quick">
                                <button type="button" class="fd-btn" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                                    <i class="bi bi-megaphone"></i> New Announcement
                                </button>
                                <a class="fd-btn-ghost" href="class_schedule.php"><i class="bi bi-calendar-week"></i> All Schedules</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($page_error !== ''): ?>
                        <div class="fd-notice mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($page_error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($advisory_classes)): ?>
                        <div class="card fd-panel mb-4">
                            <div class="card-body">
                                <div class="fd-empty">
                                    <i class="bi bi-person-badge"></i>
                                    <p>You are not currently assigned as an adviser to any section.</p>
                                    <p class="small mt-1">Once the administrator assigns you a section, it will show up here.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>

                        <!-- Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-diagram-3"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= count($advisory_classes) ?></div>
                                        <div class="fd-stat-label">Advisory section<?= count($advisory_classes) === 1 ? '' : 's' ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon"><i class="bi bi-people"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= $total_advisees ?></div>
                                        <div class="fd-stat-label">Advisees</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-megaphone"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= count($announcements) ?></div>
                                        <div class="fd-stat-label">Announcements</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-xl-3">
                                <div class="card fd-stat">
                                    <span class="fd-stat-icon is-gold"><i class="bi bi-calendar-week"></i></span>
                                    <div>
                                        <div class="fd-stat-value"><?= count($schedules) ?></div>
                                        <div class="fd-stat-label">Scheduled subjects</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advisory sections -->
                        <div class="row g-3 mb-4">
                            <?php foreach ($advisory_classes as $class):
                                $class_id = $class['class_advising'];
                                $parsed = shs_parse_section($class_id);
                                $stuTotal = $student_counts[$class_id] ?? 0;
                            ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="card fd-section-card">
                                        <div class="card-body">
                                            <div class="fd-section-head">
                                                <div>
                                                    <div class="fd-section-code"><?= htmlspecialchars($class_id) ?></div>
                                                    <div class="fd-section-meta">
                                                        <?php if ($parsed): ?>
                                                            <?= htmlspecialchars(shs_strand_label($parsed['strand'])) ?><br>
                                                            <?= htmlspecialchars($parsed['grade_level']) ?> &middot; Section <?= htmlspecialchars($parsed['letter']) ?>
                                                        <?php else: ?>
                                                            Advisory section
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="fd-section-count">
                                                    <strong><?= $stuTotal ?></strong>
                                                    <span>Student<?= $stuTotal === 1 ? '' : 's' ?></span>
                                                </div>
                                            </div>
                                            <div class="fd-section-actions">
                                                <a href="advisory_students.php?class_id=<?= urlencode($class_id) ?>" class="fd-btn">
                                                    <i class="bi bi-people-fill"></i> Manage Students
                                                </a>
                                                <a href="class_schedule.php?class_id=<?= urlencode($class_id) ?>" class="fd-btn-ghost">
                                                    <i class="bi bi-calendar-week"></i> Schedule
                                                </a>
                                                <button type="button" class="fd-btn-ghost"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#newAnnouncementModal"
                                                    data-class-id="<?= htmlspecialchars($class_id) ?>">
                                                    <i class="bi bi-megaphone"></i> Announce
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Announcements -->
                        <div class="card fd-panel mb-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title"><i class="bi bi-megaphone"></i> Class Announcements <span class="fd-count"><?= count($announcements) ?></span></h5>
                                    <p class="fd-panel-sub">Posts visible to students in your advisory sections.</p>
                                </div>
                                <button type="button" class="fd-btn fd-btn-sm" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                                    <i class="bi bi-plus-lg"></i> New Announcement
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($announcements)): ?>
                                    <div class="fd-empty">
                                        <i class="bi bi-chat-square-text"></i>
                                        <p>No announcements yet. Post one to keep your advisees informed.</p>
                                    </div>
                                <?php else: ?>
                                    <table class="table fd-table" id="announcementsTable">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Section</th>
                                                <th>Posted By</th>
                                                <th>Date Posted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($announcements as $announcement):
                                                $posted = date('M d, Y h:i A', strtotime($announcement['created_at']));
                                            ?>
                                                <tr>
                                                    <td class="fd-strong"><?= htmlspecialchars($announcement['title']) ?></td>
                                                    <td><span class="fd-pill is-type"><?= htmlspecialchars($announcement['class_name']) ?></span></td>
                                                    <td><?= htmlspecialchars($announcement['teacher_name']) ?></td>
                                                    <td data-order="<?= strtotime($announcement['created_at']) ?>"><?= $posted ?></td>
                                                    <td>
                                                        <div class="fd-actions">
                                                            <button type="button" class="fd-action view-announcement"
                                                                data-title="<?= htmlspecialchars($announcement['title']) ?>"
                                                                data-content="<?= htmlspecialchars($announcement['content']) ?>"
                                                                data-class="<?= htmlspecialchars($announcement['class_name']) ?>"
                                                                data-teacher="<?= htmlspecialchars($announcement['teacher_name']) ?>"
                                                                data-date="<?= $posted ?>">
                                                                <i class="bi bi-eye"></i> View
                                                            </button>
                                                            <?php if ($announcement['teacher_id'] == $_SESSION['teacher_id']): ?>
                                                                <button type="button" class="fd-action is-danger delete-announcement"
                                                                    data-id="<?= (int) $announcement['id'] ?>">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Schedules -->
                        <div class="card fd-panel mb-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="card-title"><i class="bi bi-calendar-week"></i> Section Schedules</h5>
                                    <p class="fd-panel-sub">Subjects taught to your advisory sections this week.</p>
                                </div>
                                <a href="class_schedule.php" class="fd-btn-ghost fd-btn-sm"><i class="bi bi-eye"></i> View All Schedules</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($schedules)): ?>
                                    <div class="fd-empty">
                                        <i class="bi bi-calendar-x"></i>
                                        <p>No subject schedules have been set for your sections yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="cs-week">
                                        <?php foreach ($shown_days as $day): ?>
                                            <section class="cs-day <?= $day === $today ? 'is-today' : '' ?>">
                                                <div class="cs-day-head">
                                                    <span><?= $day ?></span>
                                                    <?php if ($day === $today): ?><span>Today</span><?php endif; ?>
                                                </div>
                                                <div class="cs-day-body">
                                                    <?php if (empty($schedule_by_day[$day])): ?>
                                                        <div class="cs-day-empty">No classes</div>
                                                    <?php else: ?>
                                                        <?php foreach ($schedule_by_day[$day] as $sch): ?>
                                                            <div class="cs-slot">
                                                                <div class="cs-slot-time">
                                                                    <?= date('g:i A', strtotime($sch['start_time'])) ?> &ndash; <?= date('g:i A', strtotime($sch['end_time'])) ?>
                                                                </div>
                                                                <div class="cs-slot-subject"><?= htmlspecialchars($sch['subject_name']) ?></div>
                                                                <div class="cs-slot-teacher"><?= htmlspecialchars($sch['teacher_name']) ?></div>
                                                                <?php if ($show_section): ?>
                                                                    <span class="fd-pill is-type"><?= htmlspecialchars($sch['class_name']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- New Announcement Modal -->
                <div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="newAnnouncementLabel">Create New Announcement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="announcementForm" action="processes/teachers/posts/create.php" method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="classSelect" class="form-label"><b>Section</b></label>
                                        <select class="form-select" id="classSelect" name="class_name" required>
                                            <option value="">Select section</option>
                                            <?php foreach ($advisory_classes as $class): ?>
                                                <option value="<?= htmlspecialchars($class['class_advising']) ?>">
                                                    <?= htmlspecialchars($class['class_advising']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="announcementTitle" class="form-label"><b>Title</b></label>
                                        <input type="text" class="form-control" id="announcementTitle" name="title" required>
                                    </div>
                                    <div class="mb-0">
                                        <label for="announcementContent" class="form-label"><b>Content</b></label>
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
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="fd-section-label">Section</div>
                            <div id="viewAnnouncementClass"></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="fd-section-label">Posted By</div>
                            <div id="viewAnnouncementTeacher"></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="fd-section-label">Posted On</div>
                            <div id="viewAnnouncementDate"></div>
                        </div>
                    </div>
                    <div class="fd-section-label">Content</div>
                    <div id="viewAnnouncementContent" class="border p-3 bg-light rounded"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo csms_asset('js/app.js', __DIR__); ?>"></script>

    <?php include('processes/server/modals.php'); ?>

    <script>
        let announcementEditor = null;

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
            .then(editor => {
                announcementEditor = editor;
            })
            .catch(error => {
                console.error(error);
            });

        $(document).ready(function() {
            $('#announcementsTable').DataTable({
                responsive: true,
                order: [
                    [3, 'desc']
                ],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 0
                    }, // Title
                    {
                        responsivePriority: 2,
                        targets: 4
                    }, // Actions
                    {
                        orderable: false,
                        targets: 4
                    }
                ],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Search announcements...'
                }
            });

            // Pre-select the section when opened from a section card
            $('#newAnnouncementModal').on('show.bs.modal', function(event) {
                const classId = $(event.relatedTarget).data('class-id');
                $('#classSelect').val(classId || '');
            });

            // Delete announcement
            $(document).on('click', '.delete-announcement', function() {
                const announcementId = $(this).data('id');

                Swal.fire({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this announcement?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#a3191b',
                    cancelButtonColor: '#6c757d',
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

            // Handle announcement form submission
            $('#announcementForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                if (announcementEditor) {
                    announcementEditor.updateSourceElement();
                }

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        const result = JSON.parse(response);
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

        // View announcement in modal
        $(document).on('click', '.view-announcement', function() {
            $('#viewAnnouncementTitle').text($(this).data('title'));
            $('#viewAnnouncementClass').text($(this).data('class'));
            $('#viewAnnouncementTeacher').text($(this).data('teacher'));
            $('#viewAnnouncementDate').text($(this).data('date'));
            $('#viewAnnouncementContent').html(String($(this).data('content')).replace(/\n/g, '<br>'));

            $('#viewAnnouncementModal').modal('show');
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

<!-- Floating Chat Widget -->

</body>

</html>
<?php include('processes/server/alerts.php'); ?>
