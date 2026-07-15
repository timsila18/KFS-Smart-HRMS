<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'employer')) {
                $table->string('employer', 120)->default('KFS')->after('employment_status');
                $table->index('employer', 'idx_employees_employer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'employer')) {
                $table->dropIndex('idx_employees_employer');
                $table->dropColumn('employer');
            }
        });
    }
};
