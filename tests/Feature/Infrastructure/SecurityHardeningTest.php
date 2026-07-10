<?php

namespace Tests\Feature\Infrastructure;

use App\Services\Auth\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_security_and_request_id_headers(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Request-Id');
    }

    public function test_api_errors_return_problem_json(): void
    {
        Route::get('/api/test-problem', fn () => abort(418, 'Short and stout'));

        $this->getJson('/api/test-problem', ['X-Request-Id' => 'test-request-id'])
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('request_id', 'test-request-id')
            ->assertJsonPath('status', 418);
    }

    public function test_activity_logger_records_audit_entries_during_tests(): void
    {
        Route::get('/audit-test', function (ActivityLogger $logger) {
            $logger->record(request(), 'test.event', 'system', [], ['ok' => true]);

            return 'ok';
        });

        $this->get('/audit-test')->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'test.event',
            'auditable_type' => 'system',
        ]);
    }
}
