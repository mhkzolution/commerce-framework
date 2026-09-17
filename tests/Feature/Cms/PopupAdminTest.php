<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\Popup;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PopupAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
        app()->setLocale('en');
    }

    public function test_admin_can_create_and_update_popup(): void
    {
        $admin = User::query()->first();
        $imageUuid = (string) Str::uuid();

        $this->actingAs($admin)
            ->post(route('admin.cms.popups.store'), [
                'title' => 'Harvest popup',
                'status' => 'published',
                'priority' => 1,
                'is_active' => '1',
                'popup_type' => 'image',
                'image_media_uuid' => $imageUuid,
                'show_delay' => 2,
                'auto_close' => 0,
                'closable' => '1',
                'timezone' => 'Asia/Bangkok',
                'button_target' => 'self',
            ])
            ->assertRedirect(route('admin.cms.popups.index'));

        $popup = Popup::query()->where('title', 'Harvest popup')->first();
        $this->assertNotNull($popup);
        $this->assertSame('harvest-popup', $popup->slug);
        $this->assertSame(Popup::STATUS_PUBLISHED, $popup->status);
        $this->assertSame(Popup::TYPE_IMAGE, $popup->popup_type);
        $this->assertSame($imageUuid, $popup->image_media_uuid);
        $this->assertTrue($popup->is_active);
        $this->assertTrue($popup->closable);
        $this->assertSame(2, $popup->show_delay);

        $this->actingAs($admin)
            ->put(route('admin.cms.popups.update', $popup), [
                'title' => 'Harvest popup',
                'slug' => 'harvest-popup',
                'status' => 'draft',
                'priority' => 4,
                'is_active' => '0',
                'popup_type' => 'content',
                'headline' => 'New crop',
                'subheadline' => 'From the mill this week.',
                'button_text' => 'Shop now',
                'button_url' => '/shop',
                'button_target' => 'blank',
                'show_delay' => 1,
                'auto_close' => 12,
                'closable' => '1',
                'timezone' => 'Asia/Bangkok',
                'intent' => 'continue',
            ])
            ->assertRedirect(route('admin.cms.popups.edit', $popup));

        $popup->refresh();
        $this->assertSame(Popup::STATUS_DRAFT, $popup->status);
        $this->assertSame(Popup::TYPE_CONTENT, $popup->popup_type);
        $this->assertSame('New crop', $popup->headline);
        $this->assertSame('/shop', $popup->button_url);
        $this->assertSame(Popup::TARGET_BLANK, $popup->button_target);
        $this->assertFalse($popup->is_active);
        $this->assertSame(4, $popup->priority);
        $this->assertSame(12, $popup->auto_close);
    }

    public function test_create_form_renders_popup_fields(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.cms.popups.create'))
            ->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('name="slug"', false)
            ->assertSee('name="headline"', false)
            ->assertSee('name="show_delay"', false)
            ->getContent();

        $this->assertStringContainsString('data-file-attach', $html);
        $this->assertStringContainsString('data-popup-preview', $html);
        $this->assertStringContainsString('data-popup-form', $html);
        $this->assertStringContainsString(__('cms::admin.popups'), $html);
        $this->assertStringContainsString(__('cms::admin.popup_preview'), $html);
    }

    public function test_edit_form_renders_live_preview_content(): void
    {
        $popup = Popup::query()->create([
            'title' => 'Welcome overlay',
            'slug' => 'welcome-overlay',
            'status' => Popup::STATUS_PUBLISHED,
            'priority' => 2,
            'is_active' => true,
            'popup_type' => Popup::TYPE_CONTENT,
            'headline' => 'New harvest this week',
            'subheadline' => 'From the mill to your table.',
            'button_text' => 'Shop Now',
            'button_url' => '/shop',
            'closable' => true,
            'show_delay' => 3,
            'timezone' => 'Asia/Bangkok',
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.popups.edit', $popup))
            ->assertOk()
            ->assertSee('data-popup-preview', false)
            ->assertSee('data-preview-headline', false)
            ->assertSee('New harvest this week')
            ->assertSee('From the mill to your table.')
            ->assertSee('Shop Now')
            ->assertSee(__('cms::admin.popup_preview_hide_seven_days'));
    }
}
