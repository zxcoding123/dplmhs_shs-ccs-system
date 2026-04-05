<?php
// notify_submission.php
require 'processes/server/conn.php'; // Ensure this points to your database connection file
header('Content-Type: application/json');

try {
   

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $activity_id = $data['activity_id']; // ID of the activity
    $student_id = $data['student_id'];   // ID of the student to notify

    // Check if the student has already submitted the activity
    $query = "SELECT id FROM activity_submissions WHERE activity_id = :activity_id AND student_id = :student_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'activity_id' => $activity_id,
        'student_id' => $student_id
    ]);
    $submission = $stmt->fetch();

    if ($submission) {
        // If the student has already submitted, send a response
        echo json_encode(['success' => false, 'error' => 'The student has already submitted this activity.']);
    } else {
        // If no submission exists, send a notification
        $type = 'class';
        $title = 'Reminder to Submit Activity';
        $description = "You have not yet submitted Activity ID: $activity_id. Please pass your activity as soon as possible.";
        $date = date('Y-m-d H:i:s');
        $link = "submit_activity.php?activity_id=$activity_id"; // Optional: Link to submission page
        $status = 'unread';

        // Insert notification into the `student_notifications` table
        $insert_query = "INSERT INTO student_notifications (user_id, type, title, description, date, link, status) 
                         VALUES (:user_id, :type, :title, :description, :date, :link, :status)";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([
            'user_id' => $student_id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'date' => $date,
            'link' => $link,
            'status' => $status
        ]);

        // Send success response
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Handle general errors
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
