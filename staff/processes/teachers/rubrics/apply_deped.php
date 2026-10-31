<?php
/**
 * Set a class's grading components to DepEd's Written Work / Performance Tasks /
 * Quarterly Assessment weights for its subject type (includes/grading.php).
 * Other components the class already has are kept at 0% so their activities
 * stay attached but no longer count.
 */
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/grading.php';

function respond(bool $ok, string $message): void
{
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    respond(false, 'Please sign in again.');
}

$classId = (int) ($_POST['class_id'] ?? 0);
$subjectId = (int) ($_POST['subject_id'] ?? 0);
$presets = deped_weight_presets();
$preset = $presets[$_POST['preset'] ?? ''] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$classId || !$subjectId || !$preset) {
    respond(false, 'Pick a subject type.');
}

$owner = $pdo->prepare("SELECT teacher FROM classes WHERE id = ?");
$owner->execute([$classId]);
if ($owner->fetchColumn() !== ($_SESSION['teacher_name'] ?? '')) {
    http_response_code(403);
    respond(false, 'Only the teacher of this class can change its grading components.');
}
if (grades_is_locked($pdo, $classId)) {
    respond(false, 'Grades for this class are accepted and locked.');
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE rubrics SET percentile = 0 WHERE class_id = ?")->execute([$classId]);
    $find = $pdo->prepare("SELECT id FROM rubrics WHERE class_id = ? AND title = ? LIMIT 1");
    foreach ($preset['weights'] as $title => $weight) {
        $find->execute([$classId, $title]);
        $id = $find->fetchColumn();
        if ($id) {
            $pdo->prepare("UPDATE rubrics SET percentile = ? WHERE id = ?")->execute([$weight, $id]);
        } else {
            $pdo->prepare("INSERT INTO rubrics (class_id, subject_id, title, percentile) VALUES (?, ?, ?, ?)")
                ->execute([$classId, $subjectId, $title, $weight]);
        }
    }
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Applying DepEd weights failed: ' . $e->getMessage());
    respond(false, 'The weights could not be saved. Please try again.');
}

$summary = implode(', ', array_map(fn($t, $w) => "$t $w%", array_keys($preset['weights']), $preset['weights']));
respond(true, "Using DepEd weights for a {$preset['label']}: $summary. Tag each activity as one of these components.");
