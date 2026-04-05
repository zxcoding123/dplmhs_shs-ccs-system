<?php
require_once 'processes/server/conn.php'; // Database connection
session_start();

header('Content-Type: application/json'); // Set response type to JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    $submission_id = $input['submission_id'] ?? null;
    $score = $input['score'] ?? null;
    $status = $input['status'] ?? null;
    $class_id = $input['class_id'] ?? null;
    $comments = $input['comments'] ?? ''; // Optional comments

    // Check if all required fields are provided
    if ($submission_id && is_numeric($score) && $class_id && $status) {
        try {
            // Check if the submission ID exists
            $stmt_check = $pdo->prepare("SELECT * FROM activity_submissions WHERE id = :submission_id");
            $stmt_check->execute(['submission_id' => $submission_id]);
            $submission = $stmt_check->fetch();

            if ($submission) {
                $student_id = $submission['student_id'];

                // Update the grade, comments, and status for the submission
                $stmt = $pdo->prepare("
                    UPDATE activity_submissions
                    SET score = :score, feedback = :feedback, status = :status
                    WHERE id = :submission_id
                ");
                $stmt->execute([
                    'score' => $score,
                    'feedback' => $comments,
                    'status' => $status,
                    'submission_id' => $submission_id
                ]);

                // Insert a notification for the student
                $type = 'Grade Update';
                $title = 'Your Activity Submission has been Graded';
                $description = "Your activity submission has been graded. Score: $score. Status: " . ucfirst($status) . ".";
                $date = date('Y-m-d H:i:s');
                $link = "view_activity.php?submission_id=$submission_id";
                $notification_status = 'unread';

                $notification_stmt = $pdo->prepare("
                    INSERT INTO student_notifications (user_id, type, title, description, date, link, status)
                    VALUES (:user_id, :type, :title, :description, :date, :link, :status)
                ");
                $notification_stmt->execute([
                    'user_id' => $student_id,
                    'type' => $type,
                    'title' => $title,
                    'description' => $description,
                    'date' => $date,
                    'link' => $link,
                    'status' => $notification_status
                ]);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Submission ID not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data or missing fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>