<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminNavigationIaTest extends TestCase
{
    use RefreshDatabase;

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
            ['dashboard', 'sales', 'catalog', 'marketing', 'website', 'content', 'reports', 'identity', 'configuration', 'platform'],
            $ids,
        );

        $byId = [];
        foreach ($navigation as $item) {
            $byId[$item['id']] = $item;
        }

        $this->assertSame(
            ['Orders', 'Customers', 'Payments', 'POS'],
            array_column($byId['sales']['children'], 'label'),
        );
        $this->assertSame(
            ['Products', 'Categories', 'Inventory', 'Media', 'Barcode Center', 'Warehouse Scanner', 'Product Settings'],
            array_column($byId['catalog']['children'], 'label'),
        );
        $this->assertSame(
            ['Promotions', 'CRM', 'Marketplace'],
            array_column($byId['marketing']['children'], 'label'),
        );
        $this->assertSame(
            ['Storefront', 'Navigation', 'Customer Experience', 'Footer'],
            array_column($byId['website']['children'], 'label'),
        );
        $this->assertSame(
            ['Posts', 'Categories', 'Tags', 'Pages'],
            array_column($byId['content']['children'], 'label'),
        );
        $this->assertSame(
            ['Overview', 'Sales Reports', 'Order Reports', 'Product Reports'],
            array_column($byId['reports']['children'], 'label'),
        );
        $this->assertSame(
            ['Users', 'Roles', 'Permissions', 'Teams', 'Activity Logs', 'Security'],
            array_column($byId['identity']['children'], 'label'),
        );
        $this->assertSame(
            [
                'Website Settings',
                'Email',
                'Login & Security',
                'Languages',
                'Currency',
                'Tax',
                'Shipping',
                'Webhooks',
                'Notifications',
                'System Settings',
            ],
            array_column($byId['configuration']['children'], 'label'),
        );
        $this->assertSame(['Tenants'], array_column($byId['platform']['children'], 'label'));

        $this->assertSame('chart-bar', $byId['dashboard']['icon']);
        $this->assertSame('cube', $byId['catalog']['icon']);
        $this->assertSame('megaphone', $byId['marketing']['icon']);
        $this->assertSame('globe-alt', $byId['website']['icon']);
        $this->assertSame('document-text', $byId['content']['icon']);
        $this->assertSame('presentation-chart-line', $byId['reports']['icon']);
        $this->assertSame('users', $byId['identity']['icon']);
        $this->assertSame('cog', $byId['configuration']['icon']);
        $this->assertSame('building-office-2', $byId['platform']['icon']);

        $contentRoutes = array_column($byId['content']['children'], 'route');
        $this->assertSame(
            [
                'admin.cms.posts.index',
                'admin.cms.categories.index',
                'admin.cms.tags.index',
                'admin.cms.pages.index',
            ],
            $contentRoutes,
        );
        $this->assertSame(
            ['cms.post.view', 'cms.category.view', 'cms.tag.view', 'cms.page.view'],
            array_column($byId['content']['children'], 'permission'),
        );
        $this->assertSame('admin.tax.index', $byId['configuration']['children'][5]['route']);
        $this->assertSame('tax.rate.view', $byId['configuration']['children'][5]['permission']);
    }

    public function test_sidebar_is_grouped_by_merchant_domains(): void
    {
        app()->setLocale('th');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);
        $ids = array_column($nav, 'id');
        $approvedOrder = ['dashboard', 'sales', 'catalog', 'marketing', 'website', 'content', 'reports', 'identity', 'configuration', 'platform'];

        $this->assertSame(array_values(array_intersect($approvedOrder, $ids)), $ids);
        $this->assertNotContains('pos.link.0', $ids);
        $this->assertContains('dashboard', $ids);
        $this->assertContains('content', $ids);
        $this->assertContains('identity', $ids);

        $this->assertSame('แดชบอร์ด', $byId['dashboard']['label']);
        $this->assertSame('สินค้า', $byId['catalog']['label']);
        $this->assertSame('เนื้อหา', $byId['content']['label']);
        $this->assertSame('ผู้ใช้และการเข้าถึง', $byId['identity']['label']);
        $this->assertNotContains('แคตตาล็อก', array_column($nav, 'label'));
        $this->assertNotContains('ระบบกลาง', array_column($nav, 'label'));
        $this->assertCount(1, array_filter(array_column($nav, 'label'), static fn (string $label): bool => $label === 'ผู้ใช้และการเข้าถึง'));
    }

    public function test_identity_and_catalog_do_not_duplicate_module_groups(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);

        $identityChildren = array_column($byId['identity']['children'], 'label');
        $this->assertContains('Users', $identityChildren);
        $this->assertContains('Roles', $identityChildren);
        $this->assertContains('Permissions', $identityChildren);
        $this->assertContains('Activity Logs', $identityChildren);
        $this->assertContains('Security', $identityChildren);
        $this->assertNotContains('Users & access', $identityChildren);

        $catalogChildren = array_column($byId['catalog']['children'], 'label');
        $this->assertContains('Products', $catalogChildren);
        $this->assertContains('Categories', $catalogChildren);
        $this->assertContains('Media', $catalogChildren);
        if (\Illuminate\Support\Facades\Route::has('admin.products.settings.show')) {
            $this->assertContains('Product Settings', $catalogChildren);
        }

        $this->assertSame('Catalog', $byId['catalog']['label']);
        $this->assertSame('Platform', $byId['platform']['label']);
        $this->assertSame('Dashboard', $byId['dashboard']['label']);
        $this->assertArrayNotHasKey('overview', $byId);
        $this->assertArrayNotHasKey('products', $byId);
    }

    public function test_content_is_a_top_level_group_and_tax_lives_in_settings(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $byId = $this->indexById($nav);

        $this->assertArrayHasKey('content', $byId);
        $this->assertSame('Content', $byId['content']['label']);
        $this->assertSame(['Posts', 'Categories', 'Tags', 'Pages'], array_column($byId['content']['children'], 'label'));
        $this->assertSame('admin.cms.posts.index', $byId['content']['children'][0]['route']);
        $this->assertSame('cms.post.view', $byId['content']['children'][0]['permission']);
        $this->assertSame('admin.cms.pages.index', $byId['content']['children'][3]['route']);
        $this->assertSame('cms.page.view', $byId['content']['children'][3]['permission']);

        $websiteChildren = array_column($byId['website']['children'] ?? [], 'label');
        $this->assertNotContains('Content', $websiteChildren);
        $this->assertNotContains('Posts', $websiteChildren);
        $this->assertNotContains('Pages', $websiteChildren);
        $this->assertNotContains('Marketplace', $websiteChildren);

        $marketingChildren = array_column($byId['marketing']['children'], 'label');
        $this->assertSame(['Promotions', 'CRM', 'Marketplace'], $marketingChildren);
        $this->assertNotContains('Tax', $marketingChildren);

        $settingsChildren = array_column($byId['configuration']['children'], 'label');
        $this->assertContains('Tax', $settingsChildren);
        $this->assertSame('admin.tax.index', collect($byId['configuration']['children'])->firstWhere('label', 'Tax')['route']);

        $this->assertSame('document-text', $byId['content']['icon']);
        if (isset($byId['website'])) {
            $this->assertSame('globe-alt', $byId['website']['icon']);
        }
    }

    public function test_cms_module_menu_does_not_leak_duplicate_pages_link(): void
    {
        app()->setLocale('en');

        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $labels = [];
        $this->collectLabels($nav, $labels);

        $this->assertSame(1, count(array_filter($labels, static fn (string $label): bool => $label === 'Pages')));
        $this->assertSame(1, count(array_filter($labels, static fn (string $label): bool => $label === 'Posts')));
        $this->assertSame(1, count(array_filter($labels, static fn (string $label): bool => $label === 'Content')));
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
     * @param  list<string>  $labels
     */
    private function collectLabels(array $items, array &$labels): void
    {
        foreach ($items as $item) {
            $labels[] = (string) $item['label'];
            $this->collectLabels($item['children'] ?? [], $labels);
        }
    }
}
