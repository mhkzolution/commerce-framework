<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\UpsertHeroBannerData;
use Commerce\Cms\Http\Requests\UpsertHeroBannerRequest;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Services\HeroBannerService;
use Commerce\Cms\Support\CmsMediaThumbnails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class HeroBannerController extends Controller
{
    use RedirectsAfterSave;

    public function __construct(
        private readonly HeroBannerService $banners,
        private readonly CmsMediaThumbnails $thumbnails,
    ) {}

    public function index(): View
    {
        $items = HeroBanner::query()->orderBy('sort_order')->orderByDesc('id')->paginate(25);

        return view('cms::admin.hero-banners.index', [
            'items' => $items,
            'thumbnails' => $this->thumbnails->urls($items),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.hero-banners.create');
    }

    public function store(UpsertHeroBannerRequest $request): RedirectResponse
    {
        $item = $this->banners->create($this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.hero-banners.index',
            route('admin.cms.hero-banners.edit', $item),
            'Hero banner created.',
        );
    }

    public function edit(HeroBanner $heroBanner): View
    {
        return view('cms::admin.hero-banners.edit', ['item' => $heroBanner]);
    }

    public function update(UpsertHeroBannerRequest $request, HeroBanner $heroBanner): RedirectResponse
    {
        $this->banners->update($heroBanner, $this->toData($request));

        return $this->redirectAfterSave(
            $request,
            'admin.cms.hero-banners.index',
            route('admin.cms.hero-banners.edit', $heroBanner),
            'Hero banner saved.',
        );
    }

    public function destroy(HeroBanner $heroBanner): RedirectResponse
    {
        $this->banners->delete($heroBanner);

        return redirect()->route('admin.cms.hero-banners.index')->with('status', 'Hero banner deleted.');
    }

    private function toData(UpsertHeroBannerRequest $request): UpsertHeroBannerData
    {
        $mobile = $request->validated('mobile_image_media_uuid');
        $video = $request->validated('video_media_uuid');
        $mobileVideo = $request->validated('mobile_video_media_uuid');

        return new UpsertHeroBannerData(
            imageMediaUuid: $request->validated('image_media_uuid'),
            mobileImageMediaUuid: is_string($mobile) && $mobile !== '' ? $mobile : null,
            type: $request->validated('type'),
            videoMediaUuid: is_string($video) && $video !== '' ? $video : null,
            mobileVideoMediaUuid: is_string($mobileVideo) && $mobileVideo !== '' ? $mobileVideo : null,
            sortOrder: (int) ($request->validated('sort_order') ?? 0),
            isActive: $request->boolean('is_active'),
            startsAt: $request->validated('starts_at'),
            endsAt: $request->validated('ends_at'),
        );
    }
}
