<?php
include('../../server/conn.php');
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Validate required form data
    if (!isset($_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['email'], $_POST['gender'], $_POST['phone_number'], $_POST['class'])) {
        $_SESSION['STATUS'] = "INVALID_DATA";
        header('Location: ../../../teacher_management.php');
        exit();
    }

    // Get form data
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];
    $fullName = $firstName . " " . $middleName . " " . $lastName;
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
    $gender = $_POST['gender'];
    $phone_number = $_POST['phone_number'];
    $classAdvising = $_POST['class'];  // This is the class advising array

    try {
        // Step 1: Check if the email already exists for another staff member
        $sql = "SELECT id FROM staff_accounts WHERE email = :email AND id != :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STAFF_EMAIL_EXISTS";
            header('Location: ../../../teacher_management.php');
            exit();
        }

        // Check if the combination of first_name, middle_name, and last_name already exists
        $checkNameSql = "SELECT * FROM staff_accounts WHERE first_name = :first_name AND middle_name = :middle_name AND last_name = :last_name AND id != :id";
        $checkNameStmt = $pdo->prepare($checkNameSql);
        $checkNameStmt->bindParam(':first_name', $firstName);
        $checkNameStmt->bindParam(':middle_name', $middleName);
        $checkNameStmt->bindParam(':last_name', $lastName);
        $checkNameStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkNameStmt->execute();

        if ($checkNameStmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "STAFF_ALREADY_EXISTS";
            header('Location: ../../../teacher_management.php');
            exit;
        }

        // Step 2: Prepare the SQL update statement for staff_accounts
        $sql = "UPDATE staff_accounts SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name, fullName = :full_name, email = :email, department = :department, gender = :gender, phone_number = :phone_number" . ($password ? ", password = :password" : "") . " WHERE id = :id";

        // Step 3: Execute the update on the staff account
        $stmt = $pdo->prepare($sql);

        // Bind parameters for staff_accounts update
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':middle_name', $middleName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Bind password if it's being updated
        if ($password) {
            $stmt->bindParam(':password', $password);
        }

        if ($stmt->execute()) {
            // Step 4: If 'None' is selected, clear the class advising for the teacher
            if (in_array("None", $classAdvising)) {
                // Delete all current class advising assignments for this teacher
                $deleteClassSql = "DELETE FROM staff_advising WHERE fullName = :fullName";
                $deleteClassStmt = $pdo->prepare($deleteClassSql);
                $deleteClassStmt->bindParam(':fullName', $fullName);
                $deleteClassStmt->execute();

                $_SESSION['STATUS'] = "STAFF_ACCOUNT_UPDATED_CLEARED_CLASSES";
            } else {
                // Ensure that only selected classes are processed (if "None" is not in array)
                // First, delete all previous class assignments
                $deleteClassSql = "DELETE FROM staff_advising WHERE fullName = :fullName";
                $deleteClassStmt = $pdo->prepare($deleteClassSql);
                $deleteClassStmt->bindParam(':fullName', $fullName);
                $deleteClassStmt->execute();

                // Insert new class assignments
                foreach ($classAdvising as $className) {
                    // Step 4.1: Check if the teacher is already advising this class
                    $checkClassSql = "SELECT * FROM staff_advising WHERE fullName = :fullName AND class_advising = :class_advising";
                    $checkClassStmt = $pdo->prepare($checkClassSql);
                    $checkClassStmt->bindParam(':fullName', $fullName);
                    $checkClassStmt->bindParam(':class_advising', $className);
                    $checkClassStmt->execute();

                    // If no result exists, insert the new class advising
                    if ($checkClassStmt->rowCount() === 0) {
                        $query = "INSERT INTO staff_advising (fullName, class_advising) VALUES (:fullName, :class_advising)";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindParam(':fullName', $fullName);
                        $stmt->bindParam(':class_advising', $className);
                        $stmt->execute();
                    }
                }
            }

            $_SESSION['STATUS'] = "STAFF_ACCOUNT_UPDATED";
            header('Location: ../../../teacher_management.php');
            exit();
        } else {
            $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE_A";
            header('Location: ../../../teacher_management.php');
            exit();
        }

    } catch (PDOException $e) {
        file_put_contents('error_log.txt', $e->getMessage() . "\n", FILE_APPEND);
        $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE_B";
        header('Location: ../../../teacher_management.php');
    }
} else {
    $_SESSION['STATUS'] = "STAFF_ACCOUNT_FAIL_UPDATE_C";
    header('Location: ../../../teacher_management.php');
}
?>
