<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Settings\Services\FooterNavigationQuery;
use Tests\TestCase;

final class FooterNavigationQueryTest extends TestCase
{
    public function test_links_are_empty_until_navigation_recovery(): void
    {
        $this->assertSame([], (new FooterNavigationQuery)->links('main'));
        $this->assertSame([], (new FooterNavigationQuery)->links('footer'));
    }
}
