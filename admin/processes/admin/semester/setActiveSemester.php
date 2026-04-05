<?php
session_start();
require '../../server/conn.php';

// Check if semester ID is passed in URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Check if there’s already an active semester
        $checkActiveStmt = $pdo->prepare("SELECT id, name FROM semester WHERE status = 'active' AND id != :id");
        $checkActiveStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkActiveStmt->execute();
        $existingActive = $checkActiveStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingActive) {
            $_SESSION['STATUS'] = "ACTIVE_SEMESTER_CONFLICT";
            $_SESSION['active_semester_name'] = $existingActive['name'];
            header("Location: ../../../semester_management.php");
            exit();
        }

        // Begin a transaction to ensure atomicity
        $pdo->beginTransaction();

        // Step 1: Set the selected semester as active
        $sql_update_active = "UPDATE semester SET status = 'active' WHERE id = :id";
        $stmt_active = $pdo->prepare($sql_update_active);
        $stmt_active->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_active->execute();

        // Step 2: Set all other semesters to inactive
        $sql_update_inactive = "UPDATE semester SET status = 'inactive' WHERE id != :id AND status != 'archived'";
        $stmt_inactive = $pdo->prepare($sql_update_inactive);
        $stmt_inactive->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_inactive->execute();

        // Fetch the active semester name
        $getActiveSemesterStmt = $pdo->prepare("SELECT name FROM semester WHERE id = :id");
        $getActiveSemesterStmt->bindParam(':id', $id, PDO::PARAM_INT);
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

        // Set success status
        $_SESSION['STATUS'] = "SEMESTER_ACTIVATED";
        header("Location: ../../../semester_management.php");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = 'An error occurred while updating the semester: ' . $e->getMessage();
        header("Location: ../../../semester_management.php");
        exit();
    }
} else {
    $_SESSION['error'] = 'Invalid semester ID.';
    header("Location: ../../../semester_management.php");
    exit();
}
?>