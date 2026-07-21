<?php

declare(strict_types=1);

namespace Commerce\Tax\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Tax\Contracts\TaxRateServiceInterface;
use Commerce\Tax\DTO\UpsertTaxRateData;
use Commerce\Tax\Http\Requests\UpsertTaxRateRequest;
use Commerce\Tax\Models\TaxRate;
use Commerce\Tax\Services\TaxRateQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateQueryService $queryService,
        private readonly TaxRateServiceInterface $rateService,
    ) {}

    public function index(Request $request): View
    {
        return view('tax::admin.index', [
            'rates' => $this->queryService->paginate($request->string('search')->toString() ?: null),
        ]);
    }

    public function create(): View
    {
        return view('tax::admin.create');
    }

    public function store(UpsertTaxRateRequest $request): RedirectResponse
    {
        try {
            $rate = $this->rateService->create($this->toData($request->validated()));
        } catch (DomainException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.tax.edit', $rate)->with('status', 'Tax rate created.');
    }

    public function edit(TaxRate $rate): View
    {
        return view('tax::admin.edit', ['rate' => $rate]);
    }

    public function update(UpsertTaxRateRequest $request, TaxRate $rate): RedirectResponse
    {
        try {
            $this->rateService->update($rate->uuid, $this->toData($request->validated()));
        } catch (DomainException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Tax rate updated.');
    }

    public function destroy(TaxRate $rate): RedirectResponse
    {
        $this->rateService->delete($rate->uuid);

        return redirect()->route('admin.tax.index')->with('status', 'Tax rate deleted.');
    }

    /** @param array<string, mixed> $validated */
    private function toData(array $validated): UpsertTaxRateData
    {
        return new UpsertTaxRateData(
            code: (string) $validated['code'],
            name: (string) $validated['name'],
            rateBps: (int) round(((float) $validated['rate_percent']) * 100),
            countryCode: $validated['country_code'] ?? null,
            isActive: (bool) ($validated['is_active'] ?? true),
            priority: (int) ($validated['priority'] ?? 0),
        );
    }
}
