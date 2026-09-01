<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers;

use Commerce\Core\Seo\SitemapGenerator;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class SitemapController extends Controller
{
    public function __invoke(SitemapGenerator $sitemap): Response
    {
        return response($sitemap->toXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
