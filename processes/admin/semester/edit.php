<?php
session_start();

include('../..//server/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = htmlspecialchars($_GET['id']);
    $name = htmlspecialchars($_POST['semName']);
    $start_date = htmlspecialchars($_POST['semStartDate']);
    $end_date = htmlspecialchars($_POST['semEndDate']);
    $description = htmlspecialchars($_POST['semDesc']);

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $sql = "UPDATE semester SET name = :name, start_date = :start_date, end_date =:end_date, description = :description WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "SEMESTER_EDITION_SUCCESFUL";
            header("Location: ../../../semester_management.php");
            exit();
        } else {
            $_SESSION['STATUS'] = "SEMESTER_EDITION_ERROR";
            header("Location: ../../../semester_management.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "SEMESTER_EDITION_ERROR";
        header("Location: ../../../semester_management.php");
        exit();
    }
}
