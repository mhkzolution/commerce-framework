<?php

declare(strict_types=1);

namespace Commerce\Reports\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReportsHubController extends Controller
{
    public function __construct(
        private readonly SalesReportController $sales,
    ) {}

    public function index(): View
    {
        return $this->sales->canvas();
    }
}
