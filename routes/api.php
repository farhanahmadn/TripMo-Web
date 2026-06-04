<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PostApiController;

/*
|--------------------------------------------------------------------------
| API Routes — Tripmo (untuk mobile app Flutter)
| Semua di-prefix /api. Auth memakai Bearer token (Sanctum).
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/ping', fn () => response()->json(['status' => 'ok', 'app' => 'Tripmo API']));

// ── Publik ──
Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login',    [AuthApiController::class, 'login']);

Route::get('/posts',          [PostApiController::class, 'index']);
Route::get('/posts/{id}',     [PostApiController::class, 'show']);
Route::get('/users/{id}',     [PostApiController::class, 'userProfile']);

// ── Butuh token (Bearer) ──
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',    [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    Route::post('/posts',            [PostApiController::class, 'store']);
    Route::delete('/posts/{id}',     [PostApiController::class, 'destroy']);
    Route::post('/posts/{id}/rate',  [PostApiController::class, 'rate']);
});
