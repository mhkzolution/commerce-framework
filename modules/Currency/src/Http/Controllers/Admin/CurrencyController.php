<?php

declare(strict_types=1);

namespace Commerce\Currency\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Currency\Contracts\CurrencyServiceInterface;
use Commerce\Currency\Http\Requests\StoreCurrencyRequest;
use Commerce\Currency\Http\Requests\UpdateCurrencyRequest;
use Commerce\Currency\Models\Currency;
use Commerce\Currency\Services\CurrencyQueryService;
use Commerce\Currency\Support\CurrencyFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyQueryService $queryService,
        private readonly CurrencyServiceInterface $currencyService,
    ) {}

    public function index(Request $request): View
    {
        return view('currency::admin.index', [
            'currencies' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
            ),
            'baseCurrency' => $this->queryService->baseCurrency(),
        ]);
    }

    public function create(): View
    {
        return view('currency::admin.create');
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        try {
            $currency = $this->currencyService->create(CurrencyFormData::toCreateData($request->validated()));
        } catch (DomainException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.currencies.edit', $currency)
            ->with('status', 'Currency created.');
    }

    public function edit(Currency $currency): View
    {
        return view('currency::admin.edit', [
            'currency' => $currency,
        ]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        try {
            $this->currencyService->update($currency->uuid, CurrencyFormData::toUpdateData($request->validated()));
        } catch (DomainException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Currency updated.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        try {
            $this->currencyService->delete($currency->uuid);
        } catch (DomainException $exception) {
            return back()->withErrors(['currency' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.currencies.index')
            ->with('status', 'Currency deleted.');
    }
}
