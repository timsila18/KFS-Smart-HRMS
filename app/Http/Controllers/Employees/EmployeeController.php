<?php

namespace App\Http\Controllers\Employees;

use App\Exports\EmployeesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmployeeIndexRequest;
use App\Http\Requests\Employees\ImportEmployeesRequest;
use App\Http\Requests\Employees\StoreEmployeeAttachmentRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Requests\Employees\UploadEmployeePhotoRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use App\Models\Station;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Auth\ActivityLogger;
use App\Services\Employees\EmployeeRegisterService;
use App\Support\SimplePdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeRegisterService $service,
    ) {
    }

    public function index(EmployeeIndexRequest $request): Response
    {
        return Inertia::render('Employees/Index', [
            'employees' => EmployeeResource::collection($this->employees->paginate($request->validated(), (int) $request->integer('per_page', 15))),
            'filters' => $request->validated(),
            'lookups' => $this->lookups(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('Employees/Form', [
            'mode' => 'create',
            'employee' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create($request->validated(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee record created.');
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        return Inertia::render('Employees/Show', [
            'employee' => new EmployeeResource($this->employees->findByUuid($employee->uuid)),
            'auditLogs' => AuditLog::query()
                ->where('auditable_type', Employee::class)
                ->where('auditable_id', $employee->id)
                ->latest()
                ->limit(20)
                ->get(['uuid', 'event', 'old_values', 'new_values', 'created_at']),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('Employees/Form', [
            'mode' => 'edit',
            'employee' => new EmployeeResource($this->employees->findByUuid($employee->uuid)),
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee = $this->service->update($employee, $request->validated(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee record updated.');
    }

    public function destroy(Request $request, Employee $employee, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $employee->update(['deleted_by' => $request->user()->id]);
        $employee->delete();
        $activityLogger->record($request, 'employee.deleted', $employee, [], ['employee_number' => $employee->employee_number]);

        return redirect()->route('employees.index')->with('status', 'Employee record archived.');
    }

    public function uploadPhoto(UploadEmployeePhotoRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->uploadPhoto($employee, $request->file('photo'), $request);

        return back()->with('status', 'Employee photo uploaded.');
    }

    public function attach(StoreEmployeeAttachmentRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->attachFile($employee, $request->file('file'), $request->string('type')->toString(), $request);

        return back()->with('status', 'Attachment uploaded.');
    }

    public function exportExcel(EmployeeIndexRequest $request): EmployeesExport
    {
        $this->authorize('export', Employee::class);

        return new EmployeesExport($this->employees, $request->validated());
    }

    public function exportPdf(EmployeeIndexRequest $request)
    {
        $this->authorize('export', Employee::class);
        $employees = $this->employees->query($request->validated())->limit(1000)->get();

        $rows = $employees->map(fn (Employee $employee) => [
            'employee_number' => $employee->employee_number,
            'name' => $employee->full_name,
            'status' => $employee->employment_status,
            'station' => $employee->station?->name,
            'department' => $employee->department?->name,
            'position' => $employee->jobPosition?->title,
            'hire_date' => $employee->hire_date?->toDateString(),
        ])->all();

        return response(SimplePdf::table('KFS Employee Register', ['employee_number', 'name', 'status', 'station', 'department', 'position', 'hire_date'], $rows))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="kfs-employee-register.pdf"');
    }

    public function import(ImportEmployeesRequest $request): RedirectResponse
    {
        $summary = $this->service->importSpreadsheet($request->file('file'), $request);

        return back()->with(
            'status',
            "Employee import completed. Created: {$summary['created']}, updated: {$summary['updated']}, skipped: {$summary['skipped']}."
        );
    }

    private function lookups(): array
    {
        return [
            'stations' => Station::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => JobPosition::query()->where('is_active', true)->orderBy('title')->get(['id', 'title']),
        ];
    }

    private function pdfOptions(): array
    {
        $base = env('APP_RUNNING_ON_VERCEL') ? '/tmp/kfs-smart-hrms/dompdf' : storage_path('app/dompdf');

        foreach ([$base, "{$base}/fonts", "{$base}/temp"] as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        return [
            'tempDir' => "{$base}/temp",
            'fontDir' => "{$base}/fonts",
            'fontCache' => "{$base}/fonts",
            'isRemoteEnabled' => false,
        ];
    }
}
