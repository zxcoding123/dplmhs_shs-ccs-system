<?php
require '../../../../server/conn.php'; // Adjust path based on directory structure
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id)) {
        echo "Error: No ID provided.";
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo "Success"; // Simple response for fetch
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}
?>