<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employees\EmployeeController;
use App\Http\Controllers\Ess\EmployeeSelfServiceController;
use App\Http\Controllers\Leave\LeaveApprovalController;
use App\Http\Controllers\Payroll\PayrollAdministrationController;
use App\Http\Controllers\Payroll\PayrollProcessingController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('ess')->name('ess.')->middleware('permission:ess.view')->group(function (): void {
        Route::get('/', [EmployeeSelfServiceController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [EmployeeSelfServiceController::class, 'profile'])->name('profile');
        Route::get('/payslips', [EmployeeSelfServiceController::class, 'payslips'])->name('payslips');
        Route::get('/p9', [EmployeeSelfServiceController::class, 'p9'])->name('p9');
        Route::get('/leave', [EmployeeSelfServiceController::class, 'leave'])->name('leave');
        Route::get('/training', [EmployeeSelfServiceController::class, 'training'])->name('training');
        Route::get('/performance', [EmployeeSelfServiceController::class, 'performance'])->name('performance');
        Route::get('/payroll-history', [EmployeeSelfServiceController::class, 'payrollHistory'])->name('payroll-history');
        Route::get('/contracts', [EmployeeSelfServiceController::class, 'contracts'])->name('contracts');
        Route::get('/documents', [EmployeeSelfServiceController::class, 'documents'])->name('documents');
        Route::get('/messages', [EmployeeSelfServiceController::class, 'messages'])->name('messages');
        Route::get('/notifications', [EmployeeSelfServiceController::class, 'notifications'])->name('notifications');
        Route::get('/service-documents', [EmployeeSelfServiceController::class, 'serviceDocuments'])->name('service-documents');
        Route::get('/notices', [EmployeeSelfServiceController::class, 'notices'])->name('notices');
        Route::get('/attendance', [EmployeeSelfServiceController::class, 'attendance'])->name('attendance');
        Route::get('/loans-deductions', [EmployeeSelfServiceController::class, 'loansDeductions'])->name('loans-deductions');
        Route::get('/settings', [EmployeeSelfServiceController::class, 'settings'])->name('settings');
        Route::get('/requests', [EmployeeSelfServiceController::class, 'requests'])->name('requests');
        Route::post('/requests', [EmployeeSelfServiceController::class, 'storeRequest'])->middleware('permission:ess.create|ess.view')->name('requests.store');
    });

    Route::get('/employees/export/excel', [EmployeeController::class, 'exportExcel'])
        ->middleware('permission:employees.export|reports.export')
        ->name('employees.export.excel');
    Route::get('/employees/export/pdf', [EmployeeController::class, 'exportPdf'])
        ->middleware('permission:employees.export|reports.export')
        ->name('employees.export.pdf');
    Route::post('/employees/{employee:uuid}/photo', [EmployeeController::class, 'uploadPhoto'])
        ->middleware('permission:employees.update')
        ->name('employees.photo');
    Route::post('/employees/{employee:uuid}/attachments', [EmployeeController::class, 'attach'])
        ->middleware('permission:employees.update')
        ->name('employees.attachments.store');
    Route::post('/employees/import', [EmployeeController::class, 'import'])
        ->middleware('permission:employees.create')
        ->name('employees.import');
    Route::get('/employees/import/template', [EmployeeController::class, 'importTemplate'])
        ->middleware('permission:employees.create')
        ->name('employees.import.template');
    Route::resource('/employees', EmployeeController::class)
        ->parameters(['employees' => 'employee']);

    Route::prefix('payroll/administration')
        ->name('payroll.admin.')
        ->middleware('permission:payroll.view')
        ->group(function (): void {
            Route::get('/', [PayrollAdministrationController::class, 'index'])->name('index');
            Route::post('/components', [PayrollAdministrationController::class, 'storeComponent'])->middleware('permission:payroll.create')->name('components.store');
            Route::put('/components/{component:uuid}', [PayrollAdministrationController::class, 'updateComponent'])->middleware('permission:payroll.update')->name('components.update');
            Route::delete('/components/{component:uuid}', [PayrollAdministrationController::class, 'destroyComponent'])->middleware('permission:payroll.delete')->name('components.destroy');
            Route::post('/institutions', [PayrollAdministrationController::class, 'storeInstitution'])->middleware('permission:payroll.create')->name('institutions.store');
            Route::put('/institutions/{institution:uuid}', [PayrollAdministrationController::class, 'updateInstitution'])->middleware('permission:payroll.update')->name('institutions.update');
            Route::delete('/institutions/{institution:uuid}', [PayrollAdministrationController::class, 'destroyInstitution'])->middleware('permission:payroll.delete')->name('institutions.destroy');
            Route::post('/institutions/{institution:uuid}/products', [PayrollAdministrationController::class, 'storeProduct'])->middleware('permission:payroll.update')->name('products.store');
            Route::put('/institutions/{institution:uuid}/products/{product:uuid}', [PayrollAdministrationController::class, 'updateProduct'])->middleware('permission:payroll.update')->name('products.update');
            Route::delete('/institutions/{institution:uuid}/products/{product:uuid}', [PayrollAdministrationController::class, 'destroyProduct'])->middleware('permission:payroll.update')->name('products.destroy');
        });

    Route::prefix('payroll/processing')
        ->name('payroll.processing.')
        ->middleware('permission:payroll.view')
        ->group(function (): void {
            Route::get('/', [PayrollProcessingController::class, 'index'])->name('index');
            Route::post('/open', [PayrollProcessingController::class, 'open'])->middleware('permission:payroll.create')->name('open');
            Route::post('/adjustments/import', [PayrollProcessingController::class, 'importAdjustments'])->middleware('permission:payroll.update')->name('adjustments.import');
            Route::get('/{run:uuid}', [PayrollProcessingController::class, 'show'])->name('show');
            Route::post('/{run:uuid}/calculate', [PayrollProcessingController::class, 'calculate'])->middleware('permission:payroll.update')->name('calculate');
            Route::post('/{run:uuid}/approve', [PayrollProcessingController::class, 'approve'])->middleware('permission:payroll.approve')->name('approve');
            Route::post('/{run:uuid}/lock', [PayrollProcessingController::class, 'lock'])->middleware('permission:payroll.approve')->name('lock');
            Route::post('/{run:uuid}/reverse', [PayrollProcessingController::class, 'reverse'])->middleware('permission:payroll.approve')->name('reverse');
            Route::post('/{run:uuid}/outputs', [PayrollProcessingController::class, 'outputs'])->middleware('permission:payroll.update')->name('outputs');
        });

    Route::prefix('leave')
        ->name('leave.')
        ->middleware('permission:leave.view|ess.view')
        ->group(function (): void {
            Route::get('/approvals', [LeaveApprovalController::class, 'index'])
                ->middleware('permission:leave.approve|ess.approve')
                ->name('approvals.index');
            Route::post('/approvals/{approval}/approve', [LeaveApprovalController::class, 'approve'])
                ->middleware('permission:leave.approve|ess.approve')
                ->name('approvals.approve');
            Route::post('/approvals/{approval}/reject', [LeaveApprovalController::class, 'reject'])
                ->middleware('permission:leave.approve|ess.approve')
                ->name('approvals.reject');
        });

    Route::prefix('reports')
        ->name('reports.')
        ->middleware('permission:reports.view')
        ->group(function (): void {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{report:code}', [ReportController::class, 'show'])->name('show');
            Route::get('/{report:code}/export/{format}', [ReportController::class, 'export'])
                ->middleware('permission:reports.export')
                ->name('export');
            Route::post('/{report:code}/schedule', [ReportController::class, 'schedule'])
                ->middleware('permission:reports.update')
                ->name('schedule');
        });
});

require __DIR__.'/auth.php';
