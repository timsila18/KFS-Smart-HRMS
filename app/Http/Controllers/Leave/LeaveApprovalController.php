<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('leave.approve') || $request->user()?->can('ess.approve'), 403);

        return Inertia::render('Leave/Approvals', [
            'approvals' => LeaveApproval::query()
                ->with(['leaveRequest.employee.department', 'leaveRequest.employee.station', 'leaveRequest.leaveType', 'approver'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function approve(Request $request, LeaveApproval $approval): RedirectResponse
    {
        abort_unless($request->user()?->can('leave.approve') || $request->user()?->can('ess.approve'), 403);

        $approval->update([
            'status' => 'approved',
            'remarks' => $request->string('remarks')->toString() ?: null,
            'acted_at' => now(),
        ]);

        $approval->leaveRequest()->update(['status' => 'approved']);

        return back()->with('status', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveApproval $approval): RedirectResponse
    {
        abort_unless($request->user()?->can('leave.approve') || $request->user()?->can('ess.approve'), 403);

        $approval->update([
            'status' => 'rejected',
            'remarks' => $request->string('remarks')->toString() ?: null,
            'acted_at' => now(),
        ]);

        $approval->leaveRequest()->update(['status' => 'rejected']);

        return back()->with('status', 'Leave request rejected.');
    }
}
