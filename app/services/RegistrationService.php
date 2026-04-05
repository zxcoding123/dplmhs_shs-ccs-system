<?php


require_once __DIR__ . '/../DTO/EmailDTO.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/../Helpers/TemplateHelper.php';

use App\DTO\EmailDTO;

class RegistrationService
{
    private PDO $pdo;
    private MailService $mailService;

    public function __construct(PDO $pdo, MailService $mailService)
    {
        $this->pdo = $pdo;
        $this->mailService = $mailService;
    }

    public function register(array $data): void
    {
        $data = array_map('trim', $data);

        $data['fullName'] = $this->buildFullName(
            $data['firstName'] ?? '',
            $data['middleName'] ?? '',
            $data['lastName'] ?? ''
        );

        $this->validate($data);

        $this->pdo->beginTransaction();

        try {
            $userId = $this->createUser($data);

            if ($data['role'] === 'staff') {
                $this->createStaff($userId, $data);
            } elseif ($data['role'] === 'student') {
                $token = $this->createStudent($userId, $data);
            } else {
                throw new Exception("INVALID_ROLE");
            }

            $this->createNotification($data['role'], $data['fullName']);

            $this->pdo->commit();

            // ✅ pass token here
            $this->sendRegistrationEmail($data, $token);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function validate(array $data): void
    {
        if (empty($data['email']) || empty($data['password'])) {
            throw new Exception("EMPTY_FIELDS");
        }

        if ($data['password'] !== ($data['confirm_password'] ?? '')) {
            throw new Exception("PASSWORD_NOT_SAME");
        }

        // Check duplicate email
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("EMAIL_ALREADY_EXISTS");
        }

        // Student-specific validation
        if ($data['role'] === 'student') {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
            $stmt->execute([$data['student_id']]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception("STUDENT_ID_EXISTS");
            }
        }
    }

    private function createUser(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password, role, status, date_created)
            VALUES (?, ?, ?, 'active', NOW())
        ");

        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function createStaff(int $userId, array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO staff_accounts 
            (user_id, first_name, middle_name, last_name, fullName, department, phone_number, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['firstName'],
            $data['middleName'] ?? '',
            $data['lastName'],
            $data['fullName'],
            $data['department'] ?? '',
            $data['phone_number'] ?? '',
            $data['gender'] ?? ''
        ]);
    }

    private function createStudent(int $userId, array $data)
    {
        $token = bin2hex(random_bytes(25));

        $stmt = $this->pdo->prepare("
            INSERT INTO students (
                user_id, first_name, middle_name, last_name,
                student_id, gender, course, year_level, activation_token
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['firstName'],
            $data['middleName'] ?? '',
            $data['lastName'],
            $data['student_id'],
            $data['gender'],
            $data['course'],
            $data['year_level'],
            $token
        ]);

        return $token;
    }

    private function createNotification(string $role, string $fullName): void
    {
        $titles = [
            'staff' => 'New Staff Account Registration',
            'student' => 'New Student Account Registration'
        ];

        $stmt = $this->pdo->query("SELECT id FROM admin");
        $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $insert = $this->pdo->prepare("
            INSERT INTO admin_notifications 
            (admin_id, type, title, link, description, date, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'unread')
        ");

        foreach ($adminIds as $adminId) {
            $insert->execute([
                $adminId,
                $role,
                $titles[$role],
                '',
                "A new $role account registered: $fullName"
            ]);
        }
    }

    private function sendRegistrationEmail(array $data, ?string $token = null): void
    {
        $body = TemplateHelper::activation(
            $data['fullName'],
            $data['email'],
            $token ?? ''
        );

        $emailDTO = new EmailDTO(
            $data['email'],
            "Activate Your Account",
            $body,
            "CSMS System"
        );

        $sent = $this->mailService->send($emailDTO);

        if (!$sent) {
            error_log("Email failed: " . $data['email']);
        }
    }

    private function buildFullName(string $first, string $middle, string $last): string
    {
        return trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
    }
}
