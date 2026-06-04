<?php

define('LARAVEL_START', microtime(true));

/* DEBUG SEMENTARA — dump path yang diterima fungsi */
if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'whatami')) {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI'     => $_SERVER['REQUEST_URI'] ?? null,
        'PATH_INFO'       => $_SERVER['PATH_INFO'] ?? null,
        'ORIG_PATH_INFO'  => $_SERVER['ORIG_PATH_INFO'] ?? null,
        'SCRIPT_NAME'     => $_SERVER['SCRIPT_NAME'] ?? null,
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? null,
        'PHP_SELF'        => $_SERVER['PHP_SELF'] ?? null,
        'QUERY_STRING'    => $_SERVER['QUERY_STRING'] ?? null,
    ], JSON_PRETTY_PRINT);
    exit;
}

/* HTTPS dari proxy Vercel */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';

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

$app->useStoragePath($tmpStorage);

$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
