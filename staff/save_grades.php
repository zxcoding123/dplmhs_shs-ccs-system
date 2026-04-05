<?php
require 'processes/server/conn.php'; // Ensure database connection is correctly loaded
session_start();

// Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['class_id'], $_GET['subject_id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid request.']));
}

// Sanitize inputs
$class_id = filter_var($_GET['class_id'], FILTER_VALIDATE_INT);
$subject_id = filter_var($_GET['subject_id'], FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? null;
$grades = $_POST['grades'] ?? []; // Default to empty array if not set

if (!$class_id || !$subject_id) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid class or subject ID.']));
}

// Valid grade options
$validGrades = [1.00, 1.25, 1.50, 1.75, 2.00, 2.25, 2.50, 2.75, 3.00, 5.00];

// Function to map calculated grade to nearest valid grade
function mapToValidGrade($calculatedGrade, $validGrades)
{
    if ($calculatedGrade === null) return null;
    // Handle grades >= 5.0
    if ($calculatedGrade >= 5.0) return 5.00;
    // Handle grades < 1.00 (set to lowest valid grade)
    if ($calculatedGrade < 1.00) return 1.00;

    // Find the nearest valid grade
    $closest = $validGrades[0];
    $minDifference = abs($calculatedGrade - $closest);
    foreach ($validGrades as $grade) {
        $difference = abs($calculatedGrade - $grade);
        if ($difference < $minDifference) {
            $minDifference = $difference;
            $closest = $grade;
        }
    }
    return $closest;
}

try {
    $gradesSaved = false;

    // Fetch class details using the correct column name (id)
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = :class_id LIMIT 1");
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();

    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class) {
        throw new Exception("Class not found.");
    }
    $class_name = $class['name'] ?? 'Unknown Class';
    $subject_name = $class['subject'] ?? 'Unknown Subject';

    foreach ($grades as $student_id => $grade) {
        $student_id = filter_var($student_id, FILTER_VALIDATE_INT);
        if (!$student_id) continue; // Skip invalid student IDs

        $midterm = !empty($grade['midterm']) ? trim($grade['midterm']) : null;
        $final = !empty($grade['final']) ? trim($grade['final']) : null;

        // Validate and calculate overall grade
        $specialGrades = ['N/A', 'INC', 'UW', 'AW'];
        if (in_array($midterm, $specialGrades) || in_array($final, $specialGrades)) {
            $overall = 5.00; // Default to 5.0 for special cases
        } else {
            $midtermFloat = is_numeric($midterm) ? (float)$midterm : null;
            $finalFloat = is_numeric($final) ? (float)$final : null;

            if ($midtermFloat !== null && $finalFloat !== null) {
                // Validate grade range (1.00 to 5.00)
                if ($midtermFloat < 1.00 || $midtermFloat > 5.00 || $finalFloat < 1.00 || $finalFloat > 5.00) {
                    $overall = null; // Invalid grade range
                } else {
                    $calculatedOverall = $midtermFloat * 0.40 + $finalFloat * 0.60;
                    $overall = mapToValidGrade($calculatedOverall, $validGrades);
                }
            } else {
                $overall = null;
            }
        }

        // Determine status based on action
        $status = null; // Default to NULL
        if ($action === 'submit') {
            $status = 'for_approval';
        } elseif ($action === 'save') {
            $status = 'saved';
        } elseif ($action === 'revert') {
            $status = null; // Explicitly set to NULL for revert
        }

        // Check if record exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM student_grades WHERE class_id = :class_id AND student_id = :student_id");
        $checkStmt->execute([':class_id' => $class_id, ':student_id' => $student_id]);
        $exists = $checkStmt->fetchColumn() > 0;

        // Prepare update or insert query
        if ($exists) {
            $stmt = $pdo->prepare(
                "UPDATE student_grades
                 SET midterm_grade = :midterm_grade,
                     final_grade = :final_grade,
                     overall_grade = :overall_grade,
                     status = :status,
                     updated_at = NOW()
                 WHERE class_id = :class_id AND student_id = :student_id"
            );
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO student_grades (class_id, student_id, midterm_grade, final_grade, overall_grade, status, updated_at)
                 VALUES (:class_id, :student_id, :midterm_grade, :final_grade, :overall_grade, :status, NOW())"
            );
        }

        $stmt->execute([
            ':midterm_grade' => $midterm ?? null,
            ':final_grade' => $final ?? null,
            ':overall_grade' => $overall ?? null,
            ':status' => $status,
            ':class_id' => $class_id,
            ':student_id' => $student_id,
        ]);

        $gradesSaved = true;
    }

    if ($gradesSaved) {
        $stmt2 = $pdo->prepare("UPDATE classes SET grade_checker = 'available' WHERE id = :class_id");
        $stmt2->execute([':class_id' => $class_id]);

        // Set session status based on action
        switch ($action) {
            case 'submit':
                $_SESSION['STATUS'] = "SUBMISSION_FOR_APPROVAL";
                break;
            case 'save':
                $_SESSION['STATUS'] = "SAVED_GRADES";
                break;
            case 'revert':
                $_SESSION['STATUS'] = "REVERTED_GRADES";
                break;
            default:
                $_SESSION['STATUS'] = "UNKNOWN_ACTION";
                break;
        }

        if ($action === 'submit') {
            $stmt = $pdo->prepare("
                INSERT INTO admin_notifications (type, title, description, date, link, status)
                VALUES (:type, :title, :description, NOW(), :link, :status)
            ");

            $teacher_name = $_SESSION['teacher_name'] ?? 'Unknown Teacher';
            $data = [
                ':type' => 'teacher',
                ':title' => 'Final Grades Submission for Review and Approval!',
                ':description' => 'Grades have been added for review by ' . htmlspecialchars($teacher_name) . ' under the class of: ' . htmlspecialchars($class_name) . " on subject of: " . htmlspecialchars($subject_name),
                ':link' => '/admin/class_management.php',
                ':status' => 'unread'
            ];

            if ($stmt->execute($data)) {
                // No output here to avoid headers already sent
            } else {
                error_log("Failed to add notification for class_id: $class_id");
            }
        }
    }

    // Redirect without prior output
    header("Location: subject_activities.php?url=grades&class_id=$class_id&subject_id=$subject_id");
    exit;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'An error occurred. Please try again later.']));
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    http_response_code(400);
    die(json_encode(['error' => $e->getMessage()]));
}