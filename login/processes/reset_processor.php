<?php
require_once 'conn.php'; // Database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token']) && isset($_POST['password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['STATUS'] = "PASSWORDS_DO_NOT_MATCH";
        header("Location: reset_password.php?token=$token&status=passwords_do_not_match");
        exit();
    }

    try {
        // Initialize variables
        $user = null;
        $table = null;

        // Query to check token existence across tables
        $queries = [
            "SELECT 'students' as table_name, id FROM students WHERE reset_token = :token",
            "SELECT 'admin' as table_name, id FROM admin WHERE reset_token = :token",
            "SELECT 'staff_accounts' as table_name, id FROM staff_accounts WHERE reset_token = :token"
        ];

        foreach ($queries as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if ($user) {
                $table = $user['table_name'];
                break;
            }
        }

        if ($user && $table) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update the password and reset_token in the identified table
            $updateQuery = "UPDATE {$table} SET password = :password, reset_token = NULL WHERE reset_token = :token";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([':password' => $hashed_password, ':token' => $token]);

            $_SESSION['STATUS'] = "PASSWORD_RESET_SUCCESS";
            header("Location: ../index.php");
            exit();
        } else {
            // Invalid token
            $_SESSION['STATUS'] = "INVALID_TOKEN";
            header("Location: ../index.php");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>
