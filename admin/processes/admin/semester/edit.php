<?php
require '../../server/conn.php';
session_start(); // Start session for status messages

if (isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_GET['id'];
    $name = $_POST['name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $school_year = $_POST['schoolYear'];
    $description = $_POST['description'];

            // Check if the start and end dates are the same
            if ($start_date === $end_date) {
                $_SESSION['STATUS'] = "SEMESTER_DATE_ERROR";
                header("Location: ../../../semester_management.php");
                exit;
            }
    
            // Check if the semester name already exists and is not archived
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM semester WHERE school_year = :school_year AND status != 'Archived'");
            $checkStmt->bindParam(':school_year', $school_year);
            $checkStmt->execute();
            $count = $checkStmt->fetchColumn();
    
            if ($count > 0) {
                $_SESSION['STATUS'] = "SEMESTER_NAME_EXISTS";
                header("Location: ../../../semester_management.php");
                exit;
            }

    // Check for empty fields
    if (!empty($name) && !empty($start_date) && !empty($end_date) && !empty($description)) {
        try {
            // Prepare the update statement
            $stmt = $pdo->prepare("UPDATE semester SET name = :name, start_date = :start_date, end_date = :end_date, school_year = :school_year,  description = :description WHERE id = :id");
            
            // Bind parameters
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':school_year', $school_year);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $id);
            
            // Execute the statement
            if ($stmt->execute()) {
                $_SESSION['STATUS'] = "SEMESTER_UPDATE_SUCCESS";
            } else {
                $_SESSION['STATUS'] = "SEMESTER_UPDATE_FAILED";
            }
        } catch (PDOException $e) {
            $_SESSION['STATUS'] = "SEMESTER_UPDATE_ERROR";
            $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
        }
    } else {
        $_SESSION['STATUS'] = "SEMESTER_FIELDS_EMPTY";
    }

    // Redirect to semesters.php with session-based SweetAlert
    header("Location: ../../../semester_management.php");
    exit;
} else {
    header("Location: ../../../semester_management.php");
    exit;
}
