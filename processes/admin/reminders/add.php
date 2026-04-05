<?php

session_start();
date_default_timezone_set('Asia/Manila');
include('../../server/conn.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = htmlspecialchars($_POST['title']);
        $description = htmlspecialchars($_POST['description']);
        $level = htmlspecialchars($_POST['status']);    
        $due_date = $_POST['due_date'];
        $due_time = $_POST['due_time'];

        $sql = "INSERT INTO admin_reminders (title, description, level, due_date, due_time, datetime_created) 
                VALUES (:title, :description, :level, :due_date, :due_time, NOW())";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':due_time', $due_time);
        
        if ($stmt->execute()) {
          
        $_SESSION['STATUS'] = "ADD_REMINDER_SUCCESS";
        header("Location: ../../../dashboard.php");
        } else {
          
        $_SESSION['STATUS'] = "ADD_REMIDER_FAIL";
        header("Location: ../../../dashboard.php");
        }
    }
} catch (PDOException $e) {

    $_SESSION['STATUS'] = "ADD_REMIDER_FAIL";
    header("Location: ../../../dashboard.php");
}
