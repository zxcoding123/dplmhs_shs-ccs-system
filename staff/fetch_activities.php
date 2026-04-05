<?php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

$class_id = $_GET['class_id']; // Get the class ID from the request
$subject_id = $_GET['subject_id']; // Get the subject ID from the request

// Prepare the SQL statement to fetch activities
$stmt = $pdo->prepare("SELECT * FROM activities WHERE class_id = ? AND subject_id = ? ORDER BY id ASC");
$stmt->execute([$class_id, $subject_id]);

// Fetch all activities
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check counts for requirements
$requirements = [
    'midterm_quizzes' => 0,
    'final_quizzes' => 0,
    'midterm_exams' => 0,
    'final_exams' => 0,
    'lab_exercises' => 0
];

foreach ($activities as $activity) {
    if ($activity['type'] === 'quiz' && strtolower($activity['term']) === 'midterm') {
        $requirements['midterm_quizzes']++;
    } elseif ($activity['type'] === 'quiz' && strtolower($activity['term']) === 'final') {
        $requirements['final_quizzes']++;
    } elseif ($activity['type'] === 'exam' && strtolower($activity['term']) === 'midterm') {
        $requirements['midterm_exams']++;
    } elseif ($activity['type'] === 'exam' && strtolower($activity['term']) === 'final') {
        $requirements['final_exams']++;
    } elseif ($activity['type'] === 'laboratory') {
        $requirements['lab_exercises']++;
    }
}

// Create an array to hold the formatted activities
$activityList = [];
foreach ($activities as $activity) {
    $activityList[] = [
        'id' => $activity['id'],
        'title' => $activity['title'],
        'type' => $activity['type'],
        'message' => $activity['message'],
        'due_date' => $activity['due_date'],
        'due_time' => $activity['due_time'],
        'min_points' => $activity['min_points'],
        'max_points' => $activity['max_points'],
        'term' => $activity['term']
    ];
}

// Check if the class is laboratory-based
$isLaboratory = false;
$classStmt = $pdo->prepare("SELECT type FROM classes WHERE id = ?");
$classStmt->execute([$class_id]);
$class = $classStmt->fetch(PDO::FETCH_ASSOC);
if ($class && strtolower($class['type']) === 'laboratory') {
    $isLaboratory = true;
}

// Return the activities, requirements, and laboratory status as JSON
header('Content-Type: application/json');
echo json_encode([
    'activities' => $activityList,
    'requirements' => $requirements,
    'isLaboratory' => $isLaboratory
]);
?>
