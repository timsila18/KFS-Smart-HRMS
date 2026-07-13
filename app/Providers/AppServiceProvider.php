<?php

namespace App\Providers;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Eloquent\EmployeeRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url');
        $renderUrl = (string) env('RENDER_EXTERNAL_URL', '');
        $requestHost = $this->app->bound('request') ? request()->getHost() : '';
        $isRenderHost = Str::endsWith($requestHost, '.onrender.com');

        if (app()->isProduction() || $isRenderHost || Str::startsWith($appUrl, 'https://') || Str::startsWith($renderUrl, 'https://')) {
            URL::forceScheme('https');

            if ($isRenderHost) {
                URL::forceRootUrl('https://'.$requestHost);
            }
        }

        Gate::policy(\App\Models\Employee::class, \App\Policies\EmployeePolicy::class);
        Gate::policy(\App\Models\PayCode::class, \App\Policies\PayCodePolicy::class);
        Gate::policy(\App\Models\PayrollInstitution::class, \App\Policies\PayrollInstitutionPolicy::class);
        Gate::policy(\App\Models\PayrollRun::class, \App\Policies\PayrollRunPolicy::class);
        Model::unguard(false);
        Model::preventLazyLoading(! app()->isProduction());
        Vite::prefetch(concurrency: 3);
    }
}
