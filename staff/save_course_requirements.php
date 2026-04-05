<?php
require_once 'processes/server/conn.php'; // Ensure the correct path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['midterm_exams'])) {
            // For Lecture
            $midterm_exams = $_POST['midterm_exams'];
            $final_exams = $_POST['final_exams'];
            $midterm_quizzes = $_POST['midterm_quizzes'];
            $final_quizzes = $_POST['final_quizzes'];
            $midterm_assignments = $_POST['midterm_assignments'];
            $final_assignments = $_POST['final_assignments'];
            $midterm_activities = $_POST['midterm_activities'];
            $final_activities = $_POST['final_activities'];

            // Check if class_id already exists
            $checkQuery = "SELECT COUNT(*) FROM course_requirements_lecture WHERE class_id = :class_id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':class_id' => $class_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                // If class_id exists, update the record
                $sql = "UPDATE course_requirements_lecture 
                        SET midterm_exams = :midterm_exams, final_exams = :final_exams, 
                            midterm_quizzes = :midterm_quizzes, final_quizzes = :final_quizzes, 
                            midterm_assignments = :midterm_assignments, final_assignments = :final_assignments, 
                            midterm_activities = :midterm_activities, final_activities = :final_activities 
                        WHERE class_id = :class_id";
            } else {
                // If class_id does not exist, insert a new record
                $sql = "INSERT INTO course_requirements_lecture 
                        (class_id, midterm_exams, final_exams, midterm_quizzes, final_quizzes, 
                         midterm_assignments, final_assignments, midterm_activities, final_activities) 
                        VALUES 
                        (:class_id, :midterm_exams, :final_exams, :midterm_quizzes, :final_quizzes, 
                         :midterm_assignments, :final_assignments, :midterm_activities, :final_activities)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':class_id' => $class_id,
                ':midterm_exams' => $midterm_exams,
                ':final_exams' => $final_exams,
                ':midterm_quizzes' => $midterm_quizzes,
                ':final_quizzes' => $final_quizzes,
                ':midterm_assignments' => $midterm_assignments,
                ':final_assignments' => $final_assignments,
                ':midterm_activities' => $midterm_activities,
                ':final_activities' => $final_activities
            ]);

        } elseif (isset($_POST['lab_exams'])) {
            // For Laboratory
            $lab_exams = $_POST['lab_exams'];
            $lab_exercises = $_POST['lab_exercises'];
            $lab_assignments = $_POST['lab_assignments'];

            // Check if class_id already exists in lab table
            $checkQuery = "SELECT COUNT(*) FROM course_requirements_laboratory WHERE class_id = :class_id";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([':class_id' => $class_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                // Update existing record
                $sql = "UPDATE course_requirements_laboratory 
                        SET lab_exams = :lab_exams, lab_exercises = :lab_exercises, assignments = :lab_assignments 
                        WHERE class_id = :class_id";
            } else {
                // Insert new record
                $sql = "INSERT INTO course_requirements_laboratory (class_id, lab_exams, lab_exercises, assignments)
                        VALUES (:class_id, :lab_exams, :lab_exercises, :lab_assignments)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':class_id' => $class_id,
                ':lab_exams' => $lab_exams,
                ':lab_exercises' => $lab_exercises,
                ':lab_assignments' => $lab_assignments
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
