<?php

declare(strict_types=1);

namespace Tests\Feature\Channel;

use Commerce\Contracts\Channel\ChannelContextInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChannelContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requests_resolve_api_channel(): void
    {
        $this->getJson('/api/v1/tenants', ['X-Channel' => 'pos'])
            ->assertOk();

        $context = app(ChannelContextInterface::class);
        $this->assertSame('pos', $context->getChannel());
    }
}
