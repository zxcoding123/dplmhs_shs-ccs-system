<?php
/**
 * Create a registrar account (no id) or update one (id). The login lives in
 * `users` (role 'registrar'); the profile in `registrar_accounts`.
 */
require __DIR__ . '/../../../../database/connection.php';
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require_once __DIR__ . '/../../../../includes/registrar.php';

$back = '../../../registrar_management.php';

function registrar_back(string $back, string $title, string $text, string $icon = 'error'): void
{
    registrar_flash($title, $text, $icon);
    header("Location: $back");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !registrar_csrf_valid()) {
    registrar_back($back, 'Request expired', 'Reload the page and try again.');
}

$id = (int) ($_POST['id'] ?? 0);
$first = trim($_POST['first_name'] ?? '');
$middle = trim($_POST['middle_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone_number'] ?? '');
$gender = $_POST['gender'] ?? '';
$password = $_POST['password'] ?? '';
$fullName = trim($first . ' ' . ($middle !== '' ? $middle . ' ' : '') . $last);

if ($first === '' || $last === '' || $email === '') {
    registrar_back($back, 'Missing details', 'First name, last name and email are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    registrar_back($back, 'Invalid email', 'Enter a valid email address.');
}
if ($phone !== '' && !preg_match('/^\+63\d{10}$/', $phone)) {
    registrar_back($back, 'Invalid phone number', 'Use the format +639xxxxxxxxx, or leave it blank.');
}
if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
    registrar_back($back, 'Missing details', 'Pick a gender.');
}
if (($id === 0 || $password !== '') && strlen($password) < 8) {
    registrar_back($back, 'Password too short', 'The password must be at least 8 characters.');
}
if ($password !== ($_POST['confirm_password'] ?? '')) {
    registrar_back($back, "Passwords don't match", 'Type the same password in both fields.');
}

$userId = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT user_id FROM registrar_accounts WHERE id = ?");
    $stmt->execute([$id]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        registrar_back($back, 'Not found', 'That registrar account no longer exists.');
    }
}

// Email must be unique across every login, and the admin table (which can sign in on its own)
$dup = $pdo->prepare("SELECT 1 FROM users WHERE email = :email AND id <> :id
    UNION SELECT 1 FROM admin WHERE email = :email2 AND (user_id IS NULL OR user_id <> :id2)");
$dup->execute(['email' => $email, 'id' => (int) $userId, 'email2' => $email, 'id2' => (int) $userId]);
if ($dup->fetchColumn()) {
    registrar_back($back, 'Email already in use', 'Another account already uses ' . $email . '.');
}

try {
    $pdo->beginTransaction();

    if ($id === 0) {
        $pdo->prepare("INSERT INTO users (email, password, role, status, date_created) VALUES (?, ?, 'registrar', 'active', NOW())")
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = (int) $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO registrar_accounts (user_id, first_name, middle_name, last_name, fullName, phone_number, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$userId, $first, $middle, $last, $fullName, $phone, $gender]);
    } else {
        $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $userId]);
        if ($password !== '') {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        }
        $pdo->prepare("UPDATE registrar_accounts SET first_name = ?, middle_name = ?, last_name = ?, fullName = ?, phone_number = ?, gender = ? WHERE id = ?")
            ->execute([$first, $middle, $last, $fullName, $phone, $gender, $id]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Saving registrar failed: ' . $e->getMessage());
    registrar_back($back, 'Error', 'The registrar account could not be saved. Please try again.');
}

registrar_back($back, $id ? 'Saved' : 'Registrar created',
    $id ? $fullName . "'s account has been updated." : $fullName . ' can now sign in with ' . $email . '.', 'success');
