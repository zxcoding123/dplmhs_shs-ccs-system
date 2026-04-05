<?php
session_start();
require_once '../../server/conn.php'; // Ensure correct path

if (isset($_GET['resource_id'])) {
    $resource_id = intval($_GET['resource_id']); // Ensure it's an integer

    try {
        // Prepare the SQL statement to delete the resource
        $stmt = $pdo->prepare("DELETE FROM learning_resources WHERE resource_id = :resource_id");
        $stmt->bindParam(':resource_id', $resource_id, PDO::PARAM_INT);

        // Execute the statement
        if ($stmt->execute()) {
            echo "Resource deleted successfully.";
        } else {
            echo "Failed to delete the resource. Please try again.";
        }
    } catch (PDOException $e) {
        // Handle errors gracefully
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No resource ID provided.";
}
