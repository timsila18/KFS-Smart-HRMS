<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:'.config('security.api.rate_limit').',1'])
    ->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employees.view');
        Route::get('/employees/{employee:uuid}', [EmployeeController::class, 'show'])->middleware('permission:employees.view');
});
