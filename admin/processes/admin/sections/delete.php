<?php
/**
 * Delete a section. Refused while advisers, classes or subjects still use it,
 * so nothing is left pointing at a section that no longer exists.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';

$back = '../../../section_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit();
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $find = $pdo->prepare("SELECT * FROM sections WHERE id = :id");
    $find->execute(['id' => $id]);
    $section = $find->fetch(PDO::FETCH_ASSOC);
    if (!$section) {
        $_SESSION['STATUS'] = 'ADMIN_SECTION_DELETE_ERROR';
        header("Location: $back");
        exit();
    }

    $inUse = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM staff_advising WHERE class_advising = :c1)
          + (SELECT COUNT(*) FROM classes WHERE name = :c2)
          + (SELECT COUNT(*) FROM subjects WHERE course = :strand AND year_level = :suffix)");
    $inUse->execute([
        'c1' => $section['code'],
        'c2' => $section['code'],
        'strand' => $section['strand'],
        'suffix' => $section['grade'] . $section['name'],
    ]);
    if ($inUse->fetchColumn() > 0) {
        $_SESSION['STATUS'] = 'ADMIN_SECTION_IN_USE';
        header("Location: $back");
        exit();
    }

    $pdo->prepare("DELETE FROM sections WHERE id = :id")->execute(['id' => $id]);
    $_SESSION['STATUS'] = 'ADMIN_SECTION_DELETE_SUCCESS';
} catch (PDOException $e) {
    error_log('Section delete failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_SECTION_DELETE_ERROR';
}

header("Location: $back");
exit();
