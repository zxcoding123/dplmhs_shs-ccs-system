<?php
require 'processes/server/conn.php';

if (isset($_GET['class'])) {
    $selectedClass = $_GET['class'];

    // Split the class into course and year_level
    $parts = explode('-', $selectedClass);
    if (count($parts) === 2) {
        $course = $parts[0]; // BSIT or BSCS
        $year_level = $parts[1]; // 1A, 2B, etc.

        // Fetch subjects based on the parsed course and year_level
        $stmt = $pdo->prepare("SELECT id, name, code, type FROM subjects WHERE course = :course AND year_level = :year_level");
        $stmt->execute([
            ':course' => $course,
            ':year_level' => $year_level
        ]);

        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the subjects as JSON
        header('Content-Type: application/json');
        echo json_encode($subjects);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid class format. Expected format: Course-YearLevel (e.g., BSIT-1A).']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Class parameter is required.']);
}
?>
