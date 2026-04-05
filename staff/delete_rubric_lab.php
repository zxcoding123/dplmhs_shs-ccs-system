<?php
// delete_rubric.php
require 'processes/server/conn.php'; // Ensure this points to your database connection file

if (isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];

    // Prepare the DELETE query to remove the rubric based on class_id
    $stmt = $pdo->prepare("DELETE FROM laboratory_rubrics WHERE class_id = :class_id");
    
    // Execute the query
    if ($stmt->execute([':class_id' => $class_id])) {
        echo 'Rubric deleted successfully';
    } else {
        echo 'Failed to delete rubric';
    }
}
?>
