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
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

putenv("APP_STORAGE_PATH={$tmp}");
putenv("VIEW_COMPILED_PATH={$tmp}/framework/views");
putenv("LOG_CHANNEL=stderr");

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';

try {
    $app->handleRequest(\Illuminate\Http\Request::capture());
} catch (Throwable $exception) {
    error_log('KFS_VERCEL_BOOT_EXCEPTION: '.$exception::class.' '.$exception->getMessage().' in '.$exception->getFile().':'.$exception->getLine());

    throw $exception;
}
