<?php
session_start();
require_once '../../server/conn.php';

try {
    $class_id = $_POST['class_id'];

    // Clear existing rubric requirements for this class
    $stmt = $pdo->prepare("DELETE FROM rubric_requirements WHERE class_id = :class_id");
    $stmt->execute([':class_id' => $class_id]);

    // Insert new rubric requirements
    foreach ($_POST as $key => $value) {
        if (preg_match('/^(.+)_midterm$/', $key, $matches)) {
            $rubricTitle = str_replace('_', ' ', $matches[1]);
            $midtermCount = (int)$value;
            $finalKey = $matches[1] . '_final';
            $finalCount = (int)($_POST[$finalKey] ?? 0);

            $stmt = $pdo->prepare("INSERT INTO rubric_requirements (class_id, rubric_title, midterm_count, final_count) 
                                 VALUES (:class_id, :title, :midterm, :final)");
            $stmt->execute([
                ':class_id' => $class_id,
                ':title' => ucwords($rubricTitle), // Capitalize for consistency
                ':midterm' => $midtermCount,
                ':final' => $finalCount
            ]);
        }
    }

    $last_ref = $_SERVER['HTTP_REFERER'];
    header('Location:' . $last_ref );
    exit;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>