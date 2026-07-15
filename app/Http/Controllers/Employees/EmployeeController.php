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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function importTemplate(): StreamedResponse
    {
        $this->authorize('create', Employee::class);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Staff Import');

        $headers = [
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'date_of_birth',
            'hire_date',
            'employment_status',
            'station',
            'station_code',
            'department',
            'position',
            'email',
            'initial_password',
            'bank_name',
            'bank_code',
            'branch_name',
            'branch_code',
            'account_number',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'KFS-0001',
            'Jane',
            'Wanjiku',
            'Mwangi',
            'Female',
            '1990-01-31',
            now()->toDateString(),
            'active',
            'Kakamega Forest Station',
            'STN-WESTERN-KAKAMEGA-KAKAMEGA',
            'Human Resource Management',
            'Human Resource Manager',
            'jane.mwangi@kfs.go.ke',
            config('kfs-auth.default_ess_password', 'KfsEss@2026'),
            'National Bank',
            '',
            'Kakamega',
            '',
            '0123456789',
        ], null, 'A2');

        foreach (range('A', 'S') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['KFS Smart HRMS Staff Onboarding Template'],
            ['Fill Staff Import sheet and upload it from Employees > Bulk Staff Import.'],
            ['Use station_code where possible. Station may be a Conservancy, County, or Forest Station from the KFS stations list.'],
            ['If email is provided, an ESS account is created and linked to the staff profile.'],
            ['If initial_password is blank, the configured default ESS password is used.'],
            ['Dates should use YYYY-MM-DD format.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(120);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'kfs-staff-onboarding-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function lookups(): array
    {
        $stations = Station::query()
            ->where('is_active', true)
            ->orderBy('region')
            ->orderBy('county')
            ->orderBy('station_type')
            ->orderBy('name')
            ->get(['id', 'name', 'station_type', 'county', 'region'])
            ->map(fn (Station $station): array => [
                'id' => $station->id,
                'name' => $station->name,
                'display_name' => collect([
                    $station->name,
                    str($station->station_type)->headline()->toString(),
                    $station->county ?: $station->region,
                ])->filter()->implode(' - '),
            ]);

        return [
            'stations' => $stations,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => JobPosition::query()->where('is_active', true)->orderBy('title')->get(['id', 'title']),
        ];
    }

}
