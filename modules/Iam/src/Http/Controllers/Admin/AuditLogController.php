<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Models\IamAuditLog;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('iam::admin.audit-logs.index', [
            'items' => IamAuditLog::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }
}
