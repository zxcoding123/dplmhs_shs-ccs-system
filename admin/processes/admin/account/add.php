<?php
require '../../server/conn.php'; // Ensure this sets up $pdo as a PDO object
session_start();

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if $pdo is defined and connected
if (!$pdo) {
    die("Database connection failed. Check conn.php.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Log POST data
    file_put_contents('debug.log', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

    // Retrieve and sanitize input data
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $firstName = filter_var($_POST['first_name'] ?? '', FILTER_SANITIZE_STRING);
    $middleName = filter_var($_POST['middle_name'] ?? '', FILTER_SANITIZE_STRING);
    $lastName = filter_var($_POST['last_name'] ?? '', FILTER_SANITIZE_STRING);
    $phoneNumber = filter_var($_POST['phone_number'] ?? '', FILTER_SANITIZE_STRING);
    $gender = filter_var($_POST['gender'] ?? '', FILTER_SANITIZE_STRING);
    $dateCreated = date('Y-m-d H:i:s');
    // Calculate fullName
    $fullName = trim("$firstName $middleName $lastName"); // Combine names, trim extra spaces

    // Basic validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['STATUS'] = "INVALID_EMAIL";
        header("Location: ../../../admin_management.php");
        exit;
    }
    if (empty($firstName) || empty($lastName) || empty($_POST['password'])) {
        $_SESSION['STATUS'] = "MISSING_REQUIRED_FIELDS";
        header("Location: ../../../admin_management.php");
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $duplicateCount = $stmt->fetchColumn();

        if ($duplicateCount > 0) {
            $_SESSION['STATUS'] = "ADMIN_DUPLICATE_ACCOUNT";
            header("Location: ../../../admin_management.php");
            exit;
        }

        // Prepare and execute insert query, including fullName
        $stmt = $pdo->prepare("
            INSERT INTO admin (email, password, first_name, middle_name, last_name, phone_number, gender, date_created, fullName) 
            VALUES (:email, :password, :first_name, :middle_name, :last_name, :phone_number, :gender, :date_created, :fullName)
        ");

        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':middle_name', $middleName, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phoneNumber, PDO::PARAM_STR);
        $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
        $stmt->bindParam(':date_created', $dateCreated, PDO::PARAM_STR);
        $stmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "ACCOUNT_C_SUCCESSFUL";
        } else {
            $_SESSION['STATUS'] = "ADMIN_CREATE_FAILED";
            file_put_contents('debug.log', "Insert failed: " . print_r($stmt->errorInfo(), true) . "\n", FILE_APPEND);
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ADMIN_CREATE_ERROR: " . $e->getMessage();
        file_put_contents('debug.log', "PDO Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    header("Location: ../../../admin_management.php");
    exit;
} else {
    $_SESSION['STATUS'] = "INVALID_REQUEST";
    header("Location: ../../../admin_management.php");
    exit;
}
?>