<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\ActivityLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->get('auth.two_factor_pending')) {
            return redirect()->to($this->fallbackUrl($request->user()));
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        $validRecoveryCode = collect($user->two_factor_recovery_codes ?? [])
            ->contains(fn (string $code): bool => Hash::check($request->string('code'), $code));

        if (! $validRecoveryCode) {
            $logger->record($request, 'two_factor.failed', $user, [], ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'code' => __('The supplied two factor code is invalid.'),
            ]);
        }

        $request->session()->forget('auth.two_factor_pending');
        $logger->record($request, 'two_factor.passed', $user, [], ['ip' => $request->ip()]);

        return redirect()->intended($this->fallbackUrl($user));
    }

    private function fallbackUrl(?User $user): string
    {
        if ($user?->hasRole('employee')) {
            return route('ess.dashboard', absolute: false);
        }

        if ($user?->can('dashboard.view')) {
            return route('dashboard', absolute: false);
        }

        return route('ess.dashboard', absolute: false);
    }
}
