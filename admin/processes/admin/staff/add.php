<?php
include('../../server/conn.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $fullName = $firstName . " " . $middleName . " " . $lastName;
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $class = isset($_POST['class']) ? $_POST['class'] : [];  // Handle as an array of classes

    // Check if password and confirm password match
    if ($password !== $confirm_password) {
        $_SESSION['STATUS'] = "PASSWORD_MISMATCH";
        header('Location: ../../../teacher_management.php');
        exit;
    }

    try {
        // Check if email already exists in the database
        $checkEmailSql = "SELECT * FROM staff_accounts WHERE email = :email";
        $checkStmt = $pdo->prepare($checkEmailSql);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STAFF_EMAIL_EXISTS";
            header('Location: ../../../teacher_management.php');
            exit;
        }

        // Check if the combination of first_name, middle_name, and last_name already exists in the database
        $checkNameSql = "SELECT * FROM staff_accounts WHERE first_name = :first_name AND middle_name = :middle_name AND last_name = :last_name";
        $checkNameStmt = $pdo->prepare($checkNameSql);
        $checkNameStmt->bindParam(':first_name', $firstName);
        $checkNameStmt->bindParam(':middle_name', $middleName);
        $checkNameStmt->bindParam(':last_name', $lastName);
        $checkNameStmt->execute();

        if ($checkNameStmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STAFF_ALREADY_EXISTS";
            header('Location: ../../../teacher_management.php');
            exit;
        }

        // SQL query to insert a new staff account
        $sql = "INSERT INTO staff_accounts (first_name, middle_name, last_name, fullName, department, email, password, phone_number, gender) 
                VALUES (:first_name, :middle_name, :last_name, :fullName, :department, :email, :password, :phone_number, :gender)";

        // Prepare statement
        $stmt = $pdo->prepare($sql);

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':middle_name', $middleName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':gender', $gender);

        // Execute and handle result
        if ($stmt->execute()) {

            $lastInsertedId = $pdo->lastInsertId();  // Get the last inserted staff_id

            $staff_id = $lastInsertedId; // Staff ID to insert into staff_advising

            // Insert into staff_advising for each class this staff member is advising
            foreach ($class as $className) {

                // Check if the teacher is already advising this class
                $checkClassSql = "SELECT * FROM staff_advising WHERE class_advising = :class_advising";
                $checkClassStmt = $pdo->prepare($checkClassSql);
                $checkClassStmt->bindParam(':class_advising', $className);
                $checkClassStmt->execute();

                // If no result exists, insert the new class advising
                if ($checkClassStmt->rowCount() === 0) {
                    $query = "INSERT INTO staff_advising (fullName, class_advising) VALUES (:fullName, :class_advising)";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':fullName', $fullName);
                    $stmt->bindParam(':class_advising', $className);
                    $stmt->execute();
                } else {
                    // Optionally, handle if the teacher is already assigned to the class
                    $_SESSION['STATUS'] = "TEACHER_ALREADY_ASSIGNED_TO_CLASS";
                    header('Location: ../../../teacher_management.php');
                    exit;
                }
            }

            $_SESSION['STATUS'] = "STAFF_ADDED_SUCCESSFULLY";
            header('Location: ../../../teacher_management.php');
            exit();

        } else {
            $_SESSION['STATUS'] = "STAFF_ADDED_ERROR";
            header('Location: ../../../teacher_management.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "STAFF_ADDED_ERROR";
        // Optionally, log or display errors during development
        // echo "Error: " . $e->getMessage();
        header('Location: ../../../teacher_management.php');
        exit();
    }
}
?>
