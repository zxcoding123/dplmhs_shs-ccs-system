<?php
require 'processes/server/conn.php';

// Get the subject from the query string
$subject = $_GET['subject'] ?? '';

if (!$subject) {
    echo json_encode(['error' => 'Subject is required']);
    exit;
}

try {
    // Query to get the subject types based on the subject
    $stmt = $pdo->prepare("SELECT type FROM subjects WHERE name = :subject");
    $stmt->bindParam(':subject', $subject);
    $stmt->execute();

    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($types) {
        echo json_encode(['types' => $types]);
    } else {
        echo json_encode(['types' => []]); // No types found
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error fetching subject types: ' . $e->getMessage()]);
}
