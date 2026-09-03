<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Cms\Models\Page;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterPageData;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Http\Requests\PreviewFooterRequest;
use Commerce\Settings\Http\Requests\UpdateFooterRequest;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class FooterController extends Controller
{
    public function __construct(
        private readonly FooterConfigService $footerConfig,
        private readonly SettingServiceInterface $settingService,
        private readonly FooterViewModelBuilder $viewModelBuilder,
        private readonly FooterSectionRegistry $sectionRegistry,
    ) {}

    public function show(): View
    {
        $this->footerConfig->ensureRegistered();

        return view('settings::admin.footer.index', [
            'config' => $this->footerConfig->resolve(),
            'preview' => $this->footerConfig->previewCatalog(),
            'editor' => $this->editorPayload(),
        ]);
    }

    public function update(UpdateFooterRequest $request): RedirectResponse
    {
        $this->footerConfig->ensureRegistered();

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'footer',
            values: [
                'config' => $this->footerConfig->merge($request->configPayload()),
            ],
        ));

        $this->footerConfig->forgetResolved();

        return redirect()
            ->route('admin.settings.footer.show')
            ->with('status', 'Footer settings saved.');
    }

    public function preview(PreviewFooterRequest $request): JsonResponse
    {
        $config = $this->footerConfig->merge($request->configPayload());
        $footer = $this->viewModelBuilder->build($config, new FooterBuildContext(device: null));
        $visibleSectionIds = [];

        foreach ($footer->sections as $section) {
            $visibleSectionIds[$section->id] = true;
        }

        return response()->json([
            'html' => $this->renderFooterHtml($footer),
            'meta' => [
                'total_sections' => count($config['sections'] ?? []),
                'visible_sections' => count($visibleSectionIds),
                'hidden_sections' => max(0, count($config['sections'] ?? []) - count($visibleSectionIds)),
                'hidden_reasons' => $this->hiddenReasons($config, $visibleSectionIds),
            ],
        ]);
    }

    private function renderFooterHtml(FooterPageData $footer): string
    {
        return trim(view('components.storefront.layout.partials.site-footer', [
            'footer' => $footer,
        ])->render());
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, bool>  $visibleSectionIds
     * @return list<array{section_id: string, reason: string}>
     */
    private function hiddenReasons(array $config, array $visibleSectionIds): array
    {
        $hidden = [];

        foreach ($config['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionId = is_string($section['id'] ?? null) ? $section['id'] : null;

            if ($sectionId === null || isset($visibleSectionIds[$sectionId])) {
                continue;
            }

            $hidden[] = [
                'section_id' => $sectionId,
                'reason' => $this->inferHiddenReason($section),
            ];
        }

        return $hidden;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function inferHiddenReason(array $section): string
    {
        if (($section['enabled'] ?? true) !== true) {
            return 'disabled';
        }

        $type = is_string($section['type'] ?? null) ? $section['type'] : '';
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];

        return match ($type) {
            'brand' => (($settings['show_logo'] ?? true) === false
                && ($settings['show_store_name'] ?? true) === false
                && ($settings['show_description'] ?? true) === false)
                ? 'brand_content_disabled'
                : 'brand_content_unavailable',
            'navigation' => 'empty_navigation_links',
            'cms' => (is_array($settings['page_ids'] ?? null) && $settings['page_ids'] === [])
                ? 'empty_cms_selection'
                : 'cms_pages_unavailable',
            'social' => 'empty_social_links',
            'marketplace' => 'marketplace_unavailable',
            'copyright' => 'empty_copyright_text',
            'powered_by' => 'plan_restricted',
            default => 'not_rendered',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function editorPayload(): array
    {
        return [
            'templates' => array_values(array_map(
                fn (array $template): array => [
                    'type' => $template['type'],
                    'template_id' => $template['template_id'],
                    'supports_multiple' => (bool) $template['supports_multiple'],
                    'default_settings' => $template['default_settings'],
                    'label_key' => $template['label_key'],
                    'label' => $this->labelForTemplate($template),
                ],
                $this->sectionRegistry->templates(),
            )),
            'sources' => [
                'navigation' => $this->navigationSources(),
                'cms_pages' => $this->cmsPages(),
                'social' => $this->socialSources(),
            ],
            'routes' => [
                'preview' => route('admin.settings.footer.preview'),
                'site_identity' => Route::has('admin.settings.site-identity.show')
                    ? route('admin.settings.site-identity.show')
                    : null,
                'navigation' => Route::has('admin.storefront.navigation.show')
                    ? route('admin.storefront.navigation.show')
                    : null,
                'cms_pages' => Route::has('admin.cms.pages.index')
                    ? route('admin.cms.pages.index')
                    : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $template
     */
    private function labelForTemplate(array $template): string
    {
        $labelKey = is_string($template['label_key'] ?? null) ? $template['label_key'] : '';
        $translated = $labelKey !== '' ? __($labelKey) : null;

        if (is_string($translated) && $translated !== '' && $translated !== $labelKey) {
            return $translated;
        }

        return Str::headline((string) ($template['type'] ?? 'Section'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function navigationSources(): array
    {
        return [[
            'value' => 'main',
            'label' => 'Main navigation',
            'count' => 0,
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cmsPages(): array
    {
        if (! class_exists(Page::class) || ! Schema::hasTable('cms_pages')) {
            return [];
        }

        return Page::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title', 'slug'])
            ->map(static fn (Page $page): array => [
                'id' => (int) $page->id,
                'title' => (string) $page->title,
                'slug' => (string) $page->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function socialSources(): array
    {
        $all = [
            ['key' => 'facebook', 'label' => 'Facebook'],
            ['key' => 'instagram', 'label' => 'Instagram'],
            ['key' => 'tiktok', 'label' => 'TikTok'],
            ['key' => 'line', 'label' => 'LINE'],
        ];

        return [
            'configured' => [],
            'missing' => $all,
        ];
    }
}
