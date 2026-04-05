<?php
require 'processes/server/conn.php';

if (isset($_GET['class'])) {
    $class = $_GET['class'];

    try {
        $stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE class = :class LIMIT 1");
        $stmt->bindParam(':class', $class, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode(['adviser' => $result['fullName']]);
        } else {
            echo json_encode(['adviser' => null]);
        }
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required parameters']);
}
?>
