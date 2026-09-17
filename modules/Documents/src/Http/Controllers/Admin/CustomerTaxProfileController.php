<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Customers\Models\Customer;
use Commerce\Documents\Http\Requests\Admin\UpdateCustomerTaxProfileRequest;
use Commerce\Documents\Services\CustomerTaxProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CustomerTaxProfileController extends Controller
{
    public function __construct(
        private readonly CustomerTaxProfileService $taxProfiles,
    ) {}

    public function update(UpdateCustomerTaxProfileRequest $request, Customer $customer): RedirectResponse
    {
        try {
            $this->taxProfiles->upsert($customer, $request->buyer());
        } catch (DomainException $exception) {
            return back()->withErrors(['tax_id' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.customers.edit', $customer)
            ->with('status', __('documents::admin.tax_profile_saved'));
    }
}
