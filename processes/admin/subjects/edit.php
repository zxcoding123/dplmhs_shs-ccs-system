<?php
session_start();
require '../../server/conn.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $code = $_POST['code'];
    $class = $_POST['class'];
    $teacher = $_POST['teacher'];

    try {

        $checkSubjectStmt = $pdo->prepare("SELECT * FROM subjects WHERE name = :name OR code = :code");
        $checkSubjectStmt->bindParam(':name', $name);
        $checkSubjectStmt->bindParam(':code', $code);
        $checkSubjectStmt->execute();

        if ($checkSubjectStmt->rowCount() > 0) {
            // Subject already exists
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_EXISTS";
            header('Location: ../../../subject_management.php');
            exit;
        }

        
        $sql = "UPDATE subjects SET name = :name, code = :code, class = :class, teacher = :teacher WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->bindParam(':class', $class, PDO::PARAM_STR);
        $stmt->bindParam(':teacher', $teacher, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_SUCCESS";
            header('Location: ../../../subject_management.php');
        } else {
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_ERROR";
            header('Location: ../../../subject_management.php');
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_ERROR";
        header('Location: ../../../subject_management.php');
    }
}
