<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Notification\Database\Seeders\NotificationTemplateSeeder;
use Commerce\Notification\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationTemplateAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(NotificationTemplateSeeder::class);
    }

    public function test_admin_can_view_notification_templates_index(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.notification.templates.index'))
            ->assertOk()
            ->assertSee(__('notification::admin.templates_title'), false)
            ->assertSee('order.confirmation', false)
            ->assertSee('payment.failed', false);
    }

    public function test_admin_can_view_and_save_a_notification_template(): void
    {
        $template = NotificationTemplate::query()->where('code', 'order.confirmation')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->get(route('admin.notification.templates.edit', $template))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="subject"', false)
            ->assertSee('name="view"', false)
            ->assertSee('{{order_number}}', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.notification.templates.update', $template), [
                'name' => 'Harbor confirmation',
                'subject' => 'Harbor order {{order_number}} is confirmed',
                'view' => 'notification::mail.order-confirmation',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.notification.templates.edit', $template));

        $template->refresh();

        $this->assertSame('Harbor confirmation', $template->name);
        $this->assertSame('Harbor order {{order_number}} is confirmed', $template->subject);
        $this->assertSame('notification::mail.order-confirmation', $template->view);
        $this->assertTrue($template->is_active);
    }
}
