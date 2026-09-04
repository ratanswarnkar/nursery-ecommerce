<?php

namespace App\Services\Sms;

class NullSmsSender implements SmsSenderInterface
{
    public function send(string $phoneE164, string $message): bool
    {
        return true;
    }
}
