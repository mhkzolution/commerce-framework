<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateMailSettingsRequest;
use Commerce\Settings\Support\MailConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class MailSettingsController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $settings = $this->settingQueryService->getGroup('mail');

        return view('settings::admin.mail.index', [
            'mailer' => (string) ($settings['mailer'] ?? config('mail.default', 'log')),
            'host' => (string) ($settings['host'] ?? ''),
            'port' => (string) ($settings['port'] ?? '587'),
            'username' => (string) ($settings['username'] ?? ''),
            'password' => (string) ($settings['password'] ?? ''),
            'encryption' => (string) ($settings['encryption'] ?? 'tls'),
            'fromAddress' => (string) ($settings['from_address'] ?? config('mail.from.address', '')),
            'fromName' => (string) ($settings['from_name'] ?? config('mail.from.name', '')),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $values = [
            'mailer' => $validated['mailer'],
            'host' => $validated['host'] ?? '',
            'port' => $validated['port'] ?? '',
            'username' => $validated['username'] ?? '',
            'encryption' => $validated['encryption'] ?? '',
            'from_address' => $validated['from_address'] ?? '',
            'from_name' => $validated['from_name'] ?? '',
        ];

        if (($validated['password'] ?? '') !== '') {
            $values['password'] = $validated['password'];
        } else {
            $values['password'] = (string) ($this->settingQueryService->getGroup('mail')['password'] ?? '');
        }

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'mail',
            values: $values,
        ));

        MailConfigurator::apply();

        return redirect()
            ->route('admin.settings.mail.show')
            ->with('status', __('settings::admin.mail_saved'));
    }
}
