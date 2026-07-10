<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->string('key')->unique();
                $table->mediumText('value');
                $table->integer('expiration')->index();
                $this->auditColumns($table);
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->string('key')->unique();
                $table->string('owner');
                $table->integer('expiration')->index();
                $this->auditColumns($table);
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable()->index();
                $table->unsignedInteger('available_at')->index();
                $table->unsignedInteger('created_at')->index();
                $this->auditColumns($table, false);
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
                $this->auditColumns($table, false);
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestampTz('failed_at')->useCurrent();
                $this->auditColumns($table);
            });
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestampTz('last_used_at')->nullable();
                $table->timestampTz('expires_at')->nullable()->index();
                $this->auditColumns($table);
            });
        }

        $this->createIndexes();
    }

    public function down(): void
    {
        foreach ([
            'idx_audit_logs_event_created',
            'idx_audit_logs_subject',
            'idx_employees_department_status',
            'idx_employees_station_status',
            'idx_employees_search_name',
            'idx_payroll_run_items_run_employee',
            'idx_payroll_runs_period_status',
            'idx_report_runs_report_created',
            'idx_user_login_history_email_created',
        ] as $index) {
            DB::statement("drop index if exists {$index}");
        }

        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }

    private function auditColumns(Blueprint $table, bool $timestamps = true): void
    {
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->foreignId('deleted_by')->nullable()->index();

        if ($timestamps) {
            $table->timestampsTz();
            $table->softDeletesTz();
        } else {
            $table->timestampTz('updated_at')->nullable();
            $table->timestampTz('deleted_at')->nullable();
        }
    }

    private function createIndexes(): void
    {
        DB::statement('create index if not exists idx_employees_department_status on employees (department_id, employment_status) where deleted_at is null');
        DB::statement('create index if not exists idx_employees_station_status on employees (station_id, employment_status) where deleted_at is null');
        DB::statement("create index if not exists idx_employees_search_name on employees using gin (to_tsvector('simple', coalesce(employee_number,'') || ' ' || coalesce(first_name,'') || ' ' || coalesce(middle_name,'') || ' ' || coalesce(last_name,''))) where deleted_at is null");
        DB::statement('create index if not exists idx_payroll_runs_period_status on payroll_runs (payroll_period_id, status) where deleted_at is null');
        DB::statement('create index if not exists idx_payroll_run_items_run_employee on payroll_run_items (payroll_run_id, employee_id) where deleted_at is null');
        DB::statement('create index if not exists idx_report_runs_report_created on report_runs (report_catalog_id, created_at desc) where deleted_at is null');
        DB::statement('create index if not exists idx_audit_logs_event_created on audit_logs (event, created_at desc) where deleted_at is null');
        DB::statement('create index if not exists idx_audit_logs_subject on audit_logs (auditable_type, auditable_id, created_at desc) where deleted_at is null');
        DB::statement('create index if not exists idx_user_login_history_email_created on user_login_histories (email, created_at desc) where deleted_at is null');
    }
};
