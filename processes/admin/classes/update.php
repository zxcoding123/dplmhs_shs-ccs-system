<?php
require '../../server/conn.php'; 
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $class = $_POST['class'];
    $subjectName = $_POST['subjectName'];
    $teacher = $_POST['teacher'];
    $semester = $_POST['semester'];
    $classDesc = $_POST['classDesc'];

    echo $teacher;

    if (empty($id) || empty($class) || empty($subjectName) || empty($teacher) || empty($semester)) {
        echo "All fields are required.";
        exit;
    }

    

    try {
        $stmt = $pdo->prepare("UPDATE classes SET name = :class, subject = :subjectName, teacher = :teacher, semester = :semester, description = :description WHERE id = :id");
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':class', $class, PDO::PARAM_STR);
        $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
        $stmt->bindParam(':teacher', $teacher, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
        $stmt->bindParam(':description', $classDesc, PDO::PARAM_STR);

        $stmt->execute();

        $_SESSION['STATUS'] = "EDIT_CLASS_SUCCESS";
        header('Location: ../../../class_management.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "EDIT_CLASS_ERROR";
        header('Location: ../../../class_management.php');
    }
}
?>
