<?php
/**
 * NotificationHelper — the single source of notification logic for admin,
 * staff and students. Used by notifications/api.php (the bell dropdown) and
 * by any process that needs to notify someone (see NotificationHelper::send).
 *
 * Identity model (matches AuthService::setSession and ChatHelper):
 *   admin   -> admin_notifications.admin_id   = admin.id
 *   staff   -> staff_notifications.user_id    = staff_accounts.id
 *   student -> student_notifications.user_id  = students.student_id
 *
 * Every read/write is scoped to the logged-in owner, so one person can never
 * read or delete another person's notifications by guessing an id.
 */
class NotificationHelper
{
    /** role => [table, owner column, portal folder used to resolve bare links] */
    private const ROLES = [
        'admin'   => ['admin_notifications', 'admin_id', 'admin/'],
        'staff'   => ['staff_notifications', 'user_id', 'staff/'],
        'student' => ['student_notifications', 'user_id', 'students/'],
    ];

    public const MAX_PAGE = 50;

    private PDO $pdo;
    private int $userId;
    private string $role;
    private string $table;
    private string $owner;

    public function __construct(PDO $pdo, int $userId, string $role)
    {
        if (!self::isValidRole($role)) {
            throw new InvalidArgumentException('Unknown user type.');
        }
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->role = $role;
        [$this->table, $this->owner] = self::ROLES[$role];
    }

    /** Build a helper for the logged-in user, or null if nobody is logged in. */
    public static function fromSession(PDO $pdo): ?self
    {
        $id = $_SESSION['user_id'] ?? null;
        $role = (string) ($_SESSION['user_type'] ?? '');
        if ($id === null || $id === '' || !self::isValidRole($role)) {
            return null;
        }
        return new self($pdo, (int) $id, $role);
    }

    public static function isValidRole(string $role): bool
    {
        return isset(self::ROLES[$role]);
    }

    /* ------------------------------------------------------------------
     * Reading
     * ------------------------------------------------------------------ */

    /**
     * Newest first. $filter is 'all' or 'unread'; pass the last id you have
     * as $beforeId to load the next page.
     *
     * @return array{items: array, has_more: bool}
     */
    public function list(string $filter = 'all', int $limit = 20, int $beforeId = 0): array
    {
        $limit = max(1, min(self::MAX_PAGE, $limit));
        $sql = "SELECT id, type, title, description, date, link, status
                FROM {$this->table} WHERE {$this->owner} = ?";
        $params = [$this->userId];
        if ($filter === 'unread') {
            $sql .= " AND status = 'unread'";
        }
        if ($beforeId > 0) {
            $sql .= " AND id < ?";
            $params[] = $beforeId;
        }
        // One extra row tells us whether another page exists
        $sql .= " ORDER BY id DESC LIMIT " . ($limit + 1);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $limit;
        return [
            'items' => array_map([$this, 'format'], array_slice($rows, 0, $limit)),
            'has_more' => $hasMore,
        ];
    }

    public function unreadCount(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$this->owner} = ? AND status = 'unread'");
        $stmt->execute([$this->userId]);
        return (int) $stmt->fetchColumn();
    }

    public function totalCount(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$this->owner} = ?");
        $stmt->execute([$this->userId]);
        return (int) $stmt->fetchColumn();
    }

    /* ------------------------------------------------------------------
     * Writing (all return the number of rows touched)
     * ------------------------------------------------------------------ */

    public function markRead(int $id): int
    {
        return $this->exec("UPDATE {$this->table} SET status = 'read' WHERE id = ? AND {$this->owner} = ?", [$id, $this->userId]);
    }

    public function markAllRead(): int
    {
        return $this->exec("UPDATE {$this->table} SET status = 'read' WHERE {$this->owner} = ? AND status = 'unread'", [$this->userId]);
    }

    public function delete(int $id): int
    {
        return $this->exec("DELETE FROM {$this->table} WHERE id = ? AND {$this->owner} = ?", [$id, $this->userId]);
    }

    public function deleteAll(): int
    {
        return $this->exec("DELETE FROM {$this->table} WHERE {$this->owner} = ?", [$this->userId]);
    }

    /* ------------------------------------------------------------------
     * Creating
     * ------------------------------------------------------------------ */

    /**
     * Notify one user. For 'admin', pass null as $userId to notify every
     * admin (one row each, like the rest of the app does).
     *
     * $link may be a portal-relative page ("class_management.php"), a
     * root-relative path ("students/student_classes.php?class_id=1"), or ''.
     */
    public static function send(PDO $pdo, string $role, ?int $userId, string $type, string $title, string $description, string $link = ''): void
    {
        if (!self::isValidRole($role)) {
            throw new InvalidArgumentException('Unknown user type.');
        }
        [$table, $owner] = self::ROLES[$role];

        if ($userId === null) {
            if ($role !== 'admin') {
                throw new InvalidArgumentException('A user id is required for ' . $role . ' notifications.');
            }
            $pdo->prepare("INSERT INTO admin_notifications (admin_id, type, title, description, date, link, status)
                           SELECT id, ?, ?, ?, NOW(), ?, 'unread' FROM admin")
                ->execute([$type, $title, $description, $link]);
            return;
        }

        $pdo->prepare("INSERT INTO {$table} ({$owner}, type, title, description, date, link, status)
                       VALUES (?, ?, ?, ?, NOW(), ?, 'unread')")
            ->execute([$userId, $type, $title, $description, $link]);
    }

    /* ------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------ */

    private function exec(string $sql, array $params): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function format(array $row): array
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', (string) $row['date'], new DateTimeZone('Asia/Manila'));
        return [
            'id' => (int) $row['id'],
            'type' => strtolower(trim((string) $row['type'])),
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
            'date' => $date ? $date->format(DATE_ATOM) : null,
            'href' => $this->resolveLink((string) $row['link']),
            'unread' => $row['status'] === 'unread',
        ];
    }

    /**
     * Links in the table were written several ways over the years:
     *   "https://ccs-sms.com/capstone/students/x.php", "/capstone/students/x.php",
     *   "/staff/x.php", "ideas_management.php", "".
     * Normalise them to a path relative to the project root (the widget adds
     * the "../" prefix), or keep a genuinely external URL as-is.
     */
    private function resolveLink(string $link): ?string
    {
        $link = trim($link);
        if ($link === '' || $link === '#') {
            return null;
        }

        if (preg_match('#^https?://([^/]+)(/.*)?$#i', $link, $m)) {
            $host = strtolower($m[1]);
            $ownHosts = ['ccs-sms.com', 'www.ccs-sms.com', strtolower($_SERVER['HTTP_HOST'] ?? '')];
            if (!in_array($host, $ownHosts, true)) {
                return $link;
            }
            $link = $m[2] ?? '';
        }

        $link = ltrim($link, '/');
        $link = preg_replace('#^capstone/#', '', $link);
        if ($link === '') {
            return null;
        }

        // A bare page name belongs to the owner's own portal
        $path = strtok($link, '?#');
        if (strpos((string) $path, '/') === false) {
            $link = self::ROLES[$this->role][2] . $link;
        }
        return $link;
    }
}
