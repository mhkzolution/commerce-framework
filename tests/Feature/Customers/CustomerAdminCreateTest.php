<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAdminCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_view_customer_create_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.customers.create'))
            ->assertOk()
            ->assertSee('New Customer', false);
    }
}
