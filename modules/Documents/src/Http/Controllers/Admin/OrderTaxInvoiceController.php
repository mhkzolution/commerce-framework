<?php

declare(strict_types=1);

namespace Commerce\Documents\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Http\Requests\Admin\IssueTaxInvoiceRequest;
use Commerce\Documents\Services\TaxInvoiceIssueService;
use Commerce\Orders\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class OrderTaxInvoiceController extends Controller
{
    public function __construct(
        private readonly TaxInvoiceIssueService $issuer,
    ) {}

    public function store(IssueTaxInvoiceRequest $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        $createdBy = is_object($user) && isset($user->id) ? (int) $user->id : null;

        try {
            $document = $this->issuer->issue($order, $request->buyer(), $createdBy);
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('documents::admin.tax_invoice_issued', ['number' => $document->number]));
    }
}
