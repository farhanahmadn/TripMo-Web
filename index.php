<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Vercel Serverless Bootstrap untuk Laravel (entry point di ROOT)
|--------------------------------------------------------------------------
| File ini SENGAJA di root (bukan di folder api/) supaya Vercel mount-nya
| di "/" dan TIDAK mengupas prefix /api — Laravel menerima path penuh
| (mis. /api/posts), sehingga route API berfungsi.
*/

/* 1. Paksa Laravel tahu request ini HTTPS (Vercel terminasi TLS di edge). */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

/* 2. Log ke stderr supaya muncul di Vercel Function Logs. */
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';

/* 3. Filesystem Vercel read-only kecuali /tmp → storage writable di /tmp. */
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

/* DEBUG SEMENTARA — dump path yang diterima fungsi */
if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'whatami')) {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI'    => $_SERVER['REQUEST_URI'] ?? null,
        'PATH_INFO'      => $_SERVER['PATH_INFO'] ?? null,
        'ORIG_PATH_INFO' => $_SERVER['ORIG_PATH_INFO'] ?? null,
        'SCRIPT_NAME'    => $_SERVER['SCRIPT_NAME'] ?? null,
        'QUERY_STRING'   => $_SERVER['QUERY_STRING'] ?? null,
        'X_VERCEL_PATH'  => $_SERVER['HTTP_X_VERCEL_FORWARDED_FOR'] ?? null,
    ], JSON_PRETTY_PRINT);
    exit;
}

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->useStoragePath($tmpStorage);

$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
