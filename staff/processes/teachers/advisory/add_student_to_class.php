<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'TEACHER_NOT_LOGGED_IN';
    header('Location: ../../login/index.php');
    exit();
}

require '../../../processes/server/conn.php';



/* -------------------------------------------------
 * 1. Validate POST payload
 * ------------------------------------------------- */
$class_name   = $_POST['class_name']   ?? '';
$student_ids  = $_POST['student_ids']  ?? [];   // array

if ($class_name === '' || empty($student_ids)) {
    $_SESSION['error'] = 'No students were selected.';
 
}

/* -------------------------------------------------
 * 2. Confirm the teacher owns this advisory class
 * ------------------------------------------------- */
$sa = $pdo->prepare("
    SELECT id
    FROM staff_advising
    WHERE fullName       = :teacher
      AND class_advising = :class_name
    LIMIT 1
");
$sa->execute([
    'teacher'    => $_SESSION['full_name'],
    'class_name' => $class_name,
]);
$staff_advising_id = $sa->fetchColumn();

if (!$staff_advising_id) {
    $_SESSION['error'] = 'You are not authorized to modify this class.';
    header("Location: $returnUrl");
    exit();
}

/* -------------------------------------------------
 * 3. Insert rows into students_advising
 * ------------------------------------------------- */
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO students_advising (student_id, staff_advising_id)
VALUES (:sid, :sa_id)
    ");

    $added = 0;
    foreach ($student_ids as $sid) {
        $sid = (int)$sid;
        if ($sid > 0) {
            $stmt->execute(['sid' => $sid, 'sa_id' => $staff_advising_id]);
            $added += $stmt->rowCount();
        }
    }
    $pdo->commit();

    $_SESSION['success'] = $added > 0
        ? "Successfully added $added student(s) to the class."
        : 'No new students were added (they may already be in the class).';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

/* -------------------------------------------------
 * 4. Redirect back to the class view
 * ------------------------------------------------- */
header("Location: ../../../advisory_students.php?class_id=" . $class_name);
exit();
?>
