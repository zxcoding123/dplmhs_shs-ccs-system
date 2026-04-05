<?php
session_start();
require '../../server/conn.php'; // Adjust the path based on your directory structure

try {
    $stmt = $pdo->prepare("DELETE FROM admin_notifications");
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    $_SESSION['STATUS'] = "DELETE_ALL_NOTIFICATIONS";
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: ../../index.php'); // Fallback if no referrer
    exit();
}
?>
