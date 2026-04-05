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
    private $pdo;

    public function __construct($pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Raw message received: $msg\n";
        $data = json_decode($msg, true);

        if (!$data) {
            echo "Invalid JSON received!\n";
            return;
        }

        if (isset($data['action']) && $data['action'] === 'deleteMessage') {
            $id = $data['id'] ?? null; // Use 'id' instead of 'message_id'
            if (!$id) {
                echo "No id provided for deletion!\n";
                return;
            }

            // Delete from database
            $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$id]);

            // Broadcast deletion
            $deleteMessage = [
                "action" => "messageDeleted",
                "id" => $id // Use 'id'
            ];
            foreach ($this->clients as $client) {
                $client->send(json_encode($deleteMessage));
            }
            echo "Message deleted and broadcast: $id\n";
        } else {
            // Handle new message
            $required_fields = ['sender_id', 'sender_type', 'receiver_id', 'receiver_type', 'message', 'id', 'timestamp'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field])) {
                    echo "Missing field: $field\n";
                    return;
                }
            }

            $sender_id = $data['sender_id'];
            $sender_type = $data['sender_type'];
            $receiver_id = $data['receiver_id'];
            $receiver_type = $data['receiver_type'];
            $message = $data['message'];
            $id = $data['id']; // Use 'id'
            $timestamp = $data['timestamp'];

            $sender_name = $data['sender_name'] ?? $this->getUserFullName($sender_id, $sender_type);
            $receiver_name = $data['receiver_name'] ?? $this->getUserFullName($receiver_id, $receiver_type);

            if (!$sender_name || !$receiver_name) {
                echo "User not found!\n";
                return;
            }

            // Broadcast the message to all clients
            $messageData = [
                "sender_id" => $sender_id,
                "sender_type" => $sender_type,
                "receiver_id" => $receiver_id,
                "receiver_type" => $receiver_type,
                "message" => $message,
                "sender_name" => $sender_name,
                "receiver_name" => $receiver_name,
                "id" => $id, // Use 'id'
                "timestamp" => $timestamp
            ];

            foreach ($this->clients as $client) {
                $client->send(json_encode($messageData));
            }
            echo "Message broadcast: $message\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function getUserFullName($user_id, $user_type) {
        $tables = ["admin" => "admin", "student" => "students", "staff" => "staff_accounts"];
        if (!isset($tables[$user_type])) return null;

        $stmt = $this->pdo->prepare("SELECT fullName FROM " . $tables[$user_type] . " WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ? $user['fullName'] : null;
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($pdo)
        )
    ),
    8080
);

echo "WebSocket server running on ws://localhost:8080\n";
$server->run();