<?php
/**
 * School events are deleted. Official dates are only hidden, so loading the
 * official holidays again doesn't silently bring back a date the school removed.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/school-calendar.php';

$back = '../../../calendar_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: $back");
    exit();
}

calendar_ensure($pdo);

try {
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE school_calendar SET is_hidden = 1 WHERE id = ? AND preset_key IS NOT NULL")->execute([$id]);
    $pdo->prepare("DELETE FROM school_calendar WHERE id = ? AND preset_key IS NULL")->execute([$id]);
    $_SESSION['STATUS'] = 'ADMIN_CALENDAR_DELETE_SUCCESS';
} catch (PDOException $e) {
    error_log('Calendar delete failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_CALENDAR_ERROR';
}

header("Location: $back");
exit();
