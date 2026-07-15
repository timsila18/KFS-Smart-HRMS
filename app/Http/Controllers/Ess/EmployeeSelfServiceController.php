<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ess\StoreEssRequest;
use App\Services\Ess\EmployeeSelfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeSelfServiceController extends Controller
{
    public function __construct(private readonly EmployeeSelfService $ess)
    {
    }

    public function dashboard(Request $request): Response
    {
        return Inertia::render('Ess/Dashboard', $this->ess->dashboard($request->user()));
    }

    public function profile(Request $request): Response
    {
        return Inertia::render('Ess/Profile', $this->ess->profile($request->user()));
    }

    public function payslips(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Payslips', 'description' => 'View and download your payslips.', 'rows' => $this->ess->payslips($request->user())]);
    }

    public function p9(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'P9', 'description' => 'Annual taxable pay and PAYE summary.', 'rows' => $this->ess->p9($request->user())]);
    }

    public function leave(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Leave', 'description' => 'Your leave requests and balances summary.', 'rows' => $this->ess->leave($request->user())]);
    }

    public function training(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Training', 'description' => 'Your training nominations, attendance, and scores.', 'rows' => $this->ess->training($request->user())]);
    }

    public function performance(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Performance', 'description' => 'Your appraisal reviews and scores.', 'rows' => $this->ess->performance($request->user())]);
    }

    public function payrollHistory(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Payroll History', 'description' => 'Historical payroll totals and payments.', 'rows' => $this->ess->payrollHistory($request->user())]);
    }

    public function contracts(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Contracts', 'description' => 'Your employment contracts and status.', 'rows' => $this->ess->contracts($request->user())]);
    }

    public function documents(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Documents', 'description' => 'Your employee documents.', 'rows' => $this->ess->documents($request->user())]);
    }

    public function messages(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Messages', 'description' => 'Messages from HR, payroll, and station administration.', 'rows' => $this->ess->messages($request->user())]);
    }

    public function notifications(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Notifications', 'description' => 'Messages and alerts from KFS Smart HRMS.', 'rows' => $this->ess->notifications($request->user())]);
    }

    public function serviceDocuments(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Issued Documents', 'description' => 'Contracts, posting letters, appointment records, and other KFS service documents.', 'rows' => $this->ess->serviceDocuments($request->user())]);
    }

    public function notices(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Service Policies', 'description' => 'KFS circulars, policies, HR notices, and shared service forms.', 'rows' => $this->ess->notices($request->user())]);
    }

    public function attendance(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Duty Attendance', 'description' => 'Your captured duty attendance records for KFS service days.', 'rows' => $this->ess->attendance($request->user())]);
    }

    public function loansDeductions(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'Loans & Deductions', 'description' => 'Your statutory, SACCO, loan, welfare, and payroll deduction history.', 'rows' => $this->ess->loansAndDeductions($request->user())]);
    }

    public function settings(Request $request): Response
    {
        return Inertia::render('Ess/ListPage', ['title' => 'My Settings', 'description' => 'Your KFS Smart HRMS account access settings.', 'rows' => $this->ess->settings($request->user())]);
    }

    public function requests(Request $request): Response
    {
        $employee = $this->ess->employeeFor($request->user());
        return Inertia::render('Ess/Requests', ['rows' => $employee ? $this->ess->requests($employee) : []]);
    }

    public function storeRequest(StoreEssRequest $request): RedirectResponse
    {
        $this->ess->createRequest($request->user(), $request->validated());

        return back()->with('status', 'Request submitted.');
    }
}
