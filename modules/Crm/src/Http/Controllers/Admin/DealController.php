<?php

declare(strict_types=1);

namespace Commerce\Crm\Http\Controllers\Admin;

use Commerce\Crm\Models\Deal;
use Commerce\Crm\Models\Lead;
use Commerce\Crm\Services\DealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class DealController extends Controller
{
    public function __construct(private readonly DealService $deals) {}

    public function index(): View
    {
        return view('crm::admin.deals.index', [
            'items' => Deal::query()->with('lead')->latest()->paginate(25),
            'stages' => config('crm.deal_stages', []),
        ]);
    }

    public function board(): View
    {
        return view('crm::admin.deals.board', [
            'stages' => config('crm.deal_stages', []),
            'dealsByStage' => $this->deals->dealsByStage(),
        ]);
    }

    public function updateStage(Request $request, Deal $deal): RedirectResponse
    {
        $stage = $request->validate([
            'stage' => ['required', 'string', Rule::in(array_keys(config('crm.deal_stages', [])))],
        ])['stage'];

        $this->deals->moveStage($deal, $stage);

        return redirect()->route('admin.crm.deals.board')->with('status', 'Deal stage updated.');
    }

    public function create(): View
    {
        return view('crm::admin.deals.create', [
            'leads' => Lead::query()->orderBy('name')->get(),
            'stages' => config('crm.deal_stages', []),
            'statuses' => config('crm.deal_statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = $this->deals->create($this->validated($request));

        return redirect()->route('admin.crm.deals.edit', $item)->with('status', 'Deal created.');
    }

    public function edit(Deal $deal): View
    {
        return view('crm::admin.deals.edit', [
            'item' => $deal,
            'leads' => Lead::query()->orderBy('name')->get(),
            'stages' => config('crm.deal_stages', []),
            'statuses' => config('crm.deal_statuses', []),
        ]);
    }

    public function update(Request $request, Deal $deal): RedirectResponse
    {
        $this->deals->update($deal, $this->validated($request));

        return redirect()->route('admin.crm.deals.edit', $deal)->with('status', 'Deal saved.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $this->deals->delete($deal);

        return redirect()->route('admin.crm.deals.index')->with('status', 'Deal deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'lead_id' => ['nullable', 'integer', 'exists:crm_leads,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'stage' => ['nullable', 'string', Rule::in(array_keys(config('crm.deal_stages', [])))],
            'status' => ['required', 'string', Rule::in(array_keys(config('crm.deal_statuses', [])))],
        ]);
    }
}
