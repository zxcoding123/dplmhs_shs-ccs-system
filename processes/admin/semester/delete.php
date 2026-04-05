<?php
session_start();
include('../../server/conn.php');
    if (isset($_GET['id'])) {
        $id = htmlspecialchars($_GET['id']);
        $sql = "DELETE FROM semester WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "SEMESTER_DELETED_SUCCESFULLY";
            header("Location: ../../../semester_management.php");
            exit();
        } else {
            $_SESSION['STATUS'] = "SEMESTER_DELETION_ERROR";
            header("Location: ../../../semester_management.php");
        }
    } else {
        $_SESSION['STATUS'] = "SEMESTER_DELETION_ERROR";
            header("Location: ../../../semester_management.php");
    }
?>
