<?php

declare(strict_types=1);

namespace Commerce\Customers\Services\Sms;

use Commerce\Customers\Contracts\SmsSenderInterface;
use Illuminate\Support\Facades\Http;

final class HttpSmsSender implements SmsSenderInterface
{
    public function send(string $to, string $message): void
    {
        $url = (string) config('customers.storefront.sms.http_url', '');
        $apiKey = (string) config('customers.storefront.sms.api_key', '');

        if ($url === '') {
            throw new \RuntimeException('SMS HTTP URL is not configured.');
        }

        $response = Http::timeout(10)
            ->withHeaders(array_filter([
                'Authorization' => $apiKey !== '' ? 'Bearer '.$apiKey : null,
            ]))
            ->post($url, [
                'to' => $to,
                'message' => $message,
                'sender' => config('customers.storefront.sms.sender'),
            ]);

        $response->throw();
    }
}
