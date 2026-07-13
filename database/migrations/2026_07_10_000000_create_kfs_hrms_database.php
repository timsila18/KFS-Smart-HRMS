<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [];

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        foreach ($this->definitions() as $table => $definition) {
            $this->tables[] = $table;
            Schema::create($table, function (Blueprint $blueprint) use ($definition): void {
                $this->baseColumns($blueprint);
                foreach ($definition['columns'] ?? [] as $column) {
                    $this->addColumn($blueprint, $column);
                }
                foreach ($definition['unique'] ?? [] as $columns) {
                    $blueprint->unique($columns);
                }
                foreach ($definition['index'] ?? [] as $columns) {
                    $blueprint->index($columns);
                }
            });
        }

        foreach ($this->definitions() as $table => $definition) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $definition): void {
                $this->auditForeignKeys($blueprint, $table);
                foreach ($definition['foreign'] ?? [] as $foreign) {
                    $blueprint
                        ->foreign($foreign[0])
                        ->references($foreign[1] ?? 'id')
                        ->on($foreign[2])
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys($this->definitions())) as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function baseColumns(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->foreignId('deleted_by')->nullable()->index();
        $table->timestampsTz();
        $table->softDeletesTz();
    }

    private function auditForeignKeys(Blueprint $table, string $name): void
    {
        $table->foreign('created_by', "{$name}_created_by_fk")->references('id')->on('users')->nullOnDelete();
        $table->foreign('updated_by', "{$name}_updated_by_fk")->references('id')->on('users')->nullOnDelete();
        $table->foreign('deleted_by', "{$name}_deleted_by_fk")->references('id')->on('users')->nullOnDelete();
    }

    /**
     * @param array{0:string,1:string,2?:string,3?:array<string,mixed>} $column
     */
    private function addColumn(Blueprint $table, array $column): void
    {
        [$type, $name] = $column;
        $args = $column[2] ?? null;
        $options = $column[3] ?? [];

        $field = match ($type) {
            'string' => $table->string($name, $args ?? 255),
            'text' => $table->text($name),
            'longText' => $table->longText($name),
            'integer' => $table->integer($name),
            'bigInteger' => $table->bigInteger($name),
            'boolean' => $table->boolean($name),
            'date' => $table->date($name),
            'dateTimeTz' => $table->dateTimeTz($name),
            'time' => $table->time($name),
            'decimal' => $table->decimal($name, $args[0], $args[1]),
            'jsonb' => $table->jsonb($name),
            'foreignId' => $table->foreignId($name)->nullable()->index(),
            default => throw new InvalidArgumentException("Unsupported column type {$type}."),
        };

        if (($options['nullable'] ?? true) === true) {
            $field->nullable();
        }
        if (array_key_exists('default', $options)) {
            $field->default($options['default']);
        }
        if (($options['index'] ?? false) === true) {
            $field->index();
        }
        if (($options['unique'] ?? false) === true) {
            $field->unique();
        }
        if (isset($options['comment'])) {
            $field->comment($options['comment']);
        }
    }

    /**
     * 98 normalized tables grouped by enterprise HRMS bounded contexts.
     *
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            // Identity and access.
            'users' => [
                'columns' => [
                    ['string', 'name', 160, ['nullable' => false]],
                    ['string', 'email', 190, ['nullable' => false, 'unique' => true]],
                    ['dateTimeTz', 'email_verified_at'],
                    ['string', 'password', 255, ['nullable' => false]],
                    ['string', 'status', 40, ['nullable' => false, 'default' => 'active']],
                    ['string', 'remember_token', 100],
                    ['dateTimeTz', 'last_login_at'],
                ],
                'index' => [['status']],
            ],
            'user_profiles' => [
                'columns' => [['foreignId', 'user_id'], ['foreignId', 'employee_id'], ['string', 'phone', 40], ['string', 'avatar_path'], ['jsonb', 'preferences']],
                'foreign' => [['user_id', 'id', 'users'], ['employee_id', 'id', 'employees']],
                'unique' => [['user_id'], ['employee_id']],
            ],
            'password_reset_tokens' => [
                'columns' => [['foreignId', 'user_id'], ['string', 'email', 190, ['nullable' => false]], ['string', 'token', 255, ['nullable' => false]], ['dateTimeTz', 'expires_at']],
                'foreign' => [['user_id', 'id', 'users']],
                'index' => [['email'], ['token']],
            ],
            'sessions' => [
                'columns' => [['foreignId', 'user_id'], ['string', 'session_key', 190, ['nullable' => false, 'unique' => true]], ['string', 'ip_address', 64], ['text', 'user_agent'], ['longText', 'payload'], ['integer', 'last_activity', null, ['nullable' => false, 'default' => 0]]],
                'foreign' => [['user_id', 'id', 'users']],
                'index' => [['last_activity']],
            ],
            'roles' => [
                'columns' => [['foreignId', 'station_id'], ['string', 'name', 125, ['nullable' => false]], ['string', 'guard_name', 80, ['nullable' => false, 'default' => 'web']], ['string', 'scope', 80], ['text', 'description'], ['boolean', 'is_system', null, ['nullable' => false, 'default' => false]]],
                'foreign' => [['station_id', 'id', 'stations']],
                'unique' => [['name', 'guard_name']],
                'index' => [['station_id']],
            ],
            'permissions' => [
                'columns' => [['string', 'name', 160, ['nullable' => false]], ['string', 'guard_name', 80, ['nullable' => false, 'default' => 'web']], ['string', 'module', 80, ['nullable' => false]], ['text', 'description']],
                'unique' => [['name', 'guard_name']],
                'index' => [['module']],
            ],
            'model_has_roles' => [
                'columns' => [['foreignId', 'role_id'], ['string', 'model_type', 190, ['nullable' => false]], ['bigInteger', 'model_id', null, ['nullable' => false]], ['foreignId', 'station_id']],
                'foreign' => [['role_id', 'id', 'roles'], ['station_id', 'id', 'stations']],
                'unique' => [['role_id', 'model_type', 'model_id', 'station_id']],
                'index' => [['model_type', 'model_id']],
            ],
            'model_has_permissions' => [
                'columns' => [['foreignId', 'permission_id'], ['string', 'model_type', 190, ['nullable' => false]], ['bigInteger', 'model_id', null, ['nullable' => false]]],
                'foreign' => [['permission_id', 'id', 'permissions']],
                'unique' => [['permission_id', 'model_type', 'model_id']],
            ],
            'role_has_permissions' => [
                'columns' => [['foreignId', 'permission_id'], ['foreignId', 'role_id']],
                'foreign' => [['permission_id', 'id', 'permissions'], ['role_id', 'id', 'roles']],
                'unique' => [['permission_id', 'role_id']],
            ],
            // Organization.
            'departments' => ['columns' => [['foreignId', 'parent_id'], ['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'type', 60, ['nullable' => false]], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['parent_id', 'id', 'departments']]],
            'stations' => ['columns' => [['foreignId', 'parent_id'], ['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 180, ['nullable' => false]], ['string', 'station_type', 80, ['nullable' => false]], ['string', 'county', 120], ['string', 'region', 120], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['parent_id', 'id', 'stations']], 'index' => [['county'], ['region']]],
            'station_departments' => ['columns' => [['foreignId', 'station_id'], ['foreignId', 'department_id'], ['foreignId', 'head_employee_id'], ['date', 'effective_from'], ['date', 'effective_to']], 'foreign' => [['station_id', 'id', 'stations'], ['department_id', 'id', 'departments'], ['head_employee_id', 'id', 'employees']], 'unique' => [['station_id', 'department_id', 'effective_from']]],
            'job_grades' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['integer', 'rank_order', null, ['nullable' => false]], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]]],
            'job_positions' => ['columns' => [['foreignId', 'job_grade_id'], ['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'title', 180, ['nullable' => false]], ['text', 'description'], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['job_grade_id', 'id', 'job_grades']]],
            'position_establishments' => ['columns' => [['foreignId', 'station_id'], ['foreignId', 'department_id'], ['foreignId', 'job_position_id'], ['integer', 'approved_posts', null, ['nullable' => false, 'default' => 0]], ['integer', 'filled_posts', null, ['nullable' => false, 'default' => 0]], ['date', 'effective_from']], 'foreign' => [['station_id', 'id', 'stations'], ['department_id', 'id', 'departments'], ['job_position_id', 'id', 'job_positions']], 'unique' => [['station_id', 'department_id', 'job_position_id', 'effective_from']]],
            'cost_centres' => ['columns' => [['foreignId', 'station_id'], ['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['station_id', 'id', 'stations']]],

            // Employees.
            'employees' => ['columns' => [['foreignId', 'user_id'], ['foreignId', 'station_id'], ['foreignId', 'department_id'], ['foreignId', 'job_position_id'], ['string', 'employee_number', 60, ['nullable' => false, 'unique' => true]], ['string', 'first_name', 120, ['nullable' => false]], ['string', 'middle_name', 120], ['string', 'last_name', 120, ['nullable' => false]], ['date', 'date_of_birth'], ['string', 'gender', 30], ['string', 'employment_status', 60, ['nullable' => false, 'default' => 'active']], ['date', 'hire_date']], 'foreign' => [['user_id', 'id', 'users'], ['station_id', 'id', 'stations'], ['department_id', 'id', 'departments'], ['job_position_id', 'id', 'job_positions']], 'index' => [['employment_status'], ['last_name', 'first_name']]],
            'employee_identifications' => ['columns' => [['foreignId', 'employee_id'], ['string', 'id_type', 80, ['nullable' => false]], ['string', 'id_number', 120, ['nullable' => false]], ['date', 'issued_at'], ['date', 'expires_at']], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['id_type', 'id_number']]],
            'employee_contacts' => ['columns' => [['foreignId', 'employee_id'], ['string', 'contact_type', 60, ['nullable' => false]], ['string', 'value', 190, ['nullable' => false]], ['boolean', 'is_primary', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['employee_id', 'id', 'employees']], 'index' => [['contact_type']]],
            'employee_addresses' => ['columns' => [['foreignId', 'employee_id'], ['string', 'address_type', 60, ['nullable' => false]], ['string', 'line_1', 190], ['string', 'line_2', 190], ['string', 'town', 120], ['string', 'county', 120], ['string', 'postal_code', 40]] , 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_dependants' => ['columns' => [['foreignId', 'employee_id'], ['string', 'full_name', 190, ['nullable' => false]], ['string', 'relationship', 80, ['nullable' => false]], ['date', 'date_of_birth'], ['boolean', 'is_beneficiary', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_emergency_contacts' => ['columns' => [['foreignId', 'employee_id'], ['string', 'full_name', 190, ['nullable' => false]], ['string', 'relationship', 80], ['string', 'phone', 40, ['nullable' => false]], ['string', 'email', 190]], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_education' => ['columns' => [['foreignId', 'employee_id'], ['string', 'institution', 190, ['nullable' => false]], ['string', 'qualification', 190, ['nullable' => false]], ['string', 'level', 80], ['date', 'started_on'], ['date', 'completed_on']], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_professional_qualifications' => ['columns' => [['foreignId', 'employee_id'], ['string', 'body_name', 190, ['nullable' => false]], ['string', 'membership_number', 120], ['string', 'qualification', 190], ['date', 'expires_at']], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_work_experience' => ['columns' => [['foreignId', 'employee_id'], ['string', 'employer', 190, ['nullable' => false]], ['string', 'position', 160], ['date', 'started_on'], ['date', 'ended_on'], ['text', 'responsibilities']], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_bank_accounts' => ['columns' => [['foreignId', 'employee_id'], ['string', 'bank_name', 160, ['nullable' => false]], ['string', 'branch_name', 160], ['string', 'account_name', 190], ['string', 'account_number', 80, ['nullable' => false]], ['boolean', 'is_primary', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['employee_id', 'account_number']]],
            'employee_documents' => ['columns' => [['foreignId', 'employee_id'], ['string', 'document_type', 100, ['nullable' => false]], ['string', 'title', 190, ['nullable' => false]], ['string', 'file_path', 500, ['nullable' => false]], ['date', 'expires_at']], 'foreign' => [['employee_id', 'id', 'employees']]],
            'employee_assets' => ['columns' => [['foreignId', 'employee_id'], ['string', 'asset_tag', 120, ['nullable' => false]], ['string', 'asset_name', 190, ['nullable' => false]], ['date', 'issued_on'], ['date', 'returned_on'], ['string', 'status', 60, ['nullable' => false, 'default' => 'issued']]], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['asset_tag']]],
            'employee_movements' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'from_station_id'], ['foreignId', 'to_station_id'], ['foreignId', 'from_department_id'], ['foreignId', 'to_department_id'], ['string', 'movement_type', 80, ['nullable' => false]], ['date', 'effective_on'], ['text', 'reason']], 'foreign' => [['employee_id', 'id', 'employees'], ['from_station_id', 'id', 'stations'], ['to_station_id', 'id', 'stations'], ['from_department_id', 'id', 'departments'], ['to_department_id', 'id', 'departments']]],
            'employee_exit_records' => ['columns' => [['foreignId', 'employee_id'], ['string', 'exit_type', 80, ['nullable' => false]], ['date', 'notice_date'], ['date', 'exit_date'], ['text', 'reason'], ['string', 'clearance_status', 60, ['nullable' => false, 'default' => 'pending']]], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['employee_id', 'exit_date']]],

            // Contracts.
            'employment_types' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['boolean', 'is_pensionable', null, ['nullable' => false, 'default' => false]]]],
            'contract_types' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['integer', 'default_months'], ['boolean', 'requires_end_date', null, ['nullable' => false, 'default' => true]]]],
            'contracts' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'employment_type_id'], ['foreignId', 'contract_type_id'], ['string', 'contract_number', 80, ['nullable' => false, 'unique' => true]], ['date', 'start_date'], ['date', 'end_date'], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']]], 'foreign' => [['employee_id', 'id', 'employees'], ['employment_type_id', 'id', 'employment_types'], ['contract_type_id', 'id', 'contract_types']], 'index' => [['status'], ['start_date', 'end_date']]],
            'contract_renewals' => ['columns' => [['foreignId', 'contract_id'], ['date', 'previous_end_date'], ['date', 'new_end_date'], ['text', 'remarks'], ['string', 'approval_status', 60, ['nullable' => false, 'default' => 'pending']]], 'foreign' => [['contract_id', 'id', 'contracts']]],
            'probation_records' => ['columns' => [['foreignId', 'contract_id'], ['date', 'start_date'], ['date', 'end_date'], ['string', 'status', 60, ['nullable' => false, 'default' => 'active']], ['text', 'recommendation']], 'foreign' => [['contract_id', 'id', 'contracts']]],
            'deployments' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'station_id'], ['foreignId', 'department_id'], ['foreignId', 'job_position_id'], ['date', 'effective_from'], ['date', 'effective_to'], ['boolean', 'is_primary', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['employee_id', 'id', 'employees'], ['station_id', 'id', 'stations'], ['department_id', 'id', 'departments'], ['job_position_id', 'id', 'job_positions']], 'index' => [['effective_from', 'effective_to']]],
            'promotions' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'from_job_grade_id'], ['foreignId', 'to_job_grade_id'], ['foreignId', 'from_job_position_id'], ['foreignId', 'to_job_position_id'], ['date', 'effective_on'], ['string', 'approval_status', 60, ['nullable' => false, 'default' => 'pending']]], 'foreign' => [['employee_id', 'id', 'employees'], ['from_job_grade_id', 'id', 'job_grades'], ['to_job_grade_id', 'id', 'job_grades'], ['from_job_position_id', 'id', 'job_positions'], ['to_job_position_id', 'id', 'job_positions']]],

            // Payroll.
            'payroll_periods' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['date', 'starts_on'], ['date', 'ends_on'], ['date', 'pay_date'], ['string', 'status', 60, ['nullable' => false, 'default' => 'open']]], 'index' => [['starts_on', 'ends_on']]],
            'pay_groups' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['string', 'frequency', 40, ['nullable' => false, 'default' => 'monthly']]]],
            'pay_codes' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'pay_code_type', 60, ['nullable' => false]], ['boolean', 'is_taxable', null, ['nullable' => false, 'default' => false]], ['boolean', 'is_pensionable', null, ['nullable' => false, 'default' => false]], ['jsonb', 'calculation_rules']]],
            'salary_scales' => ['columns' => [['foreignId', 'job_grade_id'], ['string', 'code', 60, ['nullable' => false, 'unique' => true]], ['date', 'effective_from'], ['date', 'effective_to'], ['string', 'status', 60, ['nullable' => false, 'default' => 'active']]], 'foreign' => [['job_grade_id', 'id', 'job_grades']]],
            'salary_scale_steps' => ['columns' => [['foreignId', 'salary_scale_id'], ['integer', 'step_number', null, ['nullable' => false]], ['decimal', 'basic_salary', [14, 2], ['nullable' => false]], ['decimal', 'house_allowance', [14, 2], ['nullable' => false, 'default' => 0]]], 'foreign' => [['salary_scale_id', 'id', 'salary_scales']], 'unique' => [['salary_scale_id', 'step_number']]],
            'employee_salary_assignments' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'salary_scale_step_id'], ['foreignId', 'pay_group_id'], ['date', 'effective_from'], ['date', 'effective_to'], ['string', 'status', 60, ['nullable' => false, 'default' => 'active']]], 'foreign' => [['employee_id', 'id', 'employees'], ['salary_scale_step_id', 'id', 'salary_scale_steps'], ['pay_group_id', 'id', 'pay_groups']]],
            'payroll_runs' => ['columns' => [['foreignId', 'payroll_period_id'], ['foreignId', 'pay_group_id'], ['string', 'run_number', 80, ['nullable' => false, 'unique' => true]], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']], ['dateTimeTz', 'processed_at'], ['decimal', 'gross_total', [14, 2], ['nullable' => false, 'default' => 0]], ['decimal', 'deduction_total', [14, 2], ['nullable' => false, 'default' => 0]], ['decimal', 'net_total', [14, 2], ['nullable' => false, 'default' => 0]]], 'foreign' => [['payroll_period_id', 'id', 'payroll_periods'], ['pay_group_id', 'id', 'pay_groups']], 'index' => [['status']]],
            'payroll_run_items' => ['columns' => [['foreignId', 'payroll_run_id'], ['foreignId', 'employee_id'], ['foreignId', 'pay_code_id'], ['decimal', 'quantity', [12, 4], ['nullable' => false, 'default' => 1]], ['decimal', 'rate', [14, 4], ['nullable' => false, 'default' => 0]], ['decimal', 'amount', [14, 2], ['nullable' => false]], ['jsonb', 'calculation_snapshot']], 'foreign' => [['payroll_run_id', 'id', 'payroll_runs'], ['employee_id', 'id', 'employees'], ['pay_code_id', 'id', 'pay_codes']], 'index' => [['employee_id', 'pay_code_id']]],
            'employee_payroll_items' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'pay_code_id'], ['decimal', 'amount', [14, 2], ['nullable' => false]], ['date', 'effective_from'], ['date', 'effective_to'], ['boolean', 'is_recurring', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['employee_id', 'id', 'employees'], ['pay_code_id', 'id', 'pay_codes']]],
            'statutory_deductions' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'deduction_type', 80, ['nullable' => false]], ['jsonb', 'rules'], ['date', 'effective_from'], ['date', 'effective_to']]],
            'tax_bands' => ['columns' => [['foreignId', 'statutory_deduction_id'], ['decimal', 'lower_limit', [14, 2], ['nullable' => false]], ['decimal', 'upper_limit', [14, 2]], ['decimal', 'rate', [8, 4], ['nullable' => false]], ['date', 'effective_from']], 'foreign' => [['statutory_deduction_id', 'id', 'statutory_deductions']]],
            'pension_schemes' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['decimal', 'employee_rate', [8, 4], ['nullable' => false, 'default' => 0]], ['decimal', 'employer_rate', [8, 4], ['nullable' => false, 'default' => 0]], ['jsonb', 'rules']]],
            'employee_pension_memberships' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'pension_scheme_id'], ['string', 'member_number', 120], ['date', 'effective_from'], ['date', 'effective_to']], 'foreign' => [['employee_id', 'id', 'employees'], ['pension_scheme_id', 'id', 'pension_schemes']], 'unique' => [['pension_scheme_id', 'member_number']]],
            'payslips' => ['columns' => [['foreignId', 'payroll_run_id'], ['foreignId', 'employee_id'], ['string', 'payslip_number', 80, ['nullable' => false, 'unique' => true]], ['decimal', 'gross_pay', [14, 2], ['nullable' => false]], ['decimal', 'total_deductions', [14, 2], ['nullable' => false]], ['decimal', 'net_pay', [14, 2], ['nullable' => false]], ['string', 'file_path', 500]], 'foreign' => [['payroll_run_id', 'id', 'payroll_runs'], ['employee_id', 'id', 'employees']]],
            'payroll_adjustments' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'payroll_period_id'], ['foreignId', 'pay_code_id'], ['decimal', 'amount', [14, 2], ['nullable' => false]], ['text', 'reason'], ['string', 'approval_status', 60, ['nullable' => false, 'default' => 'pending']]], 'foreign' => [['employee_id', 'id', 'employees'], ['payroll_period_id', 'id', 'payroll_periods'], ['pay_code_id', 'id', 'pay_codes']]],
            'payroll_journals' => ['columns' => [['foreignId', 'payroll_run_id'], ['foreignId', 'cost_centre_id'], ['string', 'account_code', 80, ['nullable' => false]], ['string', 'entry_type', 20, ['nullable' => false]], ['decimal', 'amount', [14, 2], ['nullable' => false]]], 'foreign' => [['payroll_run_id', 'id', 'payroll_runs'], ['cost_centre_id', 'id', 'cost_centres']]],

            // Leave.
            'leave_types' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['boolean', 'is_paid', null, ['nullable' => false, 'default' => true]], ['boolean', 'requires_attachment', null, ['nullable' => false, 'default' => false]]]],
            'leave_policies' => ['columns' => [['foreignId', 'leave_type_id'], ['string', 'name', 160, ['nullable' => false]], ['date', 'effective_from'], ['date', 'effective_to'], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['leave_type_id', 'id', 'leave_types']]],
            'leave_policy_rules' => ['columns' => [['foreignId', 'leave_policy_id'], ['string', 'rule_key', 120, ['nullable' => false]], ['jsonb', 'rule_value']], 'foreign' => [['leave_policy_id', 'id', 'leave_policies']], 'unique' => [['leave_policy_id', 'rule_key']]],
            'leave_balances' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'leave_type_id'], ['integer', 'year', null, ['nullable' => false]], ['decimal', 'opening_balance', [8, 2], ['nullable' => false, 'default' => 0]], ['decimal', 'accrued_days', [8, 2], ['nullable' => false, 'default' => 0]], ['decimal', 'used_days', [8, 2], ['nullable' => false, 'default' => 0]], ['decimal', 'closing_balance', [8, 2], ['nullable' => false, 'default' => 0]]], 'foreign' => [['employee_id', 'id', 'employees'], ['leave_type_id', 'id', 'leave_types']], 'unique' => [['employee_id', 'leave_type_id', 'year']]],
            'leave_requests' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'leave_type_id'], ['date', 'start_date'], ['date', 'end_date'], ['decimal', 'requested_days', [8, 2], ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']], ['text', 'reason']], 'foreign' => [['employee_id', 'id', 'employees'], ['leave_type_id', 'id', 'leave_types']], 'index' => [['status'], ['start_date', 'end_date']]],
            'leave_request_days' => ['columns' => [['foreignId', 'leave_request_id'], ['date', 'leave_date'], ['decimal', 'day_fraction', [4, 2], ['nullable' => false, 'default' => 1]], ['boolean', 'is_working_day', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['leave_request_id', 'id', 'leave_requests']], 'unique' => [['leave_request_id', 'leave_date']]],
            'leave_approvals' => ['columns' => [['foreignId', 'leave_request_id'], ['foreignId', 'approver_id'], ['integer', 'approval_level', null, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'pending']], ['text', 'remarks'], ['dateTimeTz', 'acted_at']], 'foreign' => [['leave_request_id', 'id', 'leave_requests'], ['approver_id', 'id', 'users']]],
            'holiday_calendars' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'country_code', 3, ['nullable' => false, 'default' => 'KEN']], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]]],
            'holidays' => ['columns' => [['foreignId', 'holiday_calendar_id'], ['date', 'holiday_date'], ['string', 'name', 160, ['nullable' => false]], ['boolean', 'is_recurring', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['holiday_calendar_id', 'id', 'holiday_calendars']], 'unique' => [['holiday_calendar_id', 'holiday_date', 'name']]],

            // Attendance.
            'shifts' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['time', 'starts_at'], ['time', 'ends_at'], ['integer', 'grace_minutes', null, ['nullable' => false, 'default' => 0]]]],
            'shift_patterns' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 120, ['nullable' => false]], ['jsonb', 'pattern_rules']]],
            'employee_shift_assignments' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'shift_id'], ['foreignId', 'shift_pattern_id'], ['date', 'effective_from'], ['date', 'effective_to']], 'foreign' => [['employee_id', 'id', 'employees'], ['shift_id', 'id', 'shifts'], ['shift_pattern_id', 'id', 'shift_patterns']]],
            'attendance_devices' => ['columns' => [['foreignId', 'station_id'], ['string', 'device_code', 80, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'device_type', 80], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['station_id', 'id', 'stations']]],
            'attendance_logs' => ['columns' => [['foreignId', 'attendance_device_id'], ['foreignId', 'employee_id'], ['dateTimeTz', 'logged_at'], ['string', 'log_type', 40, ['nullable' => false]], ['string', 'source_reference', 190], ['jsonb', 'payload']], 'foreign' => [['attendance_device_id', 'id', 'attendance_devices'], ['employee_id', 'id', 'employees']], 'unique' => [['attendance_device_id', 'source_reference']], 'index' => [['logged_at']]],
            'attendance_records' => ['columns' => [['foreignId', 'employee_id'], ['date', 'attendance_date'], ['dateTimeTz', 'clock_in_at'], ['dateTimeTz', 'clock_out_at'], ['decimal', 'worked_hours', [8, 2], ['nullable' => false, 'default' => 0]], ['string', 'status', 60, ['nullable' => false, 'default' => 'present']]], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['employee_id', 'attendance_date']]],
            'attendance_exceptions' => ['columns' => [['foreignId', 'attendance_record_id'], ['string', 'exception_type', 80, ['nullable' => false]], ['text', 'reason'], ['string', 'status', 60, ['nullable' => false, 'default' => 'open']]], 'foreign' => [['attendance_record_id', 'id', 'attendance_records']]],
            'overtime_requests' => ['columns' => [['foreignId', 'employee_id'], ['date', 'work_date'], ['decimal', 'hours', [8, 2], ['nullable' => false]], ['text', 'reason'], ['string', 'approval_status', 60, ['nullable' => false, 'default' => 'pending']]], 'foreign' => [['employee_id', 'id', 'employees']]],
            'timesheets' => ['columns' => [['foreignId', 'employee_id'], ['date', 'period_start'], ['date', 'period_end'], ['decimal', 'total_hours', [8, 2], ['nullable' => false, 'default' => 0]], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']]], 'foreign' => [['employee_id', 'id', 'employees']], 'unique' => [['employee_id', 'period_start', 'period_end']]],

            // Performance.
            'appraisal_cycles' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['date', 'starts_on'], ['date', 'ends_on'], ['string', 'status', 60, ['nullable' => false, 'default' => 'planned']]]],
            'appraisal_forms' => ['columns' => [['foreignId', 'appraisal_cycle_id'], ['string', 'name', 160, ['nullable' => false]], ['jsonb', 'form_schema'], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['appraisal_cycle_id', 'id', 'appraisal_cycles']]],
            'appraisal_goals' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'appraisal_cycle_id'], ['string', 'title', 190, ['nullable' => false]], ['text', 'description'], ['decimal', 'weight', [5, 2], ['nullable' => false, 'default' => 0]], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']]], 'foreign' => [['employee_id', 'id', 'employees'], ['appraisal_cycle_id', 'id', 'appraisal_cycles']]],
            'appraisal_reviews' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'appraisal_cycle_id'], ['foreignId', 'reviewer_id'], ['string', 'review_stage', 80, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'pending']], ['decimal', 'overall_score', [6, 2]]], 'foreign' => [['employee_id', 'id', 'employees'], ['appraisal_cycle_id', 'id', 'appraisal_cycles'], ['reviewer_id', 'id', 'employees']]],
            'appraisal_scores' => ['columns' => [['foreignId', 'appraisal_review_id'], ['foreignId', 'appraisal_goal_id'], ['decimal', 'score', [6, 2], ['nullable' => false]], ['text', 'comments']], 'foreign' => [['appraisal_review_id', 'id', 'appraisal_reviews'], ['appraisal_goal_id', 'id', 'appraisal_goals']]],
            'performance_improvement_plans' => ['columns' => [['foreignId', 'employee_id'], ['foreignId', 'appraisal_review_id'], ['date', 'starts_on'], ['date', 'ends_on'], ['string', 'status', 60, ['nullable' => false, 'default' => 'active']], ['text', 'plan_details']], 'foreign' => [['employee_id', 'id', 'employees'], ['appraisal_review_id', 'id', 'appraisal_reviews']]],
            'disciplinary_cases' => ['columns' => [['foreignId', 'employee_id'], ['string', 'case_number', 80, ['nullable' => false, 'unique' => true]], ['string', 'case_type', 80, ['nullable' => false]], ['date', 'reported_on'], ['string', 'status', 60, ['nullable' => false, 'default' => 'open']], ['text', 'summary']], 'foreign' => [['employee_id', 'id', 'employees']]],

            // Training.
            'training_categories' => ['columns' => [['string', 'code', 40, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]]],
            'training_courses' => ['columns' => [['foreignId', 'training_category_id'], ['string', 'code', 60, ['nullable' => false, 'unique' => true]], ['string', 'title', 190, ['nullable' => false]], ['integer', 'duration_hours'], ['boolean', 'is_mandatory', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['training_category_id', 'id', 'training_categories']]],
            'training_sessions' => ['columns' => [['foreignId', 'training_course_id'], ['string', 'session_code', 80, ['nullable' => false, 'unique' => true]], ['date', 'starts_on'], ['date', 'ends_on'], ['string', 'venue', 190], ['integer', 'capacity']], 'foreign' => [['training_course_id', 'id', 'training_courses']]],
            'training_enrollments' => ['columns' => [['foreignId', 'training_session_id'], ['foreignId', 'employee_id'], ['string', 'status', 60, ['nullable' => false, 'default' => 'nominated']], ['decimal', 'score', [6, 2]], ['date', 'completed_on']], 'foreign' => [['training_session_id', 'id', 'training_sessions'], ['employee_id', 'id', 'employees']], 'unique' => [['training_session_id', 'employee_id']]],
            'training_feedback' => ['columns' => [['foreignId', 'training_enrollment_id'], ['integer', 'rating'], ['text', 'comments'], ['jsonb', 'responses']], 'foreign' => [['training_enrollment_id', 'id', 'training_enrollments']]],

            // ESS, reports, notifications, audit, settings, dashboard.
            'ess_requests' => ['columns' => [['foreignId', 'employee_id'], ['string', 'request_type', 80, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'draft']], ['jsonb', 'payload'], ['text', 'remarks']], 'foreign' => [['employee_id', 'id', 'employees']], 'index' => [['request_type'], ['status']]],
            'ess_request_approvals' => ['columns' => [['foreignId', 'ess_request_id'], ['foreignId', 'approver_id'], ['integer', 'approval_level', null, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'pending']], ['text', 'remarks'], ['dateTimeTz', 'acted_at']], 'foreign' => [['ess_request_id', 'id', 'ess_requests'], ['approver_id', 'id', 'users']]],
            'setting_groups' => ['columns' => [['string', 'code', 80, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['integer', 'sort_order', null, ['nullable' => false, 'default' => 0]]]],
            'system_settings' => ['columns' => [['foreignId', 'setting_group_id'], ['string', 'key', 160, ['nullable' => false, 'unique' => true]], ['string', 'value_type', 40, ['nullable' => false, 'default' => 'string']], ['jsonb', 'value'], ['boolean', 'is_encrypted', null, ['nullable' => false, 'default' => false]], ['boolean', 'is_public', null, ['nullable' => false, 'default' => false]]], 'foreign' => [['setting_group_id', 'id', 'setting_groups']]],
            'audit_logs' => ['columns' => [['foreignId', 'user_id'], ['string', 'auditable_type', 190, ['nullable' => false]], ['bigInteger', 'auditable_id', null, ['nullable' => false]], ['string', 'event', 80, ['nullable' => false]], ['jsonb', 'old_values'], ['jsonb', 'new_values'], ['string', 'ip_address', 64], ['text', 'user_agent']], 'foreign' => [['user_id', 'id', 'users']], 'index' => [['auditable_type', 'auditable_id'], ['event'], ['created_at']]],
            'notification_templates' => ['columns' => [['string', 'code', 80, ['nullable' => false, 'unique' => true]], ['string', 'channel', 40, ['nullable' => false]], ['string', 'subject', 190], ['text', 'body', null, ['nullable' => false]], ['jsonb', 'variables'], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]]],
            'notifications' => ['columns' => [['foreignId', 'user_id'], ['foreignId', 'notification_template_id'], ['string', 'channel', 40, ['nullable' => false]], ['string', 'subject', 190], ['text', 'body'], ['jsonb', 'data'], ['dateTimeTz', 'read_at'], ['dateTimeTz', 'sent_at']], 'foreign' => [['user_id', 'id', 'users'], ['notification_template_id', 'id', 'notification_templates']], 'index' => [['read_at'], ['sent_at']]],
            'notification_preferences' => ['columns' => [['foreignId', 'user_id'], ['string', 'notification_type', 80, ['nullable' => false]], ['string', 'channel', 40, ['nullable' => false]], ['boolean', 'is_enabled', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['user_id', 'id', 'users']], 'unique' => [['user_id', 'notification_type', 'channel']]],
            'attachment_types' => ['columns' => [['string', 'code', 80, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['jsonb', 'allowed_mime_types'], ['integer', 'max_size_kb']]],
            'attachments' => ['columns' => [['foreignId', 'attachment_type_id'], ['string', 'attachable_type', 190, ['nullable' => false]], ['bigInteger', 'attachable_id', null, ['nullable' => false]], ['string', 'file_name', 255, ['nullable' => false]], ['string', 'file_path', 500, ['nullable' => false]], ['string', 'mime_type', 120], ['bigInteger', 'size_bytes']], 'foreign' => [['attachment_type_id', 'id', 'attachment_types']], 'index' => [['attachable_type', 'attachable_id']]],
            'import_batches' => ['columns' => [['foreignId', 'user_id'], ['string', 'batch_number', 80, ['nullable' => false, 'unique' => true]], ['string', 'import_type', 80, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'queued']], ['integer', 'total_rows', null, ['nullable' => false, 'default' => 0]], ['integer', 'failed_rows', null, ['nullable' => false, 'default' => 0]], ['jsonb', 'summary']], 'foreign' => [['user_id', 'id', 'users']]],
            'export_batches' => ['columns' => [['foreignId', 'user_id'], ['string', 'batch_number', 80, ['nullable' => false, 'unique' => true]], ['string', 'export_type', 80, ['nullable' => false]], ['string', 'status', 60, ['nullable' => false, 'default' => 'queued']], ['string', 'file_path', 500], ['jsonb', 'filters']], 'foreign' => [['user_id', 'id', 'users']]],
            'report_catalogs' => ['columns' => [['string', 'code', 80, ['nullable' => false, 'unique' => true]], ['string', 'name', 160, ['nullable' => false]], ['string', 'module', 80, ['nullable' => false]], ['jsonb', 'parameters_schema'], ['boolean', 'is_active', null, ['nullable' => false, 'default' => true]]], 'index' => [['module']]],
            'report_runs' => ['columns' => [['foreignId', 'report_catalog_id'], ['foreignId', 'user_id'], ['string', 'status', 60, ['nullable' => false, 'default' => 'queued']], ['jsonb', 'parameters'], ['string', 'file_path', 500], ['dateTimeTz', 'completed_at']], 'foreign' => [['report_catalog_id', 'id', 'report_catalogs'], ['user_id', 'id', 'users']]],
            'dashboard_widgets' => ['columns' => [['foreignId', 'user_id'], ['string', 'widget_key', 120, ['nullable' => false]], ['integer', 'sort_order', null, ['nullable' => false, 'default' => 0]], ['jsonb', 'configuration'], ['boolean', 'is_visible', null, ['nullable' => false, 'default' => true]]], 'foreign' => [['user_id', 'id', 'users']], 'unique' => [['user_id', 'widget_key']]],
        ];
    }
};
