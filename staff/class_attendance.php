<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
}
include('processes/server/conn.php');
$stmt = $pdo->prepare("
    UPDATE classes_meetings
    SET status = 'Finished'
    WHERE STR_TO_DATE(CONCAT(CURDATE(), ' ', end_time), '%Y-%m-%d %h:%i %p') < NOW()
");
$stmt->execute();
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

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

    .meeting-day {
        background-color: rgba(40, 167, 69, 0.5) !important;
        /* Light green background */
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

                        <div class="card mb-4">
                            <div class="card-header">

                            </div>
                            <div class="card-body mb-4">
                                <a href="class_management.php" class="d-flex align-items-center mb-3">
                                    <i class="bi bi-arrow-left-circle"
                                        style="font-size: 1.5rem; margin-right: 5px;"></i>
                                    <p class="m-0">Back</p>
                                </a>

                                <?php
                                require 'processes/server/conn.php';

                                $archive_status = "";
                                $class_id = $_GET['class_id'] ?? null;
                                $semester = $_GET['semester'] ?? null;

                                $classData = null;
                                if ($class_id) {
                                    // Query database to get class details
                                    $stmt = $pdo->prepare("SELECT id, name, subject, teacher, semester, is_archived, type FROM classes WHERE id = :class_id LIMIT 1");
                                    $stmt->execute(['class_id' => $class_id]);
                                    $classData = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $status = $classData['is_archived'];
                                    if ($status == 1) {
                                        $archive_status = "Archived";
                                    } else {
                                        $archive_status = "Not Archived";
                                    }
                                }

                                $semester_matcher = $classData['semester'];

                                $stmt = $pdo->prepare("SELECT school_year FROM semester WHERE name = :name");
                                $stmt->bindParam(':name', $semester_matcher, PDO::PARAM_STR);
                                $stmt->execute();
                                $semester_found = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($semester_found) {
                                    $schoolYear = $semester_found['school_year'];
                                }

                                $type = $classData['type'];

                                ?>

                                <div class="row">
                                    <h2 class="bold">Class Details</h2>
                                    <div class="col">
                                        <h3><b><i class="bi bi-person-circle" style="margin-right: 5px;"></i>
                                                Teacher:</b>
                                            <span><?php echo htmlspecialchars($classData['teacher'] ?? 'Not Assigned'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-book" style="margin-right: 5px;"></i> Subject:</b>
                                            <span><?php echo htmlspecialchars($classData['subject'] ?? 'No Subject'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-building" style="margin-right: 5px;"></i> Year and
                                                Section:</b>
                                            <span><?php echo htmlspecialchars($classData['name'] ?? 'Not Available'); ?></span>
                                        </h3>
                                    </div>
                                    <div class="col">
                                        <h3><b><i class="bi bi-calendar3" style="margin-right: 5px;"></i> Semester:</b>
                                            <span><?php echo htmlspecialchars($classData['semester'] ?? 'No Semester'); ?></span>
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-range" style="margin-right: 5px;"></i> School
                                                Year:</b>
                                            <span><?php echo $schoolYear ?> - <?php echo   $schoolYear + 1 ?></span> <!-- Replace with actual school year if dynamic -->
                                        </h3>
                                        <h3><b><i class="bi bi-calendar-check" style="margin-right: 5px;"></i> Class
                                                Type:</b> <span><?php echo htmlspecialchars($type); ?></span></h3>
                                    </div>
                                </div>

                                <br>
                                <div class="d-flex align-items-center">
                                    <h2 class="bold text-center" style="margin-bottom: 10px">Class Meetings</h2>
                                    <div class="ms-auto" aria-hidden="true">
                                        <a href="class_attendance_general.php?class_id=<?php echo $_GET['class_id'] ?>&semester_id=<?php echo $_GET['semester_id'] ?>">

                                            <button type="button" class="btn btn-primary"><i class="bi bi-eye-fill"></i> View Attendance</button>
                                        </a>
                                    </div>
                                </div>


                                <hr>
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

        </div>


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
        </script>

        <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createClassModalLabel">Create Class Meeting on <span
                                id="createModalDate"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createClassForm">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" value="<?php echo $classData['subject'] ?>"
                                    readonly required>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-control" id="type" required>
                                    <option value="" disabled selected>Select type</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Late">Late</option>
                                    <option value="Make-up">Make-up</option>
                                </select>
                            </div>


                            <div class="mb-3">
                                <label for="startTime" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="startTimeCreate" value="<?php echo $startTime ?>"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="endTime" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="endTimeCreate" value="<?php echo $endTime ?>"
                                    required>
                            </div>


                            <button type="submit" class="btn btn-primary">Create Meeting</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>



        <div class="modal modal-lg fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="classModalLabel">Classes on <span id="modalDate"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="classDetails"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editClassModalLabel">Edit Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editClassForm">

                            <div class="mb-3">

                                <label for="status" class="form-label">Class Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                    <option value="Finished">Finished</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>


                            <div class="mb-3">
                                <label for="startTime" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="startTime"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="endTime" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="endTime"
                                    required>
                            </div>


                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Retrieve `class_id` and `semester_id` from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            var classId = urlParams.get('class_id');
            var semesterId = urlParams.get('semester_id');
            var archiveStatus = "<?php echo $archive_status; ?>";

            console.log("URL:", window.location.href);
            console.log("Class ID:", classId);
            console.log("Semester ID:", semesterId);

            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },

                    // Enhanced events configuration
                    events: {
                        url: `fetch_schedule_events.php?semester_id=${semesterId}&class_id=${classId}`,
                        failure: function() {
                            console.error('Failed to load events from:', `fetch_schedule_events.php?semester_id=${semesterId}&class_id=${classId}`);
                            Swal.fire('Error', 'Could not load class schedule', 'error');
                        }
                    },

                    eventColor: '#28a745',
                    eventDisplay: 'block',

                    // Add event rendering with better visibility
                    eventDidMount: function(info) {
                        console.log('Event mounted:', info.event.title, info.event.start);

                        // Add tooltip with class details
                        const type = info.event.extendedProps?.type || 'Class';
                        const status = info.event.extendedProps?.status || 'Scheduled';
                        info.el.title = `${info.event.title}\nType: ${type}\nStatus: ${status}`;

                        // Color coding based on status
                        const statusColors = {
                            'Ongoing': '#007bff',
                            'Ended': '#6c757d',
                            'Finished': '#dc3545',
                            'Rescheduled': '#ffc107',
                            'Cancelled': '#dc3545'
                        };

                        if (statusColors[status]) {
                            info.el.style.backgroundColor = statusColors[status];
                            info.el.style.borderColor = statusColors[status];
                        }
                    },

                    // Loading state handling
                    loading: function(isLoading) {
                        if (isLoading) {
                            console.log('Loading calendar events...');
                        } else {
                            console.log('Calendar events loaded');
                            // Log all loaded events for debugging
                            const events = calendar.getEvents();
                            console.log('Total events loaded:', events.length);
                            events.forEach(event => {
                                console.log('Event:', event.title, event.start, event.end);
                            });
                        }
                    },

                    dateClick: function(info) {
                        if (archiveStatus === "Archived") {
                            Swal.fire({
                                title: `Actions for ${info.dateStr}`,
                                text: "This meeting is archived, cannot create new class.",
                                icon: 'info'
                            });
                            return;
                        }

                        Swal.fire({
                            title: `Actions for ${info.dateStr}`,
                            text: "Do you want to create a new class or view existing classes?",
                            icon: 'question',
                            showDenyButton: true,
                            confirmButtonText: 'Create New Class',
                            denyButtonText: 'View Classes'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                createClassMeeting(info.dateStr, classId);
                            } else if (result.isDenied) {
                                viewClasses(info.dateStr, classId);
                            }
                        });
                    },

                    eventClick: function(info) {
                        // Show meeting details on click
                        viewClasses(info.event.startStr.split('T')[0], classId);
                    }
                });

                // Render and debug
                calendar.render();

                // Force refresh events after a short delay to ensure DOM is ready
                setTimeout(() => {
                    console.log('Refetching events...');
                    calendar.refetchEvents();
                }, 500);

                calendar.render();
            });

            function createClassMeeting(date, classId) {
                $('#createModalDate').text(date); // Set the date in the modal
                $('#createClassForm')[0].reset(); // Reset the form
                $('#createClassModal').modal('show'); // Show the modal

                $('#createClassForm').off('submit').on('submit', function(event) {
                    event.preventDefault(); // Prevent the default form submission

                    const subject = $('#subject').val();
                    const startTime = $('#startTimeCreate').val();
                    const endTime = $('#endTimeCreate').val();
                    const type = $('#type').val();
                    const status = $('#status').val();

                    const meetingData = {
                        date: date,
                        subject: subject,
                        start_time: startTime,
                        end_time: endTime,
                        class_id: classId,
                        type: type,
                        status: status,
                    };

                    fetch('create_class_meeting.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(meetingData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                return Swal.fire('Success!', 'Class meeting created successfully!', 'success'); // Return the SweetAlert promise
                            } else {
                                return Swal.fire('Error!', 'Error creating class meeting: ' + data.message, 'error');
                            }
                        })
                        .then(() => {
                            $('#createClassModal').modal('hide');
                            location.reload(); // Reload the page after closing the modal
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            Swal.fire('Error!', 'Error creating class meeting: ' + error.message, 'error');
                        });
                });
            }


            function viewClasses(date, classId) { // Accept classId as a parameter
                $('#modalDate').text(date); // Set the date in the modal
                $('#classDetails').html('<p>Loading...</p>'); // Show loading message

                // Fetch class details for the selected date and class ID
                fetch(`fetch_class_details.php?date=${date}&class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        let detailsHtml = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="text-center">
                                <th>Date</th>
                                <th>Class Section</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Class Type</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Manage</th>
                            </tr>
                        </thead>
                        <tbody>`;

                        // Check if the 'classes' array is present in the response
                        if (data.classes && data.classes.length > 0) {
                            data.classes.forEach(cls => {
                                let statusMessage;

                                // Determine the status message based on the cls.status
                                switch (cls.status) {
                                    case 'Scheduled':
                                        statusMessage = 'This meeting is scheduled regularly.';
                                        break;
                                    case 'Ongoing':
                                        statusMessage = 'This meeting is currently ongoing.';
                                        break;
                                    case 'Ended':
                                        statusMessage = 'This meeting has ended.';
                                        break;
                                    case 'Rescheduled':
                                        statusMessage = 'This meeting has been rescheduled.';
                                        break;
                                    case 'Finished':
                                        statusMessage = 'This meeting has ended.';
                                        break;
                                    case 'Cancelled':
                                        statusMessage = 'This meeting has been cancelled.';
                                        break;
                                    default:
                                        statusMessage = 'Status unknown.';
                                }

                                // Build the HTML for each class meeting with Manage buttons
                                detailsHtml += `
                            <tr>
                                <td>${cls.date}</td>
                                <td><?php echo $classData['name'] ?></td>
                                <td>${cls.start_time}</td>
                                <td>${cls.end_time}</td>
                                <td>${cls.type}</td>
                                <td>${cls.status}</td>
                                <td>${statusMessage}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="viewAttendance(<?php echo $classData['id'] ?>, ${cls.id} , <?php echo $_GET['semester_id'] ?>)">Attendance</button>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="editClass(<?php echo $classData['id'] ?>,${cls.id})">Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteMeeting(${cls.id})">Delete</button>
                                    </div>
                                </td>
                            </tr>`;
                            });
                        } else {
                            detailsHtml += `
                    <tr>
                        <td colspan="8" class="text-center">No classes found for this date.</td>
                    </tr>`;
                        }

                        detailsHtml += `
                        </tbody>
                    </table>
                </div>`;

                        // Populate the modal body and show the modal
                        $('#classDetails').html(detailsHtml);
                        $('#classModal').modal('show');
                    })
                    .catch(error => {
                        console.error('Error fetching class details:', error);
                        $('#classDetails').html('<p>Error loading class details.</p>');
                        $('#classModal').modal('show'); // Show the modal even if there's an error
                    });
            }

            // Placeholder functions for attendance, edit, and delete actions
            function viewAttendance(classId, classAttendanceId, semesterId) {
                window.location.href = "class_attendance_qr.php?class_id=" + classId + "&classAttendanceId=" + classAttendanceId + "&semesterId=" + semesterId;
            }

            function editClass(classId, clsId) {
                // Close any open modals
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const bootstrapModal = bootstrap.Modal.getInstance(modal);
                    bootstrapModal.hide();
                });

                console.log("cls id", clsId);

                // Show the edit class status modal
                const editClassModal = new bootstrap.Modal(document.getElementById('editClassModal'));
                editClassModal.show();

                // Handle the form submission
                document.getElementById('editClassForm').addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent form default submit behavior

                    // Retrieve form values
                    const meetingId = clsId;
                    const status = document.getElementById('status').value;
                    const startTime = document.getElementById('startTime').value;
                    const endTime = document.getElementById('endTime').value;

                    // Log the data to ensure values are correct
                    console.log("meetingId: ", meetingId);
                    console.log("Status: ", status);
                    console.log("StartTime: ", startTime);
                    console.log("EndTime: ", endTime);

                    // Make the AJAX request
                    fetch('update_class_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                meetingId,
                                status,
                                startTime,
                                endTime
                            })
                        })
                        .then(response => response.json()) // Parse the response to JSON
                        .then(data => {
                            // Check the success status of the response
                            if (data.success) {
                                // Show success alert
                                Swal.fire('Success!', 'Class status updated successfully!', 'success')
                                    .then(() => {
                                        location.reload(); // Reload the page after closing the alert
                                    });
                            } else {
                                // Show error alert if the update failed
                                Swal.fire('Error!', 'Failed to update class status: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            // Log any errors during the fetch operation
                            console.error('Error updating class status:', error);
                            Swal.fire('Error!', 'An error occurred while updating the class status.', 'error');
                        });
                });

            }

            function deleteMeeting(meetingId) {
                // Show SweetAlert confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send the delete request to the server
                        fetch(`delete_meeting.php?meeting_id=${meetingId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    // Show success message and reload the page after dismissal
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'Your meeting has been deleted.',
                                        icon: 'success',
                                        willClose: () => {
                                            location.reload(); // Reload the page to refresh the meetings list
                                        }
                                    });
                                } else {
                                    // Show error message
                                    Swal.fire('Error!', 'Failed to delete meeting: ' + data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error deleting meeting:', error);
                                Swal.fire('Error!', 'An error occurred while deleting the meeting.', 'error');
                            });
                    }
                });
            }
        </script>



</html>



<?php
include('processes/server/alerts.php');
?>