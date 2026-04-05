<?php
require_once '../../processes/server/conn.php';
session_start();

if (!isset($_GET['token'])) {
    $_SESSION['STATUS'] = "INVALID_REQUEST";
    header("Location: ../index.php"); // Redirect to result page
    exit("Invalid activation link.");
}

$token = $_GET['token'];

try {
    // 1. Find student with this token
    $stmt = $pdo->prepare("
        SELECT s.*, u.email 
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.activation_token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $_SESSION['STATUS'] = "INVALID_OR_EXPIRED_TOKEN";
        header("Location: ../index.php"); // Redirect to result page
        exit("Invalid or expired activation link.");
    }

    // 2. Check if already active
    if ($student['status'] === 'active') {
        $_SESSION['STATUS'] = "ALREADY_ACTIVATED";
        header("Location: ../index.php"); // Redirect to result page
        exit("Account already activated.");
    }

    // 3. Activate account
    $stmt = $pdo->prepare("
        UPDATE students 
        SET status = 'active',
            activation_token = NULL
        WHERE id = :id
    ");
    $stmt->execute([':id' => $student['id']]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['STATUS'] = "ACTIVATION_SUCCESS";
        header("Location: ../index.php"); // Redirect to result page
        exit("Account successfully activated!");
    } else {
        $_SESSION['STATUS'] = "ACTIVATION_FAILED";
        header("Location: ../index.php"); // Redirect to result page
        exit("Activation failed. Please try again.");
    }
} catch (PDOException $e) {
    $_SESSION['STATUS'] = "ERROR";
    error_log($e->getMessage());
    header("Location: ../index.php"); // Redirect to result page
    exit("Server error.");
}
