<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'TEACHER_NOT_LOGGED_IN';
    header('Location: ../../login/index.php');
    exit();
}

require '../../../processes/server/conn.php';

/* Where to send the user afterwards (adjust path as needed) */
$returnUrl = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';

/* -------------------------------------------------
 * 1. Validate POST payload
 * ------------------------------------------------- */
$student_id        = (int)($_POST['student_id']        ?? 0);
$staff_advising_id = (int)($_POST['staff_advising_id'] ?? 0);

if ($student_id <= 0 || $staff_advising_id <= 0) {
    $_SESSION['error'] = 'Invalid request parameters.';
    header("Location: $returnUrl");
    exit();
}

/* -------------------------------------------------
 * 2. Verify the teacher owns this advisory class
 * ------------------------------------------------- */
$vrf = $pdo->prepare("
    SELECT 1
    FROM staff_advising
    WHERE id       = :sa_id
      AND fullName = :teacher_name      /* use staff_id if you have it */
    LIMIT 1
");
$vrf->execute([
    'sa_id'        => $staff_advising_id,
    'teacher_name' => $_SESSION['full_name'],
]);

if (!$vrf->fetchColumn()) {
    $_SESSION['error'] = 'You are not authorized for this action.';
    header("Location: $returnUrl");
    exit();
}

/* -------------------------------------------------
 * 3. Remove the student from the advisory class
 * ------------------------------------------------- */
try {
    $del = $pdo->prepare("
        DELETE FROM students_advising
        WHERE student_id        = :sid
          AND staff_advising_id = :sa_id
    ");
    $del->execute([
        'sid'   => $student_id,
        'sa_id' => $staff_advising_id
    ]);

    if ($del->rowCount() > 0) {
        $_SESSION['success'] = 'Student was successfully removed from the class.';
    } else {
        $_SESSION['error'] = 'Student was not found in this class.';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

/* -------------------------------------------------
 * 4. Redirect back with flash message
 * ------------------------------------------------- */
header("Location: $returnUrl");
exit();
