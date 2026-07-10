<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table): void {
            $table->foreignId('approved_by')->nullable()->index()->after('processed_at');
            $table->dateTimeTz('approved_at')->nullable()->after('approved_by');
            $table->foreignId('locked_by')->nullable()->index()->after('approved_at');
            $table->dateTimeTz('locked_at')->nullable()->after('locked_by');
            $table->foreignId('reversed_by')->nullable()->index()->after('locked_at');
            $table->dateTimeTz('reversed_at')->nullable()->after('reversed_by');
            $table->foreignId('reversal_of_run_id')->nullable()->index()->after('reversed_at');
            $table->text('reversal_reason')->nullable()->after('reversal_of_run_id');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversal_of_run_id')->references('id')->on('payroll_runs')->nullOnDelete();
        });

        Schema::create('payroll_output_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('payroll_run_id')->index();
            $table->string('output_type', 80);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['payroll_run_id', 'output_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_output_files');

        Schema::table('payroll_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'approved_by',
                'approved_at',
                'locked_by',
                'locked_at',
                'reversed_by',
                'reversed_at',
                'reversal_of_run_id',
                'reversal_reason',
            ]);
        });
    }
};
