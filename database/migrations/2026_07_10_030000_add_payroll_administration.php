<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_codes', function (Blueprint $table): void {
            $table->string('component_group', 80)->nullable()->after('pay_code_type');
            $table->string('component_subtype', 100)->nullable()->after('component_group');
            $table->string('calculation_method', 80)->default('fixed')->after('component_subtype');
            $table->decimal('default_amount', 14, 2)->nullable()->after('calculation_method');
            $table->decimal('default_rate', 10, 4)->nullable()->after('default_amount');
            $table->boolean('is_recurring')->default(true)->after('is_pensionable');
            $table->boolean('requires_membership')->default(false)->after('is_recurring');
            $table->boolean('is_active')->default(true)->after('requires_membership');
            $table->integer('sort_order')->default(0)->after('is_active');

            $table->index(['pay_code_type', 'component_group']);
            $table->index(['component_subtype', 'is_active']);
        });

        Schema::create('payroll_institutions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->string('institution_type', 80);
            $table->string('code', 80)->unique();
            $table->string('name', 190);
            $table->string('registration_number', 120)->nullable();
            $table->string('contact_person', 160)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 190)->nullable();
            $table->jsonb('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['institution_type', 'is_active']);
        });

        Schema::create('payroll_institution_products', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('payroll_institution_id')->index();
            $table->foreignId('pay_code_id')->nullable()->index();
            $table->string('product_type', 80);
            $table->string('code', 80)->unique();
            $table->string('name', 190);
            $table->string('calculation_method', 80)->default('fixed');
            $table->decimal('default_amount', 14, 2)->nullable();
            $table->decimal('default_rate', 10, 4)->nullable();
            $table->jsonb('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('payroll_institution_id')->references('id')->on('payroll_institutions')->cascadeOnDelete();
            $table->foreign('pay_code_id')->references('id')->on('pay_codes')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['product_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_institution_products');
        Schema::dropIfExists('payroll_institutions');

        Schema::table('pay_codes', function (Blueprint $table): void {
            $table->dropColumn([
                'component_group',
                'component_subtype',
                'calculation_method',
                'default_amount',
                'default_rate',
                'is_recurring',
                'requires_membership',
                'is_active',
                'sort_order',
            ]);
        });
    }
};
