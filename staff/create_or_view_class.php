<?php
require 'processes/server/conn.php';
$data = json_decode(file_get_contents("php://input"), true);
$date = $data['date'] ?? null;

if ($date) {
    $existingClassesQuery = $pdo->prepare("SELECT * FROM subjects_schedule WHERE meeting_date = :date");
    $existingClassesQuery->execute(['date' => $date]);
    $existingClasses = $existingClassesQuery->fetchAll(PDO::FETCH_ASSOC);

    if ($existingClasses) {
        echo json_encode(['success' => true, 'classes' => $existingClasses]);
    } else {
        // Create a new class (using placeholder details; adjust as needed)
        $stmt = $pdo->prepare("INSERT INTO subjects (name, type, code, semester) VALUES ('New Class', 'Lecture', '123', 1)");
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'New class created']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating class']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
}
?>
