<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AdminNavigationIaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const REQUIRED_ROUTES = [
        'admin.dashboard',
        'admin.orders.index',
        'admin.payments.index',
        'admin.products.index',
        'admin.catalog.index',
        'admin.inventory.index',
        'admin.customers.index',
        'admin.crm.leads.index',
        'admin.cms.homepage.edit',
        'admin.cms.pages.index',
        'admin.cms.posts.index',
        'admin.navigation.show',
        'admin.settings.appearance.show',
        'admin.media.index',
        'admin.storefront.navigation.show',
        'admin.settings.footer.show',
        'admin.cms.categories.index',
        'admin.cms.tags.index',
        'admin.cms.hero-banners.index',
        'admin.cms.promotion-banners.index',
        'admin.cms.faq-entries.index',
        'admin.promotions.index',
        'admin.reports.index',
        'admin.reports.sales.index',
        'admin.reports.orders.index',
        'admin.reports.products.index',
        'pos.index',
        'admin.pos.registers.index',
        'warehouse.index',
        'admin.barcode.index',
        'admin.marketplace.sellers.index',
        'admin.marketplace.commissions.index',
        'admin.platform.tenants.index',
        'admin.system.modules.index',
        'admin.system.features.index',
        'admin.settings.website.show',
        'admin.settings.site-identity.show',
        'admin.settings.customer-experience.show',
        'admin.shipping.index',
        'admin.tax.index',
        'admin.settings.translations.index',
        'admin.currencies.index',
        'admin.iam.users.index',
        'admin.iam.roles.index',
        'admin.iam.permissions.index',
        'admin.iam.teams.index',
        'admin.iam.audit-logs.index',
        'admin.iam.security.show',
        'admin.settings.auth.show',
        'admin.settings.mail.show',
        'admin.notification.templates.index',
        'admin.webhooks.index',
        'admin.products.settings.show',
        'admin.settings.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_navigation_config_matches_approved_merchant_ia(): void
    {
        $navigation = config('admin.navigation');
        $this->assertIsArray($navigation);

        $ids = array_column($navigation, 'id');
        $this->assertSame(
            [
                'home',
                'orders',
                'products',
                'customers',
                'online-store',
                'marketing',
                'analytics',
                'pos',
                'warehouse',
                'marketplace',
                'platform',
                'settings',
            ],
            $ids,
        );

        $byId = [];
        foreach ($navigation as $item) {
            $byId[$item['id']] = $item;
        }

        $this->assertSame('link', $byId['home']['type']);
        $this->assertSame('admin.dashboard', $byId['home']['route']);
        $this->assertSame(['Dashboard'], $byId['home']['aliases']);
        $this->assertTrue($byId['settings']['pinned']);
        $this->assertFalse($byId['settings']['default_open']);

        foreach (['orders', 'products', 'customers', 'online-store', 'marketing', 'analytics', 'pos', 'warehouse', 'marketplace', 'platform', 'settings'] as $id) {
            $this->assertFalse($byId[$id]['default_open']);
        }

        $this->assertSame(['Orders', 'Payments'], array_column($byId['orders']['children'], 'label'));
        $this->assertSame(['Products', 'Collections', 'Inventory'], array_column($byId['products']['children'], 'label'));
        $this->assertSame(['Customers', 'Leads'], array_column($byId['customers']['children'], 'label'));
        $this->assertSame(
            ['Home', 'Pages', 'Blog', 'Navigation', 'Theme', 'Files', 'Header menu', 'Footer', 'Blog categories', 'Tags', 'Hero banners', 'Promo banners', 'FAQ'],
            array_column($byId['online-store']['children'], 'label'),
        );
        $this->assertSame(['Discounts'], array_column($byId['marketing']['children'], 'label'));
        $this->assertSame(
            ['Overview', 'Sales Reports', 'Order Reports', 'Product Reports'],
            array_column($byId['analytics']['children'], 'label'),
        );
        $this->assertSame(['POS', 'Registers'], array_column($byId['pos']['children'], 'label'));
        $this->assertSame(['Scanner', 'Barcode Center'], array_column($byId['warehouse']['children'], 'label'));
        $this->assertSame(['Sellers', 'Marketplace Operations'], array_column($byId['marketplace']['children'], 'label'));
        $this->assertSame(['Tenants', 'Modules', 'Features'], array_column($byId['platform']['children'], 'label'));
        $this->assertSame(
            [
                'General',
                'Site Identity',
                'Checkout & Experience',
                'Shipping',
                'Taxes',
                'Languages',
                'Currencies',
                'Staff',
                'Roles',
                'Permissions',
                'Teams',
                'Activity Logs',
                'Security',
                'Customer Login',
                'Email',
                'Notifications',
                'Apps & Integrations',
                'Products',
                'System',
            ],
            array_column($byId['settings']['children'], 'label'),
        );

        $this->assertSame('pos', $byId['pos']['module']);
        $this->assertSame('marketplace', $byId['marketplace']['module']);
        $this->assertSame('warehouse', $byId['warehouse']['children'][0]['module']);
        $this->assertSame('barcode', $byId['warehouse']['children'][1]['module']);
        $this->assertSame('admin.catalog.index', $byId['products']['children'][1]['route']);
        $this->assertSame('catalog.category.view', $byId['products']['children'][1]['permission']);
        $this->assertSame('admin.navigation.show', $byId['online-store']['children'][3]['route']);
        $this->assertSame('admin.storefront.navigation.show', $byId['online-store']['children'][6]['route']);
        $this->assertSame('admin.marketplace.commissions.index', $byId['marketplace']['children'][1]['route']);
        $this->assertSame('marketplace.commission.view', $byId['marketplace']['children'][1]['permission']);
        $this->assertSame('admin.tax.index', collect($byId['settings']['children'])->firstWhere('label', 'Taxes')['route']);
        $this->assertSame('tax.rate.view', collect($byId['settings']['children'])->firstWhere('label', 'Taxes')['permission']);
    }

    public function test_sidebar_keeps_every_previous_destination(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $routes = [];
        $this->collectRoutes($nav, $routes);

        foreach (self::REQUIRED_ROUTES as $route) {
            $this->assertTrue(Route::has($route), $route.' must stay registered');
            $this->assertContains($route, $routes, $route.' must remain in the sidebar');
        }
    }

    public function test_sidebar_is_grouped_by_merchant_jobs(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);
        $ids = array_column($nav, 'id');

        $this->assertSame('home', $ids[0]);
        $this->assertSame('settings', $ids[array_key_last($ids)]);
        $this->assertTrue($byId['settings']['pinned']);
        $this->assertNotContains('dashboard', $ids);
        $this->assertNotContains('catalog', $ids);
        $this->assertNotContains('sales', $ids);
        $this->assertNotContains('content', $ids);
        $this->assertNotContains('identity', $ids);
        $this->assertNotContains('configuration', $ids);
        $this->assertNotContains('system', $ids);
        $this->assertNotContains('Users & Access', array_column($nav, 'label'));
        $this->assertNotContains('Catalog', $this->collectLabels($nav));
        $this->assertSame('Home', $byId['home']['label']);
        $this->assertSame('Products', $byId['products']['label']);
        $this->assertSame('Online Store', $byId['online-store']['label']);
        $this->assertSame('Analytics', $byId['analytics']['label']);
        $this->assertSame('Point of Sale', $byId['pos']['label']);
        $this->assertSame(['Collections'], array_column(
            array_filter($byId['products']['children'], static fn (array $child): bool => $child['route'] === 'admin.catalog.index'),
            'label',
        ));
        $this->assertSame(['Customers', 'Leads'], array_column($byId['customers']['children'], 'label'));
        $this->assertSame(['Discounts'], array_column($byId['marketing']['children'], 'label'));
        $this->assertSame('Theme', collect($byId['online-store']['children'])->firstWhere('route', 'admin.settings.appearance.show')['label']);
        $this->assertFalse($byId['orders']['default_open']);
        $this->assertFalse($byId['settings']['default_open']);
    }

    public function test_thai_sidebar_uses_merchant_job_labels(): void
    {
        app()->setLocale('th');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);

        $this->assertSame('หน้าแรก', $byId['home']['label']);
        $this->assertSame('สินค้า', $byId['products']['label']);
        $this->assertSame('ร้านค้าออนไลน์', $byId['online-store']['label']);
        $this->assertSame('การวิเคราะห์', $byId['analytics']['label']);
        $this->assertSame('ตั้งค่า', $byId['settings']['label']);
        $this->assertSame('โมดูล', collect($byId['platform']['children'])->firstWhere('route', 'admin.system.modules.index')['label']);
        $this->assertSame('ฟีเจอร์', collect($byId['platform']['children'])->firstWhere('route', 'admin.system.features.index')['label']);
        $this->assertNotContains('แคตตาล็อก', $this->collectLabels($nav));
        $this->assertNotContains('แดชบอร์ด', $this->collectLabels($nav));
    }

    public function test_command_palette_keeps_legacy_search_aliases(): void
    {
        app()->setLocale('en');

        $entries = app(AdminNavigationBuilderInterface::class)->searchableItems(User::query()->first());
        $byRoute = [];
        foreach ($entries as $entry) {
            $byRoute[(string) $entry['route']] = $entry;
        }

        $this->assertStringContainsString('catalog', $byRoute['admin.products.index']['keywords']);
        $this->assertStringContainsString('crm', $byRoute['admin.crm.leads.index']['keywords']);
        $this->assertStringContainsString('promotions', $byRoute['admin.promotions.index']['keywords']);
        $this->assertStringContainsString('storefront', $byRoute['admin.settings.appearance.show']['keywords']);
        $this->assertStringContainsString('dashboard', $byRoute['admin.dashboard']['keywords']);
        $this->assertContains('Catalog', $byRoute['admin.products.index']['aliases']);
        $this->assertContains('CRM', $byRoute['admin.crm.leads.index']['aliases']);
        $this->assertContains('Promotions', $byRoute['admin.promotions.index']['aliases']);
        $this->assertContains('Storefront', $byRoute['admin.settings.appearance.show']['aliases']);
    }

    public function test_module_menus_do_not_leak_duplicate_destinations(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $labels = $this->collectLabels($nav);

        $this->assertSame(1, count(array_filter($labels, static fn (string $label): bool => $label === 'Pages')));
        $this->assertSame(1, count(array_filter($labels, static fn (string $label): bool => $label === 'Blog')));
        $this->assertSame(0, count(array_filter($labels, static fn (string $label): bool => $label === 'Content')));
        $this->assertSame(0, count(array_filter($labels, static fn (string $label): bool => $label === 'Catalog')));
        $this->assertSame(0, count(array_filter($labels, static fn (string $label): bool => $label === 'Users & Access')));
        $this->assertSame(0, count(array_filter($labels, static fn (string $label): bool => $label === 'Dashboard')));
    }

    public function test_disabled_modules_hide_their_groups(): void
    {
        app()->setLocale('en');

        $pos = SystemModule::query()->where('code', 'pos')->firstOrFail();
        app(ModuleService::class)->updateStatus($pos, ModuleStatus::Disabled);

        $marketplace = SystemModule::query()->where('code', 'marketplace')->firstOrFail();
        app(ModuleService::class)->updateStatus($marketplace, ModuleStatus::Disabled);

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $ids = array_column($nav, 'id');
        $labels = $this->collectLabels($nav);

        $this->assertNotContains('pos', $ids);
        $this->assertNotContains('marketplace', $ids);
        $this->assertNotContains('Point of Sale', $labels);
        $this->assertNotContains('Marketplace', $labels);
        $this->assertContains('warehouse', $ids);
        $this->assertContains('products', $ids);
    }

    public function test_warehouse_group_hides_when_both_children_are_disabled(): void
    {
        app()->setLocale('en');

        $warehouse = SystemModule::query()->where('code', 'warehouse')->firstOrFail();
        app(ModuleService::class)->updateStatus($warehouse, ModuleStatus::Disabled);
        $barcode = SystemModule::query()->where('code', 'barcode')->firstOrFail();
        app(ModuleService::class)->updateStatus($barcode, ModuleStatus::Disabled);

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);

        $this->assertArrayNotHasKey('warehouse', $byId);
        $this->assertNotContains('Scanner', $this->collectLabels($nav));
        $this->assertNotContains('Barcode Center', $this->collectLabels($nav));
    }

    public function test_warehouse_keeps_barcode_when_scanner_module_is_disabled(): void
    {
        app()->setLocale('en');

        $warehouse = SystemModule::query()->where('code', 'warehouse')->firstOrFail();
        app(ModuleService::class)->updateStatus($warehouse, ModuleStatus::Disabled);

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);

        $this->assertArrayHasKey('warehouse', $byId);
        $this->assertSame(['Barcode Center'], array_column($byId['warehouse']['children'], 'label'));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, array<string, mixed>>
     */
    private function indexById(array $items): array
    {
        $byId = [];
        foreach ($items as $item) {
            $byId[$item['id']] = $item;
        }

        return $byId;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private function collectLabels(array $items): array
    {
        $labels = [];
        foreach ($items as $item) {
            $labels[] = (string) $item['label'];
            $labels = [...$labels, ...$this->collectLabels($item['children'] ?? [])];
        }

        return $labels;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $routes
     */
    private function collectRoutes(array $items, array &$routes): void
    {
        foreach ($items as $item) {
            if (isset($item['route']) && is_string($item['route'])) {
                $routes[] = $item['route'];
            }

            $this->collectRoutes($item['children'] ?? [], $routes);
        }
    }
}
