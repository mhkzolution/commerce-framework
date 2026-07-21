<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.design-system');
    }
}
