<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Auth\ActivityLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(ActivityLogger $logger): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower((string) $this->string('email'));
        $user = User::query()->where('email', $email)->first();

        if (! Auth::attempt(['email' => $email, 'password' => $this->string('password'), 'status' => 'active'], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $logger->loginFailed($this, $email, $user, 'invalid_credentials');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $this->hasAllowedRole($user)) {
            Auth::logout();
            $logger->loginFailed($this, $email, $user, 'role_not_allowed');

            throw ValidationException::withMessages([
                'email' => __('Your account role is not allowed to access KFS Smart HRMS.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        $logger->loginSucceeded($this, $user);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('email')).'|'.$this->ip());
    }

    private function hasAllowedRole(User $user): bool
    {
        $allowedRoles = config('kfs-auth.allowed_login_roles', []);

        return empty($allowedRoles) || $user->hasAnyRole($allowedRoles);
    }
}
