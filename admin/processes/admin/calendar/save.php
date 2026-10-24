<?php
/**
 * Create or update a calendar entry. Form posts redirect back with a STATUS;
 * drag-and-drop moves from the calendar post ajax=1 with only id/start_date/end_date
 * and get JSON back.
 */
session_start();
require_once __DIR__ . '/../../../includes/guard.php';
require __DIR__ . '/../../../../database/connection.php';
require_once __DIR__ . '/../../../../includes/school-calendar.php';

$back = '../../../calendar_management.php';
$ajax = !empty($_POST['ajax']);

function calendar_done(bool $ajax, string $status, string $back, bool $ok, string $message = ''): void
{
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $message]);
    } else {
        $_SESSION['STATUS'] = $status;
        $month = substr($_POST['start_date'] ?? '', 0, 7);
        header('Location: ' . $back . (preg_match('/^\d{4}-\d{2}$/', $month) ? '?month=' . $month : ''));
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit();
}

calendar_ensure($pdo);

$id = (int) ($_POST['id'] ?? 0);
$start = $_POST['start_date'] ?? '';
$end = ($_POST['end_date'] ?? '') !== '' ? $_POST['end_date'] : $start;
$validDate = fn($d) => ($t = DateTime::createFromFormat('Y-m-d', $d)) && $t->format('Y-m-d') === $d;

if (!$validDate($start) || !$validDate($end) || $end < $start) {
    calendar_done($ajax, 'ADMIN_CALENDAR_INVALID', $back, false, 'Check the dates: the end date cannot be before the start date.');
}

try {
    if ($ajax) {
        // Moved or stretched on the calendar: only the dates change
        $stmt = $pdo->prepare("UPDATE school_calendar SET start_date = ?, end_date = ?,
            is_modified = IF(preset_key IS NULL, 0, 1) WHERE id = ?");
        $stmt->execute([$start, $end, $id]);
        calendar_done(true, '', $back, true, 'Moved.');
    }

    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $isCustom = $category === 'custom';
    $customLabel = $isCustom ? trim($_POST['custom_label'] ?? '') : '';
    $color = $isCustom ? calendar_valid_color($_POST['color'] ?? '') : '';
    if ($title === '' || mb_strlen($title) > 150 || !isset(CALENDAR_CATEGORIES[$category]) || mb_strlen($description) > 2000
        || ($isCustom && ($customLabel === '' || mb_strlen($customLabel) > 50))) {
        calendar_done(false, 'ADMIN_CALENDAR_INVALID', $back, false);
    }

    $data = [
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'start_date' => $start,
        'end_date' => $end,
        'category' => $category,
        'custom_label' => $isCustom ? $customLabel : null,
        'color' => $color !== '' ? $color : null,
        'no_classes' => !empty($_POST['no_classes']) ? 1 : 0,
        'is_public' => !empty($_POST['is_public']) ? 1 : 0,
    ];

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE school_calendar SET title = :title, description = :description,
            start_date = :start_date, end_date = :end_date, category = :category,
            custom_label = :custom_label, color = :color, no_classes = :no_classes,
            is_public = :is_public, is_modified = IF(preset_key IS NULL, 0, 1) WHERE id = :id");
        $stmt->execute($data + ['id' => $id]);
        calendar_done(false, 'ADMIN_CALENDAR_UPDATE_SUCCESS', $back, true);
    }

    $stmt = $pdo->prepare("INSERT INTO school_calendar (title, description, start_date, end_date, category, custom_label, color, no_classes, is_public, source)
        VALUES (:title, :description, :start_date, :end_date, :category, :custom_label, :color, :no_classes, :is_public, 'school')");
    $stmt->execute($data);
    calendar_done(false, 'ADMIN_CALENDAR_ADD_SUCCESS', $back, true);
} catch (PDOException $e) {
    error_log('Calendar save failed: ' . $e->getMessage());
    calendar_done($ajax, 'ADMIN_CALENDAR_ERROR', $back, false, 'The change could not be saved.');
}
