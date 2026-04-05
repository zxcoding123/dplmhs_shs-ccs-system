<?php
session_start();

$meetingId = $_GET['meetingId'] ?? null;
$classId = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? null;

// Check if there's a status message in the session
$statusMessage = $_SESSION['status_message'] ?? '';

// Clear the status message after displaying it
unset($_SESSION['status_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADDU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <!-- Include SweetAlert CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <link href="../external/css/index.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid main-container">
        <img src="external/img/ccs_logo-removebg-preview.png" class="img-fluid logo">
        <h5>ADDU - Student Management System</h5>
        <h5>College of Computing Studies</h5>
        <div class="status-message">
            <?php if (!empty($statusMessage)): ?>
                <h2 class="message"><?php echo htmlspecialchars($statusMessage); ?></h2>
            <?php else: ?>
                <button id="back-button" class="proceed-btn">Pefrom Attendance Again</button>
            <?php endif; ?>
            <button id="homepage-button" class="proceed-btn">Proceed to Student Homepage</button>
            <br>


        </div>
    </div>

    <!-- Include SweetAlert JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <script>
        const backbutton = document.getElementById('back-button');

        const button = document.getElementById('homepage-button');
        const button2 = document.getElementById('attendance-button');
        // Event listener for the button click
        button.addEventListener('click', () => {
            window.location.href = "https://ccs-sms.com/students/student_dashboard.php";
        });

        // Event listener for the back button click
        backbutton.addEventListener('click', () => {
            // Use JavaScript to navigate to the referrer URL
            window.location.href = document.referrer || "https://ccs-sms.com/students/student_dashboard.php";
        });



        // Event listener for the button click
        button2.addEventListener('click', () => {
            window.location.href = "https://ccs-sms.com/attendance/attendance_student_scanning.php";
        });

        // Check if there's a message to display
        <?php if (!empty($statusMessage)): ?>
            // Show SweetAlert with the status message
            swal({
                title: "Attendance Status",
                text: "<?php echo addslashes($statusMessage); ?>", // Escape quotes for JavaScript
                icon: "<?php echo strpos($statusMessage, 'success') !== false ? 'success' : 'error'; ?>",
                button: "OK",
            }).then((value) => {
                // Redirect to dashboard after closing the alert
                window.location.href = "/capstone/students/student_dashboard.php";
            });
        <?php else: ?>
            // Redirect to dashboard if there's no message
            window.location.href = "/capstone/students/student_dashboard.php";
        <?php endif; ?>
    </script>

    <style>
        .status-message {
            text-align: center;
            margin-top: 20px;
        }

        .proceed-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .proceed-btn:hover {
            background-color: #45a049;
        }
    </style>
</body>

</html>