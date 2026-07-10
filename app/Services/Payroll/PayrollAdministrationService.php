<?php

namespace App\Services\Payroll;

use App\Models\PayCode;
use App\Models\PayrollInstitution;
use App\Models\PayrollInstitutionProduct;
use App\Services\Auth\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollAdministrationService
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public function createComponent(array $data, Request $request): PayCode
    {
        return DB::transaction(function () use ($data, $request): PayCode {
            $component = PayCode::query()->create($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.component.created', $component, [], $component->toArray());

            return $component;
        });
    }

    public function updateComponent(PayCode $component, array $data, Request $request): PayCode
    {
        return DB::transaction(function () use ($component, $data, $request): PayCode {
            $old = $component->toArray();
            $component->update($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.component.updated', $component, $old, $component->fresh()->toArray());

            return $component->fresh();
        });
    }

    public function createInstitution(array $data, Request $request): PayrollInstitution
    {
        return DB::transaction(function () use ($data, $request): PayrollInstitution {
            $institution = PayrollInstitution::query()->create($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.institution.created', $institution, [], $institution->toArray());

            return $institution;
        });
    }

    public function updateInstitution(PayrollInstitution $institution, array $data, Request $request): PayrollInstitution
    {
        return DB::transaction(function () use ($institution, $data, $request): PayrollInstitution {
            $old = $institution->toArray();
            $institution->update($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.institution.updated', $institution, $old, $institution->fresh()->toArray());

            return $institution->fresh();
        });
    }

    public function createProduct(PayrollInstitution $institution, array $data, Request $request): PayrollInstitutionProduct
    {
        return DB::transaction(function () use ($institution, $data, $request): PayrollInstitutionProduct {
            $product = $institution->products()->create($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.institution_product.created', $product, [], $product->toArray());

            return $product;
        });
    }

    public function updateProduct(PayrollInstitutionProduct $product, array $data, Request $request): PayrollInstitutionProduct
    {
        return DB::transaction(function () use ($product, $data, $request): PayrollInstitutionProduct {
            $old = $product->toArray();
            $product->update($this->defaults($data));
            $this->activityLogger->record($request, 'payroll.institution_product.updated', $product, $old, $product->fresh()->toArray());

            return $product->fresh();
        });
    }

    private function defaults(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        return $data + ['is_active' => true];
    }
}
