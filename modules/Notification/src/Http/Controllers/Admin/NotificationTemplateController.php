<?php

declare(strict_types=1);

namespace Commerce\Notification\Http\Controllers\Admin;

use Commerce\Notification\Http\Requests\Admin\UpdateNotificationTemplateRequest;
use Commerce\Notification\Models\NotificationTemplate;
use Commerce\Notification\Services\NotificationTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class NotificationTemplateController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateService $templateService,
    ) {}

    public function index(): View
    {
        return view('notification::admin.templates.index', [
            'templates' => $this->templateService->all(),
        ]);
    }

    public function edit(NotificationTemplate $template): View
    {
        return view('notification::admin.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $template): RedirectResponse
    {
        $template->update([
            'name' => $request->validated('name'),
            'subject' => $request->validated('subject'),
            'view' => $request->validated('view'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.notification.templates.edit', $template)
            ->with('status', __('notification::admin.saved'));
    }
}
