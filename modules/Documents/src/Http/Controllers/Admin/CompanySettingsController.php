<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Controllers\Admin;

use Commerce\Documents\Http\Requests\Admin\UpdateCompanySettingsRequest;
use Commerce\Documents\Services\CompanyProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CompanySettingsController extends Controller
{
    public function __construct(
        private readonly CompanyProfileService $company,
    ) {}

    public function show(): View
    {
        $profile = $this->company->profile();

        return view('documents::admin.settings.company', [
            'profile' => $profile,
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $this->company->update($request->companyValues());

        return redirect()
            ->route('admin.settings.company.show')
            ->with('status', __('documents::admin.company_saved'));
    }
}
