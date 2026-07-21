<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Http\Controllers\Admin;

use Commerce\Webhooks\Contracts\WebhookServiceInterface;
use Commerce\Webhooks\Http\Requests\StoreWebhookRequest;
use Commerce\Webhooks\Http\Requests\UpdateWebhookRequest;
use Commerce\Webhooks\Models\Webhook;
use Commerce\Webhooks\Models\WebhookDelivery;
use Commerce\Webhooks\Services\WebhookDispatcher;
use Commerce\Webhooks\Services\WebhookQueryService;
use Commerce\Webhooks\Support\WebhookFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookQueryService $queryService,
        private readonly WebhookServiceInterface $webhookService,
        private readonly WebhookDispatcher $dispatcher,
    ) {}

    public function index(Request $request): View
    {
        return view('webhooks::admin.index', [
            'webhooks' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
            ),
        ]);
    }

    public function create(): View
    {
        return view('webhooks::admin.create', [
            'availableEvents' => config('webhooks.events', []),
        ]);
    }

    public function store(StoreWebhookRequest $request): RedirectResponse
    {
        $webhook = $this->webhookService->create(WebhookFormData::toCreateData($request->validated()));

        return redirect()
            ->route('admin.webhooks.show', $webhook)
            ->with('status', 'Webhook created. Save the signing secret shown below.');
    }

    public function show(Webhook $webhook): View
    {
        return view('webhooks::admin.show', [
            'webhook' => $webhook,
            'deliveries' => $this->queryService->recentDeliveries($webhook),
        ]);
    }

    public function edit(Webhook $webhook): View
    {
        return view('webhooks::admin.edit', [
            'webhook' => $webhook,
            'availableEvents' => config('webhooks.events', []),
        ]);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): RedirectResponse
    {
        $this->webhookService->update($webhook->uuid, WebhookFormData::toUpdateData($request->validated()));

        return redirect()
            ->route('admin.webhooks.show', $webhook)
            ->with('status', 'Webhook updated.');
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $this->webhookService->delete($webhook->uuid);

        return redirect()
            ->route('admin.webhooks.index')
            ->with('status', 'Webhook deleted.');
    }

    public function retryDelivery(Webhook $webhook, WebhookDelivery $delivery): RedirectResponse
    {
        if ($delivery->webhook_id !== $webhook->id) {
            abort(404);
        }

        $this->dispatcher->retry($delivery);

        return back()->with('status', 'Delivery retried.');
    }
}
