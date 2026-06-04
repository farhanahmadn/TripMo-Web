<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Vercel Serverless Bootstrap untuk Laravel
|--------------------------------------------------------------------------
*/

/* 1. Paksa Laravel tahu request ini HTTPS (Vercel terminasi TLS di edge,
|     lalu forward ke fungsi PHP via http). Tanpa ini asset() jadi http://. */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

/* 2. Arahkan log ke stderr supaya muncul di Vercel Function Logs. */
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';

/* 3. Filesystem Vercel read-only kecuali /tmp.
|     Buat struktur storage + bootstrap cache yang writable di /tmp. */
$tmpStorage = '/tmp/storage';
foreach ([
    $tmpStorage . '/app/public',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/views',
    $tmpStorage . '/framework/testing',
    $tmpStorage . '/logs',
    '/tmp/bootstrap/cache',
] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

/* 4. Pindahkan storage path Laravel ke /tmp (writable). */
$app->useStoragePath($tmpStorage);

$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
