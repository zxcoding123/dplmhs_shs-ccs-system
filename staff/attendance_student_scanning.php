<?php
session_start();
require_once 'processes/server/conn.php'; // Ensure the path is correct
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
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ADNU - CCS | Student Management System</title>
        <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link href="external/css/login.css" rel="stylesheet">
        <link rel="icon" type="image/png" sizes="32x32" href="external/img/favicon-32x32.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
                <div class="container-fluid">
                    <form id="attendanceForm" method="POST">
                        <label style="text-align: left !important;" class="bold">EMAIL</label>
                        <input class="form-control" name="email" type="email" placeholder="Email" required>
                        <br>
                        <div class="mb-3">
                            <label style="text-align: left !important;" class="bold">PASSWORD</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-center">
                            <a href="../login/create_account.php" class="gb-link me-auto">Create an Account</a>
                            <a data-bs-toggle="modal" data-bs-target="#resetPasswordModal" class="gb-link">Forgot your password?</a>
                        </div>
                        <div class="container login-container-with-input">
                            <input type="submit" value="Login" class="login-btn">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
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
                                <input type="email" class="form-control" id="emailInput" name="email" placeholder="Enter your email address" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" form="resetPasswordForm" id="submitReset">Send Reset Link</button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function captureLocation(event) {
                event.preventDefault();
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        document.getElementById('latitude').value = position.coords.latitude;
                        document.getElementById('longitude').value = position.coords.longitude;
                        event.target.submit();
                    }, function(error) {
                        alert("Unable to retrieve your location.");
                        event.target.submit();
                    });
                } else {
                    alert("Geolocation is not supported by this browser.");
                    event.target.submit();
                }
            }
            document.getElementById('attendanceForm').addEventListener('submit', captureLocation);

            document.getElementById('submitReset').addEventListener('click', function() {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request.',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                        document.getElementById('resetPasswordForm').submit();
                    }
                });
            });

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
    </body>

    </html>
<?php
    exit();
}

// Handle attendance submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meetingId = $_GET['meetingId'] ?? null;
    $classId = $_GET['class_id'] ?? null;

    if ($meetingId && $classId) {
        try {
            // Fetch meeting details
            $stmt = $pdo->prepare("SELECT ADDU_radius, start_time, end_time FROM classes_meetings WHERE class_id = :class_id AND id = :meeting_id");
            $stmt->execute([':class_id' => $classId, ':meeting_id' => $meetingId]);
            $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($meetingData) {
                $ADDURadius = $meetingData['ADDU_radius'];
                $startTime = $meetingData['start_time'];
                $endTime = $meetingData['end_time'];

                $latitude = $_POST['latitude'] ?? null;
                $longitude = $_POST['longitude'] ?? null;
                $studentId = $_SESSION['student_id'];
                $date = $_GET['date'] ?? null;
                $ipAddress = $_SERVER['REMOTE_ADDR'];

                // Check if attendance already exists with the same IP address
                $checkStmt = $pdo->prepare(
                    "SELECT ip_address FROM attendance 
                     WHERE class_id = :class_id AND date = :date 
                     AND meeting_id = :meeting_id AND ip_address = :ip_address"
                );
                $checkStmt->execute([
                    ':class_id' => $classId,
                    ':date' => $date,
                    ':meeting_id' => $meetingId,
                    ':ip_address' => $ipAddress,
                ]);

                if ($checkStmt->rowCount() > 0) {
                    $_SESSION['status_message'] = "Attendance already marked with this IP address.";
                    header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date);
                    exit();
                }

                // Determine attendance status
                $currentTime = date('h:i A');
                $currentTimestamp = strtotime($currentTime);
                $startTimeStamp = strtotime($date . ' ' . $startTime);
                $endTimeStamp = strtotime($date . ' ' . $endTime);

                $status = 'absent';
                if ($currentTimestamp >= $startTimeStamp && $currentTimestamp <= ($startTimeStamp + (5 * 60))) {
                    $status = 'present';
                } elseif ($currentTimestamp > ($startTimeStamp + (5 * 60)) && $currentTimestamp <= $endTimeStamp) {
                    $status = 'late';
                } else {
                    $_SESSION['status_message'] = "Attendance cannot be updated. Time is outside the allowed schedule.";
                    header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date);
                    exit();
                }

                // Update or insert attendance record
                if ($ADDURadius === 'on') {
                    $allowedLatitude = 6.912521400586953;
                    $allowedLongitude = 122.06354845575072;
                    $radiusInMeters = 35;

                    function getDistance($lat1, $lon1, $lat2, $lon2)
                    {
                        $earthRadius = 6371000;
                        $dLat = deg2rad($lat2 - $lat1);
                        $dLon = deg2rad($lon2 - $lon1);
                        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
                        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                        return $earthRadius * $c;
                    }

                    $distance = getDistance($latitude, $longitude, $allowedLatitude, $allowedLongitude);
                    if ($distance > $radiusInMeters) {
                        $_SESSION['status_message'] = "You are not within the required location for this activity.";
                        header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date);
                        exit();
                    }

                    $stmt = $pdo->prepare(
                        "INSERT INTO attendance 
                         (student_id, class_id, date, meeting_id, status, ip_address, latitude, longitude) 
                         VALUES (:student_id, :class_id, :date, :meeting_id, :status, :ip_address, :latitude, :longitude)
                         ON DUPLICATE KEY UPDATE 
                         status = :status, ip_address = :ip_address, latitude = :latitude, longitude = :longitude"
                    );
                    $stmt->execute([
                        ':student_id' => $studentId,
                        ':class_id' => $classId,
                        ':date' => $date,
                        ':meeting_id' => $meetingId,
                        ':status' => $status,
                        ':ip_address' => $ipAddress,
                        ':latitude' => $latitude,
                        ':longitude' => $longitude
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO attendance 
                         (student_id, class_id, date, meeting_id, status, ip_address) 
                         VALUES (:student_id, :class_id, :date, :meeting_id, :status, :ip_address)
                         ON DUPLICATE KEY UPDATE 
                         status = :status, ip_address = :ip_address"
                    );
                    $stmt->execute([
                        ':student_id' => $studentId,
                        ':class_id' => $classId,
                        ':date' => $date,
                        ':meeting_id' => $meetingId,
                        ':status' => $status,
                        ':ip_address' => $ipAddress
                    ]);
                }

                $_SESSION['status_message'] = "Attendance updated successfully as: " . htmlspecialchars($status);
                header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date);
                exit();
            } else {
                echo "No data found for the specified class and meeting ID.";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Class ID and Meeting ID are required.";
    }
} else {
    header('Location: ../index.php');
}
