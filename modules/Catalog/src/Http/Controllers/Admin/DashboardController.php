<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        return view('catalog::admin.index');
    }
}
