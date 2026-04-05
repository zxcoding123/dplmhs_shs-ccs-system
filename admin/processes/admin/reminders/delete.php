<?php
session_start();
include('../../server/conn.php');

    if (isset($_GET['id'])) {
        $id = htmlspecialchars($_GET['id']);
        $sql = "DELETE FROM admin_reminders WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "REMINDERS_DELETED_SUCCESFULLY";
            header("Location: ../../../index.php");
            exit();
        } else {
            echo "Error: Could not delete the note.";
        }
    } else {
        echo "Invalid request. No note ID provided.";
    }
?>