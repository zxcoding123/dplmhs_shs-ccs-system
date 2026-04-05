<?php
session_start();
require '../../server/conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or missing ID.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Step 1: Retrieve IDs for subjects and classes
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE semester = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $subjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT id FROM classes WHERE semester = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $classIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Step 2: Cascade deletion for related tables
        if (!empty($subjectIds)) {
            $subjectIdPlaceholders = implode(',', array_fill(0, count($subjectIds), '?'));

            // Delete from subjects_schedules
            $stmt = $pdo->prepare("DELETE FROM subjects_schedules WHERE subject_id IN ($subjectIdPlaceholders)");
            $stmt->execute($subjectIds);
        }

        if (!empty($classIds)) {
            $classIdPlaceholders = implode(',', array_fill(0, count($classIds), '?'));

            // Delete from classes_meetings
            $stmt = $pdo->prepare("DELETE FROM classes_meetings WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);

            // Handle activities and related files
            $stmt = $pdo->prepare("SELECT id FROM activities WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);
            $activityIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($activityIds)) {
                $activityIdPlaceholders = implode(',', array_fill(0, count($activityIds), '?'));

                // Delete activity_attachments and remove files
                $stmt = $pdo->prepare("SELECT file_name FROM activity_attachments WHERE activity_id IN ($activityIdPlaceholders)");
                $stmt->execute($activityIds);
                $filesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($filesToDelete as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                $stmt = $pdo->prepare("DELETE FROM activity_attachments WHERE activity_id IN ($activityIdPlaceholders)");
                $stmt->execute($activityIds);

                // Delete activity_submissions and remove files
                $stmt = $pdo->prepare("SELECT file_path FROM activity_submissions WHERE activity_id IN ($activityIdPlaceholders)");
                $stmt->execute($activityIds);
                $submissionFilesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($submissionFilesToDelete as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                $stmt = $pdo->prepare("DELETE FROM activity_submissions WHERE activity_id IN ($activityIdPlaceholders)");
                $stmt->execute($activityIds);

                // Delete activities
                $stmt = $pdo->prepare("DELETE FROM activities WHERE id IN ($activityIdPlaceholders)");
                $stmt->execute($activityIds);
            }

            // Delete from lecture_rubrics, laboratory_rubrics, and learning_resources
            $stmt = $pdo->prepare("DELETE FROM lecture_rubrics WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);

            $stmt = $pdo->prepare("DELETE FROM laboratory_rubrics WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);

            $stmt = $pdo->prepare("SELECT resource_url FROM learning_resources WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);
            $resourceFilesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($resourceFilesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM learning_resources WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);

            // Delete from attendance
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);

            // Delete from students_enrollments
            $stmt = $pdo->prepare("DELETE FROM students_enrollments WHERE class_id IN ($classIdPlaceholders)");
            $stmt->execute($classIds);
        }

        // Step 3: Delete subjects and classes
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE semester = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $pdo->prepare("DELETE FROM classes WHERE semester = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Step 4: Delete semester
        $stmt = $pdo->prepare("DELETE FROM semester WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Semester and all related records successfully deleted.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
