<?php
require 'processes/server/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $classId = $_POST['class_id'];
    $type = $_POST['type'];
    $value = floatval($_POST['value']); // Ensure value is a number

    // Determine column to update
    $column = "";
    if ($type === "quizzes") {
        $column = "quizzes";
    } elseif ($type === "assignments") {
        $column = "assignments_activities_attendance";
    } elseif ($type === "exam") {
        $column = "major_exam";
    }

    if ($column) {
        // Check if the class_id exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM lecture_rubrics WHERE class_id = :class_id");
        $checkStmt->execute(['class_id' => $classId]);
        $exists = $checkStmt->fetchColumn();

        if ($exists == 0) {
            // Insert a new record if not exists
            $insertStmt = $pdo->prepare("INSERT INTO lecture_rubrics (class_id, quizzes, assignments_activities_attendance, major_exam) 
                                         VALUES (:class_id, 0, 0, 0)");
            $insertStmt->execute(['class_id' => $classId]);
        }

        // Update the specific column
        $stmt = $pdo->prepare("UPDATE lecture_rubrics SET $column = :value WHERE class_id = :class_id");
        $stmt->execute(['value' => $value, 'class_id' => $classId]);

        echo "Updated successfully";
    } else {
        echo "Invalid type";
    }
}
?>
