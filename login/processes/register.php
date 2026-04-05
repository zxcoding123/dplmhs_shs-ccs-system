<?php
require_once 'conn.php';
require_once '../../app/services/RegistrationService.php';

require_once '../../app/bootstrap.php';
require_once '../../vendor/autoload.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("INVALID_REQUEST");
    }
    $pdo = Database::getConnection();


    $mailService = new MailService();
    $service = new RegistrationService($pdo, $mailService);

    // You may sanitize here if needed
    $service->register($_POST);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful'
    ]);
} catch (Exception $e) {

    // Map internal error codes to user-friendly messages
    $errorMap = [
        'EMPTY_FIELDS' => 'Please fill in all required fields.',
        'PASSWORD_NOT_SAME' => 'Passwords do not match.',
        'EMAIL_ALREADY_EXISTS' => 'Email is already registered.',
        'EMAIL_NOT_ADDU' => 'Use your ADDU email.',
        'NOT_SAME_ID' => 'Student ID does not match email.',
        'STUDENT_ID_EXISTS' => 'Student ID already exists.',
        'INVALID_ROLE' => 'Invalid role selected.',
        'INVALID_REQUEST' => 'Invalid request method.'
    ];

    $message = $errorMap[$e->getMessage()] ?? 'Something went wrong.';

    // echo json_encode([
    //     'success' => false,
    //     'message' => $message
    // ]);

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}
