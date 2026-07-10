<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_catalogs', function (Blueprint $table): void {
            $table->boolean('is_schedulable')->default(false)->after('is_active');
            $table->string('schedule_frequency', 40)->nullable()->after('is_schedulable');
            $table->jsonb('schedule_recipients')->nullable()->after('schedule_frequency');
            $table->dateTimeTz('next_run_at')->nullable()->after('schedule_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('report_catalogs', function (Blueprint $table): void {
            $table->dropColumn(['is_schedulable', 'schedule_frequency', 'schedule_recipients', 'next_run_at']);
        });
    }
};
