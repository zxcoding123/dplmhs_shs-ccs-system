<?php

use App\DTO\EmailDTO;

class ActivationService
{
    private $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    public function sendActivation($email, $name, $token): bool
    {
        $body = TemplateHelper::activation($name, $email, $token);

        $emailDTO = new EmailDTO(
            $email,
            $name,
            'Activate your account',
            $body
        );

        return $this->mailService->send($emailDTO);
    }
}
