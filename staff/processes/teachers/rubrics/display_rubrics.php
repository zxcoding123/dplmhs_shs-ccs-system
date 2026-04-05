<?php
session_start();
require_once '../../server/conn.php';

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection not established.");
    }

    $class_id = $_GET['class_id'] ?? null;
    $subject_id = $_GET['subject_id'] ?? null;

    if (!$class_id || !$subject_id) {
        echo json_encode(['success' => false, 'message' => 'Missing class_id or subject_id']);
        exit;
    }

    $query = "SELECT id, title FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
    $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rubrics' => $rubrics]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>