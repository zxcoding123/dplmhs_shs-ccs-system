<?php
include('processes/server/conn.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    exit('User not logged in.');
}

if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $redirectTo = $_SERVER['HTTP_REFERER'];
} else {
    // Fallback page if no referrer
    $redirectTo = 'student_dashboard.php';
}

$userId = $_SESSION['student_id'];

$sql = "DELETE FROM student_pictures WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $userId]);

if ($stmt->rowCount() > 0) {
    $_SESSION['STATUS'] = "PICTURE_REMOVED_SUCCESFUL";
} else {
     $_SESSION['STATUS'] = "PICTURE_REMOVED_UNSUCCESFUL";
}
header('Location: '.  $redirectTo );
?>
