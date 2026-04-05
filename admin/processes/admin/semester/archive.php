<?php
require '../../server/conn.php';
session_start(); // Start session for status messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semesterId = $_POST['id'];
    $semesterName = $_POST['name'];
    $archiveReason = $_POST['archive_reason'] ?? ''; // Handle reason, optional
    $archivedAt = date('Y-m-d H:i:s'); // Current timestamp for archived_at

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Archive the semester
        $stmt = $pdo->prepare("UPDATE semester SET archived = 1 WHERE id = ?");
        $stmt->execute([$semesterId]);

        $stmt = $pdo->prepare("UPDATE semester SET status = 'archived' WHERE id = ?");
        $stmt->execute([$semesterId]);

        // Archive all classes related to this semester
        $stmt = $pdo->prepare("UPDATE classes SET is_archived = 1 WHERE semester = ?");
        $stmt->execute([$semesterName]);

        // Archive all subjects related to this semester
        $stmt = $pdo->prepare("UPDATE subjects SET is_archived = 1 WHERE semester = ?");
        $stmt->execute([$semesterName]);

        // Insert into `archived_semesters` table
        $stmt = $pdo->prepare("
            INSERT INTO archived_semesters (semester_id, archive_reason, archived_at) 
            SELECT id, ?, ? 
            FROM semester 
            WHERE id = ?
        ");
        $stmt->execute([$archiveReason, $archivedAt, $semesterId]);


        $deleteCurrentSemesterStmt = $pdo->prepare("DELETE FROM current_semester");
        $deleteCurrentSemesterStmt->execute();

        
        // Commit transaction
        $pdo->commit();

        

        // Set session status for success
        $_SESSION['STATUS'] = "SEMESTER_ARCHIVED_SUCCESS";
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $pdo->rollBack();

        // Set session status for error
        $_SESSION['STATUS'] = "SEMESTER_ARCHIVE_FAILED";
    }

    // Redirect after setting session status
    header("Location: ../../../semester_management.php");
    exit;
}
?>