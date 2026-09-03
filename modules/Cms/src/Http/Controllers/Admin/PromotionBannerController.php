<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\UpsertPromotionBannerData;
use Commerce\Cms\Http\Requests\UpsertPromotionBannerRequest;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Cms\Services\PromotionBannerService;
use Commerce\Cms\Support\CmsMediaThumbnails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PromotionBannerController extends Controller
{
    use RedirectsAfterSave;

    public function __construct(
        private readonly PromotionBannerService $banners,
        private readonly CmsMediaThumbnails $thumbnails,
    ) {}

    public function index(): View
    {
        $items = PromotionBanner::query()->orderBy('sort_order')->orderByDesc('id')->paginate(25);

        return view('cms::admin.promotion-banners.index', [
            'items' => $items,
            'thumbnails' => $this->thumbnails->urls($items),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.promotion-banners.create');
    }

    public function store(UpsertPromotionBannerRequest $request): RedirectResponse
    {
        $item = $this->banners->create($this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.promotion-banners.index',
            route('admin.cms.promotion-banners.edit', $item),
            'Promotion banner created.',
        );
    }

    public function edit(PromotionBanner $promotionBanner): View
    {
        return view('cms::admin.promotion-banners.edit', ['item' => $promotionBanner]);
    }

    public function update(UpsertPromotionBannerRequest $request, PromotionBanner $promotionBanner): RedirectResponse
    {
        $this->banners->update($promotionBanner, $this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.promotion-banners.index',
            route('admin.cms.promotion-banners.edit', $promotionBanner),
            'Promotion banner saved.',
        );
    }

    public function destroy(PromotionBanner $promotionBanner): RedirectResponse
    {
        $this->banners->delete($promotionBanner);

        return redirect()->route('admin.cms.promotion-banners.index')->with('status', 'Promotion banner deleted.');
    }

    private function toData(UpsertPromotionBannerRequest $request): UpsertPromotionBannerData
    {
        $url = $request->validated('url');

        return new UpsertPromotionBannerData(
            title: $request->validated('title'),
            imageMediaUuid: $request->validated('image_media_uuid'),
            url: is_string($url) && $url !== '' ? $url : null,
            openInNewTab: $request->boolean('open_in_new_tab'),
            sortOrder: (int) ($request->validated('sort_order') ?? 0),
            isActive: $request->boolean('is_active'),
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
        );
    }
}
