<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

// Get the JSON data
$data = json_decode(file_get_contents('php://input'), true);
$meeting_id = $data['meetingId'] ?? null;
$status = $data['status'] ?? null;
$start_time = !empty($data['startTime']) ? (new DateTime($data['startTime']))->format('g:i A') : null;
$end_time = !empty($data['endTime']) ? (new DateTime($data['endTime']))->format('g:i A') : null;

$response = [];  // Create an associative array for the response

// Check if the necessary data exists
if ($meeting_id && $status) {
    try {
        // Update the class status
        $stmt = $pdo->prepare("UPDATE classes_meetings SET status = :status WHERE id = :meeting_id");
        $stmt->execute(['status' => $status, 'meeting_id' => $meeting_id]);

        // Update the start_time and end_time (if provided)
        if ($start_time && $end_time) {
            $stmt = $pdo->prepare("UPDATE classes_meetings SET start_time = :start_time, end_time = :end_time WHERE id = :meeting_id");
            $stmt->execute(['start_time' => $start_time, 'end_time' => $end_time, 'meeting_id' => $meeting_id]);
        }

        // Prepare the success response
        $response['success'] = true;
        $response['message'] = 'Class updated successfully';
    } catch (PDOException $e) {
        // Prepare the error response
        $response['success'] = false;
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    // Prepare the invalid input response
    $response['success'] = false;
    $response['message'] = 'Invalid input';
}

// Encode the response to JSON and send it back
echo json_encode($response);
?>
