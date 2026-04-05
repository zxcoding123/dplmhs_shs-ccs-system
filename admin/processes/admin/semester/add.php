<?php
require '../../server/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $school_year = $_POST['schoolYear'];
    $description = $_POST['description'];

    // Check for empty fields
    if (empty($name) || empty($start_date) || empty($end_date) || empty($description)) {
        $_SESSION['STATUS'] = "SEMESTER_FIELDS_EMPTY";
        header("Location: ../../../semester_management.php");
        exit;
    }

    try {

        // Extract year from start_date and end_date
        $start_year = date('Y', strtotime($start_date));
        $end_year = date('Y', strtotime($end_date));

        echo $start_year;

        echo $end_year;

        echo $school_year;

        // Check if the school_year matches start_date and end_date years
        if ($start_year !== $school_year || $end_year !== $school_year) {
            $_SESSION['STATUS'] = "SEMESTER_YEAR_MISMATCH";
            header("Location: ../../../semester_management.php");
            exit;
        }


        // Check if the start and end dates are the same
        if ($start_date === $end_date) {
            $_SESSION['STATUS'] = "SEMESTER_DATE_ERROR";
            header("Location: ../../../semester_management.php");
            exit;
        }

        // Check if the semester name already exists and is not archived
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM semester WHERE name = :name AND school_year = :school_year AND status != 'Archived'");
        $checkStmt->bindParam(':name', $name);
        $checkStmt->bindParam(':school_year', $school_year);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            $_SESSION['STATUS'] = "SEMESTER_NAME_EXISTS";
            header("Location: ../../../semester_management.php");
            exit;
        }

        // Prepare the insert statement
        $stmt = $pdo->prepare("INSERT INTO semester (name, start_date, end_date, school_year, description) VALUES (:name, :start_date, :end_date, :school_year, :description)");

        // Bind parameters
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':school_year', $school_year);
        $stmt->bindParam(':description', $description);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "SEMESTER_ADDED_SUCCESS";
            header("Location: ../../../semester_management.php");
            exit;
        } else {
            $_SESSION['STATUS'] = "SEMESTER_ADD_FAILED";
            header("Location: ../../../semester_management.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "SEMESTER_DATABASE_ERROR";
        header("Location: ../../../semester_management.php");
        exit;
    }
} else {
    $_SESSION['STATUS'] = "SEMESTER_ADD_INVALID_REQUEST";
    header("Location: ../../../semester_management.php");
    exit;
}
