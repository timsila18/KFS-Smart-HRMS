<?php

namespace App\Services\Auth;

use App\Jobs\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogger
{
    public function loginSucceeded(Request $request, User $user): void
    {
        DB::transaction(function () use ($request, $user): void {
            $user->forceFill(['last_login_at' => now()])->save();

            UserLoginHistory::query()->create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'success',
                'logged_in_at' => now(),
                'metadata' => ['remember' => $request->boolean('remember')],
            ]);

            $this->record($request, 'auth.login', $user, [], ['status' => 'success']);
        });
    }

    public function loginFailed(Request $request, string $email, ?User $user, string $reason): void
    {
        UserLoginHistory::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'failed',
            'failure_reason' => $reason,
            'logged_in_at' => now(),
            'metadata' => [],
        ]);

        $this->record($request, 'auth.login_failed', $user ?? $email, [], ['email' => $email, 'reason' => $reason]);
    }

    public function logout(Request $request, User $user): void
    {
        UserLoginHistory::query()
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->limit(1)
            ->update(['logged_out_at' => now()]);

        $this->record($request, 'auth.logout', $user, [], ['status' => 'success']);
    }

    public function record(Request $request, string $event, mixed $subject, array $oldValues = [], array $newValues = []): void
    {
        $payload = [
            'user_id' => $request->user()?->id,
            'auditable_type' => is_object($subject) ? $subject::class : (string) $subject,
            'auditable_id' => is_object($subject) && isset($subject->id) ? $subject->id : 0,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if (app()->runningUnitTests()) {
            AuditLog::query()->create($payload);

            return;
        }

        RecordAuditLog::dispatchAfterResponse($payload);
    }
}
