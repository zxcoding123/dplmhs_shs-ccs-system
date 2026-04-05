<?php
session_Start();
require_once 'processes/server/conn.php'; // Make sure the path is correct

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note_id = $_POST['note_id'];

    try {
        // Delete the note from the database
        $sql = "DELETE FROM teacher_notes WHERE id = :note_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':note_id', $note_id, PDO::PARAM_INT);
        $stmt->execute();

        echo 'Note deleted successfully!';
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
