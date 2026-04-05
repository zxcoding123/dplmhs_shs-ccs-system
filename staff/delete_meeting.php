<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

$meeting_id = $_GET['meeting_id'] ?? null;

if ($meeting_id) {
    // Begin a transaction to ensure atomicity
    $pdo->beginTransaction();

    try {
        // Delete from the 'attendance' table first
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE meeting_id = :meeting_id");
        $stmt->execute(['meeting_id' => $meeting_id]);

        // Delete from the 'classes_meetings' table
        $stmt = $pdo->prepare("DELETE FROM classes_meetings WHERE id = :meeting_id");
        $stmt->execute(['meeting_id' => $meeting_id]);

        // Check if rows were affected in the 'classes_meetings' table
        if ($stmt->rowCount() > 0) {
            // Commit the transaction
            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            // Roll back if the meeting was not found or already deleted
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Meeting not found or already deleted.']);
        }
    } catch (Exception $e) {
        // Roll back on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid meeting ID.']);
}
?>
