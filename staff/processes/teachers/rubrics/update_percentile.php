<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust this for security in production
session_start();
require_once '../../server/conn.php'; // Assumes this file defines $pdo or connection details

// Database connection


// Get POST data
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$rubricId = $data['rubric_id'] ?? null;
$percentile = $data['percentile'] ?? null;
$classId = $data['class_id'] ?? null;
$subjectId = $data['subject_id'] ?? null;

if (!$rubricId || $percentile === null || !$classId || !$subjectId) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Validate percentile
$percentile = (float)$percentile;
if ($percentile < 0 || $percentile > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'Percentile must be between 0 and 100'
    ]);
    exit;
}

try {
    // Update the rubric
    $stmt = $pdo->prepare("
        UPDATE rubrics 
        SET percentile = :percentile 
        WHERE id = :id 
        AND class_id = :class_id 
        AND subject_id = :subject_id
    ");
    
    $stmt->execute([
        ':percentile' => $percentile,
        ':id' => $rubricId,
        ':class_id' => $classId,
        ':subject_id' => $subjectId
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Percentile updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No rubric found or no changes made'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Update failed: ' . $e->getMessage()
    ]);
}

$pdo = null;
?>