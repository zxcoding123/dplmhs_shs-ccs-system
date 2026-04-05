<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get activity ID and other data from the request
    $activity_id = $_POST['id'];
    $title = $_POST['title'];
    $type = $_POST['type'];
    $message = $_POST['message'];
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];
    $min_points = $_POST['min_points'];
    $max_points = $_POST['max_points'];

    $due_time_12hr = date("h:iA", strtotime($due_time));

    // Update the activity details in the database
    $stmt = $pdo->prepare("UPDATE activities SET title = ?, type = ?, message = ?, due_date = ?, due_time = ?, min_points = ?, max_points = ? WHERE id = ?");
    $stmt->execute([$title, $type, $message, $due_date, $due_time_12hr, $min_points, $max_points, $activity_id]);

    // Handle file attachments
    if (!empty($_FILES['attachment']['name'][0])) {
        $file_count = count($_FILES['attachment']['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['attachment']['error'][$i] == UPLOAD_ERR_OK) {
                // Process each file upload
                $tmp_name = $_FILES['attachment']['tmp_name'][$i];
                $file_name = basename($_FILES['attachment']['name'][$i]);
                $upload_dir = 'uploads/'; // Ensure this directory exists and is writable

                // Move the uploaded file to the designated directory
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    // Insert the attachment record into the database
                    $stmt = $pdo->prepare("INSERT INTO activity_attachments (activity_id, file_name) VALUES (?, ?)");
                    $stmt->execute([$activity_id, $file_name]);
                } else {
                    echo json_encode(['error' => 'Failed to move uploaded file.']);
                    exit;
                }
            }
        }
    }

    echo json_encode(['success' => 'Activity updated successfully!']);
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
