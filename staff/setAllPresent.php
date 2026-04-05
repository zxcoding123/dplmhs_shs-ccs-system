<?php 
session_start();
$class_id = $_GET['class_id'];
$meeting_id = $_GET['meeting_id'];
require 'processes/server/conn.php'; // Ensure this points to your database connection file
$stmt = $pdo->prepare("UPDATE attendance SET status = 'Present' WHERE class_id = :class_id AND meeting_id = :meeting_id");

// Bind parameters to the query
$stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
$stmt->bindParam(':meeting_id', $meeting_id, PDO::PARAM_INT);

// Execute the query
$stmt->execute();
$_SESSION['STATUS'] = "ALL_PRESENT";
$last_ref = $_SERVER['HTTP_REFERER'];
header('Location:' . $last_ref );
?>