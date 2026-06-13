<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostinganController;
use App\Http\Controllers\SmartSystemController;
use App\Models\User;

/*
| Web Routes - Tripmo
*/

// DEBUG SEMENTARA — hapus setelah selesai. Tampilkan status koneksi DB + config.
Route::get('/db-check', function () {
    header('Content-Type: text/plain');
    $ca = config('database.connections.mysql.options.' . PDO::MYSQL_ATTR_SSL_CA);
    echo "DB_HOST: " . config('database.connections.mysql.host') . "\n";
    echo "DB_DATABASE: " . config('database.connections.mysql.database') . "\n";
    echo "CA path: " . $ca . "\n";
    echo "CA exists: " . (is_string($ca) && file_exists($ca) ? 'YES' : 'NO') . "\n";
    echo "pdo_mysql loaded: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
    echo "----\n";
    try {
        $r = DB::select('SELECT 1 AS ok');
        echo "DB CONNECT: OK -> " . json_encode($r) . "\n";
    } catch (\Throwable $e) {
        echo "DB ERROR: " . get_class($e) . "\n";
        echo $e->getMessage() . "\n";
    }
    exit;
});

// Halaman utama → redirect ke login jika belum login, dashboard jika sudah
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Penyaji foto dari database (PUBLIK agar gambar tampil untuk semua, termasuk API).
// Persisten di serverless/Vercel — tidak bergantung pada filesystem.
Route::get('/photo/{id}', function ($id) {
    $blob = DB::table('photo_blobs')->where('foto_id', $id)->first();
    if (!$blob) {
        abort(404);
    }
    return response(base64_decode($blob->data), 200, [
        'Content-Type'  => $blob->mime ?: 'image/jpeg',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('photo.show');


// AUTENTIKASI (hanya untuk tamu / belum login)

Route::middleware('guest')->group(function () {
    // Register
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});


// HALAMAN YANG BUTUH LOGIN (auth)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile/edit', [AuthController::class, 'editProfile'])->name('profile.edit');
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/profile/delete', [AuthController::class, 'deleteAccount'])->name('profile.delete');
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Postingan
    Route::post('/post', [PostinganController::class, 'store'])->name('post.store');
    Route::get('/post/{id}', [PostinganController::class, 'show'])->name('post.show');
    Route::delete('/post/{id}', [PostinganController::class, 'destroy'])->name('post.destroy');
    Route::post('/post/{id}/rate', [PostinganController::class, 'rate'])->name('post.rate');
    Route::get('/featured', [PostinganController::class, 'featured'])->name('post.featured');
    Route::get('/post/{id}/edit', [PostinganController::class, 'edit'])->name('post.edit');
    Route::put('/post/{id}', [PostinganController::class, 'update'])->name('post.update');

    Route::get('/search', [PostinganController::class, 'search'])->name('post.search');

    // Smart System routes
    Route::get('/smart/budget-insight', [SmartSystemController::class, 'budgetInsight']);
    Route::get('/smart/trending',       [SmartSystemController::class, 'trending']);
    Route::get('/smart/similar/{postId}', [SmartSystemController::class, 'similar']);
    Route::get('/smart/stats',          [SmartSystemController::class, 'personalStats']);

    Route::get('/profile/{id}', function ($id) {
        $user = User::with(['postingan.photos'])->findOrFail($id);
        return view('profile', compact('user'));
    })->name('profile.show');
});


