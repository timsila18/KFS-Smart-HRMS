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
            $table->string('photo_path', 500)->nullable()->after('employment_status');
        });

        Schema::create('employee_next_of_kin', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('employee_id')->index();
            $table->string('full_name', 190);
            $table->string('relationship', 80);
            $table->string('phone', 40)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'is_primary']);
        });

        Schema::create('employee_medical_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('employee_id')->index();
            $table->string('blood_group', 10)->nullable();
            $table->string('medical_scheme', 160)->nullable();
            $table->string('medical_membership_number', 120)->nullable();
            $table->text('allergies')->nullable();
            $table->text('conditions')->nullable();
            $table->text('disabilities')->nullable();
            $table->date('last_medical_exam_on')->nullable();
            $table->date('next_medical_exam_on')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['medical_scheme', 'medical_membership_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_medical_records');
        Schema::dropIfExists('employee_next_of_kin');

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });
    }
};
