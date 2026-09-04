<?php

namespace App\Services\Sms;

interface SmsSenderInterface
{
    /**
     * Send an SMS message to a normalized E.164 phone number.
     *
     * @param  string  $phoneE164  Target phone number in E.164 format.
     * @param  string  $message  Text content of the SMS.
     * @return bool True if successfully dispatched, false otherwise.
     */
    public function send(string $phoneE164, string $message): bool;
}
