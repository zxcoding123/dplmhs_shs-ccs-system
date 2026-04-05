<?php
include('../../server/conn.php'); 
session_start();


if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM staff_accounts WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "STAFF_DELETE_SUCCESS";
            header('Location: ../../../staff_management.php');
            exit();
        } else {
            $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
            header('Location: ../../../staff_management.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
        header('Location: ../../../staff_management.php');
    }
} else {
    $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
        header('Location: ../../../staff_management.php');
    exit();
}
?>
