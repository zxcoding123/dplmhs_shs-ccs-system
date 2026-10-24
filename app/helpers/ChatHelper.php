<?php
/**
 * ChatHelper — the single source of chat logic for admin, staff and students.
 *
 * Identity model (matches AuthService::setSession):
 *   admin   -> admin.id
 *   staff   -> staff_accounts.id
 *   student -> students.student_id (business number, NOT students.id)
 *
 * Ids are only unique WITHIN a role table, so every query here matches on
 * BOTH id and type. Never compare a chat participant by id alone.
 *
 * Messages may carry one file or photo (see ChatAttachments); the text is then optional.
 */
require_once __DIR__ . '/ChatAttachments.php';

class ChatHelper
{
    public const TYPES = ['admin', 'staff', 'student'];
    public const MAX_MESSAGE_LENGTH = 2000;

    private PDO $pdo;
    private int $userId;
    private string $userType;

    public function __construct(PDO $pdo, int $userId, string $userType)
    {
        if (!self::isValidType($userType)) {
            throw new InvalidArgumentException('Unknown user type.');
        }
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userType = $userType;
        ChatAttachments::ensureTable($pdo);
    }

    /** Build a helper for the logged-in user, or null if nobody is logged in. */
    public static function fromSession(PDO $pdo): ?self
    {
        $id = $_SESSION['user_id'] ?? null;
        $type = $_SESSION['user_type'] ?? null;
        if ($id === null || $id === '' || !self::isValidType((string) $type)) {
            return null;
        }
        return new self($pdo, (int) $id, (string) $type);
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function userType(): string
    {
        return $this->userType;
    }

    /* ------------------------------------------------------------------
     * People
     * ------------------------------------------------------------------ */

    /** Display name for any participant, or null if the account no longer exists. */
    public function getUserName(int $id, string $type): ?string
    {
        switch ($type) {
            case 'admin':
                $sql = "SELECT fullName FROM admin WHERE id = ?";
                break;
            case 'staff':
                $sql = "SELECT fullName FROM staff_accounts WHERE id = ?";
                break;
            case 'student':
                $sql = "SELECT CONCAT_WS(' ', first_name, NULLIF(middle_name, ''), last_name) FROM students WHERE student_id = ?";
                break;
            default:
                return null;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : trim((string) $name);
    }

    public const DELETED_NAME = 'Deleted account';

    /**
     * Current state of chat participants, so old conversations show who is gone.
     *
     * @param array $people list of [id, type]
     * @return array "type:id" => ['status' => 'active'|'deactivated'|'deleted', 'name' => current name or DELETED_NAME]
     */
    public function accountStatuses(array $people): array
    {
        $byType = [];
        foreach ($people as [$id, $type]) {
            if (self::isValidType($type)) {
                $byType[$type][(int) $id] = true;
            }
        }

        // The login record (users.status) decides whether someone can sign in;
        // the role table's own status is the fallback for rows without one.
        $sql = [
            'admin' => "SELECT a.id, a.fullName AS name, COALESCE(u.status, a.status) AS status
                        FROM admin a LEFT JOIN users u ON u.id = a.user_id WHERE a.id IN (%s)",
            'staff' => "SELECT s.id, s.fullName AS name, COALESCE(u.status, s.status) AS status
                        FROM staff_accounts s LEFT JOIN users u ON u.id = s.user_id WHERE s.id IN (%s)",
            'student' => "SELECT st.student_id AS id, CONCAT_WS(' ', st.first_name, NULLIF(st.middle_name, ''), st.last_name) AS name,
                          COALESCE(u.status, st.status) AS status
                          FROM students st LEFT JOIN users u ON u.id = st.user_id WHERE st.student_id IN (%s)",
        ];

        $result = [];
        foreach ($byType as $type => $ids) {
            $ids = array_keys($ids);
            foreach ($ids as $id) {
                $result["$type:$id"] = ['status' => 'deleted', 'name' => self::DELETED_NAME];
            }
            $stmt = $this->pdo->prepare(sprintf($sql[$type], implode(',', array_fill(0, count($ids), '?'))));
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $result[$type . ':' . (int) $row['id']] = [
                    'status' => strtolower(trim((string) $row['status'])) === 'active' ? 'active' : 'deactivated',
                    'name' => trim((string) $row['name']),
                ];
            }
        }
        return $result;
    }

    /** Search every role table by name (and email / student number). Excludes the current user. */
    public function searchUsers(string $term, int $limitPerType = 8): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $like = '%' . $term . '%';
        $limit = max(1, min(25, $limitPerType));

        $queries = [
            'admin' => "SELECT id, fullName AS name FROM admin
                        WHERE fullName LIKE ? OR email LIKE ? ORDER BY fullName LIMIT $limit",
            'staff' => "SELECT id, fullName AS name FROM staff_accounts
                        WHERE fullName LIKE ? OR email LIKE ? ORDER BY fullName LIMIT $limit",
            'student' => "SELECT student_id AS id, CONCAT_WS(' ', first_name, NULLIF(middle_name, ''), last_name) AS name
                          FROM students
                          WHERE CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR student_id LIKE ?
                          ORDER BY first_name, last_name LIMIT $limit",
        ];

        $results = [];
        foreach ($queries as $type => $sql) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$like, $like]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ((int) $row['id'] === $this->userId && $type === $this->userType) {
                    continue;
                }
                $results[] = ['id' => (int) $row['id'], 'type' => $type, 'name' => trim((string) $row['name'])];
            }
        }
        $statuses = $this->accountStatuses(array_map(fn($r) => [$r['id'], $r['type']], $results));
        foreach ($results as &$r) {
            $r['account_status'] = $statuses[$r['type'] . ':' . $r['id']]['status'] ?? 'active';
        }
        return $results;
    }

    /* ------------------------------------------------------------------
     * Direct messages
     * ------------------------------------------------------------------ */

    /** Latest message per conversation partner, newest first, with unread counts. */
    public function recentConversations(int $limit = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.id, m.sender_id, m.sender_type, m.receiver_id, m.receiver_type,
                   m.message, m.timestamp, m.sender_name, m.receiver_name, m.status,
                   a.kind AS att_kind, a.original_name AS att_name
            FROM messages m
            LEFT JOIN chat_attachments a ON a.scope = 'dm' AND a.message_id = m.id
            WHERE (m.sender_id = :id AND m.sender_type = :type)
               OR (m.receiver_id = :id2 AND m.receiver_type = :type2)
            ORDER BY m.id DESC
            LIMIT 1000
        ");
        $stmt->execute([
            ':id' => $this->userId, ':type' => $this->userType,
            ':id2' => $this->userId, ':type2' => $this->userType,
        ]);

        $conversations = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $isMine = $this->isMine($row);
            $partnerId = (int) ($isMine ? $row['receiver_id'] : $row['sender_id']);
            $partnerType = $isMine ? $row['receiver_type'] : $row['sender_type'];
            $key = $partnerType . ':' . $partnerId;

            if (!isset($conversations[$key])) {
                if (count($conversations) >= $limit) {
                    continue;
                }
                $conversations[$key] = [
                    'partner_id' => $partnerId,
                    'partner_type' => $partnerType,
                    'name' => $isMine ? $row['receiver_name'] : $row['sender_name'],
                    'last_message' => ChatAttachments::preview($row['att_kind'], $row['att_name'], (string) $row['message']),
                    'last_from_me' => $isMine,
                    'timestamp' => $this->isoTime($row['timestamp']),
                    'unread' => 0,
                ];
            }
            if (!$isMine && $row['status'] === 'unread') {
                $conversations[$key]['unread']++;
            }
        }

        // Show current names, and mark people whose accounts are deactivated or deleted
        $statuses = $this->accountStatuses(array_map(fn($c) => [$c['partner_id'], $c['partner_type']], $conversations));
        foreach ($conversations as $key => &$c) {
            $account = $statuses[$key] ?? ['status' => 'active', 'name' => $c['name']];
            $c['account_status'] = $account['status'];
            $c['name'] = $account['name'] ?: $c['name'];
        }
        return array_values($conversations);
    }

    /** Messages with one partner. Pass $afterId to fetch only newer messages. Marks incoming as read. */
    public function conversation(int $partnerId, string $partnerType, int $afterId = 0): array
    {
        $this->assertType($partnerType);

        $stmt = $this->pdo->prepare("
            SELECT id, sender_id, sender_type, receiver_id, receiver_type, message, timestamp, status
            FROM messages
            WHERE id > :after
              AND (
                    (sender_id = :me AND sender_type = :meType AND receiver_id = :them AND receiver_type = :themType)
                 OR (sender_id = :them2 AND sender_type = :themType2 AND receiver_id = :me2 AND receiver_type = :meType2)
              )
            ORDER BY id ASC
            LIMIT 500
        ");
        $stmt->execute([
            ':after' => $afterId,
            ':me' => $this->userId, ':meType' => $this->userType,
            ':them' => $partnerId, ':themType' => $partnerType,
            ':them2' => $partnerId, ':themType2' => $partnerType,
            ':me2' => $this->userId, ':meType2' => $this->userType,
        ]);

        $messages = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $messages[] = [
                'id' => (string) $row['id'],
                'message' => $row['message'],
                'mine' => $this->isMine($row),
                'timestamp' => $this->isoTime($row['timestamp']),
                'status' => $row['status'],
            ];
        }
        $this->withAttachments('dm', $messages);

        $this->markRead($partnerId, $partnerType);
        return $messages;
    }

    /** Ids of my sent messages to this partner that they have read (for "Seen" receipts). */
    public function readReceipts(int $partnerId, string $partnerType): array
    {
        $this->assertType($partnerType);
        $stmt = $this->pdo->prepare("
            SELECT id FROM messages
            WHERE sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ? AND status = 'read'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$this->userId, $this->userType, $partnerId, $partnerType]);
        $id = $stmt->fetchColumn();
        return ['last_read_id' => $id === false ? null : (string) $id];
    }

    /** @param array|null $file one $_FILES entry to attach */
    public function sendMessage(int $partnerId, string $partnerType, string $message, ?array $file = null): array
    {
        $this->assertType($partnerType);
        $message = $this->cleanMessage($message, $file !== null);

        if ($partnerId === $this->userId && $partnerType === $this->userType) {
            throw new ChatException('You cannot message yourself.', 'invalid_receiver');
        }

        $senderName = $this->getUserName($this->userId, $this->userType);
        if ($senderName === null) {
            throw new ChatException('Your account could not be found. Please log in again.', 'sender_not_found');
        }
        $receiverName = $this->getUserName($partnerId, $partnerType);
        if ($receiverName === null) {
            throw new ChatException("This contact's account no longer exists.", 'receiver_not_found');
        }

        $id = $this->insertWithAttachment('dm', $file, function () use ($partnerId, $partnerType, $message, $senderName, $receiverName) {
            $stmt = $this->pdo->prepare("
                INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message, sender_name, receiver_name, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')
            ");
            $stmt->execute([$this->userId, $this->userType, $partnerId, $partnerType, $message, $senderName, $receiverName]);
            return (int) $this->pdo->lastInsertId();
        }, $attachment);

        return [
            'id' => (string) $id,
            'message' => $message,
            'mine' => true,
            'timestamp' => date('c'),
            'status' => 'unread',
            'attachment' => $attachment,
        ];
    }

    /** Delete one of MY messages (and its file). Returns false if it doesn't exist or isn't mine. */
    public function deleteMessage(int $messageId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ? AND sender_type = ?");
        $stmt->execute([$messageId, $this->userId, $this->userType]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        ChatAttachments::deleteFor($this->pdo, 'dm', $messageId);
        return true;
    }

    /** Can I see this attachment? (I'm in the direct conversation, or a member of the group.) */
    public function canAccessAttachment(array $attachment): bool
    {
        if ($attachment['scope'] === 'dm') {
            $stmt = $this->pdo->prepare("SELECT 1 FROM messages WHERE id = ?
                AND ((sender_id = ? AND sender_type = ?) OR (receiver_id = ? AND receiver_type = ?))");
            $stmt->execute([$attachment['message_id'], $this->userId, $this->userType, $this->userId, $this->userType]);
        } else {
            $stmt = $this->pdo->prepare("SELECT 1 FROM group_messages gm
                JOIN group_members m ON m.group_id = gm.group_id AND m.user_id = ? AND m.user_type = ?
                WHERE gm.id = ?");
            $stmt->execute([$this->userId, $this->userType, $attachment['message_id']]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function unreadCount(): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM messages
            WHERE receiver_id = ? AND receiver_type = ? AND status = 'unread'
        ");
        $stmt->execute([$this->userId, $this->userType]);
        return (int) $stmt->fetchColumn();
    }

    private function markRead(int $partnerId, string $partnerType): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE messages SET status = 'read'
            WHERE receiver_id = ? AND receiver_type = ? AND sender_id = ? AND sender_type = ? AND status = 'unread'
        ");
        $stmt->execute([$this->userId, $this->userType, $partnerId, $partnerType]);
    }

    /* ------------------------------------------------------------------
     * Groups
     * ------------------------------------------------------------------ */

    public function groups(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT g.id, g.name, g.description,
                   (SELECT COUNT(*) FROM group_members m2 WHERE m2.group_id = g.id) AS member_count,
                   lm.message AS last_message, lm.created_at AS last_at,
                   lm.sender_id AS last_sender_id, lm.sender_type AS last_sender_type,
                   la.kind AS att_kind, la.original_name AS att_name
            FROM `groups` g
            JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ? AND gm.user_type = ?
            LEFT JOIN group_messages lm ON lm.id = (
                SELECT MAX(id) FROM group_messages WHERE group_id = g.id
            )
            LEFT JOIN chat_attachments la ON la.scope = 'group' AND la.message_id = lm.id
            ORDER BY COALESCE(lm.created_at, g.created_at) DESC
        ");
        $stmt->execute([$this->userId, $this->userType]);

        $groups = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $groups[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'member_count' => (int) $row['member_count'],
                'last_message' => $row['last_message'] !== null
                    ? ChatAttachments::preview($row['att_kind'], $row['att_name'], (string) $row['last_message'])
                    : null,
                'last_from_me' => $row['last_sender_id'] !== null
                    && (int) $row['last_sender_id'] === $this->userId
                    && $row['last_sender_type'] === $this->userType,
                'timestamp' => $row['last_at'] ? $this->isoTime($row['last_at']) : null,
            ];
        }
        return $groups;
    }

    public function groupMessages(int $groupId, int $afterId = 0): array
    {
        $this->assertMember($groupId);

        $stmt = $this->pdo->prepare("
            SELECT gm.id, gm.sender_id, gm.sender_type, gm.message, gm.created_at,
                   CASE gm.sender_type
                       WHEN 'admin' THEN (SELECT fullName FROM admin WHERE id = gm.sender_id)
                       WHEN 'staff' THEN (SELECT fullName FROM staff_accounts WHERE id = gm.sender_id)
                       WHEN 'student' THEN (SELECT CONCAT_WS(' ', first_name, NULLIF(middle_name, ''), last_name)
                                            FROM students WHERE student_id = gm.sender_id)
                   END AS sender_name
            FROM group_messages gm
            WHERE gm.group_id = ? AND gm.id > ?
            ORDER BY gm.id ASC
            LIMIT 500
        ");
        $stmt->execute([$groupId, $afterId]);

        $messages = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $messages[] = [
                'id' => (string) $row['id'],
                'message' => $row['message'],
                'mine' => (int) $row['sender_id'] === $this->userId && $row['sender_type'] === $this->userType,
                'sender_name' => $row['sender_name'] ?: self::DELETED_NAME,
                'sender_type' => $row['sender_type'],
                'timestamp' => $this->isoTime($row['created_at']),
            ];
        }
        $this->withAttachments('group', $messages);
        return $messages;
    }

    public function groupInfo(int $groupId): array
    {
        $this->assertMember($groupId);

        $stmt = $this->pdo->prepare("SELECT id, name, description FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT user_id, user_type FROM group_members WHERE group_id = ? ORDER BY joined_at");
        $stmt->execute([$groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $statuses = $this->accountStatuses(array_map(fn($m) => [(int) $m['user_id'], $m['user_type']], $rows));
        $members = [];
        foreach ($rows as $m) {
            $account = $statuses[$m['user_type'] . ':' . (int) $m['user_id']] ?? ['status' => 'deleted', 'name' => self::DELETED_NAME];
            $members[] = [
                'id' => (int) $m['user_id'],
                'type' => $m['user_type'],
                'name' => $account['name'],
                'account_status' => $account['status'],
                'me' => (int) $m['user_id'] === $this->userId && $m['user_type'] === $this->userType,
            ];
        }

        return [
            'id' => (int) $group['id'],
            'name' => $group['name'],
            'description' => $group['description'],
            'members' => $members,
        ];
    }

    /** @param array|null $file one $_FILES entry to attach */
    public function sendGroupMessage(int $groupId, string $message, ?array $file = null): array
    {
        $this->assertMember($groupId);
        $message = $this->cleanMessage($message, $file !== null);

        $id = $this->insertWithAttachment('group', $file, function () use ($groupId, $message) {
            $stmt = $this->pdo->prepare("
                INSERT INTO group_messages (group_id, sender_id, sender_type, message, status)
                VALUES (?, ?, ?, ?, 'sent')
            ");
            $stmt->execute([$groupId, $this->userId, $this->userType, $message]);
            return (int) $this->pdo->lastInsertId();
        }, $attachment);

        return [
            'id' => (string) $id,
            'message' => $message,
            'mine' => true,
            'sender_name' => $this->getUserName($this->userId, $this->userType),
            'sender_type' => $this->userType,
            'timestamp' => date('c'),
            'attachment' => $attachment,
        ];
    }

    /**
     * @param array $members list of ['id' => int, 'type' => string]
     */
    public function createGroup(string $name, string $description, array $members): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new ChatException('Group name is required (max 100 characters).', 'invalid_name');
        }
        $description = mb_substr(trim($description), 0, 500);

        $valid = [];
        foreach ($members as $m) {
            $id = (int) ($m['id'] ?? 0);
            $type = (string) ($m['type'] ?? '');
            if ($id <= 0 || !self::isValidType($type)) {
                continue;
            }
            if ($id === $this->userId && $type === $this->userType) {
                continue;
            }
            if ($this->getUserName($id, $type) === null) {
                continue;
            }
            $valid[$type . ':' . $id] = [$id, $type];
        }
        if (!$valid) {
            throw new ChatException('Add at least one member to the group.', 'no_members');
        }

        // The PDO connection is shared app-wide; only manage the transaction if nobody else is.
        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `groups` (name, description, created_by_id, created_by_type) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $this->userId, $this->userType]);
            $groupId = (int) $this->pdo->lastInsertId();

            $add = $this->pdo->prepare("INSERT INTO group_members (group_id, user_id, user_type) VALUES (?, ?, ?)");
            $add->execute([$groupId, $this->userId, $this->userType]);
            foreach ($valid as [$id, $type]) {
                $add->execute([$groupId, $id, $type]);
            }

            if ($ownTransaction) {
                $this->pdo->commit();
            }
            return $groupId;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function leaveGroup(int $groupId): void
    {
        $this->assertMember($groupId);
        $stmt = $this->pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND user_type = ?");
        $stmt->execute([$groupId, $this->userId, $this->userType]);
    }

    /* ------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------ */

    private function isMine(array $row): bool
    {
        return (int) $row['sender_id'] === $this->userId && $row['sender_type'] === $this->userType;
    }

    private function assertType(string $type): void
    {
        if (!self::isValidType($type)) {
            throw new ChatException('Unknown user type.', 'invalid_type');
        }
    }

    private function assertMember(int $groupId): void
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? AND user_type = ?");
        $stmt->execute([$groupId, $this->userId, $this->userType]);
        if (!$stmt->fetchColumn()) {
            throw new ChatException('You are not a member of this group.', 'not_member');
        }
    }

    /**
     * Saves the file (if any), runs $insert to save the message, then links the two.
     * If anything fails the saved file is removed again. $attachment gets the public info.
     */
    private function insertWithAttachment(string $scope, ?array $file, callable $insert, ?array &$attachment): int
    {
        $attachment = null;
        if ($file === null) {
            return $insert();
        }
        $meta = ChatAttachments::store($file);
        $ownTransaction = !$this->pdo->inTransaction();
        try {
            if ($ownTransaction) {
                $this->pdo->beginTransaction();
            }
            $id = $insert();
            ChatAttachments::link($this->pdo, $scope, $id, $meta);
            if ($ownTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            ChatAttachments::discard($meta);
            throw $e;
        }
        $attachment = ChatAttachments::forMessages($this->pdo, $scope, [$id])[$id] ?? null;
        return $id;
    }

    /** Adds 'attachment' (or null) to each message row. */
    private function withAttachments(string $scope, array &$messages): void
    {
        $map = ChatAttachments::forMessages($this->pdo, $scope, array_column($messages, 'id'));
        foreach ($messages as &$m) {
            $m['attachment'] = $map[(int) $m['id']] ?? null;
        }
    }

    private function cleanMessage(string $message, bool $hasAttachment = false): string
    {
        $message = trim(str_replace("\r\n", "\n", $message));
        if ($message === '' && $hasAttachment) {
            return '';
        }
        if ($message === '') {
            throw new ChatException('Message cannot be empty.', 'empty_message');
        }
        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new ChatException('Message is too long (max ' . self::MAX_MESSAGE_LENGTH . ' characters).', 'message_too_long');
        }
        return $message;
    }

    /** DB timestamps are stored in Asia/Manila; return ISO-8601 with offset so JS parses them correctly. */
    private function isoTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return (new DateTime($value, new DateTimeZone('Asia/Manila')))->format('c');
        } catch (Exception $e) {
            return null;
        }
    }
}

class ChatException extends RuntimeException
{
    private string $errorCode;

    public function __construct(string $message, string $errorCode = 'chat_error')
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
