<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductionBootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_bootstrap_command_runs_successfully(): void
    {
        $this->artisan('commerce:production-bootstrap')
            ->expectsOutputToContain('Production checklist')
            ->assertSuccessful();
    }
}
