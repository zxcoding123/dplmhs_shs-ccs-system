<?php
session_start();
require 'processes/server/conn.php'; // Include your PDO connection setup

// Get the search term and class ID from the AJAX request
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$classId = isset($_GET['class_id']) ? $_GET['class_id'] : '';

// Query students based on the search term and class_id
$studentStmt = $pdo->prepare("
    SELECT students.student_id, students.first_name, students.middle_name, students.last_name, students.gender, students.course, students.year_level
    FROM students_enrollments
    JOIN students ON students_enrollments.student_id = students.student_id
    WHERE students_enrollments.class_id = :class_id
    AND (LOWER(students.first_name) LIKE :searchTerm 
        OR LOWER(students.middle_name) LIKE :searchTerm 
        OR LOWER(students.last_name) LIKE :searchTerm)
");
$studentStmt->execute([
    'class_id' => $classId,          // Make sure class_id is correct
    'searchTerm' => '%' . strtolower($searchTerm) . '%'  // Add wildcard before and after search term
]);

$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Group students by gender
$groupedStudents = ['Male' => [], 'Female' => []];
foreach ($students as $student) {
    $gender = htmlspecialchars($student['gender']);
    if (isset($groupedStudents[$gender])) {
        $groupedStudents[$gender][] = $student;
    }
}

// Return students as JSON response
echo json_encode($groupedStudents);
?>
