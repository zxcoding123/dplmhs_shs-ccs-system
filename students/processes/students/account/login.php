<?php
session_start();
require_once '../../server/conn.php'; // Ensure this path is correct

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);  // Assuming the password field in the form is 'password'

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Directly check if the user exists and verify the password
    if ($user) {
        // Check if the provided password matches the hashed password in the database
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['fullName'] = $user['fullName'];
            $_SESSION['name'] = $user['fullName'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['course'] = $user['course'];
            $_SESSION['year_level'] = $user['year_level'];
            $_SESSION['user_type'] = 'student';

            // Redirect to the dashboard or home page
            header("Location: ../../../student_dashboard.php"); // Change to your dashboard file
            exit();
        } else {
            // If password is incorrect
            $error = "Invalid password.";
        }
    } else {
        // If user is not found
        $error = "User not found.";
    }
}
?>
