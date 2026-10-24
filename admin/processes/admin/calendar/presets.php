<?php
/**
 * action=load    adds official dates missing from the calendar (edits are kept)
 * action=restore also brings back official dates that were removed
 * action=reset   puts one official date (id) back to its built-in values
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/school-calendar.php';

$back = '../../../calendar_management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit();
}

calendar_ensure($pdo);

try {
    $action = $_POST['action'] ?? 'load';
    if ($action === 'reset') {
        $ok = calendar_reset_preset($pdo, (int) ($_POST['id'] ?? 0));
        $_SESSION['STATUS'] = $ok ? 'ADMIN_CALENDAR_RESET_SUCCESS' : 'ADMIN_CALENDAR_ERROR';
    } else {
        $changed = calendar_load_presets($pdo, $action === 'restore');
        $_SESSION['STATUS'] = $changed ? 'ADMIN_CALENDAR_PRESETS_LOADED' : 'ADMIN_CALENDAR_PRESETS_CURRENT';
        $_SESSION['CALENDAR_CHANGED'] = $changed;
    }
} catch (PDOException $e) {
    error_log('Calendar presets failed: ' . $e->getMessage());
    $_SESSION['STATUS'] = 'ADMIN_CALENDAR_ERROR';
}

header("Location: $back");
exit();
