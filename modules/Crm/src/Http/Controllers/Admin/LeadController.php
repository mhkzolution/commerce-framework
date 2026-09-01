<?php

declare(strict_types=1);

namespace Commerce\Crm\Http\Controllers\Admin;

use Commerce\Crm\Models\Lead;
use Commerce\Crm\Services\DealService;
use Commerce\Crm\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class LeadController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function index(): View
    {
        return view('crm::admin.leads.index', [
            'items' => Lead::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('crm::admin.leads.create', [
            'statuses' => config('crm.lead_statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = $this->leads->create($this->validated($request));

        return redirect()->route('admin.crm.leads.edit', $item)->with('status', 'Lead created.');
    }

    public function edit(Lead $lead): View
    {
        return view('crm::admin.leads.edit', [
            'item' => $lead,
            'statuses' => config('crm.lead_statuses', []),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->leads->update($lead, $this->validated($request));

        return redirect()->route('admin.crm.leads.edit', $lead)->with('status', 'Lead saved.');
    }

    public function qualify(Lead $lead): RedirectResponse
    {
        $this->leads->qualify($lead);

        return redirect()->route('admin.crm.leads.edit', $lead)->with('status', 'Lead qualified.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $deal = app(DealService::class)->createFromLead($lead, $data['title'], (int) $data['amount']);

        return redirect()->route('admin.crm.deals.edit', $deal)->with('status', 'Lead converted to deal.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->leads->delete($lead);

        return redirect()->route('admin.crm.leads.index')->with('status', 'Lead deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(array_keys(config('crm.lead_statuses', [])))],
        ]);
    }
}
