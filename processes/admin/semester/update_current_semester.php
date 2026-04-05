<?php
session_start();
include '../../server/conn.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $semester = $_POST['semester'];

    if ($semester) {
        try {
     
            $pdo->beginTransaction();

            $sqlDelete = "DELETE FROM current_semester";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->execute();

           
            $sqlInsert = "INSERT INTO current_semester (semester) VALUES (:semester)";
            $stmtInsert = $pdo->prepare($sqlInsert);

     
            $stmtInsert->bindParam(':semester', $semester, PDO::PARAM_INT);

          
            $stmtInsert->execute();

     
            $pdo->commit();

            $_SESSION['STATUS'] = "UPDATE_SEMESTER_SUCCESSFUL";
            header("Location: ../../../dashboard.php");
        } catch (Exception $e) {
          
            $pdo->rollBack();
            $_SESSION['STATUS'] = "UPDATE_SEMESTER_ERROR";
            header("Location: ../../../dashboard.php");
        }
    } else {
        $_SESSION['STATUS'] = "UPDATE_SEMESTER_ERROR";
        header("Location: ../../../dashboard.php");
    }
} else {
    $_SESSION['STATUS'] = "UPDATE_SEMESTER_ERROR";
    header("Location: ../../../dashboard.php");
}
