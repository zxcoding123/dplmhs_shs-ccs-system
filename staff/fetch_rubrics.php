<?php
require_once 'processes/server/conn.php'; // Database connection

$class_id = $_GET['class_id'] ?? null;
$subject_id = $_GET['subject_id'] ?? null;

if (!$class_id || !$subject_id) {
    echo json_encode(['success' => false, 'message' => 'Missing class or subject ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, title, percentile FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id");
    $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
    $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rubrics' => $rubrics]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
