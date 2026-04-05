<?php
session_start();
require '../../server/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'], $_POST['name'], $_POST['code'], $_POST['type'], $_POST['semester'], $_POST['course'], $_POST['year_level'])) {
        exit;
    }

    $subjectId = $_POST['id'];
    $subjectName = $_POST['name'];
    $subjectCode = $_POST['code'];
    $type = $_POST['type'];
    $semester = $_POST['semester'];
    $course = $_POST['course'];
    $yearLevel = $_POST['year_level'];

    $meetingDays = !empty($_POST['meeting_days']) ? $_POST['meeting_days'] : [];
    $startTime = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

    try {
        // Update query
        $sql = "UPDATE subjects 
                SET name = :name, 
                    type = :type, 
                    code = :code, 
                    semester = :semester, 
                    course = :course, 
                    year_level = :year_level 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $subjectName);
        $stmt->bindParam(':code', $subjectCode);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':course', $course);
        $stmt->bindParam(':year_level', $yearLevel);
        $stmt->bindParam(':id', $subjectId);

        // Step 1: Delete existing schedules for the subject
        $deleteSchedulesSql = "DELETE FROM subjects_schedules WHERE subject_id = :subject_id";
        $deleteStmt = $pdo->prepare($deleteSchedulesSql);
        $deleteStmt->bindParam(':subject_id', $subjectId);
        $deleteStmt->execute();

        // Step 2: Insert new schedules with conflict check
        foreach ($meetingDays as $day) {
            // Check for conflicts excluding the current subject
            $checkConflictSql = "
                SELECT ss.* 
                FROM subjects_schedules ss
                INNER JOIN subjects s ON ss.subject_id = s.id
                WHERE ss.meeting_days = :meeting_days 
                AND s.is_archived = 0
                AND ss.subject_id != :subject_id  -- Exclude the current subject
                AND (
                    (:start_time1 BETWEEN ss.start_time AND ss.end_time) 
                    OR (:end_time1 BETWEEN ss.start_time AND ss.end_time) 
                    OR (ss.start_time BETWEEN :start_time2 AND :end_time2)
                )
            ";

            $checkStmt = $pdo->prepare($checkConflictSql);
            $checkStmt->bindParam(':meeting_days', $day);
            $checkStmt->bindParam(':subject_id', $subjectId);
            $checkStmt->bindParam(':start_time1', $startTime);
            $checkStmt->bindParam(':end_time1', $endTime);
            $checkStmt->bindParam(':start_time2', $startTime);
            $checkStmt->bindParam(':end_time2', $endTime);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                // Conflict found with other subjects
                $_SESSION['STATUS'] = "SCHEDULE_CONFLICT";
                header('Location: ../../../subject_management.php');
                exit();
            }

            // No conflict, proceed with inserting the new schedule
            $insertScheduleSql = "INSERT INTO subjects_schedules (subject_id, meeting_days, start_time, end_time) 
                         VALUES (:subject_id, :meeting_days, :start_time, :end_time)";
            $scheduleStmt = $pdo->prepare($insertScheduleSql);
            $scheduleStmt->bindParam(':subject_id', $subjectId);
            $scheduleStmt->bindParam(':meeting_days', $day);
            $scheduleStmt->bindParam(':start_time', $startTime);
            $scheduleStmt->bindParam(':end_time', $endTime);
            $scheduleStmt->execute();
        }

        // Execute the subject update query
        if ($stmt->execute()) {
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_SUCCESS";
            header('Location: ../../../subject_management.php');
        } else {
            $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_ERROR";
            header('Location: ../../../subject_management.php');
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = "ADMIN_SUBJECT_UPDATE_ERROR";
        header('Location: ../../../subject_management.php');
    }
}