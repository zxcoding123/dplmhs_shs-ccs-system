<?php
include('../../server/conn.php'); 
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    echo $id;

    try {
        // Get the fullName from staff_accounts using the provided id
        $sql = "SELECT fullName FROM staff_accounts WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff) {
            $fullName = $staff['fullName'];

            echo $fullName;

            // Check if the staff's fullName exists in class_advising
            $sql = "SELECT * FROM staff_advising WHERE fullName = :fullName";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
            $stmt->execute();

            // If records are found, delete them
            if ($stmt->rowCount() > 0) {
                $deleteSql = "DELETE FROM staff_advising WHERE fullName = :fullName";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
                $deleteStmt->execute();
            }

            // Now, delete from the classes table where the teacher's fullName matches
            $updateClassSql = "UPDATE classes SET teacher = '' WHERE teacher = :fullName";
            $updateStmt = $pdo->prepare($updateClassSql);
            $updateStmt->bindParam(':fullName', $fullName, PDO::PARAM_STR);
            $updateStmt->execute();

            // Now delete from staff_accounts
            $deleteSql = "DELETE FROM staff_accounts WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            if ($deleteStmt->execute()) {
                $_SESSION['STATUS'] = "STAFF_DELETE_SUCCESS";
                header('Location: ../../../teacher_management.php');
                exit();
            } else {
                $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
                // header('Location: ../../../teacher_management.php');
                exit();
            }
        } else {
            $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
            // header('Location: ../../../teacher_management.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
        // header('Location: ../../../teacher_management.php');
        exit();
    }
} else {
    $_SESSION['STATUS'] = "STAFF_DELETE_ERROR";
    // header('Location: ../../../teacher_management.php');
    exit();
}
?>
