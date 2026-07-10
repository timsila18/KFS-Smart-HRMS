<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayrollComponentRequest;
use App\Http\Requests\Payroll\PayrollInstitutionProductRequest;
use App\Http\Requests\Payroll\PayrollInstitutionRequest;
use App\Models\PayCode;
use App\Models\PayrollInstitution;
use App\Models\PayrollInstitutionProduct;
use App\Services\Auth\ActivityLogger;
use App\Services\Payroll\PayrollAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollAdministrationController extends Controller
{
    public function __construct(private readonly PayrollAdministrationService $service)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PayCode::class);

        return Inertia::render('Payroll/Admin/Index', [
            'components' => PayCode::query()
                ->when($request->string('type')->toString(), fn ($query, string $type) => $query->where('pay_code_type', $type))
                ->when($request->string('group')->toString(), fn ($query, string $group) => $query->where('component_group', $group))
                ->orderBy('pay_code_type')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'institutions' => PayrollInstitution::query()->with('products.payCode')->orderBy('institution_type')->orderBy('name')->get(),
            'lookups' => config('payroll-admin'),
        ]);
    }

    public function storeComponent(PayrollComponentRequest $request): RedirectResponse
    {
        $this->service->createComponent($request->validated(), $request);

        return back()->with('status', 'Payroll component created.');
    }

    public function updateComponent(PayrollComponentRequest $request, PayCode $component): RedirectResponse
    {
        $this->service->updateComponent($component, $request->validated(), $request);

        return back()->with('status', 'Payroll component updated.');
    }

    public function destroyComponent(Request $request, PayCode $component, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('delete', $component);
        $component->update(['deleted_by' => $request->user()->id]);
        $component->delete();
        $activityLogger->record($request, 'payroll.component.deleted', $component, [], ['code' => $component->code]);

        return back()->with('status', 'Payroll component archived.');
    }

    public function storeInstitution(PayrollInstitutionRequest $request): RedirectResponse
    {
        $this->service->createInstitution($request->validated(), $request);

        return back()->with('status', 'Payroll institution created.');
    }

    public function updateInstitution(PayrollInstitutionRequest $request, PayrollInstitution $institution): RedirectResponse
    {
        $this->service->updateInstitution($institution, $request->validated(), $request);

        return back()->with('status', 'Payroll institution updated.');
    }

    public function destroyInstitution(Request $request, PayrollInstitution $institution, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->authorize('delete', $institution);
        $institution->update(['deleted_by' => $request->user()->id]);
        $institution->delete();
        $activityLogger->record($request, 'payroll.institution.deleted', $institution, [], ['code' => $institution->code]);

        return back()->with('status', 'Payroll institution archived.');
    }

    public function storeProduct(PayrollInstitutionProductRequest $request, PayrollInstitution $institution): RedirectResponse
    {
        $this->service->createProduct($institution, $request->validated(), $request);

        return back()->with('status', 'Institution product created.');
    }

    public function updateProduct(PayrollInstitutionProductRequest $request, PayrollInstitution $institution, PayrollInstitutionProduct $product): RedirectResponse
    {
        abort_unless($product->payroll_institution_id === $institution->id, 404);
        $this->service->updateProduct($product, $request->validated(), $request);

        return back()->with('status', 'Institution product updated.');
    }

    public function destroyProduct(Request $request, PayrollInstitution $institution, PayrollInstitutionProduct $product, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($product->payroll_institution_id === $institution->id, 404);
        $this->authorize('update', $institution);
        $product->update(['deleted_by' => $request->user()->id]);
        $product->delete();
        $activityLogger->record($request, 'payroll.institution_product.deleted', $product, [], ['code' => $product->code]);

        return back()->with('status', 'Institution product archived.');
    }
}
