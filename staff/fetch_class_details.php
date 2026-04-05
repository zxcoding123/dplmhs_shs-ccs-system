<?php
require 'processes/server/conn.php';

// Get the date and class ID from the query string
$date = $_GET['date'];
$class_id = $_GET['class_id'];

try {
    // Prepare the SQL query to fetch class meeting details for the given date and class ID
    $sql = "SELECT id, date, class_id, start_time, end_time, type, status FROM classes_meetings WHERE class_id = :classId AND date = :date";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['classId' => $class_id, 'date' => $date]);
    
    // Fetch the result and send it back as JSON
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

    // Check if any classes were found
    if (empty($classes)) {
        throw new Exception('No classes found for this date and class ID.');
    }

    header('Content-Type: application/json');
    echo json_encode(['classes' => $classes]);
} catch (Exception $e) {
    // Handle any errors
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}
