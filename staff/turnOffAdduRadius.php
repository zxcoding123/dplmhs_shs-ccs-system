<?php
session_start();
require 'processes/server/conn.php'; // Ensure this points to your database connection file
// Check if the necessary GET parameters are present
if (isset($_GET['class_id']) && isset($_GET['meeting_id']) && isset($_GET['action'])) {
    $classId = $_GET['class_id'];
    $attendanceId = $_GET['meeting_id'];
    $action = $_GET['action'];  // 'on' or 'off' to toggle ADDU radius status

    // Assuming $pdo is your PDO connection
    try {
        // Prepare an SQL query to update the addu_radius field based on the action
        $sql = "UPDATE classes_meetings
                SET addu_radius = :action
                WHERE class_id = :class_id
                AND id = :attendance_id";

        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':class_id', $classId);
        $stmt->bindParam(':attendance_id', $attendanceId);

        // Execute the query
        $stmt->execute();

            // Optional: Add success message or redirect
        ;
    } catch (PDOException $e) {
        // Handle potential errors
        echo "Error: " . $e->getMessage();
    }

    $_SESSION['STATUS'] = "CHANGE_RADIUS";
    $last_ref = $_SERVER['HTTP_REFERER'];
    header('Location:' . $last_ref);
}
