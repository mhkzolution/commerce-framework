<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class CustomerExperienceNotificationsIsolationTest extends TestCase
{
    public function test_storefront_notifications_do_not_loop_the_feed_forever(): void
    {
        $path = dirname(__DIR__, 3).'/resources/js/storefront/customer-experience.js';
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('function initNotifications', $contents);
        $this->assertStringContainsString('cx:notifications:seen', $contents);
        $this->assertStringNotContainsString('% items.length', $contents);
        $this->assertStringNotContainsString('window.setInterval', $contents);
    }
}
