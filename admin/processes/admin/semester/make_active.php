<?php
require '../../server/conn.php';
session_start(); // Start session for status messages

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $semester_id = $_GET['id'];

    try {
        // Begin transaction to ensure consistency
        $pdo->beginTransaction();

        // Set all other semesters to 'inactive'
        $updateOthersStmt = $pdo->prepare("UPDATE semester SET status = 'inactive' WHERE id != :id AND status != 'archived'");
        $updateOthersStmt->bindParam(':id', $semester_id);
        $updateOthersStmt->execute();

        // Set the selected semester as 'active'
        $setActiveStmt = $pdo->prepare("UPDATE semester SET status = 'active' WHERE id = :id");
        $setActiveStmt->bindParam(':id', $semester_id);
        $setActiveStmt->execute();

        // Fetch the active semester name
        $getActiveSemesterStmt = $pdo->prepare("SELECT name FROM semester WHERE id = :id");
        $getActiveSemesterStmt->bindParam(':id', $semester_id);
        $getActiveSemesterStmt->execute();
        $activeSemester = $getActiveSemesterStmt->fetch(PDO::FETCH_ASSOC);

        if ($activeSemester) {
            // Delete any existing data in the current_semester table
            $deleteCurrentSemesterStmt = $pdo->prepare("DELETE FROM current_semester");
            $deleteCurrentSemesterStmt->execute();

            // Insert the new active semester into the current_semester table
            $insertCurrentSemesterStmt = $pdo->prepare("INSERT INTO current_semester (semester) VALUES (:semester)");
            $insertCurrentSemesterStmt->bindParam(':semester', $activeSemester['name']);
            $insertCurrentSemesterStmt->execute();
        }

        // Commit the transaction
        $pdo->commit();
        
        $_SESSION['STATUS'] = "SEMESTER_ACTIVATED";
    } catch (PDOException $e) {
        // Rollback transaction if something went wrong
        $pdo->rollBack();
        $_SESSION['STATUS'] = "SEMESTER_ACTIVATION_ERROR";
        $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
    }

    // Redirect to semesters.php
    // header("Location: ../../../semester_management.php");
    exit();
} else {
    $_SESSION['STATUS'] = "INVALID_REQUEST";
    header("Location: ../../../semester_management.php");
    exit();
}
