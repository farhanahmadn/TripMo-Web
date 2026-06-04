<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'whatami')) {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'PATH_INFO'   => $_SERVER['PATH_INFO'] ?? null,
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
        'x-now-route' => $_SERVER['HTTP_X_NOW_ROUTE_MATCHES'] ?? null,
        'headers'     => array_filter($_SERVER, fn($k) => str_starts_with($k, 'HTTP_X_'), ARRAY_FILTER_USE_KEY),
    ], JSON_PRETTY_PRINT);
    exit;
}

/*
|--------------------------------------------------------------------------
| Penyesuaian Serverless (Vercel) — dijalankan untuk SEMUA request
|--------------------------------------------------------------------------
| vercel-php memakai public/index.php sebagai entry. Blok ini menyiapkan
| environment serverless SEBELUM Laravel boot.
*/

/* 1. HTTPS: Vercel terminasi TLS di edge lalu forward via http. */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

/* 2. Cegah pemotongan prefix path (mis. /api): paksa SCRIPT_NAME ke root
|     sehingga Symfony menghitung base path kosong → REQUEST_URI penuh dipakai. */
if (PHP_SAPI !== 'cli') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF']    = '/index.php';
    unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);
}

/* 3. Log ke stderr (muncul di Vercel Function Logs). */
if (getenv('VERCEL') || isset($_SERVER['VERCEL'])) {
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';
}

/* 4. Filesystem read-only kecuali /tmp → storage writable di /tmp. */
$__isServerless = getenv('VERCEL') || isset($_SERVER['VERCEL']) || is_dir('/var/task');
if ($__isServerless) {
    $tmpStorage = '/tmp/storage';
    foreach ([
        $tmpStorage . '/app/public',
        $tmpStorage . '/framework/cache/data',
        $tmpStorage . '/framework/sessions',
        $tmpStorage . '/framework/views',
        $tmpStorage . '/framework/testing',
        $tmpStorage . '/logs',
        '/tmp/bootstrap/cache',
    ] as $__dir) {
        if (!is_dir($__dir)) {
            @mkdir($__dir, 0755, true);
        }
    }
}

// Maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (!empty($__isServerless)) {
    $app->useStoragePath('/tmp/storage');
}

$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
