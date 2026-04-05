<?php
require 'processes/server/conn.php'; // Include your PDO connection setup

// Check if subjectId is provided in the GET request
if (isset($_GET['subjectId'])) {
    $subjectId = $_GET['subjectId'];

    // Prepare SQL query to fetch the type of the subject
    $query = "SELECT `type` FROM `subjects` WHERE `id` = :subjectId";
    $stmt = $pdo->prepare($query);

    // Bind the subjectId to the query
    $stmt->bindParam(':subjectId', $subjectId, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // If subject exists, return its type
        echo json_encode(['type' => $row['type']]);
    } else {
        // Default value if subject not found
        echo json_encode(['type' => 'Lecture']);
    }

    // Close the statement (optional in PDO)
    $stmt = null;
}
?>
