<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminLocaleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_header_posts_available_locales(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="locale"', $html);
        $this->assertStringContainsString('value="th"', $html);
        $this->assertStringContainsString('value="en"', $html);
        $this->assertStringContainsString('ไทย', $html);
        $this->assertStringContainsString('English', $html);
        $this->assertStringNotContainsString('disabled', $html);
    }

    public function test_switching_to_thai_persists_and_translates_the_admin_shell(): void
    {
        $admin = User::query()->first();
        $sessionKey = (string) config('admin.locale.session_key', 'commerce.locale');

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), ['locale' => 'th'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas($sessionKey, 'th');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('แดชบอร์ด', false)
            ->assertSee('สินค้า', false);

        $this->assertSame('th', app()->getLocale());
    }

    public function test_switching_to_english_restores_english_admin_labels(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), ['locale' => 'th']);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Catalog', false)
            ->assertDontSee('แดชบอร์ด', false);

        $this->assertSame('en', app()->getLocale());
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->actingAs(User::query()->first())
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), ['locale' => 'xx'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors('locale');
    }
}
