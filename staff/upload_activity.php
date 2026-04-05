<?php
require '../../server/conn.php'; // Ensure this points to your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $message = $_POST['message'];
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];
    $min_points = $_POST['min_points'];
    $max_points = $_POST['max_points'];
    $class_id = $_POST['class_id']; // Ensure this is passed in the form
    $subject_id = $_POST['subject_id']; // Ensure this is passed in the form

    $due_time_12hr = date("h:iA", strtotime($due_time));
    $current_time = date('Y-m-d H:i:s');

    // Check for duplicate activity
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE title = ? AND class_id = ? AND subject_id = ?");
    $stmt->execute([$title, $class_id, $subject_id]);
    if ($stmt->fetchColumn() > 0) {
        die("Activity with this title already exists for this class and subject.");
    }

    // Insert activity
    $stmt = $pdo->prepare("INSERT INTO activities (title, type, message, due_date, due_time, min_points, max_points, class_id, subject_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $type, $message, $due_date, $due_time_12hr, $min_points, $max_points, $class_id, $subject_id, $current_time, $current_time])) {
        $activity_id = $pdo->lastInsertId();

        // Handle file upload if exists
        if (!empty($_FILES['attachment']['tmp_name'])) {
            $file_name = $_FILES['attachment']['name'];
            $file_tmp_path = $_FILES['attachment']['tmp_name'];
            $upload_dir = '../../../uploads/files/';
            $new_file_name = uniqid() . '-' . $file_name;
            $destination = $upload_dir . $new_file_name;

            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            if (move_uploaded_file($file_tmp_path, $destination)) {
                $stmt = $pdo->prepare("INSERT INTO activity_attachments (activity_id, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$activity_id, $file_name, $new_file_name, $current_time]);
            } else {
                die("Failed to upload the attachment.");
            }
        }

        header('Location: ../../../teacher_dashboard.php?success=1'); // Redirect to a success page
    } else {
        die("Failed to create activity.");
    }
} else {
    die("Invalid request method.");
}
?>
