<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attachment_id = $_POST['attachment_id']; // The ID of the attachment to remove

    // Fetch the file name to delete from the database
    $stmt = $pdo->prepare("SELECT file_name FROM activity_attachments WHERE id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($attachment) {
        $file_name = $attachment['file_name'];
        $file_path = 'uploads/' . $file_name;

        // Delete the attachment record from the database
        $stmt = $pdo->prepare("DELETE FROM activity_attachments WHERE id = ?");
        $stmt->execute([$attachment_id]);

        // Remove the file from the filesystem
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        echo json_encode(['success' => 'Attachment removed successfully!']);
    } else {
        echo json_encode(['error' => 'Attachment not found.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
