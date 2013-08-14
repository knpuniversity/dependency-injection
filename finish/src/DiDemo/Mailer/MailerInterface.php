<?php

namespace DiDemo\Mailer;

interface MailerInterface
{
    public function sendMessage($recipientEmail, $subject, $message, $from);
}