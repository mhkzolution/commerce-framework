<?php

declare(strict_types=1);

namespace Commerce\Customers\Services\Sms;

use Commerce\Customers\Contracts\SmsSenderInterface;
use Illuminate\Support\Facades\Log;

final class LogSmsSender implements SmsSenderInterface
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS (log driver)', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
