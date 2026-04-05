<?php
// Include database connection (replace with actual database connection code)
require 'processes/server/conn.php';

// Decode the incoming JSON request
$data = json_decode(file_get_contents('php://input'), true);

// Check if required fields are present
if (
    isset($data['class_id']) && 
    isset($data['major_exam']) &&
    isset($data['quizzes']) && 
    isset($data['assignments_activities_attendance'])
) {
    // Clean and sanitize the data
    $classId = intval($data['class_id']);
    $majorExam = floatval($data['major_exam']);
    $quizzes = floatval($data['quizzes']);
    $assignments_activities_attendance = floatval($data['assignments_activities_attendance']);

    // Prepare the SQL query to update the grading schema
    try {
        // Check if grading exists for this class_id
        $stmt = $pdo->prepare("SELECT * FROM lecture_rubrics WHERE class_id = :class_id");
        $stmt->execute(['class_id' => $classId]);

        // If grading schema exists for this class, update it, else insert a new one
        if ($stmt->rowCount() > 0) {
            // Update existing grading schema
            $updateQuery = "
                UPDATE lecture_rubrics SET
                    major_exam = :major_exam,
                    quizzes = :quizzes,
                    assignments_activities_attendance = :assignments_activities_attendance
                WHERE class_id = :class_id
            ";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([
                'major_exam' => $majorExam,
                'quizzes' => $quizzes,
                'assignments_activities_attendance' => $assignments_activities_attendance,
                'class_id' => $classId
            ]);
            echo json_encode(['success' => true, 'message' => 'Grading updated successfully.']);
        } else {
            // If no grading entry exists, insert a new one
            $insertQuery = "
                INSERT INTO lecture_rubrics (class_id, major_exam, quizzes, assignments_activities_attendance)
                VALUES (:class_id, :major_exam, :quizzes, :assignments_activities_attendance)
            ";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([
                'class_id' => $classId,
                'major_exam' => $majorExam,
                'quizzes' => $quizzes,
                'assignments_activities_attendance' => $assignments_activities_attendance,
            ]);
            echo json_encode(['success' => true, 'message' => 'Grading created successfully.']);
        }
    } catch (PDOException $e) {
        // Handle any database errors
        error_log("Error updating grading: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
}
?>
