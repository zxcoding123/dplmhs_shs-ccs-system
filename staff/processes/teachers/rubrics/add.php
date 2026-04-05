<?php
session_start();
require_once '../../server/conn.php'; // Assumes this file defines $pdo

header('Content-Type: application/json');

// Ensure PDO is available
if (!isset($pdo) || !$pdo instanceof PDO) {
    echo json_encode(['success' => false, 'message' => 'Database connection not established']);
    exit;
}

try {
    // Handle POST request (Create or Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Trim and sanitize POST inputs
        $class_id = filter_input(INPUT_POST, 'class_id', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
        $rubric_id = filter_input(INPUT_POST, 'rubric_id', FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH) ?: null;

        // Input validation
        if (!$class_id || !$subject_id || !$title) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing required fields (class_id, subject_id, title)']);
            exit;
        }

        if ($rubric_id) {
        // Step 1: Fetch the current title of the rubric
        $select_stmt = $pdo->prepare("SELECT title FROM rubrics WHERE id = :id");
        $select_stmt->execute([':id' => $rubric_id]);
        $rubric = $select_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rubric) {
            throw new Exception("No rubric found with ID: $rubric_id");
        }
        $rubric_title = $rubric['title'];
     

        // Step 2: Update the rubrics table with the new title, class_id, and subject_id
        $stmt = $pdo->prepare("
            UPDATE rubrics 
            SET title = :title, 
                class_id = :class_id, 
                subject_id = :subject_id 
            WHERE id = :id
        ");
        $stmt->execute([
            ':title' => $title,
            ':class_id' => $class_id,
            ':subject_id' => $subject_id,
            ':id' => $rubric_id
        ]);

        // Check if the rubric was updated
        $rubric_updated = $stmt->rowCount();
        if ($rubric_updated === 0) {
            throw new Exception("No rubric updated with ID: $rubric_id");
        }

        // Step 3: Update the activities table based on class_id and subject_id using the original rubric title
        $activity_stmt = $pdo->prepare("
            UPDATE activities 
            SET type = :new_type 
            WHERE class_id = :class_id 
            AND subject_id = :subject_id
            AND type = :old_type
        ");
        $activity_stmt->execute([
            ':old_type' => $rubric_title, // Use the original rubric title
            ':class_id' => $class_id,
            ':subject_id' => $subject_id,
            ':new_type' => $title
        ]);
        
        
                $message = 'Rubric updated successfully';
                
        } else {
            // Check if rubric already exists (case-insensitive)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id AND LOWER(title) = LOWER(:title)");
            $stmt->execute([
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $title
            ]);
            $exists = $stmt->fetchColumn();

            if ($exists > 0) {
                echo json_encode(['success' => false, 'message' => "Rubric with title '$title' already exists for this class and subject"]);
                exit;
            }

            // Create new rubric
            $stmt = $pdo->prepare("INSERT INTO rubrics (class_id, subject_id, title) VALUES (:class_id, :subject_id, :title)");
            $stmt->execute([
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $title,
            ]);
            $message = 'Rubric created successfully';
        }

        echo json_encode(['success' => true, 'message' => $message]);
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
        // Step 1: Fetch the rubric title using rubric_id
        $stmt = $pdo->prepare("SELECT title FROM rubrics WHERE id = :id AND class_id = :class_id");
        $stmt->execute([':id' => $rubric_id, ':class_id' => $class_id]);
        $rubric = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rubric) {
            throw new PDOException("No rubric found with id: $rubric_id and class_id: $class_id");
        }
        $rubric_title = $rubric['title'];

        // Step 2: Fetch activities where type matches the rubric title
        $stmt = $pdo->prepare("SELECT id FROM activities WHERE class_id = :class_id AND type = :type");
        $stmt->execute([':class_id' => $class_id, ':type' => $rubric_title]);
        $activities = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($activities) {
            // Convert activity IDs into a string for IN clause
            $activity_ids = implode(',', array_map('intval', $activities));

            // Delete matching activity submissions
            $stmt = $pdo->prepare("DELETE FROM activity_submissions WHERE activity_id IN ($activity_ids)");
            $stmt->execute();

            // Delete activities themselves
            $stmt = $pdo->prepare("DELETE FROM activities WHERE id IN ($activity_ids)");
            $stmt->execute();
        }

        // Step 3: Delete the rubric
        $stmt = $pdo->prepare("DELETE FROM rubrics WHERE id = :id AND class_id = :class_id");
        $stmt->execute([':id' => $rubric_id, ':class_id' => $class_id]);

        // Check if rubric was deleted
        if ($stmt->rowCount() === 0) {
            throw new PDOException("Failed to delete rubric with id: $rubric_id");
        }

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
        $class_id = filter_input(INPUT_GET, 'class_id', FILTER_SANITIZE_NUMBER_INT);
        $subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_SANITIZE_NUMBER_INT);

        if (!$class_id || !$subject_id) {
            echo json_encode(['success' => false, 'message' => 'Missing class_id or subject_id']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, title, percentile FROM rubrics WHERE class_id = :class_id AND subject_id = :subject_id");
        $stmt->execute([':class_id' => $class_id, ':subject_id' => $subject_id]);
        $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'rubrics' => $rubrics]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}