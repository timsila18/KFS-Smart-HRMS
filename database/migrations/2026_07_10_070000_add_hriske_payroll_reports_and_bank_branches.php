<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_branches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->string('bank_code', 20);
            $table->string('branch_code', 20);
            $table->string('bank_name', 190);
            $table->string('branch_name', 190);
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['bank_code', 'branch_code']);
            $table->index(['bank_name', 'branch_name']);
        });

        Schema::table('employee_bank_accounts', function (Blueprint $table): void {
            $table->foreignId('bank_branch_id')->nullable()->after('employee_id')->index();
            $table->string('bank_code', 20)->nullable()->after('bank_name');
            $table->string('branch_code', 20)->nullable()->after('branch_name');
            $table->jsonb('metadata')->nullable()->after('is_primary');
            $table->foreign('bank_branch_id')->references('id')->on('bank_branches')->nullOnDelete();
            $table->index(['bank_code', 'branch_code']);
        });

        foreach ([
            'payroll_run_items' => ['amount' => [36, 18], 'quantity' => [24, 12], 'rate' => [24, 12]],
            'payroll_adjustments' => ['amount' => [36, 18]],
            'payroll_runs' => ['gross_total' => [36, 18], 'deduction_total' => [36, 18], 'net_total' => [36, 18]],
            'payslips' => ['gross_pay' => [36, 18], 'total_deductions' => [36, 18], 'net_pay' => [36, 18]],
            'pay_codes' => ['default_amount' => [36, 18], 'default_rate' => [24, 12]],
            'payroll_institution_products' => ['default_amount' => [36, 18], 'default_rate' => [24, 12]],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $schema) use ($columns): void {
                foreach ($columns as $column => [$precision, $scale]) {
                    $schema->decimal($column, $precision, $scale)->change();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('employee_bank_accounts', function (Blueprint $table): void {
            $table->dropForeign(['bank_branch_id']);
            $table->dropColumn(['bank_branch_id', 'bank_code', 'branch_code', 'metadata']);
        });

        Schema::dropIfExists('bank_branches');
    }
};
