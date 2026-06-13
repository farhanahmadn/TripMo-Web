<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Vercel Serverless Entry — WAJIB di folder api/ (syarat Vercel functions)
|--------------------------------------------------------------------------
*/

/* 1. HTTPS dari proxy Vercel (TLS diterminasi di edge). */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

/* 2. Cegah pemotongan prefix /api: fungsi ini ada di /api/index.php sehingga
|     Symfony menghitung base path = /api dan MEMOTONG-nya dari pathInfo.
|     Paksa SCRIPT_NAME ke root agar base path kosong → path penuh dipakai. */
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);

/* 3. Log ke stderr (muncul di Vercel Function Logs). */
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = $_SERVER['LOG_CHANNEL'] = 'stderr';

/* 4. Storage writable di /tmp (filesystem read-only kecuali /tmp). */
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

/* 5. Arahkan SEMUA cache Laravel ke /tmp (bootstrap/cache di /var/task
|     bersifat read-only). Tanpa ini, package discovery gagal menulis manifest
|     → provider inti seperti [view] tak ter-register → 500.
|     WAJIB di-set SEBELUM bootstrap/app.php dibuat. */
foreach ([
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
    'APP_CONFIG_CACHE'   => '/tmp/bootstrap/cache/config.php',
    'APP_ROUTES_CACHE'   => '/tmp/bootstrap/cache/routes.php',
    'APP_EVENTS_CACHE'   => '/tmp/bootstrap/cache/events.php',
] as $__k => $__v) {
    putenv("$__k=$__v");
    $_ENV[$__k] = $_SERVER[$__k] = $__v;
}

/* DEBUG SEMENTARA — healthcheck mentah sebelum Laravel boot. Hapus setelah selesai.
|  Akses: /__health  (mem-bypass seluruh framework + middleware). */
if (($_SERVER['REQUEST_URI'] ?? '') === '/__health' || strpos($_SERVER['REQUEST_URI'] ?? '', '/__health') === 0) {
    header('Content-Type: text/plain');
    $envFile = __DIR__ . '/../.env';
    echo "ENV FILE on server: " . (file_exists($envFile) ? 'ADA' : 'TIDAK ADA') . "\n";
    foreach (['APP_KEY', 'APP_ENV', 'APP_DEBUG', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $k) {
        $v = getenv($k);
        echo str_pad($k, 16) . ": " . ($v === false ? '(KOSONG)' : ($k === 'APP_KEY' ? '(terisi, ' . strlen($v) . ' char)' : $v)) . "\n";
    }
    $ca = __DIR__ . '/../ca.pem';
    echo "ca.pem            : " . (file_exists($ca) ? 'ADA' : 'TIDAK ADA') . "\n";
    echo "pdo_mysql         : " . (extension_loaded('pdo_mysql') ? 'ADA' : 'TIDAK ADA') . "\n";
    echo "----\n";
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: 3306, getenv('DB_DATABASE'));
        $pdo = new PDO($dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [
            PDO::MYSQL_ATTR_SSL_CA => $ca,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        echo "PDO CONNECT       : OK -> " . $pdo->query('SELECT 1')->fetchColumn() . "\n";
    } catch (\Throwable $e) {
        echo "PDO ERROR         : " . $e->getMessage() . "\n";
    }
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath($tmpStorage);

$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
