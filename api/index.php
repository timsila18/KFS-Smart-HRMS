<?php

declare(strict_types=1);

$_ENV['APP_RUNNING_ON_VERCEL'] = 'true';
$_SERVER['APP_RUNNING_ON_VERCEL'] = 'true';

$tmp = '/tmp/kfs-smart-hrms';

foreach ([
    "{$tmp}/cache",
    "{$tmp}/framework/cache",
    "{$tmp}/framework/sessions",
    "{$tmp}/framework/views",
    "{$tmp}/logs",
    "{$tmp}/bootstrap/cache",
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

try {
    putenv("APP_STORAGE_PATH={$tmp}");
    putenv("VIEW_COMPILED_PATH={$tmp}/framework/views");
    putenv("APP_CONFIG_CACHE={$tmp}/bootstrap/cache/config.php");
    putenv("APP_EVENTS_CACHE={$tmp}/bootstrap/cache/events.php");
    putenv("APP_PACKAGES_CACHE={$tmp}/bootstrap/cache/packages.php");
    putenv("APP_ROUTES_CACHE={$tmp}/bootstrap/cache/routes-v7.php");
    putenv("APP_SERVICES_CACHE={$tmp}/bootstrap/cache/services.php");
    putenv("LOG_CHANNEL=stderr");

    require __DIR__.'/../vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require __DIR__.'/../bootstrap/app.php';

    $request = \Illuminate\Http\Request::capture();
    $app->instance('request', $request);

    /** @var \Illuminate\Contracts\Http\Kernel $kernel */
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    foreach ([
        \Illuminate\Auth\AuthServiceProvider::class,
        \Illuminate\Cache\CacheServiceProvider::class,
        \Illuminate\Cookie\CookieServiceProvider::class,
        \Illuminate\Database\DatabaseServiceProvider::class,
        \Illuminate\Encryption\EncryptionServiceProvider::class,
        \Illuminate\Filesystem\FilesystemServiceProvider::class,
        \Illuminate\Hashing\HashServiceProvider::class,
        \Illuminate\Session\SessionServiceProvider::class,
        \Illuminate\Translation\TranslationServiceProvider::class,
        \Illuminate\Validation\ValidationServiceProvider::class,
        \Illuminate\View\ViewServiceProvider::class,
    ] as $provider) {
        $app->register($provider);
    }

    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $exception) {
    error_log('KFS_VERCEL_BOOT_EXCEPTION: '.$exception::class.' '.$exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine().PHP_EOL.$exception->getTraceAsString());

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');

    echo 'KFS Smart HRMS could not boot on Vercel. Check Vercel runtime logs for KFS_VERCEL_BOOT_EXCEPTION.';
}
