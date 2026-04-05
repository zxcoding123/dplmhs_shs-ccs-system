<?php
require 'processes/server/conn.php';

$query = $_GET['query'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$searchTerm = "%$query%";

// Search across all user types
$members = [];

// Search admins
$stmt = $pdo->prepare("
    SELECT id, fullName, 'admin' as type 
    FROM admin 
    WHERE fullName LIKE ? OR email LIKE ?
    LIMIT 5
");
$stmt->execute([$searchTerm, $searchTerm]);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
$members = array_merge($members, $admins);

// Search staff
$stmt = $pdo->prepare("
    SELECT id, fullName, 'staff' as type 
    FROM staff_accounts 
    WHERE fullName LIKE ? OR email LIKE ?
    LIMIT 5
");
$stmt->execute([$searchTerm, $searchTerm]);
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
$members = array_merge($members, $staff);

// Search students
$stmt = $pdo->prepare("
    SELECT id, fullName, 'student' as type 
    FROM students 
    WHERE fullName LIKE ? OR email LIKE ? OR student_id LIKE ?
    LIMIT 5
");
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$members = array_merge($members, $students);

echo json_encode($members);
?>