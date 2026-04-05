<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('../../server/conn.php');


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = htmlspecialchars($_POST['title']);
        $description = htmlspecialchars($_POST['description']);
        $level = htmlspecialchars($_POST['status']);    
        $due_date = $_POST['due_date'];

        // Convert 24-hour format to 12-hour format with AM/PM
        $due_time = date("h:i A", strtotime($_POST['due_time']));

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
            header("Location: ../../../index.php");
        } else {
            $_SESSION['STATUS'] = "ADD_REMINDER_FAIL";
            header("Location: ../../../index.php");
        }
    }
