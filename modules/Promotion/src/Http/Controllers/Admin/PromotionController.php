<?php

declare(strict_types=1);

namespace Commerce\Promotion\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Promotion\Contracts\PromotionCrudServiceInterface;
use Commerce\Promotion\DTO\UpsertPromotionData;
use Commerce\Promotion\Http\Requests\UpsertPromotionRequest;
use Commerce\Promotion\Models\Promotion;
use Commerce\Promotion\Services\PromotionQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionQueryService $queryService,
        private readonly PromotionCrudServiceInterface $promotionService,
    ) {}

    public function index(Request $request): View
    {
        return view('promotion::admin.index', [
            'promotions' => $this->queryService->paginate($request->string('search')->toString() ?: null),
        ]);
    }

    public function create(): View { return view('promotion::admin.create'); }

    public function store(UpsertPromotionRequest $request): RedirectResponse
    {
        try {
            $promotion = $this->promotionService->create($this->toData($request->validated()));
        } catch (DomainException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.promotions.edit', $promotion)->with('status', 'Promotion created.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('promotion::admin.edit', ['promotion' => $promotion]);
    }

    public function update(UpsertPromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        try {
            $this->promotionService->update($promotion->uuid, $this->toData($request->validated()));
        } catch (DomainException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->promotionService->delete($promotion->uuid);

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion deleted.');
    }

    /** @param array<string, mixed> $validated */
    private function toData(array $validated): UpsertPromotionData
    {
        $type = (string) $validated['type'];
        $value = $type === Promotion::TYPE_PERCENTAGE
            ? (int) round(((float) $validated['value']) * 100)
            : (int) round(((float) $validated['value']) * 100);

        return new UpsertPromotionData(
            code: (string) $validated['code'],
            name: (string) $validated['name'],
            type: $type,
            value: $value,
            minSubtotal: isset($validated['min_subtotal']) ? (int) round(((float) $validated['min_subtotal']) * 100) : null,
            maxUses: isset($validated['max_uses']) ? (int) $validated['max_uses'] : null,
            startsAt: isset($validated['starts_at']) ? new \DateTimeImmutable((string) $validated['starts_at']) : null,
            endsAt: isset($validated['ends_at']) ? new \DateTimeImmutable((string) $validated['ends_at']) : null,
            isActive: (bool) ($validated['is_active'] ?? true),
        );
    }
}
