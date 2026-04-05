<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust this for security in production
session_start();
require_once 'processes/server/conn.php'; // Assumes this file defines $pdo or connection details

// Get parameters
$classId = isset($_GET['class_id']) ? $_GET['class_id'] : null;
$subjectId = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;

if (!$classId || !$subjectId) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

try {
    // Adjust the query based on your table structure
    $stmt = $pdo->prepare("
        SELECT id, title, percentile 
        FROM rubrics 
        WHERE class_id = :class_id 
        AND subject_id = :subject_id
    ");
    $stmt->execute([
        ':class_id' => $classId,
        ':subject_id' => $subjectId
    ]);
    
    $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'rubrics' => $rubrics
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
}

$pdo = null;
?>