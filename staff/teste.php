<?php
require 'processes/server/conn.php'; // Ensure this points to the correct database connection file

$class_id = $_GET['class_id'] ?? null;
$startTime = $endTime = '';

try {
    if ($class_id) {
        // Step 1: Retrieve subject_id from classes table
        $stmt = $pdo->prepare("SELECT subject_id FROM classes WHERE id = :class_id LIMIT 1");
        $stmt->execute(['class_id' => $class_id]);
        $classData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($classData && isset($classData['subject_id'])) {
            $subject_id = $classData['subject_id'];

            // Step 2: Retrieve start_time and end_time from subjects_schedules table
            $stmt = $pdo->prepare("SELECT start_time, end_time FROM subjects_schedules WHERE subject_id = :subject_id LIMIT 1");
            $stmt->execute(['subject_id' => $subject_id]);
            $scheduleData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($scheduleData) {
                // Format times to display in <input type="time">
                $startTime = date('H:i', strtotime($scheduleData['start_time']));
                $endTime = date('H:i', strtotime($scheduleData['end_time']));
            }
        }
    }

    echo $startTime;
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
