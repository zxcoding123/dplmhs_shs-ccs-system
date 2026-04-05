<?php
require '../vendor/autoload.php';

require '../app/Services/ActivationService.php';
require '../app/Services/MailService.php';
require '../app/Helpers/TemplateHelper.php';
require '../app/DTO/EmailDTO.php';

$activationService = new ActivationService();

if (isset($_GET['email'], $_GET['name'], $_GET['token'])) {

    $email = $_GET['email'];
    $name = $_GET['name'];
    $token = $_GET['token'];

    $success = $activationService->sendActivation($email, $name, $token);

    header('Location: /index.php?status=' . ($success ? 'sent' : 'error'));
    exit;
}

// fallback
http_response_code(400);
echo "Invalid request";
