<?php
session_start();
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
    include('processes/server/alerts.php');
    ?>

<?php
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the class_id and meetingId from the URL
    $meetingId = $_GET['meetingId'] ?? null;
    $classId = $_GET['class_id'] ?? null;

    // Check if the necessary variables are set
    if ($meetingId && $classId) {
        try {
            // Assuming $pdo is your PDO connection
            $sql = "SELECT ADDU_radius FROM classes_meetings WHERE class_id = :class_id AND id = :meeting_id";
            $stmt = $pdo->prepare($sql);

            // Bind the parameters
            $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
            $stmt->bindParam(':meeting_id', $meetingId, PDO::PARAM_INT);

            // Execute the query
            $stmt->execute();

            // Check if a row was returned
            if ($stmt->rowCount() > 0) {
                // Fetch the data
                $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);

                // Get the ADDU_radius value
                $ADDURadius = $meetingData['ADDU_radius'] ?? null;

                // Check the ADDU_radius value and echo the appropriate message
                if ($ADDURadius === 'on') {
                    function updateAttendance($studentId, $classId, $date, $startTime, $endTime, $meetingId, $latitude, $longitude)
                    {
                        global $pdo; // Ensure you use your PDO connection
                        $currentTime = date('h:i A');
                        $currentTimestamp = strtotime($currentTime);
                        $startTimeStamp = strtotime($date . ' ' . $startTime);
                        $endTimeStamp = strtotime($date . ' ' . $endTime);

                        // Define the allowed GPS location coordinates for the class (the location you shared)

                        // ADDU GPS

                        $allowedLatitude = 6.912521400586953;
                        $allowedLongitude = 122.06354845575072;

                        // $allowedLatitude = 6.9366084; 
                        // $allowedLongitude = 122.0842319;

                        // Define the acceptable radius in meters (say, 50 meters within the defined latitude and longitude)
                        $radiusInMeters = 35;

                        // Function to calculate distance between two lat/long points using the Haversine formula
                        function getDistance($lat1, $lon1, $lat2, $lon2)
                        {
                            $earthRadius = 6371000; // Earth radius in meters
                            $dLat = deg2rad($lat2 - $lat1);  // Convert degrees to radians
                            $dLon = deg2rad($lon2 - $lon1);

                            // Calculate the distance
                            $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
                            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                            $distance = $earthRadius * $c; // Distance in meters

                            return $distance;
                        }

                        // Get the current distance from the student's location to the allowed location
                        $distance = getDistance($latitude, $longitude, $allowedLatitude, $allowedLongitude);

                        // If the student is not within the allowed area
                        if ($distance > $radiusInMeters) {
                            $_SESSION['status_message'] = "You are not within the required location for this activity.";
                            $meetingId = $_GET['meetingId'] ?? null;
                            $classId = $_GET['class_id'] ?? null;
                            $date = $_GET['date'] ?? null;
                            header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                            exit();
                        }

                        // Get the user's IP address
                        $ipAddress = $_SERVER['REMOTE_ADDR'];

                        // Check if a record already exists with the same IP address
                        $stmt = $pdo->prepare(
                            "SELECT ip_address FROM attendance 
                         WHERE student_id = :student_id AND class_id = :class_id AND date = :date 
                         AND meeting_id = :meeting_id AND ip_address = :ip_address"
                        );
                        $stmt->execute([
                            ':student_id' => $studentId,
                            ':class_id' => $classId,
                            ':date' => $date,
                            ':meeting_id' => $meetingId,
                            ':ip_address' => $ipAddress,
                        ]);

                        if ($stmt->rowCount() > 0) {
                            // If IP address already exists, set an error message and stop execution
                            $_SESSION['status_message'] = "Attendance already marked with this IP address.";
                            $meetingId = $_GET['meetingId'] ?? null;
                            $classId = $_GET['class_id'] ?? null;
                            $date = $_GET['date'] ?? null;
                            header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                            exit();
                        }

                        // Determine the status for the update
                        $status = 'absent'; // Default status
                        if ($currentTimestamp >= $startTimeStamp && $currentTimestamp <= ($startTimeStamp + (5 * 60))) {
                            $status = 'present'; // Present if within 5 minutes after the start time
                        } elseif ($currentTimestamp > ($startTimeStamp + (5 * 60)) && $currentTimestamp <= $endTimeStamp) {
                            $status = 'late'; // Late if after 5 minutes but before the end time
                        } else {
                            $_SESSION['status_message'] = "Attendance cannot be updated. Time is outside the allowed schedule.";

                            header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                            exit();
                        }

                        // Update the attendance record in the database and include IP address
                        $stmt = $pdo->prepare(
                            "UPDATE attendance 
                         SET status = :status, ip_address = :ip_address 
                         WHERE student_id = :student_id AND meeting_id = :meeting_id 
                         AND class_id = :class_id AND date = :date"
                        );
                        $stmt->execute([
                            ':status' => $status,
                            ':ip_address' => $ipAddress,
                            ':student_id' => $studentId,
                            ':meeting_id' => $meetingId,
                            ':class_id' => $classId,
                            ':date' => $date,
                        ]);



                        $_SESSION['status_message'] = "Attendance updated successfully as: " . htmlspecialchars(ucfirst($status));
                        header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                        exit();
                    }
                } elseif ($ADDURadius === 'off') {
                    function updateAttendance($studentId, $classId, $date, $startTime, $endTime, $meetingId)
                    {
                        global $pdo; // Ensure you use your PDO connection
                        $currentTime = date('h:i A');
                        $currentTimestamp = strtotime($currentTime);
                        $startTimeStamp = strtotime($date . ' ' . $startTime);
                        $endTimeStamp = strtotime($date . ' ' . $endTime);



                        // Get the user's IP address
                        $ipAddress = $_SERVER['REMOTE_ADDR'];

                        // Check if a record already exists with the same IP address
                        $stmt = $pdo->prepare(
                            "SELECT ip_address FROM attendance 
                         WHERE class_id = :class_id AND date = :date 
                         AND meeting_id = :meeting_id AND ip_address = :ip_address"
                        );
                        $stmt->execute([
                            ':class_id' => $classId,
                            ':date' => $date,
                            ':meeting_id' => $meetingId,
                            ':ip_address' => $ipAddress,
                        ]);

                        if ($stmt->rowCount() > 0) {
                            // If IP address already exists, set an error message and stop execution
                            $_SESSION['status_message'] = "Attendance already marked with this IP address.";
                            $meetingId = $_GET['meetingId'] ?? null;
                            $classId = $_GET['class_id'] ?? null;
                            $date = $_GET['date'] ?? null;
                            header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                            exit();
                        }

                        // Determine the status for the update
                        $status = 'absent'; // Default status
                        if ($currentTimestamp >= $startTimeStamp && $currentTimestamp <= ($startTimeStamp + (5 * 60))) {
                            $status = 'present'; // Present if within 5 minutes after the start time
                        } elseif ($currentTimestamp > ($startTimeStamp + (5 * 60)) && $currentTimestamp <= $endTimeStamp) {
                            $status = 'late'; // Late if after 5 minutes but before the end time
                        } else {
                            $_SESSION['status_message'] = "Attendance cannot be updated. Time is outside the allowed schedule.";

                            header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                            exit();
                        }

                        // Update the attendance record in the database and include IP address
                        $stmt = $pdo->prepare(
                            "UPDATE attendance 
                         SET status = :status, ip_address = :ip_address 
                         WHERE meeting_id = :meeting_id 
                         AND class_id = :class_id AND date = :date"
                        );
                        $stmt->execute([
                            ':status' => $status,
                            ':ip_address' => $ipAddress,

                            ':meeting_id' => $meetingId,
                            ':class_id' => $classId,
                            ':date' => $date,
                        ]);



                        $_SESSION['status_message'] = "Attendance updated successfully as: " . htmlspecialchars($status);
                        header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
                        exit();
                    }
                }
            } else {
                echo "No data found for the specified class and meeting ID.";
            }
        } catch (PDOException $e) {
            // Handle database connection errors
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Class ID and Meeting ID are required.";
    }


    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $studentId = $_SESSION['student_id'];
    $meetingId = $_GET['meetingId'] ?? null;
    $classId = $_GET['class_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM classes_meetings WHERE id = :meetingId");
    $stmt->execute([':meetingId' => $meetingId]);
    $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);

    $startTime = $meetingData['start_time'];
    $endTime = $meetingData['end_time'];

    var_dump($latitude, $longitude, $studentId, $meetingId, $classId, $date, $startTime, $endTime);


    // Validate QR data
    if ($classId && $date && $startTime && $endTime) {
        // Call the function to update attendance
        $meetingId = $_GET['meetingId'] ?? null;
        $classId = $_GET['class_id'] ?? null;

        // Check if the necessary variables are set
        if ($meetingId && $classId) {
            try {
                // Assuming $pdo is your PDO connection
                $sql = "SELECT ADDU_radius FROM classes_meetings WHERE class_id = :class_id AND id = :meeting_id";
                $stmt = $pdo->prepare($sql);

                // Bind the parameters
                $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
                $stmt->bindParam(':meeting_id', $meetingId, PDO::PARAM_INT);

                // Execute the query
                $stmt->execute();

                // Check if a row was returned
                if ($stmt->rowCount() > 0) {
                    // Fetch the data
                    $meetingData = $stmt->fetch(PDO::FETCH_ASSOC);



                    // Check the ADDU_radius value and echo the appropriate message
                    if ($ADDURadius === 'on') {
                        updateAttendance($studentId, $classId, $date, $startTime, $endTime, $meetingId, $latitude, $longitude);
                    } elseif ($ADDURadius === 'off') {
                        updateAttendance($studentId, $classId, $date, $startTime, $endTime, $meetingId);
                    } else {
                        echo "Invalid ADDU_radius value";  // Handle unexpected values
                    }
                } else {
                    echo "No data found for the specified class and meeting ID.";
                }
            } catch (PDOException $e) {
                // Handle database connection errors
                echo "Error: " . $e->getMessage();
            }
        } else {
            echo "Class ID and Meeting ID are required.";
        }
    } else {
        $meetingId = $_GET['meetingId'] ?? null;
        $classId = $_GET['class_id'] ?? null;
        $date = $_GET['date'] ?? null;
        $_SESSION['status_message'] = "Invalid attendance data.";
        header('Location: ../students/attendance_status.php?meetingId=' . $meetingId . '&classId=' . $classId . '&date=' . $date); // Redirect to the status page
    }
} else {
    header('Location: ../index.php');
}
