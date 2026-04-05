<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\DTO\EmailDTO; // ✅ THIS FIXES YOUR ERROR
class MailService
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../Config/mail.php';
    }

    public function send(EmailDTO $emailDTO): bool
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port = $this->config['port'];

            $mail->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );

            $mail->addAddress($emailDTO->to);

            $mail->isHTML(true);
            $mail->Subject = $emailDTO->subject;
            $mail->Body = $emailDTO->body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage()); // logging
            return false;
        }
    }
}
