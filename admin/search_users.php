<?php
session_start();
require "processes/server/conn.php";

$searchTerm = $_GET['query'] ?? '';

// Search across all user tables.
// NOTE: the students table has no fullName column (only first_name /
// middle_name / last_name), so it must be built with CONCAT the same way
// it's precomputed in the admin and staff_accounts tables.
$query = "
    SELECT id, 'admin' AS user_type, fullName FROM admin WHERE fullName LIKE ?
    UNION
    SELECT student_id as id, 'student' AS user_type, CONCAT(first_name, ' ', middle_name, ' ', last_name) AS fullName FROM students WHERE CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?
    UNION
    SELECT id, 'staff' AS user_type, fullName FROM staff_accounts WHERE fullName LIKE ?
";

$stmt = $pdo->prepare($query);
$search = "%$searchTerm%";
$stmt->execute([$search, $search, $search]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>
