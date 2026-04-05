<?php
require 'processes/server/conn.php'; // Adjust the path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notif_name = $_POST['notif_name'];

    // Update the notification status to 'false'
    $query = "UPDATE admin_auto_notifications SET status = 'false' WHERE name = :name";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':name', $notif_name, PDO::PARAM_STR);

    if ($stmt->execute()) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            header('Location: ../../index.php'); // Fallback if no referrer
            exit();
        }
    } else {
        header('Location: ../../index.php'); // Fallback if no referrer
    }
}
?>