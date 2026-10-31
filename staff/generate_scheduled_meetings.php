<?php
session_start();
require __DIR__ . '/../database/connection.php';
header('Content-Type: application/json');

/**
 * Creates a meeting for every day in a date range that the class's subject
 * schedule (subjects_schedules) falls on. Days that already have a meeting at
 * the same start time are skipped, so running it twice adds nothing new.
 */

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'You are not logged in']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$classId = !empty($data['class_id']) ? (int) $data['class_id'] : 0;
$from = DateTime::createFromFormat('!Y-m-d', $data['from'] ?? '');
$to = DateTime::createFromFormat('!Y-m-d', $data['to'] ?? '');

if (!$classId || !$from || !$to) {
    echo json_encode(['success' => false, 'message' => 'Pick a start and end date']);
    exit;
}
if ($to < $from) {
    echo json_encode(['success' => false, 'message' => 'The end date must be on or after the start date']);
    exit;
}
if ($from->diff($to)->days > 366) {
    echo json_encode(['success' => false, 'message' => 'The range can be at most one year']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, subject, subject_id, is_archived FROM classes WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $classId]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit;
    }
    if ((int) $class['is_archived'] === 1) {
        echo json_encode(['success' => false, 'message' => 'This class is archived']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT meeting_days, start_time, end_time FROM subjects_schedules WHERE subject_id = :subject_id");
    $stmt->execute(['subject_id' => $class['subject_id']]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($schedules)) {
        echo json_encode(['success' => false, 'message' => 'This subject has no schedule set yet']);
        exit;
    }

    // Schedules store "07:00"; meetings store "7:00 AM"
    $slotsByDay = [];
    foreach ($schedules as $sch) {
        $start = new DateTime($sch['start_time']);
        $end = new DateTime($sch['end_time']);
        $slotsByDay[$sch['meeting_days']][] = [$start->format('g:i A'), $end->format('g:i A')];
    }

    $stmt = $pdo->prepare("SELECT date, start_time FROM classes_meetings WHERE class_id = :class_id AND date BETWEEN :from AND :to");
    $stmt->execute(['class_id' => $classId, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]);
    $existing = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existing[$row['date'] . '|' . $row['start_time']] = true;
    }

    $stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = :class_id");
    $stmt->execute(['class_id' => $classId]);
    $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $meetingStmt = $pdo->prepare("
        INSERT INTO classes_meetings (date, status, start_time, end_time, class_id, type)
        VALUES (:date, :status, :start_time, :end_time, :class_id, 'Regular')
    ");
    $attendanceStmt = $pdo->prepare("
        INSERT INTO attendance (student_id, class_id, meeting_id, status, date)
        VALUES (:student_id, :class_id, :meeting_id, 'absent', :date)
    ");

    $today = date('Y-m-d');
    $created = 0;
    $skipped = 0;

    $pdo->beginTransaction();

    for ($day = clone $from; $day <= $to; $day->modify('+1 day')) {
        $date = $day->format('Y-m-d');
        foreach ($slotsByDay[$day->format('l')] ?? [] as [$startTime, $endTime]) {
            if (isset($existing[$date . '|' . $startTime])) {
                $skipped++;
                continue;
            }

            $meetingStmt->execute([
                'date' => $date,
                'status' => $date === $today ? 'Ongoing' : 'Scheduled',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'class_id' => $classId,
            ]);
            $meetingId = $pdo->lastInsertId();

            foreach ($studentIds as $studentId) {
                $attendanceStmt->execute([
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'meeting_id' => $meetingId,
                    'date' => $date,
                ]);
            }
            $created++;
        }
    }

    // One notice per student for the whole batch rather than one per meeting
    if ($created > 0 && count($studentIds) > 0) {
        $notificationStmt = $pdo->prepare("
            INSERT INTO student_notifications (user_id, type, title, description, link, status)
            VALUES (:user_id, 'class', :title, :description, :link, 'unread')
        ");
        $range = $from->format('M j') . ' – ' . $to->format('M j, Y');
        foreach ($studentIds as $studentId) {
            $notificationStmt->execute([
                'user_id' => $studentId,
                'title' => 'Class meetings added for Subject: ' . $class['subject'] . '!',
                'description' => $created . ' meeting' . ($created === 1 ? '' : 's') . ' were scheduled for ' . $class['subject'] . ' (' . $range . '). Please monitor them regularly!',
                'link' => 'https://ccs-sms.com/capstone/students/student_dashboard.php?class_id=' . $classId,
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'created' => $created, 'skipped' => $skipped]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error generating scheduled meetings: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
