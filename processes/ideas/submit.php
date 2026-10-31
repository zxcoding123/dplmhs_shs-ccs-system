<?php
/**
 * Post a new idea from ideas.php. Signed-in admins, teachers and students only;
 * the author's vote is added automatically and admins get a notification.
 */
session_start();
require __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../includes/feature-requests.php';

function ideas_back(string $type, string $message, array $old = []): void
{
    $_SESSION['IDEAS_FLASH'] = [$type, $message];
    if ($old) {
        $_SESSION['IDEAS_OLD'] = $old;
    }
    header('Location: ../../ideas.php' . ($type === 'success' ? '?sort=new#ideas' : '#suggest'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../ideas.php');
    exit();
}

$viewer = ideas_viewer();
if (!$viewer) {
    ideas_back('warning', 'Please log in to post an idea.');
}
if (!ideas_csrf_valid()) {
    ideas_back('warning', 'Your session expired. Please try again.');
}

$title = trim(preg_replace('/\s+/', ' ', $_POST['title'] ?? ''));
$details = trim($_POST['details'] ?? '');
$category = $_POST['category'] ?? '';
$old = ['title' => $title, 'details' => $details, 'category' => $category];

if (mb_strlen($title) < 8 || mb_strlen($title) > 150 || mb_strlen($details) > 3000 || !isset(IDEAS_CATEGORIES[$category])) {
    ideas_back('warning', 'Give your idea a title of 8–150 characters and pick a type.', $old);
}

try {
    ideas_ensure($pdo);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM feature_requests WHERE author_role = ? AND author_id = ? AND created_at >= NOW() - INTERVAL 1 DAY');
    $stmt->execute([$viewer['role'], $viewer['id']]);
    if ((int) $stmt->fetchColumn() >= IDEAS_DAILY_LIMIT) {
        ideas_back('warning', 'You have posted ' . IDEAS_DAILY_LIMIT . ' ideas today. Please come back tomorrow, or upvote ideas that are already on the board.', $old);
    }

    $stmt = $pdo->prepare('SELECT id FROM feature_requests WHERE title = ? AND is_hidden = 0 LIMIT 1');
    $stmt->execute([$title]);
    if ($stmt->fetchColumn()) {
        ideas_back('info', 'That idea is already on the board. Upvote it instead so it moves up the list.', $old);
    }

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO feature_requests (title, details, category, author_role, author_id, author_name, vote_count) VALUES (?, ?, ?, ?, ?, ?, 1)')
        ->execute([$title, $details !== '' ? $details : null, $category, $viewer['role'], $viewer['id'], $viewer['name'] !== '' ? $viewer['name'] : null]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO feature_request_votes (request_id, voter_role, voter_id) VALUES (?, ?, ?)')
        ->execute([$id, $viewer['role'], $viewer['id']]);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Idea submit failed: ' . $e->getMessage());
    ideas_back('danger', 'Your idea could not be posted. Please try again.', $old);
}

try {
    // One notification row per admin, like RegistrationService
    $insert = $pdo->prepare("INSERT INTO admin_notifications (admin_id, type, title, description, date, link, status) VALUES (?, 'idea', ?, ?, NOW(), 'ideas_management.php', 'unread')");
    $notice = ['New idea: ' . mb_substr($title, 0, 80), IDEAS_ROLES[$viewer['role']] . ' ' . ideas_display_name(['author_name' => $viewer['name'], 'author_role' => $viewer['role']]) . ' suggested an improvement.'];
    foreach ($pdo->query('SELECT id FROM admin')->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
        $insert->execute([$adminId, ...$notice]);
    }
} catch (PDOException $e) {
    error_log('Idea notification failed: ' . $e->getMessage());
}

ideas_back('success', 'Thanks! Your idea is on the board. You can follow its progress here.');
