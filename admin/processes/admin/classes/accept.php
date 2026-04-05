<?php
require '../../../processes/server/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $classId = $_GET['id'];
    $requestor = $_GET['requestor'];
    $subject = $_GET['subject'];
    $class = $_GET['class'];



    try {
        $stmt = $pdo->prepare("UPDATE classes SET status = 'accepted' WHERE id = :id");
        $stmt->execute([':id' => $classId]);

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

            $notificationTitle = 'Your submitted pending class for ' . htmlspecialchars($class) . ' to be taught under subject of: ' . htmlspecialchars($subject) . ' has been accepted!';
            $notificationDescription = 'Your pending class submission has been accepted. You may now proceed to complete any additional steps required for its integration.';
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
            
            $_SESSION['STATUS'] = 'CLASS_STATUS_ACCEPTED';

        } else {

            $_SESSION['STATUS'] = 'CLASS_STATUS_ERROR';

        }

        header("Location: ../../../class_management.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = 'Database error: ' . $e->getMessage();

  
        header("Location: ../../../class_management.php");
        exit();
    }
} else {
    $_SESSION['STATUS'] = 'CLASS_STATUS_ERROR';

 
    header("Location: ../../../class_management.php");
    exit();
}
