<?php
include('../../server/conn.php');
session_start(); 
date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user input
    $username_or_email = htmlspecialchars($_POST['username_email']);
    $password_account = $_POST['password'];

    try {
        // Create a new PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare SQL to check if the username or email exists
        $sql = "SELECT * FROM staff_accounts WHERE email = :username_or_email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username_or_email', $username_or_email);
        $stmt->execute();

        // Fetch the user's record
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($teacher && password_verify($password_account, $teacher['password'])) {
            // Successful login
            $_SESSION['user_id'] = $teacher['id'];
            $_SESSION['teacher_id'] = $teacher['id'];
            $_SESSION['teacher_name'] = $teacher['fullName'];
            $_SESSION['email'] = $teacher['email'];
            $_SESSION['user_type'] = 'staff';
            $_SESSION['name'] = $teacher['fullName'];
            $_SESSION['STATUS'] = "TEACHER_LOGIN_SUCCESFUL";

            // Redirect to the dashboard
            header("Location: ../../../index.php");
            exit;
        } else {
            // Invalid login credentials
            $_SESSION['STATUS'] = 'STAFF_INVALID_LOGIN';
            header("Location: ../../../index.php"); 
            exit;
        }
    } catch (PDOException $e) {
        // Handle any connection errors
        $_SESSION['STATUS'] = 'STAFF_LOGIN_ERROR';
        header("Location: ../../../index.php"); 
        exit;
    }
}
?>
