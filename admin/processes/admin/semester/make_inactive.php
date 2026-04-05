<?php
require '../../server/conn.php';
session_start(); // Start session for status messages

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Prepare the update statement
        $stmt = $pdo->prepare("UPDATE semester SET status = 'Inactive' WHERE id = :id");
        
        // Bind parameters
        $stmt->bindParam(':id', $id);
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "SEMESTER_INACTIVE";
        } else {
            $_SESSION['STATUS'] = "SEMESTER_INACTIVE_ERROR";
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "SEMESTER_INACTIVE_ERROR";
        $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
    }
    
    // Redirect to semesters.php
    header("Location: ../../../semester_management.php");
    exit;
} else {
    $_SESSION['STATUS'] = "INVALID_REQUEST";
    header("Location: ../../../semester_management.php");
    exit;
}
