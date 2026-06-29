<?php
class AuthService
{
    private PDO $pdo;
    private string $statusSessionKey = 'STATUS';
    private int $maxAttempts = 5;
    private int $lockoutSeconds = 300; // 5 minutes

    // Role redirects (easily maintainable)
    private array $roleRedirects = [
        'admin' => '../../admin/index.php',
        'staff' => '../../staff/index.php',
        'student' => '../../students/student_dashboard.php'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {

            $this->autoLogin();
        }
    }

    /**
     * Login a user
     */
    public function login(string $email, string $password, bool $remember = true): void
    {
        $email = strtolower(trim($email));
        if (empty($email) || empty($password)) {
            $this->fail("EMPTY_FIELDS");
        }

        $this->checkLockout($email);

        $user = $this->findUser($email);

        // Dummy hash for timing attack protection
        $dummyHash = '$2y$10$usesomesillystringforsalt$';

        if (!$user) {
            password_verify($password, $dummyHash); // ⬅️ critical
            $this->recordFailedAttempt($email);
            error_log("Invalid login attempt (non-existent email): " . $email);
            $this->fail("INVALID_CREDENTIALS");
        }


        $this->verifyUser($user, $password);

        session_regenerate_id(true);

        $this->clearFailedAttempts($email);

        $this->setSession($user);

        if ($remember) {
            $this->setRememberMe($user);
        }

        $this->redirectByRole($user['role']);

        exit;
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        header("Location: ../../login/index.php");
        exit;
    }

    /**
     * Find user by email — checks users table first, falls back to admin table
     */
    private function findUser(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$user) {
            $stmt = $this->pdo->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($admin) {
                $admin['role']   = 'admin';
                $admin['status'] = 'active';
                $user = $admin;
            }
        }

        return $user;
    }

    /**
     * Verify password and status
     */
    private function verifyUser(array $user, string $password): void
    {
        if (strtolower(trim($user['status'])) !== 'active') {
            error_log("Active is inactive for user: " . $user['email']);
            $this->fail("INVALID_CREDENTIALS");
        }

        if (!password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($user['email']);
            error_log("Invalid password attempt for user: " . $user['email']);
            $this->fail("INVALID_CREDENTIALS");
        }
    }

    /**
     * Set session variables
     */
    private function setSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['role'];
        $_SESSION['name'] = $user['fullName'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        // Legacy session keys — admin/staff/student pages still check their own key names
        if ($user['role'] === 'admin') {
            $_SESSION['admin_id'] = $user['id'];
        } elseif ($user['role'] === 'staff') {
            $stmt = $this->pdo->prepare("SELECT id FROM staff_accounts WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if ($row) {
                $_SESSION['teacher_id'] = $row['id'];
            }
        } elseif ($user['role'] === 'student') {
            $stmt = $this->pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if ($row) {
                $_SESSION['student_id'] = $row['id'];
            }
        }

        // Update last login
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    }

    /**
     * Redirect based on role
     */
    private function redirectByRole(string $role): void
    {
        if (!isset($this->roleRedirects[$role])) {
            $this->fail("UNKNOWN_ROLE");
        }

        $_SESSION[$this->statusSessionKey] = strtoupper($role) . "_LOGIN_SUCCESSFUL";

        header("Location: " . $this->roleRedirects[$role]);
        exit;
    }

    /**
     * Remember me (set cookie)
     */
    private function setRememberMe(array $user): void
    {
        $token = bin2hex(random_bytes(32)); // raw token (cookie)
        $tokenHash = hash('sha256', $token); // stored in DB

        $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30));

        $stmt = $this->pdo->prepare("
        INSERT INTO remember_tokens (user_id, token_hash, expires_at)
        VALUES (?, ?, ?)
    ");
        $stmt->execute([$user['id'], $tokenHash, $expiresAt]);

        setcookie(
            'remember_me',
            $token,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'secure' => true,      // ✅ HTTPS only
                'httponly' => true,    // ✅ no JS access
                'samesite' => 'Strict' // ✅ CSRF protection
            ]
        );
    }

    /**
     * Check lockout status
     */
    private function checkLockout(string $email): void
    {
        $stmt = $this->pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
        $attempt = $stmt->fetch();

        if ($attempt && $attempt['attempts'] >= $this->maxAttempts) {
            $lastAttempt = strtotime($attempt['last_attempt']);
            if (time() - $lastAttempt < $this->lockoutSeconds) {
                $this->fail("TOO_MANY_ATTEMPTS");
            }
        }
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $email): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO login_attempts (email, attempts, last_attempt) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt->execute([$email]);
    }

    /**
     * Clear failed attempts after successful login
     */
    private function clearFailedAttempts(string $email): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }

    /**
     * Fail helper
     */
    private function fail(string $message): void
    {
        $_SESSION[$this->statusSessionKey] = $message;
        header("Location: ../../login/index.php");
        exit;
    }

    private function autoLogin(): void
    {
        echo "test";
        if (!isset($_COOKIE['remember_me'])) {
            echo "test";
            return;
        }

        $token = $_COOKIE['remember_me'];
        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare("
        SELECT * FROM remember_tokens 
        WHERE token_hash = ? AND expires_at > NOW()
        LIMIT 1
    ");
        $stmt->execute([$tokenHash]);
        $record = $stmt->fetch();

        if (!$record) {
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$record['user_id']]);
        $user = $stmt->fetch();

        if (!$user) return;

        $this->setSession($user);

        // ✅ REDIRECT AFTER AUTO LOGIN
        $this->redirectByRole($user['role']);
        exit;
    }

    private function rotateRememberToken(int $tokenId, int $userId): void
    {
        // delete old token
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE id = ?");
        $stmt->execute([$tokenId]);

        // issue new one
        $this->setRememberMe(['id' => $userId]);
    }
}
