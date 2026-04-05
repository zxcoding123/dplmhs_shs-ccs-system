<?php
require '../../../processes/server/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = $_GET['id']; // Get the class ID from the form
    $reason = $_POST['reason']; // Get the selected reason from the dropdown
    $classId = $_GET['id'];
    $requestor = $_GET['requestor'];
    $subject = $_GET['subject'];
    $class = $_GET['class'];


    // If "Other" was selected, use the provided reason from the textarea
    if ($reason === 'Other') {
        $reason = $_POST['other_reason'];
    }

    try {
        // Update the class status to 'disapproved' and store the reason
        $stmt = $pdo->prepare("UPDATE classes SET status = 'disapproved', reason = :reason WHERE id = :id");
        $stmt->execute([':id' => $classId, ':reason' => $reason]);

        // Check if the class was successfully updated
        if ($stmt->rowCount() > 0) {

            $getStaffIdQuery = "SELECT id FROM staff_accounts WHERE fullName = :fullName LIMIT 1";
            $getStaffIdStmt = $pdo->prepare($getStaffIdQuery);
            $getStaffIdStmt->execute([
                ':fullName' => $requestor
            ]);

            // Fetch the result
            $staffAccount = $getStaffIdStmt->fetch(PDO::FETCH_ASSOC);

            if ($staffAccount) {
                $requestorId = $staffAccount['id']; // Retrieved ID
                echo "Requestor ID: " . $requestorId;
            } else {
                echo "No matching staff member found with the name: " . htmlspecialchars($requestor);
            }

            $notificationTitle = 'Your submitted pending class for ' . htmlspecialchars($class) . ' to be taught under subject of: ' . htmlspecialchars($subject) . ' has been rejected due to: ' .  $reason;
            $notificationDescription = 'Your pending class submission for ' . htmlspecialchars($class) . ', under the subject of ' . htmlspecialchars($subject) . ', has been rejected due to: ' .  $reason . 'Please review the submission details and address any issues before resubmitting.';
            $date = date('Y-m-d H:i:s'); // Correct date format
            $link = '/staff/class_management.php';
            $type = "class";
            try {
                $insertStaffNotificationQuery = "INSERT INTO staff_notifications (user_id, type, title, description, date, link) 
                                                  VALUES (:user_id, :type, :title, :description, :date, :link)";
                $insertStaffNotificationStmt = $pdo->prepare($insertStaffNotificationQuery);
                $insertStaffNotificationStmt->execute([
                    ':user_id' => $requestorId,
                    ':type' => $type,
                    ':title' => $notificationTitle,
                    ':description' => $notificationDescription,
                    ':date' => $date,
                    ':link' => $link,
                ]);
                echo "Notification successfully inserted!";
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }


            $_SESSION['STATUS'] = 'CLASS_STATUS_DISAPPROVED';


        } else {
            $_SESSION['STATUS'] = 'CLASS_STATUS_DISAPPROVE_ERROR';
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = 'CLASS_STATUS_DISAPPROVE_ERROR';
    }

    // Redirect back to the previous page or dashboard
    header('Location: ../../../class_management.php');
    exit();
}
?>
