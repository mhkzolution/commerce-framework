<?php

declare(strict_types=1);

namespace Commerce\Shipping\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Shipping\Contracts\ShippingMethodServiceInterface;
use Commerce\Shipping\DTO\CreateShippingMethodData;
use Commerce\Shipping\DTO\UpdateShippingMethodData;
use Commerce\Shipping\Http\Requests\StoreShippingMethodRequest;
use Commerce\Shipping\Http\Requests\UpdateShippingMethodRequest;
use Commerce\Shipping\Models\ShippingMethod;
use Commerce\Shipping\Services\ShippingMethodQueryService;
use Commerce\Shipping\Support\ShippingFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ShippingMethodController extends Controller
{
    public function __construct(
        private readonly ShippingMethodQueryService $queryService,
        private readonly ShippingMethodServiceInterface $methodService,
    ) {}

    public function index(Request $request): View
    {
        return view('shipping::admin.index', [
            'methods' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
            ),
        ]);
    }

    public function create(): View
    {
        return view('shipping::admin.create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        try {
            $method = $this->methodService->create(ShippingFormData::toCreateData($request->validated()));
        } catch (DomainException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.shipping.edit', $method)
            ->with('status', 'Shipping method created.');
    }

    public function edit(ShippingMethod $method): View
    {
        return view('shipping::admin.edit', [
            'method' => $method,
        ]);
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $method): RedirectResponse
    {
        try {
            $this->methodService->update($method->uuid, ShippingFormData::toUpdateData($request->validated()));
        } catch (DomainException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Shipping method updated.');
    }

    public function destroy(ShippingMethod $method): RedirectResponse
    {
        $this->methodService->delete($method->uuid);

        return redirect()
            ->route('admin.shipping.index')
            ->with('status', 'Shipping method deleted.');
    }
}
