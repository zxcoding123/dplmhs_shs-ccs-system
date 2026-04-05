<?php
require_once 'processes/server/conn.php'; // Ensure the correct path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['midterm_exams'])) {
            // Retrieve form inputs
            $midterm_exams = $_POST['midterm_exams'];
            $final_exams = $_POST['final_exams'];
            $midterm_assignments = $_POST['midterm_assignments'];
            $final_assignments = $_POST['final_assignments'];
            $midterm_exercises = $_POST['midterm_exercises'];
            $final_exercises = $_POST['final_exercises'];

            // Check if class_id already exists
            $checkQuery = "SELECT COUNT(*) FROM course_requirements_laboratory WHERE class_id = :class_id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':class_id' => $class_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                // If class_id exists, update the record
                $sql = "UPDATE course_requirements_laboratory 
                        SET midterm_exams = :midterm_exams, final_exams = :final_exams, 
                            midterm_assignments = :midterm_assignments, final_assignments = :final_assignments, 
                            midterm_exercises = :midterm_exercises, final_exercises = :final_exercises
                        WHERE class_id = :class_id";
            } else {
                // If class_id does not exist, insert a new record
                $sql = "INSERT INTO course_requirements_laboratory 
                        (class_id, midterm_exams, final_exams, midterm_assignments, final_assignments, 
                         midterm_exercises, final_exercises) 
                        VALUES 
                        (:class_id, :midterm_exams, :final_exams, :midterm_assignments, :final_assignments, 
                         :midterm_exercises, :final_exercises)";
            }

            // Execute the query
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':class_id' => $class_id,
                ':midterm_exams' => $midterm_exams,
                ':final_exams' => $final_exams,
                ':midterm_assignments' => $midterm_assignments,
                ':final_assignments' => $final_assignments,
                ':midterm_exercises' => $midterm_exercises,
                ':final_exercises' => $final_exercises
            ]);
        }

        $pdo->commit();
        $last_ref = $_SERVER['HTTP_REFERER'];
        header('Location:' . $last_ref );
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
