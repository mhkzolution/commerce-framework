<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers\Admin;

use Commerce\Cart\DTO\CartLineData;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Services\PosSaleService;
use Commerce\Pos\Services\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TerminalController extends Controller
{
    public function __construct(
        private readonly PosSaleService $saleService,
        private readonly PosSessionService $sessionService,
        private readonly ProductQueryServiceInterface $productQueryService,
    ) {}

    public function show(Register $register): View
    {
        abort_if(! $register->is_active, 404);

        $session = $this->sessionService->openSession(
            $register,
            (string) auth()->user()?->name,
        );

        $cart = $this->saleService->cart($register)->get();

        return view('pos::admin.terminal.index', [
            'register' => $register,
            'session' => $session,
            'cart' => $cart,
        ]);
    }

    public function search(Register $register, Request $request): JsonResponse
    {
        $variants = $this->saleService->searchProducts($request->string('q')->toString());

        return response()->json([
            'results' => collect($variants)->map(static fn ($variant): array => [
                'uuid' => $variant->uuid,
                'sku' => $variant->sku,
                'name' => $variant->name ?: $variant->product?->name,
                'price' => $variant->price,
                'currency' => config('cart.default_currency', 'USD'),
            ])->values(),
        ]);
    }

    public function addItem(Register $register, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchasable_uuid' => ['nullable', 'uuid'],
            'sku' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $variant = null;

        if (! empty($data['purchasable_uuid'])) {
            $variant = $this->productQueryService->findVariantByUuid($data['purchasable_uuid']);
        }

        if ($variant === null && ! empty($data['sku'])) {
            $variant = $this->saleService->findVariantBySku($data['sku']);
        }

        if ($variant === null) {
            return back()->withErrors(['terminal' => 'Product not found.']);
        }

        try {
            $this->saleService->cart($register)->add(new CartLineData(
                purchasableUuid: $variant->uuid,
                quantity: (int) ($data['quantity'] ?? 1),
            ));
        } catch (DomainException $exception) {
            return back()->withErrors(['terminal' => $exception->getMessage()]);
        }

        return back()->with('status', 'Item added.');
    }

    public function updateItem(Register $register, string $purchasable, Request $request): RedirectResponse
    {
        $quantity = (int) $request->validate(['quantity' => ['required', 'integer', 'min:0']])['quantity'];

        try {
            $this->saleService->cart($register)->update($purchasable, $quantity);
        } catch (DomainException $exception) {
            return back()->withErrors(['terminal' => $exception->getMessage()]);
        }

        return back();
    }

    public function removeItem(Register $register, string $purchasable): RedirectResponse
    {
        $this->saleService->cart($register)->remove($purchasable);

        return back();
    }

    public function complete(Register $register, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);

        $session = $this->sessionService->activeSession($register);

        try {
            $order = $this->saleService->completeSale(
                register: $register,
                session: $session,
                customerName: $data['customer_name'] ?? null,
                customerEmail: $data['customer_email'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['terminal' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.pos.terminal.show', $register)
            ->with('status', "Sale completed: {$order->order_number}");
    }

    public function closeSession(Register $register): RedirectResponse
    {
        $session = $this->sessionService->activeSession($register);

        if ($session !== null) {
            $this->sessionService->closeSession($session);
            $this->saleService->cart($register)->clear();
        }

        return redirect()
            ->route('admin.pos.registers.index')
            ->with('status', 'Session closed.');
    }
}
