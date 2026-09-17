<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Customers\Models\Customer;
use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Orders\Models\Order;

final class CustomerTaxProfileService extends BaseService
{
    public function forCustomer(Customer $customer): ?CustomerTaxProfile
    {
        return CustomerTaxProfile::query()
            ->where('customer_id', $customer->getKey())
            ->first();
    }

    public function upsert(Customer $customer, BuyerTaxData $buyer): CustomerTaxProfile
    {
        $buyer->assertComplete();

        return CustomerTaxProfile::query()->updateOrCreate(
            ['customer_id' => $customer->getKey()],
            [
                'company_name' => $buyer->companyName,
                'tax_id' => $buyer->taxId,
                'branch_no' => $buyer->branchNo,
                'billing_address' => $buyer->billingAddress,
            ],
        );
    }

    public function prefill(Order $order): BuyerTaxData
    {
        $customer = $this->customerForOrder($order);

        if ($customer instanceof Customer) {
            $profile = $this->forCustomer($customer);

            if ($profile instanceof CustomerTaxProfile) {
                return BuyerTaxData::fromArray([
                    'company_name' => $profile->company_name,
                    'tax_id' => $profile->tax_id,
                    'branch_no' => $profile->branch_no,
                    'billing_address' => is_array($profile->billing_address) ? $profile->billing_address : [],
                ]);
            }
        }

        $address = is_array($order->billing_address) && $order->billing_address !== []
            ? $order->billing_address
            : (is_array($order->shipping_address) ? $order->shipping_address : []);

        return BuyerTaxData::fromArray([
            'company_name' => (string) ($order->customer_name ?: $customer?->name ?: ''),
            'tax_id' => '',
            'branch_no' => CustomerTaxProfile::HEAD_OFFICE_BRANCH,
            'billing_address' => $address,
        ]);
    }

    public function customerForOrder(Order $order): ?Customer
    {
        $uuid = $order->customer_uuid;

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return Customer::query()->where('uuid', $uuid)->first();
    }
}
