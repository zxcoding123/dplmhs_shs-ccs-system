<?php
session_start();
require "processes/server/conn.php"; // Ensure this file correctly sets up $pdo

try {
    // Check if required POST parameters are set
    if (!isset($_POST['id']) || !isset($_POST['value']) || !isset($_POST['teacher']) || !isset($_POST['subject']) || !isset($_POST['class'])) {
        die("Error: Missing required parameters.");
    }

    // Get and validate parameters
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $value = filter_var($_POST['value'], FILTER_SANITIZE_STRING);
    $teacher = filter_var($_POST['teacher'], FILTER_SANITIZE_STRING);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $class = filter_var($_POST['class'], FILTER_SANITIZE_STRING);

    if (!$id || !$value) {
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referrer");
    }

    // Validate the status parameter
    $validStatuses = ['accept', 'reject'];
    if (!in_array($value, $validStatuses)) {
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referrer");
    }

    // Map 'accept'/'reject' to 'accepted'/'rejected' for the database
    $status = ($value === 'accept') ? 'accepted' : 'rejected';

    // Prepare and execute the UPDATE query
    $sql = "UPDATE student_grades SET status = :status WHERE class_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        $message = "Grade status updated successfully";

        // Fetch teacher ID
        $getStaffIdQuery = "SELECT id FROM staff_accounts WHERE fullName = :fullName LIMIT 1";
        $getStaffIdStmt = $pdo->prepare($getStaffIdQuery);
        $getStaffIdStmt->execute([':fullName' => $teacher]);
        $staffAccount = $getStaffIdStmt->fetch(PDO::FETCH_ASSOC);

        if (!$staffAccount) {
            echo "No matching staff member found with the name: " . htmlspecialchars($teacher);
            $teacherId = null; // Handle case where teacher isn’t found
        } else {
            $teacherId = $staffAccount['id'];
            echo "Teacher ID: " . $teacherId;
        }

        // Differentiate notifications based on status
        $date = date('Y-m-d H:i:s');
        $link = '/capstone/staff/class_management.php';
        $type = "class";

        if ($value === 'accept') {
            $notificationTitle = 'Grades Accepted!';
            $notificationDescription = "The grades for the subject of $subject under class $class have been successfully accepted and are now final!";

            $_SESSION['STATUS'] = "GRADES_ACCEPTED";


        } else { // $value === 'reject'
            $notificationTitle = 'Grades Rejected!';
            $notificationDescription = "The grades for the subject of $subject under class $class have been rejected. Please review and resubmit if necessary.";
            $_SESSION['STATUS'] = "GRADES_REJECTED";
        }

        // Insert notification (only if teacher ID is valid)
        if ($teacherId) {
            $insertStaffNotificationQuery = "INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                                             VALUES (:user_id, :type, :title, :description, :date, :link)";
            $insertStaffNotificationStmt = $pdo->prepare($insertStaffNotificationQuery);
            $insertStaffNotificationStmt->execute([
                ':user_id' => $teacherId,
                ':type' => $type,
                ':title' => $notificationTitle,
                ':description' => $notificationDescription,
                ':date' => $date,
                ':link' => $link,
            ]);
            echo "Notification successfully inserted!";
        } else {
            echo "Notification not inserted due to missing teacher ID.";
        }

      

        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referrer");
        exit;
    } else {

        $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referrer");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>