<?php
require 'processes/server/conn.php';

$date = $_GET['date'] ?? null;

if ($date) {
    // Fetch classes for the specified date
    $query = $pdo->prepare("SELECT * FROM classes_meetings WHERE date = :date");
    $query->execute(['date' => $date]);
    $classes = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($classes) {
        foreach ($classes as $class) {
            echo "<p>Class ID: " . htmlspecialchars($class['class_id']) . " - Status: " . htmlspecialchars($class['status']) . "</p>";
        }
    } else {
        echo "<p>No classes scheduled for this date.</p>";
    }
} else {
    echo "<p>Invalid date.</p>";
}
?>
