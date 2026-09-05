<?php

declare(strict_types=1);

namespace Tests\Unit\Customers;

use Commerce\Customers\Support\StorefrontAuthRedirect;
use Illuminate\Http\Request;
use Tests\TestCase;

final class StorefrontAuthRedirectTest extends TestCase
{
    public function test_rejects_admin_and_api_urls(): void
    {
        $request = Request::create('https://shop.test/account/register', 'GET');

        $this->assertFalse(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://shop.test/admin/login', $request));
        $this->assertFalse(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://shop.test/admin', $request));
        $this->assertFalse(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://shop.test/api/v1/storefront/wishlist', $request));
        $this->assertFalse(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://evil.test/account', $request));
        $this->assertFalse(StorefrontAuthRedirect::isAllowedStorefrontUrl('javascript:alert(1)', $request));
    }

    public function test_allows_storefront_paths(): void
    {
        $request = Request::create('https://shop.test/account/register', 'GET');

        $this->assertTrue(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://shop.test/account', $request));
        $this->assertTrue(StorefrontAuthRedirect::isAllowedStorefrontUrl('https://shop.test/checkout', $request));
        $this->assertTrue(StorefrontAuthRedirect::isAllowedStorefrontUrl('/cart', $request));
    }
}
