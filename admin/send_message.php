<?php
require __DIR__ . '/../database/connection.php';  // Ensure this file correctly sets up $pdo
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

// Function to fetch user's full name based on type.
// NOTE: students are identified throughout the chat system (session,
// search_users.php, the messages table) by their `student_id` business
// number, NOT the students.id row primary key, and the students table has
// no fullName column (only first_name/middle_name/last_name).
function getUserFullName($pdo, $user_id, $user_type) {
    if ($user_type === 'admin') {
        $stmt = $pdo->prepare("SELECT fullName FROM admin WHERE id = ?");
    } elseif ($user_type === 'staff') {
        $stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = ?");
    } elseif ($user_type === 'student') {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS fullName FROM students WHERE student_id = ?");
    } else {
        return null;
    }

    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ? $user['fullName'] : null;
}

// Fetch names for sender and receiver
$sender_name = getUserFullName($pdo, $sender_id, $sender_type);
$receiver_name = getUserFullName($pdo, $receiver_id, $receiver_type);

if (!$sender_name) {
    echo json_encode(["success" => false, "error" => "Your account could not be found. Please log in again.", "code" => "sender_not_found"]);
    exit;
}

if (!$receiver_name) {
    // Most commonly this happens when replying to an old conversation
    // whose recipient account has since been deleted.
    echo json_encode(["success" => false, "error" => "This contact's account no longer exists. Start a new conversation instead.", "code" => "receiver_not_found"]);
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