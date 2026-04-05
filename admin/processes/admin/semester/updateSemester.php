<?php
session_start();
require '../../server/conn.php';

// Check if the form is submitted via POST and ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id']; // Cast to integer
    $name = trim($_POST['name'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $school_year = $_POST['schoolYear'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // Validate required fields
    if (empty($name) || empty($start_date) || empty($end_date) || empty($school_year)) {
        $_SESSION['STATUS'] = "SEMESTER_FIELDS_EMPTY";
        header("Location: ../../../semester_management.php");
        exit;
    }

    // Check if start and end dates are the same
    if ($start_date === $end_date) {
        $_SESSION['STATUS'] = "SEMESTER_DATE_ERROR";
        header("Location: ../../../semester_management.php");
        exit;
    }

    // Validate that start_date and end_date years match school_year
    $start_year = date('Y', strtotime($start_date));
    $end_year = date('Y', strtotime($end_date));
    if ($start_year != $school_year || $end_year != $school_year) {
        $_SESSION['STATUS'] = "SEMESTER_YEAR_MISMATCH";
        header("Location: ../../../semester_management.php");
        exit;
    }

    // Check if the semester name already exists for a different semester (excluding this one) and is not archived
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM semester WHERE name = :name AND school_year = :school_year AND id != :id AND status != 'archived'");
    $checkStmt->bindParam(':name', $name);
    $checkStmt->bindParam(':school_year', $school_year);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        $_SESSION['STATUS'] = "SEMESTER_NAME_EXISTS";
        header("Location: ../../../semester_management.php");
        exit;
    }

    try {
        // Prepare the update statement with proper parameter binding
        $sql = "UPDATE semester SET name = :name, start_date = :start_date, end_date = :end_date, school_year = :school_year, description = :description, updated_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':school_year', $school_year);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            $_SESSION['STATUS'] = "SEMESTER_UPDATE_SUCCESS";
        } else {
            $_SESSION['STATUS'] = "SEMESTER_UPDATE_NO_CHANGES";
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "SEMESTER_UPDATE_ERROR";
        $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
    }
} else {
    $_SESSION['STATUS'] = "INVALID_REQUEST";
}

// Always redirect back to semester_management.php
header("Location: ../../../semester_management.php");
exit;
?>