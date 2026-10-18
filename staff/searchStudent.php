<?php
require __DIR__ . '/../database/connection.php'; // Ensure this points to your database connection file
require_once __DIR__ . '/../includes/academic.php';

// Get the search term and class ID from AJAX request
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$classId = isset($_GET['class_id']) ? $_GET['class_id'] : '';

// Prepare search term with wildcards for the SQL LIKE operator
$searchTerm = "%" . strtolower($searchTerm) . "%";

// Step 1: Retrieve the class name from the classes table
$stmtClass = $pdo->prepare("SELECT name FROM classes WHERE id = :class_id");
$stmtClass->bindParam(':class_id', $classId, PDO::PARAM_INT);
$stmtClass->execute();
$class = $stmtClass->fetch(PDO::FETCH_ASSOC);

if ($class) {
    $className = $class['name'];

    // Step 2-3: Get the grade level from the section code (e.g., STEM-11A → "Grade 11")
    $section = shs_parse_section($className);
    $yearLevel = $section['grade_level'] ?? null;

    if ($yearLevel) {
        // Step 4: Query students based on year level, search term, and class_id
        $stmt = $pdo->prepare("
            SELECT s.student_id, s.fullName, s.course, s.year_level
            FROM students s
            LEFT JOIN students_enrollments se ON s.student_id = se.student_id AND se.class_id = :class_id
            WHERE LOWER(s.fullName) LIKE :searchTerm 
              AND s.year_level = :year_level 
              AND se.student_id IS NULL
            ORDER BY s.fullName
        ");
        $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':year_level', $yearLevel, PDO::PARAM_STR);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return results as JSON response
        echo json_encode($students);
    } else {
        echo json_encode(['error' => 'Invalid year level mapping for the class name.']);
    }
} else {
    echo json_encode(['error' => 'Class not found for the provided class ID.']);
}
?>