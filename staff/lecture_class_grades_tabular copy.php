<?php
include('processes/server/conn.php');
// Assuming PDO connection is already established
$class_id = $_GET['id'] ?? null;

if (!$class_id) {
    die("Class ID is required");
}

// Function to calculate grade based on percentage
function calculateGrade($percentage) {
    if ($percentage >= 0.95) return "1.00";
    if ($percentage >= 0.90) return "1.25";
    if ($percentage >= 0.85) return "1.75";
    if ($percentage >= 0.80) return "2.00";
    if ($percentage >= 0.75) return "2.25";
    if ($percentage >= 0.70) return "2.50";
    if ($percentage >= 0.65) return "2.75";
    if ($percentage >= 0.60) return "3.00";
    return "5.00";
}

// Fetch rubrics with percentiles
$rubricsStmt = $pdo->prepare("SELECT DISTINCT title, percentile FROM rubrics WHERE class_id = ?");
$rubricsStmt->execute([$class_id]);
$rubrics = $rubricsStmt->fetchAll(PDO::FETCH_ASSOC);
$rubricTypes = array_column($rubrics, 'title');
$percentiles = array_column($rubrics, 'percentile', 'title');
$hasAttendance = in_array('Attendance', $rubricTypes);

$activityTypes = array_diff($rubricTypes, ['Attendance']);
$activities = [];
if (!empty($activityTypes)) {
    $placeholders = implode(',', array_fill(0, count($activityTypes), '?'));
    $activitiesStmt = $pdo->prepare("SELECT id, type, max_points, term FROM activities WHERE class_id = ? AND type IN ($placeholders)");
    $activitiesStmt->execute(array_merge([$class_id], $activityTypes));
    $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $activities = [];
}

// Organize activities by type and term
$activitiesByType = [];
$activityIds = [];
foreach ($activities as $activity) {
    $activityIds[] = $activity['id'];
    if (!isset($activitiesByType[$activity['type']])) {
        $activitiesByType[$activity['type']] = ['midterm' => [], 'final' => []];
    }
    $activitiesByType[$activity['type']][$activity['term']][] = $activity;
}

// Fetch student submissions
$submissions = [];
if (!empty($activityIds)) {
    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
    $submissionsStmt = $pdo->prepare("SELECT activity_id, student_id, score FROM activity_submissions WHERE activity_id IN ($placeholders)");
    $submissionsStmt->execute($activityIds);
    $submissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);
}

$studentScores = [];
foreach ($submissions as $submission) {
    $studentScores[$submission['student_id']][$submission['activity_id']] = $submission['score'];
}

// Fetch students from enrollments
$stmt = $pdo->prepare("SELECT student_id FROM students_enrollments WHERE class_id = ?");
$stmt->execute([$class_id]);
$studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$students = [];
if (!empty($studentIds)) {
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = $pdo->prepare("SELECT id, fullName FROM students WHERE id IN ($placeholders) ORDER BY fullName");
    $stmt->execute($studentIds);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Attendance handling
$totalMeetings = 0;
$attendanceDates = [];
$attendanceRecords = [];
if ($hasAttendance) {
    $stmt = $pdo->prepare("SELECT id, date FROM classes_meetings WHERE class_id = ? ORDER BY date ASC");
    $stmt->execute([$class_id]);
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalMeetings = count($meetings);
    $attendanceDates = array_column($meetings, 'date', 'id');

    $attendanceStmt = $pdo->prepare("SELECT student_id, meeting_id, status 
                                    FROM attendance 
                                    WHERE class_id = ?");
    $attendanceStmt->execute([$class_id]);
    $attendanceRecordsRaw = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($attendanceRecordsRaw as $record) {
        $studentId = $record['student_id'];
        $meetingId = $record['meeting_id'];
        if (!isset($attendanceRecords[$studentId])) {
            $attendanceRecords[$studentId] = [];
        }
        $attendanceRecords[$studentId][$meetingId] = $record['status'];
    }
}

// Calculate total points by type and term
$totalPoints = [];
foreach ($activityTypes as $type) {
    $totalPoints[$type] = ['midterm' => 0, 'final' => 0];
    foreach ($activitiesByType[$type]['midterm'] as $activity) {
        $totalPoints[$type]['midterm'] += $activity['max_points'];
    }
    foreach ($activitiesByType[$type]['final'] as $activity) {
        $totalPoints[$type]['final'] += $activity['max_points'];
    }
}

// Calculate grades for each student
$studentGrades = [];
foreach ($students as $student) {
    $studentId = $student['id'];
    $grades = ['midterm' => 0, 'final' => 0];

    // Calculate scores for activities
    foreach ($activityTypes as $type) {
        $midtermScore = 0;
        $finalScore = 0;

        foreach ($activitiesByType[$type]['midterm'] as $activity) {
            $score = $studentScores[$studentId][$activity['id']] ?? 0;
            $midtermScore += $score;
        }
        foreach ($activitiesByType[$type]['final'] as $activity) {
            $score = $studentScores[$studentId][$activity['id']] ?? 0;
            $finalScore += $score;
        }

        $midtermTotal = $totalPoints[$type]['midterm'] > 0 ? $totalPoints[$type]['midterm'] : 1;
        $finalTotal = $totalPoints[$type]['final'] > 0 ? $totalPoints[$type]['final'] : 1;

        $midtermPercentage = $midtermScore / $midtermTotal;
        $finalPercentage = $finalScore / $finalTotal;

        $grades['midterm'] += $midtermPercentage * ($percentiles[$type] / 100);
        $grades['final'] += $finalPercentage * ($percentiles[$type] / 100);
    }

    // Add attendance if applicable
    if ($hasAttendance && $totalMeetings > 0) {
        $presentCount = 0;
        foreach ($attendanceDates as $meetingId => $date) {
            $status = $attendanceRecords[$studentId][$meetingId] ?? 'absent';
            if ($status === 'present') {
                $presentCount++;
            }
        }
        $attendancePercentage = $presentCount / $totalMeetings;
        $grades['midterm'] += $attendancePercentage * ($percentiles['Attendance'] / 100) / 2;
        $grades['final'] += $attendancePercentage * ($percentiles['Attendance'] / 100) / 2;
    }

    // Apply grading scale
    $studentGrades[$studentId] = [
        'fullName' => $student['fullName'],
        'midterm' => calculateGrade($grades['midterm']),
        'final' => calculateGrade($grades['final']),
        'gpa' => number_format((floatval(calculateGrade($grades['midterm'])) + floatval(calculateGrade($grades['final']))) / 2, 2)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Grades for Class ID: <?php echo htmlspecialchars($class_id); ?></h2>
        <?php if (empty($students)): ?>
            <p>No students enrolled in this class.</p>
        <?php elseif (empty($rubricTypes)): ?>
            <p>No rubrics defined for this class.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Midterm Grade</th>
                        <th>Final Grade</th>
                        <th>GPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentGrades as $studentId => $grades): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($studentId); ?></td>
                            <td><?php echo htmlspecialchars($grades['fullName']); ?></td>
                            <td><?php echo $grades['midterm']; ?></td>
                            <td><?php echo $grades['final']; ?></td>
                            <td><?php echo $grades['gpa']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>