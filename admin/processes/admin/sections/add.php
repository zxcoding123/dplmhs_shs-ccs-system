<?php
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/academic.php';

$back = '../../../section_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit();
}

$strand = $_POST['strand'] ?? '';
$grade = $_POST['grade'] ?? '';
$name = trim($_POST['name'] ?? '');
$room = trim($_POST['room'] ?? '');

if (!isset(shs_strands()[$strand]) || !in_array($grade, shs_grades(), true)
    || !shs_valid_section_name($name) || mb_strlen($room) > 50) {
    $_SESSION['STATUS'] = 'ADMIN_SECTION_INVALID';
    header("Location: $back");
    exit();
}

$code = shs_section_code($strand, $grade, $name);

try {
    $exists = $pdo->prepare("SELECT COUNT(*) FROM sections WHERE code = :code");
    $exists->execute(['code' => $code]);
    if ($exists->fetchColumn() > 0) {
        $_SESSION['STATUS'] = 'ADMIN_SECTION_EXISTS';
        header("Location: $back");
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO sections (strand, grade, name, code, room) VALUES (:strand, :grade, :name, :code, :room)");
    $stmt->execute([
        'strand' => $strand,
        'grade' => $grade,
        'name' => $name,
        'code' => $code,
        'room' => $room !== '' ? $room : null,
    ]);
    $_SESSION['STATUS'] = 'ADMIN_SECTION_ADD_SUCCESS';
} catch (PDOException $e) {
    error_log('Section add failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_SECTION_ADD_ERROR';
}

header("Location: $back");
exit();
