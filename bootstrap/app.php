<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Daftarkan API routes secara EKSPLISIT (lebih andal di serverless
        // dibanding parameter `api:` yang bisa terlewat jika deteksi gagal).
        then: function () {
            // DIAGNOSTIK: route inline (tanpa file) untuk cek apakah `then` jalan
            Route::middleware('api')->get('mobile/inline-test', function () {
                return response()->json([
                    'then_ran'        => true,
                    'api_file_exists' => file_exists(base_path('routes/api.php')),
                    'api_file_path'   => base_path('routes/api.php'),
                ]);
            });

            Route::middleware('api')
                ->prefix('mobile')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Vercel/proxy: percayai semua proxy agar HTTPS, host, dan IP
        // terdeteksi benar (mencegah asset() jadi http:// → mixed content).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
