<?php
session_start();
include('../../server/conn.php');
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (isset($_GET['id'])) {
        $id = htmlspecialchars($_GET['id']);
        $sql = "DELETE FROM admin_notes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "NOTES_DELETED_SUCCESFULLY";
            header("Location: ../../../dashboard.php");
            exit();
        } else {
            echo "Error: Could not delete the note.";
        }
    } else {
        echo "Invalid request. No note ID provided.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
