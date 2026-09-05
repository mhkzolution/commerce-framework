<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Customers\Http\Controllers\Storefront\ThailandLocationController;
use Commerce\Customers\Http\Requests\StoreAddressRequest;
use Commerce\Customers\Http\Requests\StoreCustomerRequest;
use Commerce\Customers\Http\Resources\CustomerAddressResource;
use Commerce\Customers\Http\Resources\CustomerResource;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Services\CustomerQueryService;
use Commerce\Orders\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::post('/customers', function (StoreCustomerRequest $request, CustomerServiceInterface $customers) {
        try {
            $customer = $customers->create(new CreateCustomerData(
                email: $request->validated('email'),
                name: $request->validated('name'),
                phone: $request->validated('phone'),
                status: $request->validated('status'),
            ));

            return ApiResponse::success(new CustomerResource($customer), status: 201);
        } catch (DomainException $exception) {
            return ApiResponse::error('customer.invalid', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.customers.store');

    Route::get('/customers/{uuid}', function (CustomerQueryService $customers, string $uuid) {
        $customer = $customers->findByUuid($uuid);

        if ($customer === null) {
            return ApiResponse::error('customer.not_found', 'Customer not found.', status: 404);
        }

        return ApiResponse::success(new CustomerResource($customer));
    })->name('api.v1.customers.show');

    Route::get('/customers/{uuid}/orders', function (CustomerQueryService $customers, string $uuid) {
        $customer = $customers->findByUuid($uuid);

        if ($customer === null) {
            return ApiResponse::error('customer.not_found', 'Customer not found.', status: 404);
        }

        if (! app()->bound(OrderQueryServiceInterface::class)) {
            return ApiResponse::error('orders.unavailable', 'Orders module is not available.', status: 503);
        }

        $orders = app(OrderQueryServiceInterface::class)->paginateForCustomer($uuid);

        return ApiResponse::success(
            OrderResource::collection($orders->items()),
            meta: [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        );
    })->name('api.v1.customers.orders');

    Route::get('/customers/{uuid}/addresses', function (CustomerQueryService $customers, CustomerAddressQueryService $addresses, string $uuid) {
        $customer = $customers->findByUuid($uuid);

        if ($customer === null) {
            return ApiResponse::error('customer.not_found', 'Customer not found.', status: 404);
        }

        return ApiResponse::success(
            CustomerAddressResource::collection($addresses->forCustomer($uuid)),
        );
    })->name('api.v1.customers.addresses.index');

    Route::post('/customers/{uuid}/addresses', function (
        StoreAddressRequest $request,
        CustomerQueryService $customers,
        CustomerAddressServiceInterface $addressService,
        string $uuid,
    ) {
        $customer = $customers->findByUuid($uuid);

        if ($customer === null) {
            return ApiResponse::error('customer.not_found', 'Customer not found.', status: 404);
        }

        try {
            $address = $addressService->create(new CreateAddressData(
                customerUuid: $uuid,
                line1: $request->validated('line1'),
                city: $request->validated('city'),
                postalCode: $request->validated('postal_code'),
                countryCode: $request->validated('country_code'),
                type: $request->validated('type'),
                label: $request->validated('label'),
                line2: $request->validated('line2'),
                state: $request->validated('state'),
                isDefault: (bool) $request->boolean('is_default'),
            ));

            return ApiResponse::success(new CustomerAddressResource($address), status: 201);
        } catch (DomainException $exception) {
            return ApiResponse::error('address.invalid', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.customers.addresses.store');
});

Route::prefix('api/v1/storefront/locations/thailand')->middleware(['api', 'web'])->name('api.v1.storefront.locations.thailand.')->group(function (): void {
    Route::get('/provinces', [ThailandLocationController::class, 'provinces'])->name('provinces');
    Route::get('/districts/{province}', [ThailandLocationController::class, 'districts'])->name('districts');
    Route::get('/subdistricts/{district}', [ThailandLocationController::class, 'subdistricts'])->name('subdistricts');
});
