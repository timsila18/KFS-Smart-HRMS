<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'payroll_status')) {
                $table->string('payroll_status', 60)->default('live')->after('employment_status');
            }

            if (! Schema::hasColumn('employees', 'account_status')) {
                $table->string('account_status', 60)->default('active')->after('payroll_status');
            }

            if (! Schema::hasColumn('employees', 'separated_at')) {
                $table->dateTimeTz('separated_at')->nullable()->after('account_status');
            }

            if (! Schema::hasColumn('employees', 'reinstated_at')) {
                $table->dateTimeTz('reinstated_at')->nullable()->after('separated_at');
            }
        });

        DB::statement("create index if not exists idx_employees_payroll_status on employees (payroll_status) where deleted_at is null");
        DB::statement("create index if not exists idx_employees_account_status on employees (account_status) where deleted_at is null");
        DB::statement("update employees set payroll_status = 'stopped' where employment_status in ('separated', 'exited', 'inactive') and payroll_status = 'live'");
        DB::statement("update employees set account_status = 'suspended' where employment_status in ('separated', 'exited') and account_status = 'active'");
    }

    public function down(): void
    {
        DB::statement('drop index if exists idx_employees_payroll_status');
        DB::statement('drop index if exists idx_employees_account_status');

        Schema::table('employees', function (Blueprint $table): void {
            foreach (['reinstated_at', 'separated_at', 'account_status', 'payroll_status'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
