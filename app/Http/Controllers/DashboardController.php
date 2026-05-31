<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardController - Tripmo
 * untuk halaman utama setelah login.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard
     */
    public function index()
    {
        $user = Auth::user();

        $postingan = \App\Models\Postingan::where('user_id', $user->id)
                        ->with('photos')
                        ->latest()
                        ->get();

        $feed = \App\Models\Postingan::with(['user', 'photos'])
                    ->withAvg('ratings', 'score')
                    ->where('user_id', '!=', $user->id)
                    ->latest()
                    ->get();

        // Rekomendasi: prioritas rating >= 4, fallback ke rating tertinggi, terakhir postingan terbaru
        $recommendations = \App\Models\Postingan::with(['user', 'photos'])
                    ->withAvg('ratings', 'score')
                    ->where('user_id', '!=', $user->id)
                    ->orderByDesc('ratings_avg_score')
                    ->orderByDesc('created_at')
                    ->get()
                    ->filter(fn($p) => ($p->ratings_avg_score ?? 0) > 0) // Hanya post yang sudah di-rating
                    ->values()
                    ->take(5);

        // Jika rekomendasi kosong, gunakan postingan terbaru
        if ($recommendations->isEmpty()) {
            $recommendations = $feed->take(5);
        }

        return view('dashboard.index', compact('user', 'postingan', 'feed', 'recommendations'));
    }
}
