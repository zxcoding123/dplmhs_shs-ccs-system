<?php
/**
 * Ideas & Improvements board: signed-in users suggest system needs and upvote them;
 * admins triage them from Admin > Ideas & Improvements (admin/ideas_management.php).
 *
 *   require_once __DIR__ . '/../includes/feature-requests.php';
 *   ideas_ensure($pdo);                 // creates the tables on first use
 *   $viewer = ideas_viewer();           // ['role' => 'staff', 'id' => '12', 'name' => '…'] or null
 *   $rows = ideas_rows($pdo, $viewer, ['status' => 'planned', 'sort' => 'top']);
 *
 * Voters and authors are stored as (role, id) because $_SESSION['user_id'] is the id
 * within the role table (admin.id, staff_accounts.id, students.student_id).
 */

/** status => [label, short description, admin pill tone, public colour] */
const IDEAS_STATUSES = [
    'open' => ['New', 'Waiting to be reviewed', '', '#5d6475'],
    'under_review' => ['Under review', 'Being looked into', 'is-info', '#2f5bb7'],
    'planned' => ['Planned', 'Accepted and scheduled', 'is-warn', '#a16207'],
    'in_progress' => ['In progress', 'Being built now', 'is-warn', '#c46a12'],
    'done' => ['Done', 'Available in the system', 'is-ok', '#1f7a3a'],
    'declined' => ['Not planned', 'Won\'t be added for now', 'is-alert', '#a3191b'],
];

/** Statuses that still need work; the board shows these by default. */
const IDEAS_ACTIVE_STATUSES = ['open', 'under_review', 'planned', 'in_progress'];

/** category => [label, icon] */
const IDEAS_CATEGORIES = [
    'feature' => ['New feature', 'bi-stars'],
    'improvement' => ['Improvement', 'bi-arrow-up-circle'],
    'bug' => ['Something isn\'t working', 'bi-bug'],
    'other' => ['Other', 'bi-chat-left-dots'],
];

const IDEAS_ROLES = ['admin' => 'Admin', 'staff' => 'Teacher', 'student' => 'Student'];

/** New ideas one person can post per day. */
const IDEAS_DAILY_LIMIT = 5;

function ideas_ensure(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `feature_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(150) NOT NULL,
        `details` text DEFAULT NULL,
        `category` varchar(20) NOT NULL DEFAULT 'feature',
        `status` varchar(20) NOT NULL DEFAULT 'open',
        `author_role` varchar(10) NOT NULL,
        `author_id` varchar(50) NOT NULL,
        `author_name` varchar(150) DEFAULT NULL,
        `admin_response` text DEFAULT NULL,
        `responded_at` datetime DEFAULT NULL,
        `vote_count` int(11) NOT NULL DEFAULT 0,
        `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_requests_status` (`status`, `is_hidden`),
        KEY `idx_requests_author` (`author_role`, `author_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `feature_request_votes` (
        `request_id` int(11) NOT NULL,
        `voter_role` varchar(10) NOT NULL,
        `voter_id` varchar(50) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`request_id`, `voter_role`, `voter_id`),
        CONSTRAINT `fk_request_votes_request` FOREIGN KEY (`request_id`) REFERENCES `feature_requests` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** The signed-in admin, teacher or student, or null for guests. */
function ideas_viewer(): ?array
{
    $role = $_SESSION['user_type'] ?? '';
    $id = $role === 'admin' ? ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? '') : ($_SESSION['user_id'] ?? '');
    if (!isset(IDEAS_ROLES[$role]) || (string) $id === '') {
        return null;
    }
    return ['role' => $role, 'id' => (string) $id, 'name' => trim((string) ($_SESSION['name'] ?? ''))];
}

/** "Maria S." — first name and last initial, so students aren't named in full on a public page. */
function ideas_display_name(array $row): string
{
    $parts = preg_split('/\s+/', trim((string) ($row['author_name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return IDEAS_ROLES[$row['author_role']] ?? 'Someone';
    }
    $name = $parts[0];
    if (count($parts) > 1) {
        $name .= ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.';
    }
    return $name;
}

function ideas_status(string $status): array
{
    return IDEAS_STATUSES[$status] ?? IDEAS_STATUSES['open'];
}

/**
 * Ideas for the board or the admin list. Filters:
 *   status  – a status key, 'active' (default) or 'all'
 *   sort    – 'top' (default) or 'new'
 *   q       – search in title and details
 *   hidden  – include hidden ideas (admin)
 * Each row gets has_voted for $viewer.
 */
function ideas_rows(PDO $pdo, ?array $viewer, array $filters = [], int $limit = 0, int $offset = 0): array
{
    [$where, $params] = ideas_where($filters);
    $params['voter_role'] = $viewer['role'] ?? '';
    $params['voter_id'] = $viewer['id'] ?? '';
    $order = ($filters['sort'] ?? 'top') === 'new' ? 'r.created_at DESC' : 'r.vote_count DESC, r.created_at DESC';

    $sql = "SELECT r.*, (v.request_id IS NOT NULL) AS has_voted
        FROM feature_requests r
        LEFT JOIN feature_request_votes v ON v.request_id = r.id AND v.voter_role = :voter_role AND v.voter_id = :voter_id
        WHERE $where ORDER BY $order" . ($limit > 0 ? ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset) : '');
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ideas_count(PDO $pdo, array $filters = []): int
{
    [$where, $params] = ideas_where($filters);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM feature_requests r WHERE $where");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/** status => number of visible ideas. */
function ideas_status_counts(PDO $pdo, bool $includeHidden = false): array
{
    $counts = array_fill_keys(array_keys(IDEAS_STATUSES), 0);
    $sql = 'SELECT status, COUNT(*) FROM feature_requests' . ($includeHidden ? '' : ' WHERE is_hidden = 0') . ' GROUP BY status';
    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR) as $status => $n) {
        if (isset($counts[$status])) {
            $counts[$status] = (int) $n;
        }
    }
    return $counts;
}

function ideas_where(array $filters): array
{
    $where = [];
    $params = [];
    if (empty($filters['hidden'])) {
        $where[] = 'r.is_hidden = 0';
    }
    $status = $filters['status'] ?? 'active';
    if ($status === 'active') {
        $where[] = "r.status IN ('" . implode("','", IDEAS_ACTIVE_STATUSES) . "')";
    } elseif (isset(IDEAS_STATUSES[$status])) {
        $where[] = 'r.status = :status';
        $params['status'] = $status;
    }
    $q = trim($filters['q'] ?? '');
    if ($q !== '') {
        $where[] = '(r.title LIKE :q1 OR r.details LIKE :q2)';
        $params['q1'] = $params['q2'] = '%' . addcslashes($q, '%_\\') . '%';
    }
    return [$where ? implode(' AND ', $where) : '1', $params];
}

/** Adds or removes $viewer's vote; returns [has_voted, vote_count]. */
function ideas_toggle_vote(PDO $pdo, int $requestId, array $viewer): array
{
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM feature_request_votes WHERE request_id = ? AND voter_role = ? AND voter_id = ?');
        $del->execute([$requestId, $viewer['role'], $viewer['id']]);
        $voted = $del->rowCount() === 0;
        if ($voted) {
            $pdo->prepare('INSERT INTO feature_request_votes (request_id, voter_role, voter_id) VALUES (?, ?, ?)')
                ->execute([$requestId, $viewer['role'], $viewer['id']]);
        }
        // Recount rather than +1/-1 so the cached total can't drift
        $pdo->prepare('UPDATE feature_requests SET vote_count = (SELECT COUNT(*) FROM feature_request_votes WHERE request_id = ?), updated_at = updated_at WHERE id = ?')
            ->execute([$requestId, $requestId]);
        $count = $pdo->prepare('SELECT vote_count FROM feature_requests WHERE id = ?');
        $count->execute([$requestId]);
        $total = (int) $count->fetchColumn();
        $pdo->commit();
        return [$voted, $total];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Per-session token for the board's forms. */
function ideas_csrf_token(): string
{
    if (empty($_SESSION['ideas_csrf'])) {
        $_SESSION['ideas_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['ideas_csrf'];
}

function ideas_csrf_valid(): bool
{
    return !empty($_SESSION['ideas_csrf']) && hash_equals($_SESSION['ideas_csrf'], (string) ($_POST['csrf'] ?? ''));
}
