<?php
// Include your database connection
require 'processes/server/conn.php';

// Check if the 'course' parameter is provided
if (isset($_GET['course'])) {
    $course = $_GET['course'];

    try {
        // Fetch the year levels associated with the course
        $stmt = $pdo->prepare("SELECT DISTINCT year_level FROM courses WHERE course = :course ORDER BY year_level");
        $stmt->bindParam(':course', $course);
        $stmt->execute();

        $yearLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the year levels as a JSON response
        echo json_encode(['year_levels' => $yearLevels]);

    } catch (PDOException $e) {
        // Handle any errors
        echo json_encode(['year_levels' => []]);
    }
} else {
    echo json_encode(['year_levels' => []]);
}
?>
