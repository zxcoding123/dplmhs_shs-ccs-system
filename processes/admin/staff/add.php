<?php
include('../../server/conn.php'); 
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $class = $_POST['class'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];

    // Check if password and confirm password match
    if ($password !== $confirm_password) {
        $_SESSION['STATUS'] = "PASSWORD_MISMATCH";
        header('Location: ../../../staff_management.php');
        exit;
    }

    try {
        // Check if email already exists in the database
        $checkEmailSql = "SELECT * FROM staff_accounts WHERE email = :email";
        $checkStmt = $pdo->prepare($checkEmailSql);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            // Email already exists
            $_SESSION['STATUS'] = "STAFF_EMAIL_EXISTS";
            header('Location: ../../../staff_management.php');
            exit;
        }

        // SQL query to insert a new staff account
        $sql = "INSERT INTO staff_accounts (fullName, department, email, password, class, phone_number, gender) 
                VALUES (:fullName, :department, :email, :password, :class, :phone_number, :gender)";

        // Prepare statement
        $stmt = $pdo->prepare($sql);

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':class', $class);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':gender', $gender);

        // Execute and handle result
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "STAFF_ADDED_SUCCESSFULLY";
            header('Location: ../../../staff_management.php');
        } else {
            $_SESSION['STATUS'] = "STAFF_ADDED_ERROR";
            header('Location: ../../../staff_management.php');
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "STAFF_ADDED_ERROR";
        // Optionally, log or echo the error during development
        // echo "Error: " . $e->getMessage();
        header('Location: ../../../staff_management.php');
    }
}
