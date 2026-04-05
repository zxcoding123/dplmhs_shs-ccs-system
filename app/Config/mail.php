<?php
return [
    'host' => $_ENV['MAIL_HOST'],
    'port' => $_ENV['MAIL_PORT'],
    'username' => $_ENV['MAIL_USERNAME'],
    'password' => $_ENV['MAIL_PASSWORD'],
    'encryption' => $_ENV['MAIL_ENCRYPTION'],
    'from_email' => $_ENV['MAIL_FROM_EMAIL'],
    'from_name' => $_ENV['MAIL_FROM_NAME'],
];
