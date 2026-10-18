<?php
// Start output buffering to prevent header errors due to early output
ob_start();
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = "TEACHER_NOT_LOGGED_IN";
    header("Location: ../login/index.php");
    exit;
}

include __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../app/helpers/StaffNameHelper.php';

try {
    // Get the teacher's ID
    $teacher_id = $_SESSION['teacher_id'];

    // Check if the form has been submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the data from the form
        $firstName = $_POST['firstName'];
        $middleName = $_POST['middleName'];
        $lastName = $_POST['lastName'];
        $fullName = $firstName . " " . $middleName . " " . $lastName;

        $email = strtolower(trim($_POST['email']));
        $password = isset($_POST['password']) && !empty($_POST['password'])
            ? password_hash($_POST['password'], PASSWORD_DEFAULT)
            : null; // Only hash the password if provided
        $department = $_POST['department'];
        $phone_number = $_POST['phone_number'];
        $gender = $_POST['gender'];
        $previousPage = $_SERVER['HTTP_REFERER'] ?? 'index.php';

        // The email must not belong to any other account (users.email is unique across roles)
        $emailStmt = $pdo->prepare("
            SELECT 1 FROM users
            WHERE email = :email
              AND id <> COALESCE((SELECT user_id FROM staff_accounts WHERE id = :teacher_id), 0)
            UNION
            SELECT 1 FROM staff_accounts WHERE email = :email2 AND id <> :teacher_id2
        ");
        $emailStmt->execute(['email' => $email, 'teacher_id' => $teacher_id, 'email2' => $email, 'teacher_id2' => $teacher_id]);
        if ($emailStmt->fetchColumn()) {
            $_SESSION['STATUS'] = "NEW_INFO_EMAIL_EXISTS";
            header('Location: ' . $previousPage);
            exit;
        }

        // Classes and advisory sections are linked by name, so two teachers can't share one
        if (StaffNameHelper::nameTaken($pdo, $fullName, (int) $teacher_id)) {
            $_SESSION['STATUS'] = "NEW_INFO_NAME_EXISTS";
            header('Location: ' . $previousPage);
            exit;
        }
        $oldFullName = StaffNameHelper::currentName($pdo, (int) $teacher_id);

        // Profile picture: only accept real images, and keep the current one if none was uploaded
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $fileTmpPath = $_FILES['avatar']['tmp_name'];
            $mimeType = $_FILES['avatar']['error'] === UPLOAD_ERR_OK ? mime_content_type($fileTmpPath) : '';

            if (!isset($allowedTypes[$mimeType]) || $_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                $_SESSION['STATUS'] = "NEW_INFO_INVALID_AVATAR";
                header('Location: ' . $previousPage);
                exit;
            }

            $uploadDir = __DIR__ . '/../uploads/profile_pictures/';
            // Remove any earlier picture with a different extension
            foreach (glob($uploadDir . 'teacher_' . (int) $teacher_id . '.*') as $oldFile) {
                unlink($oldFile);
            }
            $newFileName = 'teacher_' . (int) $teacher_id . '.' . $allowedTypes[$mimeType];
            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                $avatar = $newFileName;
            }
        }

        // Build the SQL query
        $query = "UPDATE staff_accounts
                  SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name, fullName = :fullName, email = :email, department = :department,
                      phone_number = :phone_number, gender = :gender";

        if ($avatar) {
            $query .= ", avatar = :avatar";
        }
        if ($password) {
            $query .= ", password = :password";
        }
        $query .= " WHERE id = :teacher_id";

        // Prepare the query
        $stmt = $pdo->prepare($query);

        // Bind parameters
        $params = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'fullName' => $fullName,
            'email' => $email,
            'department' => $department,
            'phone_number' => $phone_number,
            'gender' => $gender,
            'teacher_id' => $teacher_id
        ];
        if ($avatar) {
            $params['avatar'] = $avatar;
        }
        if ($password) {
            $params['password'] = $password;
        }

        $pdo->beginTransaction();

        // Execute the query
        $stmt->execute($params);

        // Keep the shared users record (used for login) in sync
        $userStmt = $pdo->prepare("UPDATE users SET email = :email" . ($password ? ", password = :password" : "") . " WHERE id = (SELECT user_id FROM staff_accounts WHERE id = :teacher_id)");
        $userParams = ['email' => $email, 'teacher_id' => $teacher_id];
        if ($password) {
            $userParams['password'] = $password;
        }
        $userStmt->execute($userParams);

        // Move classes, advisory sections, notes, etc. over to the new name
        if ($oldFullName !== null) {
            StaffNameHelper::renameEverywhere($pdo, (int) $teacher_id, $oldFullName, $fullName);
        }

        $pdo->commit();

        // Pages read the teacher's name from the session
        $_SESSION['teacher_name'] = $fullName;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['name'] = $fullName;
        $_SESSION['email'] = $email;

        // Add a notification for the admin
        $adminTitle = "Staff Account Updated";
        $adminDescription = "The profile for staff member $fullName has been updated.";
        $adminLink = "teacher_management.php";
        $adminType = "teacher";
        $adminStatus = "unread";

        // One notification per admin (admin_notifications.admin_id is required)
        $adminNotificationStmt = $pdo->prepare("INSERT INTO admin_notifications (type, title, description, date, link, status, admin_id)
            SELECT :type, :title, :description, NOW(), :link, :status, id FROM admin");

        $adminNotificationStmt->execute([
            'type' => $adminType,
            'title' => $adminTitle,
            'description' => $adminDescription,
            'link' => $adminLink,
            'status' => $adminStatus
        ]);

        $_SESSION['STATUS'] = "NEW_INFO_SUCCESSFUL";
        
        // Redirect back to the previous page
        $previousPage = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'defaultPage.php';
        header('Location: ' . $previousPage);
        exit;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = "Database Error: " . $e->getMessage() . "\n";
    $errorDetails = "Date: " . date('Y-m-d H:i:s') . "\n" . "File: " . __FILE__ . "\n" . "Line: " . $e->getLine() . "\n";
    $logData = $errorMessage . $errorDetails;

    // Save to a text file
    file_put_contents('error_log.txt', $logData, FILE_APPEND);

    // Handle session and redirect back to the previous page
    $_SESSION['STATUS'] = "NEW_INFO_ERROR";
    $previousPage = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'defaultPage.php';
    header('Location: ' . $previousPage);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = "General Error: " . $e->getMessage() . "\n";
    $errorDetails = "Date: " . date('Y-m-d H:i:s') . "\n" . "File: " . __FILE__ . "\n" . "Line: " . $e->getLine() . "\n";
    $logData = $errorMessage . $errorDetails;

    // Save to a text file
    file_put_contents('error_log.txt', $logData, FILE_APPEND);

    // Handle session and redirect back to the previous page
    $_SESSION['STATUS'] = "NEW_INFO_ERROR";
    $previousPage = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'defaultPage.php';
    header('Location: ' . $previousPage);
    exit;
}

// End output buffering
ob_end_flush();
?>
