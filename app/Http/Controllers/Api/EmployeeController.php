<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmployeeIndexRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeController extends Controller
{
    public function index(EmployeeIndexRequest $request, EmployeeRepositoryInterface $employees)
    {
        return EmployeeResource::collection($employees->paginate($request->validated(), (int) $request->integer('per_page', 15)));
    }

    public function show(Employee $employee, EmployeeRepositoryInterface $employees): EmployeeResource
    {
        $this->authorize('view', $employee);

        return new EmployeeResource($employees->findByUuid($employee->uuid));
    }
}
