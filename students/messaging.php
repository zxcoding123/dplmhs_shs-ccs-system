<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
session_start();
require 'processes/server/conn.php'; // Include your PDO connection setup
$action = $_GET['action'] ?? null;

if ($action === 'search_users') {
    searchUsers();
} elseif ($action === 'get_conversation') {
    getConversation();
} elseif ($action === 'send_message') {
    sendMessage();
} elseif ($action === 'get_recent_conversations') {
    get_recent_conversations();
} elseif ($action === 'mark_messages_as_read') {
    mark_messages_as_read();
} else if ($action === 'getCount') {
    getCount();
} else if ($action === 'get_unread_messages') {
    getUnreadMessages();
} else if($action ==='delete_message'){
    delete_message();
}

function delete_message(){
    global $pdo;
    $messageId = $_GET['message_id'];
    $query = "DELETE FROM messages WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$messageId]);
    echo json_encode(['status' => 'success']);
    exit;
}


function getCount()
{

    global $pdo;
    $userId = $_SESSION['user_id']; // Assuming user is logged in

    $query = "SELECT COUNT(*) AS unreadCount FROM messages WHERE status = 'unread' AND receiver_id = :userId AND receiver_type = :userType";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':userId' => $userId,
        ':userType' => 'admin'  // Adjust this based on the logged-in user type
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the unread message count as JSON response
    echo json_encode(['unreadCount' => $result['unreadCount']]);
}

function mark_messages_as_read()
{
    global $pdo;

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get parameters from the URL
        $senderId = $_GET['sender_id'];
        $senderType = $_GET['sender_type'];
        $receiverId = $_GET['receiver_id'];
        $receiverType = $_GET['receiver_type'];

        // Update message status to 'read' in the database
        $updateQuery = "UPDATE messages SET status = 'read' WHERE sender_id = :receiverId AND sender_type = :receiverType AND receiver_id = :senderId AND receiver_type = :senderType AND status = 'unread'";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            ':senderId' => $senderId,
            ':senderType' => $senderType,
            ':receiverId' => $receiverId,
            ':receiverType' => $receiverType,
        ]);
    }
}




function searchUsers()
{
    global $pdo;
    $query = $_GET['query'] ?? '';
    $query = "%$query%";

    // Get the logged-in user's full name from the session
    $currentUserFullName = $_SESSION['full_name'];

    $results = [];
    $tables = [
        'admin' => 'fullName AS name, id, "admin" AS type, fullName',  // Fields for admin table
        'students' => 'fullName AS name, student_id AS id, "student" AS type, fullName',  // Fields for students table
        'staff_accounts' => 'fullName AS name, id, "staff" AS type, fullName',  // Fields for staff table
    ];

    foreach ($tables as $table => $fields) {
        // Adjust the WHERE clause
        if ($table == 'admin') {
            $stmt = $pdo->prepare("SELECT $fields FROM $table WHERE fullName LIKE :query AND fullName != :currentUserFullName");
        } else {
            $stmt = $pdo->prepare("SELECT $fields FROM $table WHERE fullName LIKE :query AND fullName != :currentUserFullName");
        }

        // Bind parameters
        $stmt->bindParam(':query', $query, PDO::PARAM_STR);
        $stmt->bindParam(':currentUserFullName', $currentUserFullName, PDO::PARAM_STR);

        // Execute and merge the results
        $stmt->execute();
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Return a JSON response
    echo json_encode(['success' => true, 'data' => $results]);
    exit;  // Ensure no extra output is sent after the response
}

function getConversation()
{
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
        exit;
    }

    global $pdo;

    // Get logged-in user details from session
    $userId = (int) $_SESSION['user_id'];
    $userType = trim($_SESSION['user_type']);

    // Get receiver details from GET parameters
    $receiverId = isset($_GET['receiver_id']) ? (int) $_GET['receiver_id'] : 0;
    $receiverType = isset($_GET['receiver_type']) ? trim($_GET['receiver_type']) : '';

    // Debug session and GET parameters
    error_log("Session - user_id: $userId, user_type: $userType");
    error_log("GET - receiver_id: $receiverId, receiver_type: $receiverType");

    // Validate required parameters
    if (!$userId || !$userType || !$receiverId || !$receiverType) {
        error_log("Missing parameters: userId=$userId, userType=$userType, receiverId=$receiverId, receiverType=$receiverType");
        echo json_encode(['success' => false, 'message' => 'Invalid or missing parameters.']);
        exit;
    }

    try {
        // Query to fetch messages between the logged-in user and the specified receiver
        $sql = "
            SELECT id, sender_id, sender_type, receiver_id, receiver_type, message, timestamp 
            FROM messages
            WHERE 
                (sender_id = '$userId' AND sender_type = '$userType' AND receiver_id = '$receiverId' AND receiver_type = '$receiverType')
                OR
                (sender_id = '$receiverId' AND sender_type = '$receiverType' AND receiver_id =  '$userId' AND receiver_type = '$userType')
            ORDER BY timestamp ASC
        ";

        $stmt = $pdo->prepare($sql);

        // Debug the query execution
        error_log("Executing query with user_id=$userId, user_type=$userType, receiver_id=$receiverId, receiver_type=$receiverType");

        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($messages)) {
            echo json_encode(['success' => true, 'message' => 'No messages found.', 'data' => []]);
        } else {
            echo json_encode(['success' => true, 'data' => $messages]);
        }
    } catch (PDOException $e) {
        error_log("Error executing query: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching messagesss.']);
    }
}



function getRecentConversations()
{
    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
        exit;
    }

    global $pdo;
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];

    // Log the session values for debugging
    error_log("User ID: $userId, User Type: $userType");

    // Validate GET parameters
    if (!isset($_GET['user_id'], $_GET['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        exit;
    }

    $receiverId = $_GET['user_id'];
    $receiverType = $_GET['user_type'];

    // Log GET parameters for debugging
    error_log("Received Receiver ID: $receiverId");
    error_log("Received Receiver Type: $receiverType");

    // Prepare the SQL query dynamically using placeholders
    $query = "
        SELECT sender_id, sender_type, message, timestamp
        FROM messages
        WHERE
            (
                (sender_id = :currentUserId AND sender_type = :currentUserType AND receiver_id = :receiverId AND receiver_type = :receiverType)
                OR
                (sender_id = :receiverId AND sender_type = :receiverType AND receiver_id = :currentUserId AND receiver_type = :currentUserType)
            )
        ORDER BY timestamp ASC
    ";

    $stmt = $pdo->prepare($query);

    // Bind parameters to ensure safety and prevent SQL injection
    $stmt->bindParam(':currentUserId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':currentUserType', $userType, PDO::PARAM_STR);
    $stmt->bindParam(':receiverId', $receiverId, PDO::PARAM_INT);
    $stmt->bindParam(':receiverType', $receiverType, PDO::PARAM_STR);

    // Execute the query
    $stmt->execute();

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the results for debugging
    error_log("Messages fetched: " . print_r($messages, true));

    // Return messages or an empty array if none are found
    if (empty($messages)) {
        error_log('No messages found for the conversation.');
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }

    echo json_encode(['success' => true, 'data' => $messages]);
}

function get_recent_conversations()
{

    // Ensure user_id and user_type are set in the session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
        exit;
    }

    global $pdo;

    // Fetch recent conversations
    try {
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'];


        $query = "
        SELECT 
            sender_id, 
            sender_type, 
            sender_name,
            receiver_id, 
            receiver_type, 
            receiver_name,
            message, 
            timestamp,
            status,
            CASE 
                WHEN sender_id = $userId AND sender_type = '$userType' THEN receiver_name
                ELSE sender_name
            END AS conversation_name
        FROM messages
        WHERE (
                (sender_id = $userId AND sender_type = '$userType') 
             OR (receiver_id = $userId AND receiver_type = '$userType')
              )
        AND NOT (sender_id = receiver_id AND sender_type = receiver_type)
        GROUP BY 
            LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
        ORDER BY timestamp ASC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging: Log the result of the query
        error_log("Fetched conversations: " . print_r($conversations, true));

        // Check if we have any results
        if ($conversations) {
            echo json_encode(['success' => true, 'data' => $conversations]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No conversations found.']);
        }
    } catch (PDOException $e) {
        error_log("Error fetching conversations: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

}

function getUnreadMessages()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
        exit;
    }

    global $pdo;
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];

    try {
        $query = "
            SELECT 
                sender_id, 
                sender_type,
                sender_name,
                receiver_id, 
                receiver_type,
                message,
                timestamp,
                status
            FROM messages
            WHERE 
                receiver_id = :userId 
                AND receiver_type = :userType
                AND status = 'unread'
            ORDER BY timestamp DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['userId' => $userId, 'userType' => $userType]);

        $unreadMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $unreadMessages]);
    } catch (PDOException $e) {
        error_log("Error fetching unread messages: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}



function sendMessage()
{
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['name'])) {
        echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
        exit;
    }

    // Check incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['message'], $data['receiver_id'], $data['receiver_type'], $data['receiver_name'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Extract data
    $message = trim($data['message']);
    $receiverId = $data['receiver_id'];
    $receiverType = $data['receiver_type'];
    $receiverName = trim($data['receiver_name']);
    $senderId = $_SESSION['user_id'];
    $senderType = $_SESSION['user_type'];
    $senderName = $_SESSION['name'];

    // Ensure the message is not empty
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    global $pdo;

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, sender_type, sender_name, receiver_id, receiver_type, receiver_name, message) 
            VALUES (:sender_id, :sender_type, :sender_name, :receiver_id, :receiver_type, :receiver_name, :message)
        ");

        // Bind parameters
        $stmt->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindParam(':sender_type', $senderType, PDO::PARAM_STR);
        $stmt->bindParam(':sender_name', $senderName, PDO::PARAM_STR);
        $stmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_type', $receiverType, PDO::PARAM_STR);
        $stmt->bindParam(':receiver_name', $receiverName, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);

        // Execute and check the insertion
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
        }
    } catch (PDOException $e) {
        error_log("Error sending message: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
