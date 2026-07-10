<?php

namespace App\Http\Middleware;

use App\Services\Auth\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeout = max(1, (int) config('kfs-auth.session_timeout_minutes', 30)) * 60;
        $lastActivity = (int) $request->session()->get('auth.last_activity_at', now()->timestamp);

        if ((now()->timestamp - $lastActivity) > $timeout) {
            app(ActivityLogger::class)->record($request, 'auth.session_timeout', $request->user(), [], ['timeout_seconds' => $timeout]);
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Your session expired. Please sign in again.'),
            ]);
        }

        $request->session()->put('auth.last_activity_at', now()->timestamp);

        return $next($request);
    }
}
