<?php
session_start();

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
    <title>AdNU - CCS | Student Management System</title>
    <link rel="icon" href="../external/img/favicon-32x32.png" type="image/x-icon">
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
</head>

<body>
    <h1>Attendance Status</h1>

    <!-- Include SweetAlert JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <script>
        // Check if there's a message to display
        <?php if (!empty($statusMessage)): ?>
            // Show SweetAlert with the status message
            swal({
                title: "Attendance Status",
                text: "<?php echo addslashes($statusMessage); ?>", // Escape quotes for JavaScript
                icon: "<?php echo strpos($statusMessage, 'success') !== false ? 'success' : 'error'; ?>",
                button: "OK",
            }).then((value) => {
                // Redirect to index.php after closing the alert
                window.location.href = "index.php";
            });
        <?php else: ?>
            // Redirect to index.php if there's no message
            window.location.href = "index.php";
        <?php endif; ?>
    </script>

</body>

</html>