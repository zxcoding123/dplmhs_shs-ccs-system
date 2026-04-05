<?php
require '../vendor/autoload.php';
require "processes/server/conn.php";  // Include database connection

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    protected $clients;
    private $pdo; // Database connection

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        echo "Raw message received: $msg\n"; // Log incoming messages
        $data = json_decode($msg, true);
    
        if (!$data) {
            echo "Invalid JSON received!\n";
            return;
        }
    
        // Print the JSON data before sending it to clients
        $response = json_encode([
            "message" => $data['message'],
            "sender_id" => $data['sender_id'],
        ]);
        echo "Sending response: $response\n";
    
        foreach ($this->clients as $client) {
            $client->send($response);
        }
        
        echo "Message received: {$msg}\n";
        $data = json_decode($msg, true);

        if (!isset($data['sender_id'], $data['sender_type'], $data['receiver_id'], $data['receiver_type'], $data['message'])) {
            echo "Invalid message format\n";
            return;
        }

        $sender_id = $data['sender_id'];
        $sender_type = $data['sender_type'];
        $receiver_id = $data['receiver_id'];
        $receiver_type = $data['receiver_type'];
        $message = $data['message'];

        // Fetch sender and receiver names
        $sender_name = $this->getUserFullName($sender_id, $sender_type);
        $receiver_name = $this->getUserFullName($receiver_id, $receiver_type);

        if (!$sender_name || !$receiver_name) {
            echo "User not found!\n";
            return;
        }

        // // Store message in the database
        // $stmt = $this->pdo->prepare("INSERT INTO messages 
        //     (sender_id, sender_type, receiver_id, receiver_type, message, sender_name, receiver_name, status) 
        //     VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')");
        // $stmt->execute([$sender_id, $sender_type, $receiver_id, $receiver_type, $message, $sender_name, $receiver_name]);

        // // Broadcast the message to all clients
        // foreach ($this->clients as $client) {
        //     $client->send(json_encode([
        //         "sender_id" => $sender_id,
        //         "sender_type" => $sender_type,
        //         "receiver_id" => $receiver_id,
        //         "receiver_type" => $receiver_type,
        //         "message" => $message,
        //         "sender_name" => $sender_name,
        //         "receiver_name" => $receiver_name
        //     ]));
        // }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    // Function to get the full name of the user
    private function getUserFullName($user_id, $user_type) {
        $tables = ["admin" => "admin", "student" => "students", "staff" => "staff_accounts"];

        if (!isset($tables[$user_type])) return null;

        $stmt = $this->pdo->prepare("SELECT fullName FROM " . $tables[$user_type] . " WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ? $user['fullName'] : null;
    }
}

// Create WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($pdo) // Pass database connection
        )
    ),
    8080 // WebSocket server listens on port 8080
);

echo "WebSocket server running on ws://localhost:8080\n";
$server->run();
