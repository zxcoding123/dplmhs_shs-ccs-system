<?php
include('../../server/conn.php');
session_start();
date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = htmlspecialchars($_POST['username_email']);
    $password_account = $_POST['password'];

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM admin WHERE username = :username_or_email OR email = :username_or_email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username_or_email', $username_or_email);
        $stmt->execute();

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password_account, $admin['password'])) {
            // Update the last_login timestamp
            $update_sql = "UPDATE admin SET last_login = NOW() WHERE id = :admin_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(':admin_id', $admin['id']);
            $update_stmt->execute();

            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['STATUS'] = "ADMIN_LOGIN_SUCCESFUL";
            header("Location: ../../../admin/index.php");
            exit;
        } else {
            $_SESSION['STATUS'] = 'ADMIN_INVALID_LOGIN';
            header("Location: ../../../admin_login_page.php");
            exit;
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
