
<?php


require 'processes/server/conn.php'; // Include your PDO connection setup

if (isset($_GET['subjectName'])) {
    $subjectName = $_GET['subjectName'];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT type FROM subjects WHERE name = :name");
        $stmt->bindParam(':name', $subjectName, PDO::PARAM_STR);
        $stmt->execute();
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['types' => $types]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['types' => []]);
}


