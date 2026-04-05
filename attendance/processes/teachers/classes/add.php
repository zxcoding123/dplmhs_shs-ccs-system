<?php
session_start();
require '../../../processes/server/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Fetch the subject_id based on the subjectName
        $subject_id = $_POST['subjectId'];
        $subject_query = "SELECT id, code, name, type FROM subjects WHERE id = :id";
        $subject_stmt = $pdo->prepare($subject_query);
        $subject_stmt->bindParam(':id', $subject_id);
        $subject_stmt->execute();

        echo $subject_id;
        // Check if the subject exists
        $subject = $subject_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$subject) {
            echo "Error: Subject not found.";
            exit;
        }

        $subject_id = $subject['id'];
        $subject_code = $subject['code'];
        $subject_name = $subject['name'];
        $subject_type = $subject['type'];

        // Generate class code dynamically (example: use class name and a random number)
        $class_name = $_POST['class'];

        function generateClassCode($length = 6)
        {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $classCode = '';
            for ($i = 0; $i < $length; $i++) {
                $classCode .= $characters[rand(0, strlen($characters) - 1)];
            }
            return $classCode;
        }

        $classCode = generateClassCode();

        // Prepare the SQL statement for insertion
        $sql = "INSERT INTO classes (
                    name, type, subject, subject_id, code, teacher, semester, 
                    studentTotal, description, classCode, requestor, status, 
                    reason, datetime_added, is_archived
                ) VALUES (
                    :name, :type, :subject, :subject_id, :code, :teacher, :semester, 
                    :studentTotal, :description, :classCode, :requestor, :status, 
                    :reason, :datetime_added, :is_archived
                )";

        $stmt = $pdo->prepare($sql);

        // Bind the form data to the statement
        $stmt->bindParam(':name', $_POST['class']);
        $stmt->bindValue(':type', $subject_type); // Assuming "type" is static
        $stmt->bindParam(':subject', $subject_name);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindValue(':code', $subject_code); // Use class name for code, or change based on your requirements
        $stmt->bindParam(':teacher', $_SESSION['teacher_name']);
        $stmt->bindParam(':semester', $_POST['semester']);
        $stmt->bindValue(':studentTotal', 0); // Default value
        $stmt->bindParam(':description', $_POST['classDesc']);
        $stmt->bindParam(':classCode', $classCode, PDO::PARAM_STR);
        $stmt->bindParam(':requestor', $_SESSION['teacher_name']); // Logged-in adviser
        $stmt->bindValue(':status', 'pending'); // Default status
        $stmt->bindValue(':reason', null); // Default null
        $stmt->bindValue(':datetime_added', date('Y-m-d H:i:s')); // Current datetime
        $stmt->bindValue(':is_archived', 0); // Default not archived

        // Execute the statement
        $stmt->execute();

        
        $stmt = $pdo->prepare("
        INSERT INTO admin_notifications (type, title, description, date, link, status)
        VALUES (:type, :title, :description, NOW(), :link, :status)
    ");

    // Define the notification data for "Staff Account Registration"
    $data = [
        ':type' => 'teacher', // Change type if needed (e.g., 'success', 'warning', etc.)
        ':title' => 'New Class Addition',
        ':description' => 'A new class to be taught has been added by ' . $_SESSION['teacher_name'],
        ':link' => 'teacher_management.php', // Update with the relevant URL
        ':status' => 'unread' // Default status is 'unread'
    ];

    // Execute the query with the data
    if ($stmt->execute($data)) {
        echo "Notification added successfully.";
    } else {
        echo "Failed to add notification.";
    }

        // Success message
        echo "Class successfully added.";
    } catch (PDOException $e) {
        // Error message
        echo "Error: " . $e->getMessage();
    }

    $_SESSION['STATUS'] = "CLASS_ADDED_SUCCESFUL";
if (!empty($_SERVER['HTTP_REFERER'])) {
    // Redirect to the referrer page
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // Fallback in case HTTP_REFERER is not set
    header('Location: ../..teacher_dashboard.php'); // Adjust fallback location as needed
    exit;
}
}
?>