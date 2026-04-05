<?php
session_start();
require_once 'processes/server/conn.php'; // Assumes this file defines $pdo

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection not established.");
    }

    // Handle POST request (Create or Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $class_id = $_POST['class_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $title = $_POST['title'] ?? null;
        $rubric_id = isset($_POST['rubric_id']) && !empty($_POST['rubric_id']) ? $_POST['rubric_id'] : null;

        if (!$class_id || !$subject_id || !$title) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if ($rubric_id) {
            $stmt = $pdo->prepare("UPDATE rubrics SET title = :title, class_id = :class_id, subject_id = :subject_id WHERE id = :id");
            $stmt->execute([
                ':title' => $title,
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':id' => $rubric_id
            ]);
            echo json_encode(['success' => true, 'message' => 'Rubric updated successfully']);
            
            
            // Step 2: Update the activities table based on class_id and subject_id
        $activity_stmt = $pdo->prepare("
            UPDATE activities 
            SET type = :type 
            WHERE class_id = :class_id 
            AND subject_id = :subject_id
        ");
        $activity_stmt->execute([
            ':type' => $type, // Same type as the rubric
            ':class_id' => $class_id,
            ':subject_id' => $subject_id
        ]);
        
        
        } else {
            $stmt = $pdo->prepare("INSERT INTO rubrics (class_id, subject_id, title) VALUES (:class_id, :subject_id, :title)");
            $stmt->execute([
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $title
            ]);
            echo json_encode(['success' => true, 'message' => 'Rubric created successfully']);
        }
        exit;
    }

    // Handle DELETE request
    if (isset($_GET['delete']) && !empty($_GET['rubric_id'])) {
        $rubric_id = $_GET['rubric_id'];
        $class_id = $_GET['class_id'] ?? null;
        $subject_id = $_GET['subject_id'];

        if (!$class_id) {
            echo json_encode(['success' => false, 'message' => 'Missing class_id']);
            exit;
        }

        // Start a transaction
        $pdo->beginTransaction();

        try {
            // Fetch all activities related to the rubric
            $stmt = $pdo->prepare("SELECT id FROM activities WHERE class_id = :class_id AND subject_id = :subject_id");
            $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
            $activities = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($activities) {
                // Convert activity IDs into a string for IN clause
                $activity_ids = implode(',', array_map('intval', $activities));

                // Delete matching activity submissions
                $pdo->exec("DELETE FROM activity_submissions WHERE activity_id IN ($activity_ids)");

                // Delete activities themselves
                $pdo->exec("DELETE FROM activities WHERE id IN ($activity_ids)");
            }

            // Finally, delete the rubric
            $stmt = $pdo->prepare("DELETE FROM rubrics WHERE id = :id AND class_id = :class_id");
            $stmt->execute([':id' => $rubric_id, ':class_id' => $class_id]);

            // Commit the transaction if all operations succeed
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Rubric and related activities deleted successfully']);
        } catch (PDOException $e) {
            // Roll back the transaction on failure
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
        }
        exit;
    }

    // Fetch existing rubrics for display
    if (isset($_GET['class_id']) && isset($_GET['subject_id'])) {
        $class_id = $_GET['class_id'];
        $subject_id = $_GET['subject_id'];

        $stmt = $pdo->prepare("SELECT id, title FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id");
        $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
        $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'rubrics' => $rubrics]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
