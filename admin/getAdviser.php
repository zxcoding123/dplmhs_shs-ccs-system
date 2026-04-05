<?php
require 'processes/server/conn.php';

if (isset($_GET['class'])) {
    $class = $_GET['class'];

    try {
        // Query the staff_advising table to get full name of the adviser for a particular class
        $stmt = $pdo->prepare("
       SELECT sa.fullName, sa.class_advising
FROM staff_advising sa
LEFT JOIN staff_accounts sa1 ON sa1.fullName = sa.fullName
WHERE sa.class_advising = :class

        ");
        $stmt->execute(['class' => $class]);
        $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adviser) {

            echo json_encode([
                'fullName' => $adviser['fullName'],
            ]);
        } else {
            echo json_encode(['fullName' => 'No adviser assigned']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error fetching adviser: ' . $e->getMessage()]);
    }
}
