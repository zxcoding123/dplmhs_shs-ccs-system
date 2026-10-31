<?php
/**
 * Adviser registers a new student account and places it in their advisory section.
 * Strand and grade level come from the section code (e.g. "STEM-11A" -> STEM, Grade 11),
 * so an adviser can only create students for a section they actually advise.
 */
session_start();
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['STATUS'] = 'TEACHER_NOT_LOGGED_IN';
    header('Location: ../../../../login/index.php');
    exit();
}

require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/academic.php';
require_once __DIR__ . '/../../../../includes/registrar.php';

$class_name = $_POST['class_name'] ?? '';
$returnUrl  = '../../../advisory_students.php?class_id=' . urlencode($class_name);

function fail_back(string $message, string $returnUrl): void
{
    $_SESSION['error'] = $message;
    // Refill the form (minus the password) so the adviser doesn't retype everything
    $_SESSION['new_student_form'] = array_diff_key($_POST, ['password' => true]);
    header("Location: $returnUrl");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $returnUrl");
    exit();
}

/* -------------------------------------------------
 * 1. Confirm the teacher advises this section
 * ------------------------------------------------- */
$sa = $pdo->prepare("
    SELECT id
    FROM staff_advising
    WHERE fullName       = :teacher
      AND class_advising = :class_name
    LIMIT 1
");
$sa->execute([
    'teacher'    => $_SESSION['full_name'] ?? '',
    'class_name' => $class_name,
]);
$staff_advising_id = $sa->fetchColumn();

if (!$staff_advising_id) {
    $_SESSION['error'] = 'You are not authorized to add students to this class.';
    header('Location: ../../../class_management.php');
    exit();
}

$section = shs_parse_section($class_name);
if (!$section) {
    fail_back('This section code is not in the expected format (e.g. STEM-11A).', $returnUrl);
}
$course     = $section['strand'];
$year_level = $section['grade_level'];

/* -------------------------------------------------
 * 2. Validate the form
 * ------------------------------------------------- */
$firstName  = trim($_POST['first_name'] ?? '');
$middleName = trim($_POST['middle_name'] ?? '');
$lastName   = trim($_POST['last_name'] ?? '');
$studentId  = trim($_POST['student_id'] ?? '');
$gender     = $_POST['gender'] ?? '';
$email      = strtolower(trim($_POST['email'] ?? ''));
$password   = $_POST['password'] ?? '';

if ($firstName === '' || $lastName === '' || $studentId === '' || $email === '' || $password === '') {
    fail_back('Please fill in all required fields.', $returnUrl);
}
if (!ctype_digit($studentId) || strlen($studentId) > 9) {
    fail_back('Student ID must be a number of up to 9 digits.', $returnUrl);
}
if (!in_array($gender, ['Male', 'Female', 'Others'], true)) {
    fail_back('Please select a gender.', $returnUrl);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail_back('Please enter a valid email address.', $returnUrl);
}
if (strlen($password) < 8) {
    fail_back('The password must be at least 8 characters.', $returnUrl);
}

$dup = $pdo->prepare("SELECT 1 FROM users WHERE email = :email");
$dup->execute(['email' => $email]);
if ($dup->fetchColumn()) {
    fail_back('That email address is already used by another account.', $returnUrl);
}

$dup = $pdo->prepare("SELECT 1 FROM students WHERE student_id = :student_id");
$dup->execute(['student_id' => $studentId]);
if ($dup->fetchColumn()) {
    fail_back("Student ID $studentId is already registered.", $returnUrl);
}

/* -------------------------------------------------
 * 3. Create the account and add it to the section
 * ------------------------------------------------- */
$fullName = trim($firstName . ' ' . ($middleName !== '' ? $middleName . ' ' : '') . $lastName);

try {
    $pdo->beginTransaction();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (email, password, role, status, date_created) VALUES (:email, :password, 'student', 'active', NOW())")
        ->execute(['email' => $email, 'password' => $hash]);
    $userId = (int) $pdo->lastInsertId();

    // The adviser vouches for the student, so the account is active right away (no activation email)
    $pdo->prepare("
        INSERT INTO students (user_id, first_name, middle_name, last_name, student_id, gender, course, year_level, status)
        VALUES (:user_id, :first_name, :middle_name, :last_name, :student_id, :gender, :course, :year_level, 'active')
    ")->execute([
        'user_id'     => $userId,
        'first_name'  => $firstName,
        'middle_name' => $middleName,
        'last_name'   => $lastName,
        'student_id'  => $studentId,
        'gender'      => $gender,
        'course'      => $course,
        'year_level'  => $year_level,
    ]);
    $studentRowId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO students_advising (student_id, staff_advising_id) VALUES (:sid, :sa_id)")
        ->execute(['sid' => $studentRowId, 'sa_id' => $staff_advising_id]);

    // So the registrar sees the student as enrolled in this section for the current term
    $term = enrollment_current_term($pdo);
    enrollment_save($pdo, $studentRowId, [
        'school_year'    => $term['school_year'],
        'term'           => $term['term'],
        'strand'         => $course,
        'grade_level'    => $year_level,
        'section_code'   => $class_name,
        'admission_type' => $section['grade'] === '11' ? 'new' : 'continuing',
        'status'         => 'enrolled',
        'remarks'        => 'Added by adviser',
    ], 'staff', $_SESSION['full_name'] ?? 'Adviser');

    // Let the admins know (one notification per admin)
    $pdo->prepare("
        INSERT INTO admin_notifications (admin_id, type, title, description, date, link, status)
        SELECT id, 'student', 'New Student Added by Adviser', :description, NOW(), '', 'unread' FROM admin
    ")->execute([
        'description' => ($_SESSION['full_name'] ?? 'An adviser') . " added $fullName ($studentId) to $class_name.",
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Adviser create student failed: ' . $e->getMessage());
    fail_back('The student could not be created because of a database error. Please try again.', $returnUrl);
}

unset($_SESSION['new_student_form']);
// Shown once on the class page so the adviser can hand the login details to the student
$_SESSION['new_student_credentials'] = [
    'name'     => $fullName,
    'email'    => $email,
    'password' => $password,
];
header("Location: $returnUrl");
exit();
