<?php
session_start();
require_once '../../server/conn.php'; // Assumes this file defines $pdo

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection not established.");
    }

    // Handle POST request (Create or Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $class_id = $_POST['class_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $title = trim($_POST['title'] ?? null); // Trim to remove extra whitespace
        $rubric_id = isset($_POST['rubric_id']) && !empty($_POST['rubric_id']) ? $_POST['rubric_id'] : null;

        if (!$class_id || !$subject_id || !$title) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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

            echo json_encode(['success' => true, 'message' => 'Rubric updated successfully']);
        } else {
            // Check if a rubric with the same title, class_id, and subject_id already exists
            $check_stmt = $pdo->prepare("
                SELECT id 
                FROM rubrics 
                WHERE class_id = :class_id 
                AND subject_id = :subject_id 
                AND title = :title
            ");
            $check_stmt->execute([
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $title
            ]);
            $existing_rubric = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_rubric) {
                echo json_encode(['success' => false, 'message' => 'A rubric with this title already exists for this class and subject']);
                exit;
            }

            // If no duplicate exists, proceed with insertion
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
