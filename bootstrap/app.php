<?php

use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestCorrelation;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        App\Console\Commands\ImportHriskeBankBranches::class,
        App\Console\Commands\ImportHriskePaymentBreakdown::class,
        App\Console\Commands\RunScheduledReports::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->append(RequestCorrelation::class);

        $middleware->web(append: [
            SecurityHeaders::class,
            HandleAppearance::class,
            EnforceSessionTimeout::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            Log::error($exception->getMessage(), [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $status = method_exists($exception, 'getStatusCode')
                ? $exception->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            if ($status < 400 || $status >= 600) {
                $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            }

            return response()->json([
                'message' => $status === Response::HTTP_INTERNAL_SERVER_ERROR && app()->isProduction()
                    ? 'An unexpected server error occurred.'
                    : $exception->getMessage(),
                'type' => 'https://kfs-smart-hrms.local/problems/'.str((string) $status),
                'status' => $status,
                'request_id' => $request->headers->get('X-Request-Id'),
            ], $status)->withHeaders(['Content-Type' => 'application/problem+json']);
        });
    })->create();
