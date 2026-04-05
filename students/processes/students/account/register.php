<?php
session_start();
require_once '../../server/conn.php'; // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $lastName = trim($_POST['last_name']);
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $studentId = trim($_POST['student_id']);
    $course = trim($_POST['course']);
    $yearLevel = trim($_POST['year_level']);
    $gender = trim($_POST['gender']);

    // Validate inputs
    if (empty($lastName) || empty($firstName) || empty($email) || empty($password) || empty($confirmPassword) || empty($studentId) || empty($course) || empty($yearLevel)) {
        $_SESSION['STATUS'] = 'Please fill in all fields.';
        header('Location: ../student_login_page.php'); // Change to your error page
        exit();
    }

    if ($password !== $confirmPassword) {
        $_SESSION['STATUS'] = 'Passwords do not match.';
        header('Location: ../student_login_page.php'); // Change to your error page
        exit();
    }

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL statement to insert student data
    try {
        $sql = "INSERT INTO students (fullName, student_id, course, year_level, email, password, gender) 
                VALUES (:fullName, :student_id, :course, :year_level, :email, :password, :gender)";

        $stmt = $pdo->prepare($sql);
        $fullName = $firstName . ' ' . $middleName . ' ' . $lastName; // Concatenate names
        $qrCode = $_POST['uploadQR']; // Assuming the QR code is generated and uploaded as a data URL

        $stmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_STR);
        $stmt->bindParam(':course', $course, PDO::PARAM_STR);
        $stmt->bindParam(':year_level', $yearLevel, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);

        $stmt->execute();

        $_SESSION['STATUS'] = 'Account created successfully!';
        header('Location: ../../../student_login_page.php');
        exit();
    } catch (PDOException $e) {
        // Handle SQL error
        $_SESSION['STATUS'] = 'Database error: ' . $e->getMessage();
        echo $e->getMessage();
        header('Location: ../../../student_create_account.php'); 
        exit();
    }
}
