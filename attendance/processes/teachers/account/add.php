<?php
session_start();
require_once '../../server/conn.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phone_number'];
    $password = $_POST['password'];
    $gender = $_POST['gender'];

    // Create full name by combining first, middle, and last names
    $fullName = trim("$firstName $middleName $lastName");

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if the account already exists
    $checkSql = "SELECT * FROM staff_accounts WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        $_SESSION['STATUS'] = "STAFF_ACCOUNT_EXISTS";
        header('Location: ../../teacher_login_page.php');
    } else {
        // Prepare the SQL statement for inserting the new account
        $sql = "INSERT INTO staff_accounts (fullName, email, password, department, class, date_created, phone_number, gender) 
                VALUES (:fullName, :email, :password, NULL, NULL, NOW(), :phoneNumber, :gender)";

        try {
            // Prepare and execute the insert statement
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':fullName', $fullName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phoneNumber', $phoneNumber);
            $stmt->bindParam(':gender', $gender);

            // Execute the statement
            if ($stmt->execute()) {

                $_SESSION['STATUS'] = "STAFF_CREATE_ACC_SUCCESFUL";

                $stmt = $pdo->prepare("
                INSERT INTO admin_notifications (type, title, description, date, link, status)
                VALUES (:type, :title, :description, NOW(), :link, :status)
            ");
        
            // Define the notification data for "Staff Account Registration"
            $data = [
                ':type' => 'teacher', // Change type if needed (e.g., 'success', 'warning', etc.)
                ':title' => 'New Staff Account Registration',
                ':description' => 'A new staff account has been registered successfully.',
                ':link' => 'teacher_management.php', // Update with the relevant URL
                ':status' => 'unread' // Default status is 'unread'
            ];
        
            // Execute the query with the data
            if ($stmt->execute($data)) {
                echo "Notification added successfully.";
            } else {
                echo "Failed to add notification.";
            }

                header('Location: ../../../teacher_login_page.php'); 
                exit();
            } else {
                // Handle failure
                $_SESSION['STATUS'] = "STAFF_CREATE_ACC_ERROR";
        header('Location: ../../../teacher_login_page.php');
        exit();
            }
        } catch (PDOException $e) {
            $_SESSION['STATUS'] = "STAFF_CREATE_ACC_ERROR";
            header('Location: ../../../teacher_login_page.php');
            exit();
        }

    }
}


