<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Auth\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
            'twoFactorEnabled' => config('kfs-auth.two_factor_enabled'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(app(ActivityLogger::class));
        $request->session()->regenerate();
        $request->session()->put('auth.last_activity_at', now()->timestamp);

        /** @var User $user */
        $user = $request->user();

        if (config('kfs-auth.two_factor_enabled') && $user->two_factor_enabled) {
            $request->session()->put('auth.two_factor_pending', true);

            return redirect()->route('two-factor.challenge');
        }

        return redirect()->to($this->postLoginUrl($request, $user));
    }

    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        if ($request->user()) {
            $logger->logout($request, $request->user());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectRoute(User $user): string
    {
        foreach (config('kfs-auth.post_login_routes', []) as $role => $route) {
            if ($user->hasRole($role)) {
                return $route;
            }
        }

        return 'dashboard';
    }

    private function postLoginUrl(Request $request, User $user): string
    {
        $fallback = route($this->redirectRoute($user), absolute: false);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?: '/';

        if ($this->canOpenPath($user, $path)) {
            return $intended;
        }

        return $fallback;
    }

    private function canOpenPath(User $user, string $path): bool
    {
        return match (true) {
            $path === '/dashboard' => $user->can('dashboard.view'),
            Str::startsWith($path, '/ess') => $user->can('ess.view'),
            Str::startsWith($path, '/employees') => $user->can('employees.view'),
            Str::startsWith($path, '/leave') => $user->can('leave.view') || $user->can('ess.view'),
            Str::startsWith($path, '/payroll') => $user->can('payroll.view'),
            Str::startsWith($path, '/reports') => $user->can('reports.view'),
            default => true,
        };
    }
}
