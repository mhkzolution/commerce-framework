<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Settings\Services\FooterSocialQuery;
use Tests\TestCase;

final class FooterSocialQueryTest extends TestCase
{
    public function test_links_are_empty_until_website_settings(): void
    {
        $this->assertSame([], (new FooterSocialQuery)->links());
    }
}
