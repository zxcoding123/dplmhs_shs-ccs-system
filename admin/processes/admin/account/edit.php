<?php
session_start();
require_once '../../server/conn.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_POST['id']; // It's safer to use POST to send the admin_id
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $middle_name = htmlspecialchars(trim($_POST['middle_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Check for duplicate username or email
    $sql = "SELECT * FROM admin WHERE email = :email AND id != :admin_id"; // Exclude current admin_id
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':admin_id', $admin_id); // Exclude the current admin from the check
    $stmt->execute();
    
    // Fetch results
    if ($stmt->rowCount() > 0) {
        // If there's a duplicate
        $_SESSION['STATUS'] = "DUPLICATE_ACCOUNT";
        header('Location: ../../../admin_management.php');
        exit;
    }

    // If passwords are set, validate them
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $_SESSION['STATUS'] = "PASSWORDS_DO_NOT_MATCH";
            header('Location: ../../../admin_management.php');
            exit;
        }
        // Hash the new password before storing it
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE admin 
                SET first_name = :first_name, 
                     middle_name = :middle_name,
                    last_name = :last_name, 
                    email = :email, 
                    gender = :gender,
                    password = :password 
                WHERE id = :admin_id";
    } else {
        // If passwords are empty, do not update them
        $sql = "UPDATE admin 
                SET first_name = :first_name, 
                           middle_name = :middle_name,
                    last_name = :last_name, 
                    email = :email, 
                    gender = :gender 
                WHERE id = :admin_id";
    }

    // Prepare the update statement
    try {
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':middle_name', $middle_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':admin_id', $admin_id);

        if (!empty($password)) {
            $stmt->bindParam(':password', $password);
        }

        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "ADMIN_EDIT_SUCCESFUL";
            header('Location: ../../../admin_management.php');
            exit;
        } else {
            $_SESSION['STATUS'] = "ADMIN_EDIT_FAILED";
            header('Location: ../../../admin_management.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ADMIN_EDIT_ERROR: " . $e->getMessage();
        header('Location: ../../../admin_management.php');
        exit;
    }
} else {
    $_SESSION['STATUS'] = "ADMIN_EDIT_ERROR";
    header('Location: ../../../admin_management.php');
    exit;
}
