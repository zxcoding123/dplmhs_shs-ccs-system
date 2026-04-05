<?php
session_start();
require '../../server/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subjectName = !empty($_POST['subjectName']) ? htmlspecialchars($_POST['subjectName']) : null;
    $subjectCode = !empty($_POST['subjectCode']) ? htmlspecialchars($_POST['subjectCode']) : null;
    $type = !empty($_POST['type']) ? htmlspecialchars($_POST['type']) : null;
    $semester = !empty($_POST['semester']) ? htmlspecialchars($_POST['semester']) : null;
    $course = !empty($_POST['course']) ? htmlspecialchars($_POST['course']) : null;
    $yearLevel = !empty($_POST['year_level']) ? htmlspecialchars($_POST['year_level']) : null;

    // Process meeting days, start time, and end time
    $meetingDays = !empty($_POST['meeting_days']) ? $_POST['meeting_days'] : [];
    $startTime = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

    // Ensure meeting days, start time, and end time are valid
    if (!$subjectName || !$subjectCode || !$type || !$semester || !$course || !$yearLevel || empty($meetingDays) || !$startTime || !$endTime) {
        $_SESSION['STATUS'] = "ADMIN_SUBJECT_ADD_FAIL";
        header('Location: ../../../subject_management.php');
        exit();
    }

    try {
        // Step 1: Check schedule conflicts before adding the subject
        foreach ($meetingDays as $day) {
            $checkConflictSql = "
        SELECT ss.* 
        FROM subjects_schedules ss
        INNER JOIN subjects s ON ss.subject_id = s.id
        WHERE ss.meeting_days = :meeting_days 
        AND s.is_archived = 0
        AND (
            (:start_time1 BETWEEN ss.start_time AND ss.end_time) 
            OR (:end_time1 BETWEEN ss.start_time AND ss.end_time) 
            OR (ss.start_time BETWEEN :start_time2 AND :end_time2)
        )
    ";

            $checkStmt = $pdo->prepare($checkConflictSql);
            $checkStmt->bindParam(':meeting_days', $day);
            $checkStmt->bindParam(':start_time1', $startTime);
            $checkStmt->bindParam(':end_time1', $endTime);
            $checkStmt->bindParam(':start_time2', $startTime);
            $checkStmt->bindParam(':end_time2', $endTime);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                // Conflict found
                $_SESSION['STATUS'] = "SCHEDULE_CONFLICT";
                header('Location: ../../../subject_management.php');
                exit();
            }
        }

        // Step 2: Proceed with adding the subject only if no conflicts are found
        // Check if the subject already exists (only non-archived subjects will be considered)
        $checkSubjectStmt = $pdo->prepare("SELECT * FROM subjects WHERE (name = :name OR code = :code) AND type = :type AND is_archived = 0");
        $checkSubjectStmt->bindParam(':name', $subjectName);
        $checkSubjectStmt->bindParam(':code', $subjectCode);
        $checkSubjectStmt->bindParam(':type', $type);
        $checkSubjectStmt->execute();

        if ($checkSubjectStmt->rowCount() > 0) {
            // Subject already exists
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_EXISTS";
            header('Location: ../../../subject_management.php');
            exit;
        }

        // Insert the subject with the new fields for course and year level
        $sql = "INSERT INTO subjects (name, type, code, semester, course, year_level) 
                VALUES (:name, :type, :code, :semester, :course, :year_level)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $subjectName);
        $stmt->bindParam(':code', $subjectCode);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':course', $course);
        $stmt->bindParam(':year_level', $yearLevel);

        if ($stmt->execute()) {
            // Get the last inserted subject ID
            $subjectId = $pdo->lastInsertId();

            // Step 3: Insert new schedules
            foreach ($meetingDays as $day) {
                $insertScheduleSql = "INSERT INTO subjects_schedules (subject_id, meeting_days, start_time, end_time) 
                                      VALUES (:subject_id, :meeting_days, :start_time, :end_time)";
                $scheduleStmt = $pdo->prepare($insertScheduleSql);
                $scheduleStmt->bindParam(':subject_id', $subjectId);
                $scheduleStmt->bindParam(':meeting_days', $day);
                $scheduleStmt->bindParam(':start_time', $startTime);
                $scheduleStmt->bindParam(':end_time', $endTime);
                $scheduleStmt->execute();
            }

            $_SESSION['STATUS'] = "ADMIN_SUBJECT_ADD_SUCCESS";
            header('Location: ../../../subject_management.php');
            exit();
        } else {
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_ADD_FAIL";
            header('Location: ../../../subject_management.php');
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    $_SESSION['STATUS'] = "ADMIN_SUBJECT_ADD_FAIL";
    header('Location: ../../../subject_management.php');
    exit();
}
