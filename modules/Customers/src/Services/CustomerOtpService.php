<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Customers\Contracts\SmsSenderInterface;
use Commerce\Customers\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class CustomerOtpService
{
    public function __construct(
        private readonly SmsSenderInterface $sms,
    ) {}

    public function send(string $identifier): void
    {
        $normalized = $this->normalizeIdentifier($identifier);
        $code = (string) random_int(100000, 999999);

        DB::table('customer_login_otps')
            ->where('identifier', $normalized)
            ->delete();

        DB::table('customer_login_otps')->insert([
            'identifier' => $normalized,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('customers.storefront.otp.ttl_minutes', 10)),
            'created_at' => now(),
        ]);

        $message = __('customers::auth.otp_sms_message', ['code' => $code]);

        if ($this->isPhone($normalized)) {
            $this->sms->send($normalized, $message);

            return;
        }

        Mail::raw($message, function ($mail) use ($normalized): void {
            $mail->to($normalized)->subject(__('customers::auth.otp_mail_subject'));
        });
    }

    public function verify(string $identifier, string $code): bool
    {
        $normalized = $this->normalizeIdentifier($identifier);

        $record = DB::table('customer_login_otps')
            ->where('identifier', $normalized)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($record === null) {
            return false;
        }

        if ($record->attempts >= (int) config('customers.storefront.otp.max_attempts', 5)) {
            return false;
        }

        DB::table('customer_login_otps')
            ->where('id', $record->id)
            ->update(['attempts' => $record->attempts + 1]);

        if (! Hash::check($code, $record->code_hash)) {
            return false;
        }

        DB::table('customer_login_otps')->where('id', $record->id)->delete();

        return true;
    }

    public function findCustomer(string $identifier): ?Customer
    {
        $normalized = $this->normalizeIdentifier($identifier);

        if ($this->isPhone($normalized)) {
            return Customer::query()->where('phone', $normalized)->first();
        }

        return Customer::query()->where('email', $normalized)->first();
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($this->isPhone($identifier)) {
            return preg_replace('/\s+/', '', $identifier) ?? $identifier;
        }

        return Str::lower($identifier);
    }

    private function isPhone(string $identifier): bool
    {
        return (bool) preg_match('/^\+?[0-9]{8,15}$/', preg_replace('/\s+/', '', $identifier) ?? '');
    }
}
