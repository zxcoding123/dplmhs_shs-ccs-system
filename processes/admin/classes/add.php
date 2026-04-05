<?php
require '../../../processes/server/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class = $_POST['class'];
    $subjectName = $_POST['subjectName'];
    $teacher = $_POST['teacher'];
    $semester = $_POST['semester'];
    $classDesc = $_POST['classDesc'];

    // Check for empty fields
    if (empty($class) || empty($subjectName) || empty($teacher) || empty($semester) || empty($classDesc)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Get the subject code for the given subject name
    $sql = "SELECT type, code FROM subjects WHERE name = :subjectName";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':subjectName', $subjectName, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $subjectCode = $result['code'];
        $type = $result['type'];

        echo $type;
    } else {
        echo json_encode(['success' => false, 'message' => 'Subject not found.']);
        exit;
    }

    // Generate a unique class code
    function generateClassCode($length = 6)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $classCode = '';
        for ($i = 0; $i < $length; $i++) {
            $classCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $classCode;
    }

    try {
        // Check if the class already exists
        $checkClassStmt = $pdo->prepare("SELECT * FROM classes WHERE name = :name LIMIT 1");
        $checkClassStmt->bindParam(':name', $class, PDO::PARAM_STR);
        $checkClassStmt->execute();

        if ($checkClassStmt->rowCount() > 0) {
            // Class already exists
            $_SESSION['STATUS'] = "NEW_CLASS_EXISTS";
            header('Location: ../../../class_management.php');
            exit;
        }

        // Insert the new class into the classes table
        $classCode = generateClassCode();

        $stmt = $pdo->prepare("INSERT INTO classes (name, code, type, subject, teacher, semester, description, studentTotal, classCode) 
                               VALUES (:name, :code, :type, :subject, :teacher, :semester, :classDesc, 0, :classCode)");
        $stmt->bindParam(':name', $class, PDO::PARAM_STR);
        $stmt->bindParam(':code', $subjectCode, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':subject', $subjectName, PDO::PARAM_STR);
        $stmt->bindParam(':teacher', $teacher, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
        $stmt->bindParam(':classDesc', $classDesc, PDO::PARAM_STR);
        $stmt->bindParam(':classCode', $classCode, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "ADDED_NEW_CLASS_SUCCESS";
            // header('Location: ../../../class_management.php');
        } else {
            $_SESSION['STATUS'] = "ADDED_NEW_CLASS_FAILED";
            header('Location: ../../../class_management.php');
        }
    } catch (PDOException $e) {
        // Log the error message for debugging
        $_SESSION['STATUS'] = "ADDED_NEW_CLASS_FAILED";
        echo 'Error: ' . $e->getMessage();  // For debugging, you can log this instead in production
        header('Location: ../../../class_management.php');
    }
}
