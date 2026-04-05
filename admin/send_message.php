<?php
require "processes/server/conn.php";  // Ensure this file correctly sets up $pdo
session_start();
date_default_timezone_set('Asia/Manila');

// Validate session variables
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(["success" => false, "error" => "Session data missing"]);
    exit;
}

$sender_id = $_SESSION['user_id'];
$sender_type = $_SESSION['user_type'];

// Validate POST data
if (!isset($_POST['receiver_id']) || !isset($_POST['receiver_type']) || !isset($_POST['message'])) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$receiver_id = $_POST['receiver_id'];
$receiver_type = $_POST['receiver_type'];
$message = trim($_POST['message']);

// Function to fetch user's full name based on type
function getUserFullName($pdo, $user_id, $user_type) {
    $tables = ["admin" => "admin", "student" => "students", "staff" => "staff_accounts"];
    if (!isset($tables[$user_type])) return null;

    $stmt = $pdo->prepare("SELECT fullName FROM " . $tables[$user_type] . " WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ? $user['fullName'] : null;
}

// Fetch names for sender and receiver
$sender_name = getUserFullName($pdo, $sender_id, $sender_type);
$receiver_name = getUserFullName($pdo, $receiver_id, $receiver_type);

if (!$sender_name || !$receiver_name) {
    echo json_encode(["success" => false, "error" => "User not found"]);
    exit;
}

// Insert into database
$stmt = $pdo->prepare("INSERT INTO messages 
    (sender_id, sender_type, receiver_id, receiver_type, message, sender_name, receiver_name, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')");

if ($stmt->execute([$sender_id, $sender_type, $receiver_id, $receiver_type, $message, $sender_name, $receiver_name])) {
    $id = $pdo->lastInsertId(); // Use 'id' as the column name
    echo json_encode([
        "success" => true,
        "id" => $id, // Return 'id' instead of 'message_id'
        "sender_id" => $sender_id,
        "sender_type" => $sender_type,
        "receiver_id" => $receiver_id,
        "receiver_type" => $receiver_type,
        "message" => $message,
        "sender_name" => $sender_name,
        "receiver_name" => $receiver_name,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
} else {
    error_log("SQL Error: " . implode(" | ", $stmt->errorInfo()));
    echo json_encode(["success" => false, "error" => "Database error"]);
}
?>