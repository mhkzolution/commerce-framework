<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Services\PosRegisterResolver;
use Commerce\Pos\Services\PosSessionService;
use Commerce\Pos\Services\PosStateService;
use Commerce\Pos\Support\PosSessionStateFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class PosController extends Controller
{
    public function __construct(
        private readonly PosRegisterResolver $registerResolver,
        private readonly PosSessionService $sessionService,
        private readonly PosStateService $stateService,
        private readonly PosSessionStateFactory $sessionStateFactory,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $register = $this->registerResolver->resolve($request);
        } catch (DomainException $exception) {
            return view('pos::pos.no-register', [
                'message' => $exception->getMessage(),
            ]);
        }

        $session = $this->sessionService->activeSession($register);
        $registers = $this->activeRegisters();

        if ($session === null) {
            return view('pos::pos.open', [
                'register' => $register,
                'registers' => $registers,
            ]);
        }

        $state = $this->sessionStateFactory->make($register->uuid);
        $posState = $this->stateService->build($register, $session, $state);

        return view('pos::pos.index', [
            'register' => $register,
            'session' => $session,
            'posState' => $posState,
            'apiRoutes' => $this->apiRoutes(),
            'registers' => $registers,
        ]);
    }

    public function openSession(Request $request): RedirectResponse
    {
        $register = $this->registerResolver->resolve($request);

        $data = $request->validate([
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->sessionService->openSession(
            register: $register,
            openedBy: (string) auth()->user()?->name,
            openingBalance: (int) round(((float) ($data['opening_balance'] ?? 0)) * 100),
            openedByUserId: auth()->id(),
        );

        return redirect()
            ->route('pos.index')
            ->with('status', 'เปิดกะเรียบร้อยแล้ว');
    }

    /** @return array<string, string> */
    private function apiRoutes(): array
    {
        return [
            'state' => route('pos.api.state'),
            'search' => route('pos.api.search'),
            'addItem' => route('pos.api.cart.items.store'),
            'updateItem' => route('pos.api.cart.items.update', ['purchasable' => '__UUID__']),
            'removeItem' => route('pos.api.cart.items.destroy', ['purchasable' => '__UUID__']),
            'clearCart' => route('pos.api.cart.clear'),
            'attachCustomer' => route('pos.api.customer.attach'),
            'searchCustomers' => route('pos.api.customers.search'),
            'updateNotes' => route('pos.api.notes'),
            'updatePaymentMethod' => route('pos.api.payment-method'),
            'updateMixedPayments' => route('pos.api.payments'),
            'applyCoupon' => route('pos.api.coupon.apply'),
            'removeCoupon' => route('pos.api.coupon.remove'),
            'setLinePrice' => route('pos.api.cart.items.price', ['purchasable' => '__UUID__']),
            'sync' => route('pos.api.sync'),
            'receipt' => route('pos.api.receipt', ['orderUuid' => '__ORDER__']),
            'printReceipt' => route('pos.receipt.show', ['orderUuid' => '__ORDER__']),
            'hold' => route('pos.api.hold'),
            'resume' => route('pos.api.holds.resume', ['holdId' => '__ID__']),
            'checkout' => route('pos.api.checkout'),
        ];
    }

    /**
     * @return Collection<int, Register>
     */
    private function activeRegisters(): Collection
    {
        return Register::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
}
