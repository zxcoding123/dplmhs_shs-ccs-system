<?php
session_start();
require 'processes/server/conn.php'; // Include your PDO connection setup

// Assuming you have a function to check if the user is authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate the inputs
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if ($newPassword === $confirmPassword) {
        // Hash the new password using bcrypt
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Get the student ID from the session
        $studentId = $_SESSION['student_id'];

        try {
            // Prepare the SQL statement to update the password for the specific student
            $sql = "UPDATE students SET password = :password WHERE student_id = :student_id";
            $stmt = $pdo->prepare($sql);

            // Bind the parameters to the query
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_STR);  // Assuming student_id is a string, if integer use PDO::PARAM_INT

            // Execute the query
            $stmt->execute();

            // Notify the user of success
      $_SESSION['STATUS'] = "NEW_PASS_OK";
        } catch (PDOException $e) {
            // Handle any errors with a message
            echo "Error updating password: " . $e->getMessage();
            $_SESSION['STATUS'] = "NEW_PASS_NOT";
        }
    } else {
        // If the passwords don't match, show an error
        echo "Passwords do not match.";
    }
}
header("Location: student_dashboard.php"); // Change this to your desired page
?>

?>
