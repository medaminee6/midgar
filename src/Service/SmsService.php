<?php

namespace App\Service;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        // À remplacer par Twilio / fournisseur tunisien
        file_put_contents(
            __DIR__ . '/../../var/sms.log',
            date('Y-m-d H:i:s') . " $phone : $message\n",
            FILE_APPEND
        );
    }
}
