<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\UserLoginHistory;
use App\Services\Dashboard\KfsDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, KfsDashboardService $dashboard): Response
    {
        return Inertia::render('Dashboard', [
            'dashboard' => $dashboard->summary(),
            'loginHistory' => UserLoginHistory::query()
                ->where('user_id', $request->user()->id)
                ->latest('logged_in_at')
                ->limit(5)
                ->get(['uuid', 'ip_address', 'status', 'failure_reason', 'logged_in_at', 'logged_out_at']),
            'activityLogs' => AuditLog::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(5)
                ->get(['uuid', 'event', 'auditable_type', 'created_at']),
        ]);
    }
}
