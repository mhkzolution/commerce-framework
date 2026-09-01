<?php

declare(strict_types=1);

namespace Commerce\Customers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomerResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('storefront.account.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('customers::auth.reset_password_mail_subject'))
            ->line(__('customers::auth.reset_password_mail_intro'))
            ->action(__('customers::auth.reset_password_mail_action'), $url)
            ->line(__('customers::auth.reset_password_mail_expire', ['count' => config('auth.passwords.customers.expire')]))
            ->line(__('customers::auth.reset_password_mail_ignore'));
    }
}
