<?php
/**
 * Teacher saves, submits or reverts a class's quarter grades (Grades tab in subject_activities.php).
 *
 * Grades are DepEd quarterly grades (60–100) or a text grade (N/A, DRP); the final
 * grade is the average of both quarters (includes/grading.php). Only the class's
 * own teacher can save, and nothing changes once an admin or registrar has
 * accepted (locked) the grades.
 */
require __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../includes/grading.php';
session_start();

if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'TEACHER_NOT_LOGGED_IN';
    header('Location: ../login/index.php');
    exit;
}

$class_id = filter_var($_GET['class_id'] ?? null, FILTER_VALIDATE_INT);
$subject_id = filter_var($_GET['subject_id'] ?? null, FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$grades = $_POST['grades'] ?? [];
$back = "subject_activities.php?url=grades&class_id=$class_id&subject_id=$subject_id";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$class_id || !$subject_id || !in_array($action, ['save', 'submit', 'revert'], true) || !is_array($grades)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid request.']));
}

try {
    $stmt = $pdo->prepare("SELECT name, subject, teacher FROM classes WHERE id = ? LIMIT 1");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class) {
        throw new Exception('Class not found.');
    }
    if ($class['teacher'] !== ($_SESSION['teacher_name'] ?? '')) {
        http_response_code(403);
        die(json_encode(['error' => 'Only the teacher of this class can change its grades.']));
    }

    $state = grades_class_state($pdo, $class_id);
    if ($state === 'locked') {
        $_SESSION['STATUS'] = 'GRADES_LOCKED';
        header("Location: $back");
        exit;
    }
    if ($state === 'submitted' && $action !== 'revert') {
        $_SESSION['STATUS'] = 'GRADES_ALREADY_SUBMITTED';
        header("Location: $back");
        exit;
    }

    $status = ['submit' => 'for_approval', 'save' => 'saved', 'revert' => null][$action];
    $allowed = fn($g) => $g === 'N/A' || $g === 'DRP' || (ctype_digit((string) $g) && deped_is_number($g));

    $enrolled = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
    $enrolled->execute([$class_id]);
    $enrolled = array_map('intval', $enrolled->fetchAll(PDO::FETCH_COLUMN));

    $pdo->beginTransaction();
    $upsert = $pdo->prepare("
        INSERT INTO student_grades (class_id, student_id, midterm_grade, final_grade, overall_grade, status, updated_at)
        VALUES (:class_id, :student_id, :q1, :q2, :final, :status, NOW())
    ");
    $update = $pdo->prepare("
        UPDATE student_grades SET midterm_grade = :q1, final_grade = :q2, overall_grade = :final, status = :status, updated_at = NOW()
        WHERE class_id = :class_id AND student_id = :student_id
    ");
    $exists = $pdo->prepare("SELECT 1 FROM student_grades WHERE class_id = ? AND student_id = ?");

    foreach ($grades as $studentId => $grade) {
        $studentId = (int) $studentId;
        if (!in_array($studentId, $enrolled, true)) {
            continue; // not in this class
        }
        $q1 = trim((string) ($grade['midterm'] ?? 'N/A'));
        $q2 = trim((string) ($grade['final'] ?? 'N/A'));
        if (!$allowed($q1) || !$allowed($q2)) {
            throw new Exception('Grades must be whole numbers from 60 to 100, N/A or DRP.');
        }
        $params = [
            'class_id' => $class_id, 'student_id' => $studentId,
            'q1' => $q1, 'q2' => $q2, 'final' => deped_final_grade($q1, $q2), 'status' => $status,
        ];
        $exists->execute([$class_id, $studentId]);
        ($exists->fetchColumn() ? $update : $upsert)->execute($params);
    }

    $pdo->prepare("UPDATE classes SET grade_checker = 'available' WHERE id = ?")->execute([$class_id]);

    if ($action === 'submit') {
        grades_log_submission($pdo, $class_id, $_SESSION['teacher_name']);
        $pdo->prepare("INSERT INTO admin_notifications (admin_id, type, title, description, date, link, status)
            SELECT id, 'teacher', 'Grades submitted for review', ?, NOW(), 'admin/class_management.php', 'unread' FROM admin")
            ->execute([$_SESSION['teacher_name'] . " submitted grades for {$class['subject']} ({$class['name']}). An admin or the registrar can review them."]);
    }
    $pdo->commit();

    $_SESSION['STATUS'] = ['submit' => 'SUBMISSION_FOR_APPROVAL', 'save' => 'SAVED_GRADES', 'revert' => 'REVERTED_GRADES'][$action];
    header("Location: $back");
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Saving grades failed: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'An error occurred. Please try again later.']));
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    die(json_encode(['error' => $e->getMessage()]));
}
