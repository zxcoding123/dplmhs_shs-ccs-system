<?php
// Assuming you already have the database connection set up.
require 'processes/server/conn.php'; // Include your PDO connection setup

// Get course and year_level from the POST request
$course = isset($_GET['course']) ? $_GET['course'] : '';
$year_level = isset($_GET['year_level']) ? $_GET['year_level'] : '';

if ($course && $year_level) {
    try {
        // Query to fetch the subjects for the selected course and year_level
        $sql = "
            SELECT * 
            FROM subjects
            WHERE course = :course AND year_level = :year_level
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':course' => $course,
            ':year_level' => $year_level
        ]);

        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any subjects are found
        if (!empty($subjects)) {
            foreach ($subjects as $subject) {
                echo "<option value='" . htmlspecialchars($subject['name']) . "'>" .
                    htmlspecialchars($subject['name']) . " (" .
                    htmlspecialchars($subject['type']) . ")</option>";
            }
        } else {
            echo "<option value='' disabled>No subjects available.</option>";
        }
    } catch (PDOException $e) {
        echo "<option value='' disabled>Error fetching subjects: " . $e->getMessage() . "</option>";
    }
} else {
    echo "<option value='' disabled>Please select both course and year level</option>";
}
?>