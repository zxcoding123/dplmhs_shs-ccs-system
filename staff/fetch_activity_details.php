<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

$activity_id = $_GET['id']; // Get the activity ID from the request

// Prepare the SQL statement to fetch the activity details
$stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->execute([$activity_id]);

// Fetch the activity details
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if ($activity) {
    // Convert created_at and updated_at to 12-hour format with AM/PM
    $created_at = (new DateTime($activity['created_at']))->format('m/d/Y h:i A');
    $updated_at = (new DateTime($activity['updated_at']))->format('m/d/Y h:i A');

    // Prepare the SQL statement to fetch attachments for the activity
    $stmt = $pdo->prepare("SELECT * FROM activity_attachments WHERE activity_id = ?");
    $stmt->execute([$activity_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format attachments to include URLs
    foreach ($attachments as &$attachment) {
        $attachment['url'] = 'uploads/' . $attachment['file_name']; // Assuming files are stored in the 'uploads' directory
    }

    // Return the activity details and attachments as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'id' => $activity['id'], // Include the activity ID
        'title' => $activity['title'],
        'type' => $activity['type'],
        'message' => $activity['message'],
        'due_date' => $activity['due_date'],
        'due_time' => $activity['due_time'],
        'min_points' => $activity['min_points'],
        'max_points' => $activity['max_points'],
        'class_id' => $activity['class_id'],
        'subject_id' => $activity['subject_id'],
        'created_at' => $created_at, // Formatted created_at
        'updated_at' => $updated_at, // Formatted updated_at
        'attachments' => $attachments // Include attachments
    ]);
} else {
    echo json_encode(['error' => 'Activity not found']);
}
?>
