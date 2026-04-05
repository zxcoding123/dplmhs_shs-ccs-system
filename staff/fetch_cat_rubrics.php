<?php
require 'processes/server/conn.php'; // Include your PDO connection file

header('Content-Type: application/json');

if (isset($_GET['class_id']) && isset($_GET['subject_id'])) {
    $class_id = filter_input(INPUT_GET, 'class_id', FILTER_SANITIZE_NUMBER_INT);
    $subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$class_id || !$subject_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters (class_id, subject_id)']);
        exit;
    }

    try {
        // Fetch rubrics related to the class and subject
        $stmt = $pdo->prepare("SELECT id, title FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id");
        $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
        $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'rubrics' => $rubrics]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching rubrics: ' . $e->getMessage()]);
    }
}
?>
