<?php
include('../../server/conn.php');
session_start();
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
    $class = $_POST['class'];

    try {
        // Check if email already exists for another staff member
        $sql = "SELECT id FROM staff_accounts WHERE email = :email AND id != :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STAFF_EMAIL_EXISTS";
            header('Location: ../../../staff_management.php');
            exit();
        }

        // Prepare the SQL statement for updating the account
        if ($password) {
            // Update with password
            $sql = "UPDATE staff_accounts SET fullName = :full_name, email = :email, department = :department, password = :password, class = :class WHERE id = :id";
        } else {
            // Update without password
            $sql = "UPDATE staff_accounts SET fullName = :full_name, email = :email, department = :department, class = :class WHERE id = :id";
        }

        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':class', $class);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Bind the password only if it's provided
        if ($password) {
            $stmt->bindParam(':password', $password);
        }

        // Execute the query and check for success
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "STAFF_ACCOUNT_UPDATED";
            header('Location: ../../../staff_management.php');
            exit();
        } else {
            $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE";
            header('Location: ../../../staff_management.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE";
        header('Location: ../../../staff_management.php');
    }
} else {
    $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE";
    header('Location: ../../../staff_management.php');
}
