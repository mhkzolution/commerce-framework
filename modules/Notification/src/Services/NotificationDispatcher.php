<?php

declare(strict_types=1);

namespace Commerce\Notification\Services;

use Commerce\Contracts\Notification\NotificationDispatcherInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;

final class NotificationDispatcher extends BaseService implements NotificationDispatcherInterface
{
    public function __construct(
        private readonly NotificationTemplateService $templateService,
    ) {}

    public function send(string $templateCode, object $recipient, array $variables = []): void
    {
        $dbTemplate = $this->templateService->findByCode($templateCode);
        $template = $dbTemplate !== null
            ? $this->templateToArray($dbTemplate)
            : $this->templateFromConfig($templateCode);

        if ($template === null) {
            return;
        }

        $email = $recipient->email ?? null;
        if ($email === null || $email === '') {
            return;
        }

        $subject = $this->render($template['subject'], $variables);
        $view = $template['view'];

        if ($view === '') {
            return;
        }

        Mail::send($view, array_merge($variables, ['recipient' => $recipient]), function ($message) use ($email, $subject, $variables): void {
            $message->to($email, $variables['customer_name'] ?? null)->subject($subject);
        });
    }

    /** @return array{subject: string, view: string}|null */
    private function templateFromConfig(string $templateCode): ?array
    {
        $templates = config('notification.templates', []);
        $template = $templates[$templateCode] ?? null;

        if ($template === null) {
            return null;
        }

        return [
            'subject' => (string) $template['subject'],
            'view' => (string) $template['view'],
        ];
    }

    /** @return array{subject: string, view: string} */
    private function templateToArray(NotificationTemplate $template): array
    {
        return [
            'subject' => (string) ($template->subject ?? ''),
            'view' => (string) ($template->view ?? ''),
        ];
    }

    /** @param array<string, mixed> $variables */
    private function render(string $template, array $variables): string
    {
        $rendered = $template;
        foreach ($variables as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $rendered = str_replace('{{'.$key.'}}', (string) $value, $rendered);
            }
        }

        return $rendered;
    }
}
