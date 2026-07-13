<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('uuid')->default(DB::raw('gen_random_uuid()'))->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->foreignId('deleted_by')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
