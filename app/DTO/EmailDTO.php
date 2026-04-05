<?php

namespace App\DTO;

class EmailDTO
{
    public string $to;
    public string $subject;
    public string $body;
    public string $fromName; // ← 4th parameter

    public function __construct(
        string $to,
        string $subject,
        string $body,
        string $fromName
    ) {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->fromName = $fromName;
    }
}
