<?php
session_start();
require_once '../../server/conn.php';

header('Content-Type: application/json');

try {
    $rubricId = isset($_GET['rubric_id']) ? $_GET['rubric_id'] : null;

    if (empty($rubricId)) {
        throw new Exception('Rubric ID is required');
    }

    $stmt = $pdo->prepare("SELECT title, value FROM specific_rubrics WHERE rubric_id = ?");
    $stmt->execute([$rubricId]);
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'criteria' => $criteria
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>