<?php
session_start();
require '../../server/conn.php';

if (isset($_GET['id'])) {
  
    $id = intval($_GET['id']);


    $sql = "DELETE FROM subjects WHERE id = :id";
    $stmt = $pdo->prepare($sql);


    $stmt->bindParam(':id', $id, PDO::PARAM_INT);


    if ($stmt->execute()) {
        $_SESSION['STATUS'] = "ADMIN_SUBJECT_DELETE_SUCCESS";
        header('Location: ../../../subject_management.php');
        exit();
    } else {
        $_SESSION['STATUS'] = "ADMIN_SUBJECT_DELETE_ERROR";
        header('Location: ../../../subject_management.php');
        exit();
    }
} else {
   
    header('Location: subjects_list.php');
    exit();
}
