<?php
/**
 * Create a student (no id) or update one's details (id).
 *
 * New students get an active login straight away (the registrar vouches for
 * them, like an adviser does) and, when "enroll now" is ticked, a record for the
 * current term, which also lists them under their section's adviser.
 * The student ID is fixed once created: chat, attendance and grades key on it.
 */
require __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_POST['id'] ?? 0);
$back = $id ? '../student.php?id=' . $id : '../students.php';
registrar_require_post($back);

function student_fail(string $back, string $message, bool $refill): void
{
    if ($refill) {
        $_SESSION['registrar_student_form'] = array_diff_key($_POST, ['csrf' => true]);
    }
    registrar_redirect($back, ['Check the details', $message, 'warning']);
}

$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$lrn = trim($_POST['lrn'] ?? '');
$gender = $_POST['gender'] ?? '';
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$isNew = $id === 0;

if ($first === '' || $last === '' || $email === '') {
    student_fail($back, 'First name, last name and email are required.', $isNew);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    student_fail($back, 'Enter a valid email address.', $isNew);
}
if (!in_array($gender, ['Male', 'Female', 'Others'], true)) {
    student_fail($back, 'Pick a gender.', $isNew);
}
if (!registrar_valid_lrn($lrn)) {
    student_fail($back, 'The LRN must be exactly 12 digits, or left blank.', $isNew);
}
if (($isNew || $password !== '') && strlen($password) < 8) {
    student_fail($back, 'The password must be at least 8 characters.', $isNew);
}

$userId = 0;
if (!$isNew) {
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $userId = (int) $stmt->fetchColumn();
    if (!$userId) {
        registrar_redirect('../students.php', ['Not found', 'That student record no longer exists.', 'error']);
    }
}

$dup = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
$dup->execute([$email, $userId]);
if ($dup->fetchColumn()) {
    student_fail($back, $email . ' is already used by another account.', $isNew);
}
if ($lrn !== '') {
    $dup = $pdo->prepare("SELECT 1 FROM students WHERE lrn = ? AND id <> ?");
    $dup->execute([$lrn, $id]);
    if ($dup->fetchColumn()) {
        student_fail($back, 'LRN ' . $lrn . ' already belongs to another student.', $isNew);
    }
}

$fullName = trim($first . ' ' . ($middle !== '' ? $middle . ' ' : '') . $last);

/* ---------- Update ---------- */
if (!$isNew) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE students SET first_name = ?, middle_name = ?, last_name = ?, lrn = ?, gender = ? WHERE id = ?")
            ->execute([$first, $middle, $last, $lrn !== '' ? $lrn : null, $gender, $id]);
        $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $userId]);
        if ($password !== '') {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Registrar student update failed: ' . $e->getMessage());
        registrar_redirect($back, ['Error', 'The student could not be saved. Please try again.', 'error']);
    }

    if ($password !== '') {
        $_SESSION['registrar_new_credentials'] = ['name' => $fullName, 'email' => $email, 'password' => $password, 'reset' => true];
    }
    registrar_redirect($back, ['Saved', $fullName . "'s details have been updated.", 'success']);
}

/* ---------- Create (the student ID is assigned by registrar_insert_student) ---------- */
$term = enrollment_current_term($pdo);
[$record, $error] = enrollment_validate([
    'school_year'    => $term['school_year'],
    'term'           => $term['term'],
    'strand'         => $_POST['strand'] ?? '',
    'grade'          => $_POST['grade'] ?? '',
    'section_code'   => $_POST['section_code'] ?? '',
    'admission_type' => $_POST['admission_type'] ?? 'new',
    'status'         => 'enrolled',
]);
if ($error) {
    student_fail($back, $error, true);
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("INSERT INTO users (email, password, role, status, date_created) VALUES (?, ?, 'student', 'active', NOW())")
        ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();

    [$newId, $studentId] = registrar_insert_student($pdo, [
        'user_id'     => $userId,
        'first_name'  => $first,
        'middle_name' => $middle,
        'last_name'   => $last,
        'lrn'         => $lrn !== '' ? $lrn : null,
        'gender'      => $gender,
        'course'      => $record['strand'],
        'year_level'  => $record['grade_level'],
        'status'      => 'active',
    ]);

    if (!empty($_POST['enroll_now'])) {
        enrollment_save($pdo, $newId, $record, 'registrar', $registrarName);
    }

    $pdo->commit();
} catch (PDOException | RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Registrar create student failed: ' . $e->getMessage());
    student_fail($back, 'The student could not be created because of a database error. Please try again.', true);
}

// Shown once on the student's page so the registrar can hand over the login
$_SESSION['registrar_new_credentials'] = ['name' => $fullName, 'email' => $email, 'password' => $password, 'student_id' => $studentId];
registrar_redirect('../student.php?id=' . $newId);
