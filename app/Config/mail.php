<?php
// Values come from .env. Defaults keep a missing setting from printing PHP warnings,
// which would corrupt JSON responses such as login/processes/register.php.
return [
    'host' => $_ENV['MAIL_HOST'] ?? '',
    'port' => $_ENV['MAIL_PORT'] ?? 587,
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? '',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'DPLMHS - Stand-Alone Senior High School',
];
