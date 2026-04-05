<?php
session_start();
include('processes/server/alerts.php');
require_once 'processes/server/conn.php'; // Make sure the path is correct
date_default_timezone_set('Asia/Manila'); // Set the timezone for the script

// Destroy any existing session on page load to force login again
session_destroy(); // This will destroy the session
session_start();   // Restart session to initiate the login flow again


// Check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';


    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Check if the account is inactive
        if ($student['status'] == 'inactive') {
            $_SESSION['STATUS'] = "INACTIVE_ACCOUNT";
            header('Location: ../../login/index.php');
            exit;
        }

        // Verify the provided password
        if (password_verify($password, $student['password'])) {
            // Set session variables for the logged-in student
            $_SESSION['user_id'] = $student['student_id'];
            $_SESSION['fullName'] = $student['fullName'];
            $_SESSION['name'] = $student['fullName'];
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['course'] = $student['course'];
            $_SESSION['year_level'] = $student['year_level'];
            $_SESSION['STATUS'] = "STUDENT_LOGIN_SUCCESSFUL";
        } else {
            // If password verification fails
            $_SESSION['STATUS'] = "LOGIN_ERROR";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        // If no student account matches the email
        $_SESSION['STATUS'] = "EMAIL_NOT_FOUND";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}


// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    // Show login form if not logged in
?>
    <!DOCTYPE html>
    <html>

    <head>

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>ADDU - CCS | Student Management System</title>
            <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
                integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <link href="external/css/login.css" rel="stylesheet">
            <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link
                href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
                rel="stylesheet">
        </head>
    </head>

    <body>
        <?php if (isset($loginError)) {
            echo "<p style='color:red;'>$loginError</p>";
        } ?>
        <div class="container-fluid login-container">

            <div class="actual-login-container">
                <small><a href="index.html" class="gb"><i class="bi bi-arrow-left-circle-fill"></i> Go back</a></small>
                <img src="external/img/ADDU_Logo-removebg-preview.png" class="img-fluid big-logo">
                <h5 class="bold">STUDENT LOGIN</h5>

                <div class="container-fluid ">
                    <form id="attendanceForm" method="POST">
                        <label style="text-align: left !important;" class="bold">EMAIL</label>
                        <input class="form-control" name="email" type="email" placeholder="Email" required>
                        <br>
                        <div class="mb-3">
                            <label style="text-align: left !important;" class="bold">PASSWORD</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Create a password" required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                </div>

                <div class="d-flex justify-content-between text-center">
                    <a href="../login/create_account.php" class="gb-link me-auto" class="gb">Create an Account</a> &nbsp;
                    <a data-bs-toggle="modal" data-bs-target="#resetPasswordModal" class="gb-link">Forgot your password?</a>

                </div>
                <div class="container login-container-with-input">
                    <input type="submit" value="Login" class="login-btn">
                </div>
            </div>

            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            </form>
        </div>
    </body>

    </html>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm" action="processes/reset.php" method="POST">
                        <div class="mb-3">
                            <label for="emailInput" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="emailInput" name="email"
                                placeholder="Enter your email address" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" form="resetPasswordForm" id="submitReset">Send Reset
                        Link</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <script>
        function captureLocation(event) {
            event.preventDefault(); // Prevent form from submitting immediately

            // Use geolocation API to get the user's current position
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;

                    // Assign latitude and longitude to hidden inputs
                    document.getElementById('latitude').value = latitude;
                    document.getElementById('longitude').value = longitude;

                    // Submit the form after geolocation data is set
                    event.target.submit();
                }, function(error) {
                    alert("Unable to retrieve your location.");
                    event.target.submit(); // Submit anyway if geolocation fails (without coordinates)
                });
            } else {
                alert("Geolocation is not supported by this browser.");
                event.target.submit(); // Submit anyway
            }
        }

        document.getElementById('attendanceForm').addEventListener('submit', captureLocation);
    </script>

    <script>
        document.getElementById('submitReset').addEventListener('click', function() {
            // Show SweetAlert2 modal
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading(); // Show loading indicator
                    // Submit the form after showing the alert
                    document.getElementById('resetPasswordForm').submit();
                }
            });
        });
    </script>

    <script>
        function togglePassword(fieldId, toggleButton) {
            const field = document.getElementById(fieldId);
            const icon = toggleButton.querySelector("i");

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            }
        }
    </script>



<?php
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['meetingId'])) {
    $meetingId = $_GET['meetingId'] ?? null;
    $classId = $_GET['class_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;


    $studentId = $_SESSION['student_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'];



    if (!$meetingId || !$classId || !$date) {
        $_SESSION['status_message'] = "Missing required attendance parameters";
        header("Location: ../students/attendance_status.php?meetingId={$meetingId}&classId={$classId}&date={$date}");
        exit;
    }




    // Replace the existing attendance recording section with this:

    try {
        // First check if IP address already exists for this meeting (excluding current student)
        $ipCheckStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM attendance 
         WHERE class_id = :class_id 
         AND meeting_id = :meeting_id 
         AND date = :date 
         AND ip_address = :ip_address
         AND student_id != :student_id"
        );
        $ipCheckStmt->execute([
            ':class_id' => $classId,
            ':meeting_id' => $meetingId,
            ':date' => $date,
            ':ip_address' => $ipAddress,
            ':student_id' => $studentId
        ]);

        if ($ipCheckStmt->fetchColumn() > 0) {
            $_SESSION['status_message'] = "Attendance cannot be recorded: This IP address has already been used for this session";
            header("Location: ../students/attendance_status.php?meetingId={$meetingId}&classId={$classId}&date={$date}");
            exit;
        }

        // Get meeting details
        $stmt = $pdo->prepare(
            "SELECT start_time, end_time, ADDU_radius 
         FROM classes_meetings 
         WHERE id = :meetingId AND class_id = :classId"
        );
        $stmt->execute([':meetingId' => $meetingId, ':classId' => $classId]);
        $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$meetingData) {
            $_SESSION['status_message'] = "Invalid meeting or class ID";
            header("Location: ../students/attendance_status.php?meetingId={$meetingId}&classId={$classId}&date={$date}");
            exit;
        }

        $startTime = $meetingData['start_time'];
        $endTime = $meetingData['end_time'];
        $ADDURadius = $meetingData['ADDU_radius'];

        // Modified recordAttendance function
        function recordAttendance($pdo, $studentId, $classId, $date, $startTime, $endTime, $meetingId, $ipAddress, $latitude = null, $longitude = null)
        {
            $currentTime = date('h:i A');
            $currentTimestamp = strtotime($currentTime);
            $startTimeStamp = strtotime($date . ' ' . $startTime);
            $endTimeStamp = strtotime($date . ' ' . $endTime);

            // Determine status
            $status = 'absent';
            if ($currentTimestamp >= $startTimeStamp && $currentTimestamp <= ($startTimeStamp + 300)) { // 5 minutes
                $status = 'present';
            } elseif ($currentTimestamp > ($startTimeStamp + 300) && $currentTimestamp <= $endTimeStamp) {
                $status = 'late';
            } else {
                $_SESSION['status_message'] = "Attendance cannot be updated. Time is outside the allowed schedule";
                return false;
            }

            // Check if attendance record already exists
            $checkStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM attendance 
             WHERE student_id = :student_id 
             AND class_id = :class_id 
             AND meeting_id = :meeting_id"
            );
            $checkStmt->execute([
                ':student_id' => $studentId,
                ':class_id' => $classId,
                ':meeting_id' => $meetingId
            ]);

            $params = [
                ':student_id' => $studentId,
                ':class_id' => $classId,
                ':date' => $date,
                ':meeting_id' => $meetingId,
                ':status' => $status,
                ':ip_address' => $ipAddress
            ];

            if ($checkStmt->fetchColumn() > 0) {
                // Update existing record
                if ($latitude && $longitude) {

                    $sql = "UPDATE attendance 
                        SET status = :status,
                            ip_address = :ip_address,
                          
                            date = :date
                        WHERE student_id = :student_id 
                        AND class_id = :class_id 
                        AND meeting_id = :meeting_id";
                } else {
                    $sql = "UPDATE attendance 
                        SET status = :status,
                            ip_address = :ip_address,
                            date = :date
                        WHERE student_id = :student_id 
                        AND class_id = :class_id 
                        AND meeting_id = :meeting_id";
                }
            } else {
                // Insert new record
                if ($latitude && $longitude) {

                    $sql = "INSERT INTO attendance 
                        (student_id, class_id, date, meeting_id, status, ip_address)
                        VALUES 
                        (:student_id, :class_id, :date, :meeting_id, :status, :ip_address)";
                } else {
                    $sql = "INSERT INTO attendance 
                        (student_id, class_id, date, meeting_id, status, ip_address)
                        VALUES 
                        (:student_id, :class_id, :date, :meeting_id, :status, :ip_address)";
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $status;
        }

        // Process attendance based on radius setting
        if ($ADDURadius === 'on') {

            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;

            // Add GPS check
            if (empty($latitude) || empty($longitude) || $latitude == 0 || $longitude == 0) {
                $_SESSION['status_message'] = "GPS location is required. Please enable your location services";
                header("Location: ../students/attendance_status.php?meetingId={$meetingId}&classId={$classId}&date={$date}");
                exit;
            }


            function getDistance($lat1, $lon1, $lat2, $lon2)
            {
                $earthRadius = 6371000;
                $dLat = deg2rad($lat2 - $lat1);
                $dLon = deg2rad($lon2 - $lon1);
                $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                return $earthRadius * $c;
            }

            $allowedLatitude = 6.912526470449727;
            $allowedLongitude = 122.06363439095645;
            $radiusInMeters = 100;

            $distance = getDistance($latitude, $longitude, $allowedLatitude, $allowedLongitude);
            if ($distance > $radiusInMeters) {
                $_SESSION['status_message'] = "You are not within the required location for this activity.";

                echo $distance;

                echo $radiusInMeters;


                header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date);
                exit();
            }

            if (!$latitude || !$longitude) {
                $_SESSION['status_message'] = "Location data required for this attendance";
            } else {




                $allowedLatitude = 6.912526470449727;
                $allowedLongitude = 122.06363439095645;
                $radiusInMeters = 100;

                $distance = getDistance($latitude, $longitude, $allowedLatitude, $allowedLongitude);
                if ($distance > $radiusInMeters) {
                    $_SESSION['status_message'] = "You are not within the required location";
                } else {
                    $status = recordAttendance($pdo, $studentId, $classId, $date, $startTime, $endTime, $meetingId, $ipAddress, $latitude, $longitude);
                    if ($status) {
                        $_SESSION['status_message'] = "Attendance recorded successfully as: " . htmlspecialchars($status);
                    }
                }
            }
        } else {
            $status = recordAttendance($pdo, $studentId, $classId, $date, $startTime, $endTime, $meetingId, $ipAddress);
            if ($status) {
                $_SESSION['status_message'] = "Attendance recorded successfully as: " . htmlspecialchars($status);
            }
        }
    } catch (PDOException $e) {
        $_SESSION['status_message'] = "Database error: " . $e->getMessage();
    }
}

header("Location: ../students/attendance_status.php?meetingId={$meetingId}&classId={$classId}&date={$date}");
exit;
