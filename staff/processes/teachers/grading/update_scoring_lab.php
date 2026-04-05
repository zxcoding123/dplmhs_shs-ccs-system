<?php
header('Content-Type: application/json');
require '../../../processes/server/conn.php';

try {
    $studentId = $_POST['student_id'];
    $activityId = $_POST['activity_id'];
    $score = $_POST['score'];

    // Fetch max_points from activities table
    $maxStmt = $pdo->prepare("
        SELECT max_points 
        FROM activities 
        WHERE id = :activity_id
    ");
    $maxStmt->execute(['activity_id' => $activityId]);
    $activity = $maxStmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Activity not found']);
        exit;
    }

    $maxPoints = $activity['max_points'];
    $validatedScore = min(floatval($score), $maxPoints); // Cap score at max_points

    // Check if record exists first
    $checkStmt = $pdo->prepare("
        SELECT score 
        FROM activity_submissions 
        WHERE student_id = :student_id AND activity_id = :activity_id
    ");
    $checkStmt->execute([
        'student_id' => $studentId,
        'activity_id' => $activityId
    ]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update only if record exists
        $stmt = $pdo->prepare("
            UPDATE activity_submissions 
            SET score = :score, updated_at = NOW(), status = 'graded',
            WHERE student_id = :student_id AND activity_id = :activity_id
        ");
        $stmt->execute([
            'student_id' => $studentId,
            'activity_id' => $activityId,
            'score' => $validatedScore
        ]);
        echo json_encode(['success' => true, 'score' => $validatedScore]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No existing submission found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>