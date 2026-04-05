<?php
session_start();
require '../server/conn.php';

try {
    $query = "UPDATE admin_auto_notifications SET status = 'false'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    echo "All statuses set to 'false'.";
    $_SESSION['STATUS'] = "EVERYTHING_FALSE";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header('Location: ../../index.php'); // Fallback if no referrer
    exit();
}


?>